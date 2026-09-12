#!/usr/bin/env bash
#
# STEP 3 of the Magento 2.4.9 upgrade: BACKUP
#
# This is the safety net. shell/04-vhost-switch.sh refuses to run without it.
#
# Writes to /var/backups/marytylor-2.4.9/<timestamp>/ -- OUTSIDE the project,
# so a mistake inside the docroot cannot destroy the backup with it.
#
#   bash shell/03-backup.sh
#
set -euo pipefail

MAGENTO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
STAMP="$(date +%Y%m%d-%H%M%S)"
BACKUP_ROOT="/var/backups/marytylor-2.4.9"
BACKUP_DIR="${BACKUP_ROOT}/${STAMP}"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()   { printf '    \033[0;32m[ok]\033[0m %s\n' "$*"; }
die()  { printf '\n\033[0;31m[FATAL]\033[0m %s\n' "$*" >&2; exit 1; }

cd "$MAGENTO_ROOT"
[[ -f app/etc/env.php ]] || die "app/etc/env.php not found -- is this a Magento root?"

mkdir -p "$BACKUP_DIR" || die "cannot create $BACKUP_DIR (need sudo?)"
log "Backing up to $BACKUP_DIR"

# --------------------------------------------------- read DB creds from env.php
eval "$(php -r '
$e = include "app/etc/env.php";
$d = $e["db"]["connection"]["default"];
printf("DB_HOST=%s\nDB_NAME=%s\nDB_USER=%s\n",
    escapeshellarg($d["host"]), escapeshellarg($d["dbname"]), escapeshellarg($d["username"]));
')"
# Password is passed to mysqldump via a private defaults file, never argv,
# so it is not visible in `ps`.
MYCNF="$(mktemp)"
chmod 600 "$MYCNF"
trap 'rm -f "$MYCNF"' EXIT
php -r '
$e = include "app/etc/env.php";
$d = $e["db"]["connection"]["default"];
printf("[client]\nhost=%s\nuser=%s\npassword=\"%s\"\n",
    $d["host"], $d["username"], addslashes($d["password"]));
' > "$MYCNF"

# ------------------------------------------------------------------ database
log "Dumping database '${DB_NAME}' (~1.8GB raw, expect a few minutes)"

mysqldump --defaults-extra-file="$MYCNF" \
    --single-transaction \
    --quick \
    --routines \
    --triggers \
    --events \
    --no-tablespaces \
    --default-character-set=utf8mb4 \
    "$DB_NAME" | gzip -1 > "${BACKUP_DIR}/${DB_NAME}.sql.gz"

[[ -s "${BACKUP_DIR}/${DB_NAME}.sql.gz" ]] || die "dump is empty -- backup FAILED"

# Verify the gzip stream is not truncated
gzip -t "${BACKUP_DIR}/${DB_NAME}.sql.gz" || die "dump is corrupt -- backup FAILED"
ok "database: $(du -h "${BACKUP_DIR}/${DB_NAME}.sql.gz" | cut -f1)"

# Sanity-check the dump actually contains the big tables
for t in sales_order customer_entity catalog_product_entity; do
    zgrep -qm1 "CREATE TABLE \`${t}\`" "${BACKUP_DIR}/${DB_NAME}.sql.gz" \
        || die "dump is missing table ${t} -- backup FAILED"
done
ok "dump verified: contains sales_order, customer_entity, catalog_product_entity"

# ------------------------------------------------------------------- configs
log "Copying configuration"
mkdir -p "${BACKUP_DIR}/app-etc"
cp -av app/etc/env.php app/etc/config.php "${BACKUP_DIR}/app-etc/" >/dev/null
cp -av composer.json composer.lock "${BACKUP_DIR}/" >/dev/null
chmod -R go-rwx "${BACKUP_DIR}/app-etc"
ok "env.php, config.php, composer.json, composer.lock (perms locked to owner)"

# ----------------------------------------------------------------- git branch
log "Tagging the pre-upgrade tree in git"

if git rev-parse --git-dir >/dev/null 2>&1; then
    git rev-parse --verify "pre-2.4.9-upgrade" >/dev/null 2>&1 \
        && ok "branch pre-2.4.9-upgrade already exists" \
        || { git branch "pre-2.4.9-upgrade" && ok "created branch pre-2.4.9-upgrade"; }
    git rev-parse HEAD > "${BACKUP_DIR}/git-HEAD.txt"
    git status --short > "${BACKUP_DIR}/git-status.txt"
    ok "recorded HEAD $(cut -c1-12 < "${BACKUP_DIR}/git-HEAD.txt")"
else
    ok "not a git repo -- skipped"
fi

# -------------------------------------------------------------- code snapshot
log "Snapshotting code (excluding vendor/ and regenerables)"

tar -czf "${BACKUP_DIR}/code.tar.gz" \
    --exclude='./vendor' \
    --exclude='./generated' \
    --exclude='./var/cache' \
    --exclude='./var/page_cache' \
    --exclude='./var/view_preprocessed' \
    --exclude='./var/session' \
    --exclude='./pub/static' \
    --exclude='./.git' \
    --exclude='./pub/media/catalog/product/cache' \
    -C "$MAGENTO_ROOT" . 2>/dev/null || true

[[ -s "${BACKUP_DIR}/code.tar.gz" ]] || die "code snapshot is empty -- backup FAILED"
ok "code: $(du -h "${BACKUP_DIR}/code.tar.gz" | cut -f1)"

# ------------------------------------------------------------------- manifest
cat > "${BACKUP_DIR}/MANIFEST.txt" <<MANIFEST
Magento pre-2.4.9 upgrade backup
Taken            : $(date -Is)
Magento version  : $(php bin/magento --version 2>/dev/null || echo unknown)
PHP version      : $(php -r 'echo PHP_VERSION;')
Database         : ${DB_NAME} @ ${DB_HOST}
Git HEAD         : $(git rev-parse HEAD 2>/dev/null || echo n/a)
Git branch       : pre-2.4.9-upgrade

RESTORE
  Database:
    zcat ${BACKUP_DIR}/${DB_NAME}.sql.gz | mysql -u <user> -p ${DB_NAME}
  Code:
    git checkout pre-2.4.9-upgrade && composer install
  Config:
    cp ${BACKUP_DIR}/app-etc/*.php ${MAGENTO_ROOT}/app/etc/
MANIFEST

log "Backup complete"
cat <<SUMMARY

    Location : ${BACKUP_DIR}
    Total    : $(du -sh "$BACKUP_DIR" | cut -f1)

$(ls -lh "$BACKUP_DIR" | tail -n +2 | sed 's/^/    /')

    Read ${BACKUP_DIR}/MANIFEST.txt for restore instructions.

    NEXT: only when the Plumrocket licence is sorted and you are in a
          maintenance window ->  sudo bash shell/04-vhost-switch.sh

SUMMARY
