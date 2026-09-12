#!/usr/bin/env bash
#
# Database-only backup.
#
#   bash shell/db-backup.sh [target-dir]
#
# Default target: <magento-root>/backup
#
# Reads credentials from app/etc/env.php. The password is passed to mysqldump
# via a private defaults-file, never on the command line, so it does not show
# up in `ps`.
#
# The target directory gets a deny-all .htaccess and 0700 permissions, because
# /var/www/html is the default vhost's DocumentRoot on this host.
#
set -euo pipefail

MAGENTO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET_DIR="${1:-${MAGENTO_ROOT}/backup}"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()   { printf '    \033[0;32m[ok]\033[0m %s\n' "$*"; }
warn() { printf '    \033[0;33m[warn]\033[0m %s\n' "$*"; }
die()  { printf '\n\033[0;31m[FATAL]\033[0m %s\n' "$*" >&2; exit 1; }

cd "$MAGENTO_ROOT"
[[ -f app/etc/env.php ]] || die "app/etc/env.php not found -- not a Magento root"

# ------------------------------------------------------------------- target
mkdir -p "$TARGET_DIR"
chmod 700 "$TARGET_DIR"

# /var/www/html is a DocumentRoot on this host -- belt and braces.
cat > "${TARGET_DIR}/.htaccess" <<'HT'
Require all denied
HT
ok "target: ${TARGET_DIR} (0700, deny-all .htaccess)"

# --------------------------------------------------------- credentials
DB_NAME=$(php -r '$e=include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["dbname"];')
DB_HOST=$(php -r '$e=include "app/etc/env.php"; echo $e["db"]["connection"]["default"]["host"];')

MYCNF="$(mktemp)"
chmod 600 "$MYCNF"
trap 'rm -f "$MYCNF"' EXIT
php -r '
$e = include "app/etc/env.php";
$d = $e["db"]["connection"]["default"];
printf("[client]\nhost=%s\nuser=%s\npassword=\"%s\"\n",
    $d["host"], $d["username"], addslashes($d["password"]));
' > "$MYCNF"

ok "database: ${DB_NAME} @ ${DB_HOST}"

# ------------------------------------------------- broken views (read-only)
# Views whose DEFINER account no longer exists abort mysqldump with error 1356.
# This database has such a view (migration damage). Views hold no data, so we
# record their definitions to a file and exclude them from the dump instead of
# modifying the database -- a backup must never mutate what it is backing up.
VIEW_DEFS="${TARGET_DIR}/broken-view-definitions.sql"
IGNORE_ARGS=()

BROKEN=$(php -r '
mysqli_report(MYSQLI_REPORT_OFF);
$e = include "app/etc/env.php";
$d = $e["db"]["connection"]["default"];
$m = @new mysqli($d["host"], $d["username"], $d["password"], $d["dbname"]);
if ($m->connect_error) { exit(0); }
$db = $d["dbname"];
$acc = [];
if ($r = $m->query("SELECT CONCAT(user,\"@\",host) a FROM mysql.user")) {
    while ($x = $r->fetch_assoc()) { $acc[$x["a"]] = true; }
}
$r = $m->query("SELECT TABLE_NAME, DEFINER FROM information_schema.VIEWS WHERE TABLE_SCHEMA=\"$db\"");
if (!$r) { exit(0); }
while ($v = $r->fetch_assoc()) {
    if (!isset($acc[$v["DEFINER"]])) { echo $v["TABLE_NAME"]."\n"; }
}
' 2>/dev/null || true)

if [[ -n "$BROKEN" ]]; then
    export VIEW_DEFS
    php -r '
    mysqli_report(MYSQLI_REPORT_OFF);
    $e = include "app/etc/env.php";
    $d = $e["db"]["connection"]["default"];
    $m = @new mysqli($d["host"], $d["username"], $d["password"], $d["dbname"]);
    $db = $d["dbname"];
    $out = "-- Definitions of views excluded from the dump (orphaned DEFINER).\n"
         . "-- Recreate with: shell/fix-orphaned-view-definers.sh\n\n";
    $r = $m->query("SELECT TABLE_NAME, VIEW_DEFINITION, DEFINER FROM information_schema.VIEWS WHERE TABLE_SCHEMA=\"$db\"");
    $acc = [];
    if ($q = $m->query("SELECT CONCAT(user,\"@\",host) a FROM mysql.user")) {
        while ($x = $q->fetch_assoc()) { $acc[$x["a"]] = true; }
    }
    while ($v = $r->fetch_assoc()) {
        if (isset($acc[$v["DEFINER"]])) continue;
        $out .= sprintf("-- %s (broken DEFINER=%s)\nCREATE OR REPLACE DEFINER=CURRENT_USER SQL SECURITY INVOKER VIEW `%s` AS %s;\n\n",
            $v["TABLE_NAME"], $v["DEFINER"], $v["TABLE_NAME"], $v["VIEW_DEFINITION"]);
    }
    file_put_contents(getenv("VIEW_DEFS"), $out);
    ' 2>/dev/null || true
    chmod 600 "$VIEW_DEFS" 2>/dev/null || true

    while IFS= read -r v; do
        [[ -n "$v" ]] || continue
        IGNORE_ARGS+=( "--ignore-table=${DB_NAME}.${v}" )
        warn "excluding broken view: ${v}"
    done <<< "$BROKEN"

    warn "their definitions are saved to $(basename "$VIEW_DEFS")"
    warn "views contain NO data -- this dump is still a complete data backup"
fi

# ----------------------------------------------------------------- dump
STAMP="$(date +%Y%m%d-%H%M%S)"
OUT="${TARGET_DIR}/${DB_NAME}-${STAMP}.sql.gz"

log "Dumping ${DB_NAME} -> $(basename "$OUT")"
printf '    (1.77GB of data, expect a few minutes)\n'

# pipefail is set, so a mysqldump failure is caught even though it pipes to gzip.
set -o pipefail
mysqldump --defaults-extra-file="$MYCNF" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    --no-tablespaces \
    --default-character-set=utf8mb4 \
    ${IGNORE_ARGS[@]+"${IGNORE_ARGS[@]}"} \
    "$DB_NAME" | gzip -1 > "$OUT" \
    || die "mysqldump FAILED -- removing partial file: $(rm -f "$OUT"; echo done)"

chmod 600 "$OUT"

# --------------------------------------------------------------- verify
log "Verifying"

[[ -s "$OUT" ]] || die "dump is empty"
gzip -t "$OUT"  || die "gzip stream is corrupt or truncated"
ok "gzip stream intact"

# A truncated dump can still be valid gzip, so check the dump's own end marker.
zcat "$OUT" | tail -5 | grep -q "Dump completed" \
    || die "no 'Dump completed' marker -- the dump is TRUNCATED, do not trust it"
ok "'Dump completed' marker present"

for t in sales_order customer_entity catalog_product_entity; do
    zgrep -qm1 "CREATE TABLE \`${t}\`" "$OUT" || die "dump is missing table ${t}"
done
ok "key tables present: sales_order, customer_entity, catalog_product_entity"

TABLES=$(zgrep -c "^CREATE TABLE" "$OUT" || true)
ok "tables in dump: ${TABLES}"

# ------------------------------------------------------------- summary
log "Backup complete"
cat <<SUMMARY

    File   $OUT
    Size   $(du -h "$OUT" | cut -f1)
    Tables ${TABLES}

    Restore:
        zcat "$OUT" | mysql -u root -p ${DB_NAME}
${BROKEN:+
    NOTE: excluded broken view(s) -- definitions in $(basename "$VIEW_DEFS")
          Fix them with: bash shell/fix-orphaned-view-definers.sh
}
SUMMARY

if ! grep -qs "^backup/" "${MAGENTO_ROOT}/.gitignore" 2>/dev/null; then
    warn "this repo has no .gitignore and tracks everything --"
    warn "add 'backup/' to .gitignore so dumps are never committed"
fi
