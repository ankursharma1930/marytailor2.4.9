#!/usr/bin/env bash
#
# STEP 8 of the Magento 2.4.9 upgrade: ADOBE SECURITY PATCHES
#
#   bash shell/08-security-patches.sh             apply missing patches, then rebuild
#   bash shell/08-security-patches.sh --check     report status only -- changes nothing
#   bash shell/08-security-patches.sh --revert    remove the patches (newest first), then rebuild
#
# Adobe no longer ships 2.4.9-pN Composer releases. Security fixes arrive as
# monthly *isolated patch files* -- each month requires the months before it --
# plus out-of-band hotfixes. Everything released for 2.4.9 as of 2026-09-13,
# in application order:
#
#   249-2026-07-001-CE  APSB26-73   2026-07-14  byte-identical to QPT MCLOUD-15066
#   249-2026-08-001-CE  APSB26-92   2026-08-11  byte-identical to QPT MCLOUD-15306
#   249-2026-09-001-CE  APSB26-138  2026-09-08
#   VULN-39341_249      APSB26-146  2026-09-07  CVE-2026-75650: critical,
#                                               unauthenticated RCE, exploited
#                                               in the wild. NOT in the Sep file.
#
# A 2.4.9 downloaded today is still the May 2026 code: Composer versions are
# immutable, so none of these fixes arrive through Composer.
#
# The files are kept in patches/adobe/2.4.9/ and pinned by SHA-256. Only the
# CE files apply -- this install has no EE or B2B packages.
#
# Do NOT also apply MCLOUD-15066 / MCLOUD-15306 with vendor/bin/magento-patches.
# They are the same diffs, and Adobe warns double application breaks installs.
#
# vendor/ is not tracked in git, so `composer install` / `composer update`
# silently REMOVES these patches (and re-copies lib/web/ and pub/errors/ from
# magento2-base). Re-run this script after every Composer operation.
#
# DISRUPTIVE while it runs: maintenance mode stays on through the DI compile
# and static deploy, because patched constructors with a stale generated/
# are fatal. Does NOT touch the database; it does rewrite the module order in
# app/etc/config.php, which the August patch makes stale.
#
# Idempotent: applied patches are detected and skipped, and pending patches are
# test-applied to a scratch copy first, so the live tree is never left
# half-patched. If a run dies part-way, re-running finishes it -- including
# turning maintenance mode back off.
#
set -euo pipefail

MAGENTO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
TARGET="2.4.9"
LOCALES="en_US"
PATCH_DIR="patches/adobe/2.4.9"
BACKUP_DIR="var/backups"
REBUILD_MARKER="var/.security-patches-rebuild-pending"
SMOKE_HOST="marytailor.local"
STAMP="$(date +%Y%m%d-%H%M%S)"

# file|sha256|label -- in application order
PATCHES=(
    "249-2026-07-001-CE.patch|1a51872724b0cd2ab75075265d7b321c15273082d382952d1ef997940c9fa8c9|APSB26-73  Jul 2026 monthly"
    "249-2026-08-001-CE.patch|292c6579eb3d87e5ad69d2508856d247dd386510ba735a5d3477ab5f188d103f|APSB26-92  Aug 2026 monthly"
    "249-2026-09-001-CE.patch|b92de99cdc27f72c7953d837dd2e9112dd011e7578026dcf4330ffe7d1100042|APSB26-138 Sep 2026 monthly"
    "VULN-39341_249.patch|8df23110e1909e2d5f0c2f0ecbe7f803670dfb0c9c3d15a527371c04b7825bf7|APSB26-146 VULN-39341 hotfix"
)

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()   { printf '    \033[0;32m[ok]\033[0m %s\n' "$*"; }
warn() { printf '    \033[0;33m[warn]\033[0m %s\n' "$*"; }
die()  { printf '\n\033[0;31m[FATAL]\033[0m %s\n' "$*" >&2; exit 1; }

MODE="apply"
case "${1:-}" in
    "")        ;;
    --check)   MODE="check" ;;
    --revert)  MODE="revert" ;;
    -h|--help) sed -n '5,7p' "${BASH_SOURCE[0]}" | sed 's/^# *//'; exit 0 ;;
    *)         die "unknown option '$1' -- use --check or --revert" ;;
esac

cd "$MAGENTO_ROOT"

MAINT_BY_US=0
SNAPSHOT=""
on_exit() {
    local rc=$?
    if [[ $rc -ne 0 && $MAINT_BY_US -eq 1 ]]; then
        printf '\n    Maintenance mode is still ON. Fix the error above and re-run this\n' >&2
        printf '    script -- it resumes where it stopped and turns maintenance off.\n' >&2
        [[ -z "$SNAPSHOT" ]] || printf '    Pre-change files: %s\n' "$SNAPSHOT" >&2
        printf '\n' >&2
    fi
}
trap on_exit EXIT

# ------------------------------------------------------------------- helpers
pfile()  { printf '%s/%s' "$PATCH_DIR" "${PATCHES[$1]%%|*}"; }
psha()   { local r="${PATCHES[$1]#*|}"; printf '%s' "${r%%|*}"; }
plabel() { printf '%s' "${PATCHES[$1]##*|}"; }

# Paths a patch touches, relative to the Magento root.
touched() { awk '/^diff --git /{ sub(/^a\//, "", $3); print $3 }' "$(pfile "$1")"; }

# Paths a patch creates as executable. git apply drops that mode when
# core.fileMode=false -- which this repo sets -- so it is restored by hand.
executables() {
    awk '/^diff --git /{ p = $3; sub(/^a\//, "", p) } /^new file mode 100755/{ print p }' "$(pfile "$1")"
}

# --exclude flags for every file a LATER patch also touches. Jul creates
# vendor/bin/patch-status and Aug rewrites it, so once Aug is in, Jul can only
# be recognised by its other 31 files.
later_excludes() {
    local i="$1" j
    for ((j = i + 1; j < ${#PATCHES[@]}; j++)); do
        touched "$j"
    done | sort -u | sed 's/^/--exclude=/'
}

is_applied() {
    local -a ex
    mapfile -t ex < <(later_excludes "$1")
    git apply --check -R "${ex[@]}" "$(pfile "$1")" 2>/dev/null
}

package_version() {
    php -r '
        $f = "vendor/composer/installed.json";
        if (!is_file($f)) { echo "none"; exit; }
        $j = json_decode(file_get_contents($f), true);
        foreach (($j["packages"] ?? $j) as $p) {
            if ($p["name"] === $argv[1]) { echo $p["version"]; exit; }
        }
        echo "none";
    ' "$1"
}

# Test-apply patches, in the order given, to a scratch copy of the files they
# touch. The live tree is only modified once this has succeeded.
simulate() {
    local direction="$1"; shift
    local stage f i out rc=0
    local -a flags=()
    [[ "$direction" == reverse ]] && flags=(-R)
    stage="$(mktemp -d)"
    while read -r f; do
        if [[ -e "$f" ]]; then cp -p --parents "$f" "$stage/"; fi
    done < <(for i in "$@"; do touched "$i"; done | sort -u)
    for i in "$@"; do
        if ! out="$(cd "$stage" && git apply "${flags[@]}" "$MAGENTO_ROOT/$(pfile "$i")" 2>&1)"; then
            warn "$(plabel "$i") does not ${direction}-apply cleanly on top of the current tree:"
            printf '%s\n' "$out" | head -20 | sed 's/^/        /'
            rc=1
            break
        fi
    done
    rm -rf "$stage"
    return $rc
}

# The August patch adds a <sequence> to Magento_Cms, so config.php's module
# order goes stale and module:config:status says "outdated, run setup:upgrade".
modules_outdated() { ! php bin/magento module:config:status >/dev/null 2>&1; }

# setup:upgrade would also run schema and data patches against live data, so
# only its first step (Installer::createModulesConfig) is reproduced: rewrite
# the modules list in Magento's computed order. Unlike setup:upgrade it refuses
# to silently enable a module that is on disk but not in config.php.
resort_modules() {
    php <<'PHP'
<?php
use Magento\Framework\App\DeploymentConfig\Reader;
use Magento\Framework\App\DeploymentConfig\Writer;
use Magento\Framework\Config\File\ConfigFilePool;
use Magento\Framework\Module\ModuleList\Loader;

require 'app/bootstrap.php';
$om = \Magento\Framework\App\Bootstrap::create(BP, $_SERVER)->getObjectManager();
$current = $om->get(Reader::class)->load(ConfigFilePool::APP_CONFIG)['modules'] ?? [];
$sorted = [];
foreach (array_keys($om->get(Loader::class)->load()) as $module) {
    if (!array_key_exists($module, $current)) {
        fwrite(STDERR, "{$module} is on disk but not in config.php -- refusing to enable it implicitly\n");
        exit(1);
    }
    $sorted[$module] = $current[$module] ? 1 : 0;
}
if (count($sorted) !== count($current)) {
    fwrite(STDERR, "config.php lists modules that are not on disk\n");
    exit(1);
}
if ($sorted === $current) {
    echo "unchanged\n";
    exit(0);
}
$om->get(Writer::class)->saveConfig([ConfigFilePool::APP_CONFIG => ['modules' => $sorted]], true);
echo "rewritten\n";
PHP
}

# ---------------------------------------------------------------------- guards
log "Pre-flight checks"

[[ "$(id -u)" -ne 0 ]] || die "do not run this as root -- it would leave generated/ and pub/static owned by root.
       Run it as the CLI user (payless)."
[[ -f app/etc/env.php ]] || die "app/etc/env.php not found -- is this a Magento root?"
command -v git >/dev/null || die "git is required -- the patches are applied with 'git apply'"
ok "running as $(whoami)"

INSTALLED="$(package_version magento/product-community-edition)"
[[ "$INSTALLED" == "$TARGET" ]] \
    || die "these patch files are for ${TARGET}, but vendor/ holds ${INSTALLED}.
       Download the files for that version from the APSB26-73/92/138/146 bulletins."
ok "magento/product-community-edition ${INSTALLED}"

[[ "$(package_version magento/magento2-ee-base)" == none ]] \
    || die "Adobe Commerce (EE) detected -- the EE patch files are needed too, and ${PATCH_DIR} has only CE"

for i in "${!PATCHES[@]}"; do
    f="$(pfile "$i")"
    [[ -f "$f" ]] || die "missing ${f}"
    printf '%s  %s\n' "$(psha "$i")" "$f" | sha256sum -c --quiet - >/dev/null 2>&1 \
        || die "${f} does not match its pinned SHA-256 -- refusing to apply an unverified patch"
done
ok "${#PATCHES[@]} patch files present, SHA-256 verified"

EXC_BEFORE="$(wc -l < var/log/exception.log 2>/dev/null || echo 0)"

# ---------------------------------------------------------------------- status
log "Status"
APPLIED=()
MISSING=()
for i in "${!PATCHES[@]}"; do
    if is_applied "$i"; then
        APPLIED+=("$i")
        printf '    \033[0;32m%-8s\033[0m %-32s %s\n' applied "$(plabel "$i")" "$(pfile "$i")"
    else
        MISSING+=("$i")
        printf '    \033[0;33m%-8s\033[0m %-32s %s\n' missing "$(plabel "$i")" "$(pfile "$i")"
    fi
done

NOT_EXECUTABLE=()
for i in "${APPLIED[@]}"; do
    while read -r f; do
        if [[ -f "$f" && ! -x "$f" ]]; then NOT_EXECUTABLE+=("$f"); fi
    done < <(executables "$i")
done

MODULES_OUTDATED=0
if modules_outdated; then MODULES_OUTDATED=1; fi
if [[ $MODULES_OUTDATED -eq 1 ]]; then
    printf '    \033[0;33m%-8s\033[0m app/etc/config.php module order\n' outdated
else
    printf '    \033[0;32m%-8s\033[0m app/etc/config.php module order\n' current
fi

if [[ "$MODE" == check ]]; then
    PROBLEMS=0
    if [[ ${#MISSING[@]} -gt 0 ]]; then
        simulate forward "${MISSING[@]}" \
            || die "the missing patches do not apply -- the files named above differ from stock ${TARGET}"
        warn "${#MISSING[@]} patch(es) missing; they apply cleanly"
        PROBLEMS=1
    fi
    for f in "${NOT_EXECUTABLE[@]}"; do warn "${f} should be executable"; PROBLEMS=1; done
    if [[ $MODULES_OUTDATED -eq 1 ]]; then warn "config.php module order is outdated"; PROBLEMS=1; fi
    if [[ -f "$REBUILD_MARKER" ]]; then warn "a previous run did not finish"; PROBLEMS=1; fi
    if [[ $PROBLEMS -eq 1 ]]; then
        warn "run: bash shell/08-security-patches.sh"
        exit 2
    fi
    ok "all ${#PATCHES[@]} patches applied and the rebuild is complete"
    exit 0
fi

for f in "${NOT_EXECUTABLE[@]}"; do chmod +x "$f"; ok "made ${f} executable"; done

# The work list: missing patches oldest-first, or applied patches newest-first.
TODO=()
if [[ "$MODE" == apply ]]; then
    TODO=("${MISSING[@]}")
else
    for ((k = ${#APPLIED[@]} - 1; k >= 0; k--)); do TODO+=("${APPLIED[k]}"); done
fi

if [[ ${#TODO[@]} -eq 0 && ! -f "$REBUILD_MARKER" && $MODULES_OUTDATED -eq 0 ]]; then
    if [[ "$MODE" == apply ]]; then ok "all patches already applied -- nothing to do"; else ok "no patches applied -- nothing to revert"; fi
    exit 0
fi

if [[ ${#TODO[@]} -gt 0 ]]; then
    log "Test-applying to a scratch copy first"
    if [[ "$MODE" == apply ]]; then
        simulate forward "${TODO[@]}" \
            || die "nothing was changed. The files named above differ from stock ${TARGET} -- find out why before patching."
    else
        simulate reverse "${TODO[@]}" \
            || die "nothing was changed. The files named above no longer match the patched state."
    fi
    ok "all ${#TODO[@]} patch(es) ${MODE} cleanly"

    log "Snapshotting the files the patches touch"
    mkdir -p "$BACKUP_DIR"
    SNAPSHOT="${BACKUP_DIR}/security-patches-${STAMP}.tar.gz"
    for i in "${!PATCHES[@]}"; do touched "$i"; done | sort -u > "${BACKUP_DIR}/security-patches-${STAMP}.files"
    cp -p app/etc/config.php "${BACKUP_DIR}/security-patches-${STAMP}.config.php"
    while read -r f; do
        if [[ -e "$f" ]]; then printf '%s\n' "$f"; fi
    done < "${BACKUP_DIR}/security-patches-${STAMP}.files" | tar -czf "$SNAPSHOT" -T -
    ok "$SNAPSHOT"
    ok "(.files lists every path, including ones the patches create; .config.php is app/etc/config.php)"
elif [[ -f "$REBUILD_MARKER" ]]; then
    log "The previous run did not finish -- resuming"
else
    log "Patches are in place but config.php module order is stale -- rebuilding"
fi

# ----------------------------------------------------------------- maintenance
log "Maintenance mode"
# A run that died after enabling maintenance records that in the marker, so
# this run knows to turn it back off rather than treating it as someone else's.
if [[ -f "$REBUILD_MARKER" ]] && grep -qx 'maintenance=ours' "$REBUILD_MARKER"; then
    MAINT_BY_US=1
fi
if php bin/magento maintenance:status | grep -q 'is enabled'; then
    if [[ $MAINT_BY_US -eq 1 ]]; then ok "still on from the interrupted run"; else ok "already on -- it will be left on"; fi
else
    php bin/magento maintenance:enable >/dev/null
    MAINT_BY_US=1
    ok "maintenance mode ON"
fi
if [[ $MAINT_BY_US -eq 1 ]]; then echo "maintenance=ours" > "$REBUILD_MARKER"; else echo "maintenance=theirs" > "$REBUILD_MARKER"; fi

# ---------------------------------------------------------------------- patch
if [[ ${#TODO[@]} -gt 0 ]]; then
    log "$([[ "$MODE" == apply ]] && echo Applying || echo Reverting)"
    for i in "${TODO[@]}"; do
        if [[ "$MODE" == apply ]]; then
            git apply "$(pfile "$i")" 2>&1 | sed 's/^/        /'
            while read -r f; do chmod +x "$f"; done < <(executables "$i")
        else
            git apply -R "$(pfile "$i")" 2>&1 | sed 's/^/        /'
        fi
        ok "$(plabel "$i")"
    done
fi

# --------------------------------------------------------------------- rebuild
log "Module order in app/etc/config.php"
# config.php is tracked, but usually carries uncommitted changes -- git alone
# cannot restore it, so keep the exact previous copy.
mkdir -p "$BACKUP_DIR"
cp -p app/etc/config.php "${BACKUP_DIR}/security-patches-${STAMP}.config.php"
RESORT="$(resort_modules)" || die "could not re-sort the modules list -- see the message above"
case "$RESORT" in
    rewritten) ok "re-sorted to match the module.xml sequences"
               ok "previous copy: ${BACKUP_DIR}/security-patches-${STAMP}.config.php" ;;
    *)         ok "already current" ;;
esac

# Production mode never regenerates DI on demand, and the patches change
# constructors, di.xml and plugins. Storefront requests that arrive while
# generated/ is empty log "Class ...\Http\Interceptor does not exist" even
# under maintenance mode; that is expected and stops once this finishes.
log "Compiling dependency injection"
php bin/magento setup:di:compile

# setup:static-content:deploy -f does not replace files that are already
# deployed (doc/03-upgrade-execution-log.md, fault 3e), so the deployed copies
# of every patched web asset are removed first and checked afterwards.
log "Removing stale deployed copies of patched web assets"
shopt -s nullglob
DEPLOYED=()      # deployed path
DEPLOYED_SRC=()  # its source file, or "" for js-translation.json
while read -r f; do
    module=""
    case "$f" in
        lib/web/*)
            rel="${f#lib/web/}"; area="*" ;;
        vendor/magento/*/view/*/web/*)
            pkg="${f#vendor/magento/}"; pkg="${pkg%%/*}"
            area="${f#vendor/magento/${pkg}/view/}"; area="${area%%/*}"
            rel="${f#vendor/magento/${pkg}/view/${area}/web/}"
            module="$(php -r 'echo simplexml_load_file($argv[1])->module["name"];' "vendor/magento/${pkg}/etc/module.xml")/"
            [[ "$area" == base ]] && area="*" ;;
        */i18n/*.csv)
            rel="js-translation.json"; area="*"; f="" ;;
        *)
            continue ;;
    esac
    for d in pub/static/$area/*/*/*/"${module}${rel}"; do
        rm -f "$d"
        DEPLOYED+=("$d")
        DEPLOYED_SRC+=("$f")
    done
done < <(for i in "${!PATCHES[@]}"; do touched "$i"; done | sort -u)
shopt -u nullglob
ok "removed ${#DEPLOYED[@]} deployed file(s)"

rm -rf var/view_preprocessed/*

log "Deploying static content (${LOCALES})"
php bin/magento setup:static-content:deploy -f $LOCALES

# Ships in the distribution and is not regenerated by the deploy command.
# See doc/00-local-environment-setup.md, fault 1.
if [[ ! -f pub/static/.htaccess ]]; then
    cp vendor/magento/magento2-base/pub/static/.htaccess pub/static/.htaccess
    ok "restored pub/static/.htaccess (was missing)"
fi

MINIFY="$(php bin/magento config:show dev/js/minify_files 2>/dev/null || echo 0)"
for k in "${!DEPLOYED[@]}"; do
    d="${DEPLOYED[k]}"; src="${DEPLOYED_SRC[k]}"
    if [[ ! -f "$d" ]]; then
        [[ -n "$src" ]] || { warn "${d} was not regenerated"; continue; }
        die "${d} was not redeployed -- the patched asset is not being served"
    fi
    if [[ -n "$src" ]] && ! cmp -s "$src" "$d"; then
        [[ "$MINIFY" == 1 ]] && { ok "${d} redeployed (minified)"; continue; }
        die "${d} differs from ${src} -- a stale copy is still being served"
    fi
done
ok "every patched web asset is redeployed from its patched source"

log "Flushing caches"
php bin/magento cache:flush >/dev/null
# cache:flush leaves tag files owned by this user at 664, which php-fpm
# (www-data) cannot update -- the homepage then 500s. Remove them and let
# www-data recreate them. See doc/03-upgrade-execution-log.md, fault 3f.
rm -rf var/cache/* var/page_cache/*
ok "caches cleared"

# ---------------------------------------------------------------- verification
log "Verification"
for i in "${!PATCHES[@]}"; do
    if is_applied "$i"; then
        [[ "$MODE" == apply ]] || die "$(plabel "$i") is still applied"
        ok "applied  $(plabel "$i")"
        while read -r f; do
            [[ -x "$f" ]] || die "${f} is not executable"
        done < <(executables "$i")
    else
        [[ "$MODE" == revert ]] || die "$(plabel "$i") is not applied"
        ok "removed  $(plabel "$i")"
    fi
done
if modules_outdated; then
    die "module:config:status still reports config.php as outdated"
fi
ok "module:config:status: up to date"

if [[ $MAINT_BY_US -eq 1 ]]; then
    php bin/magento maintenance:disable >/dev/null
    MAINT_BY_US=0
    ok "maintenance mode OFF"
fi
rm -f "$REBUILD_MARKER"

FRONT_NAME="$(php -r '$e = include "app/etc/env.php"; echo $e["backend"]["frontName"] ?? "admin";')"
CODE="$(curl -s -o /dev/null -w '%{http_code}' -m 60 -H "Host: ${SMOKE_HOST}" http://127.0.0.1/ || echo 000)"
[[ "$CODE" == 200 ]] && ok "storefront returns 200" || warn "storefront returned ${CODE}"
CODE="$(curl -s -o /dev/null -w '%{http_code}' -m 60 -H "Host: ${SMOKE_HOST}" "http://127.0.0.1/${FRONT_NAME}" || echo 000)"
[[ "$CODE" == 200 || "$CODE" == 302 ]] && ok "admin returns ${CODE}" || warn "admin returned ${CODE}"

EXC_AFTER="$(wc -l < var/log/exception.log 2>/dev/null || echo 0)"
if [[ "$EXC_AFTER" -gt "$EXC_BEFORE" ]]; then
    warn "var/log/exception.log grew during this run (requests during the DI compile are expected):"
    tail -n "+$((EXC_BEFORE + 1))" var/log/exception.log | grep -m 5 '^\[' | cut -c1-200 | sed 's/^/        /'
else
    ok "no new entries in var/log/exception.log"
fi

# --------------------------------------------------------------------- summary
if [[ "$MODE" == revert ]]; then
    printf '\n    Patches removed. This install is exposed to CVE-2026-75650 again.\n\n'
    exit 0
fi

cat <<SUMMARY

    All ${#PATCHES[@]} Adobe security patches for ${TARGET} are applied.

    Re-run this script after EVERY composer install / update -- Composer
    restores stock files and silently removes these patches.

    Not automated -- APSB26-146 (VULN-39341) remediation. The flaw was exploited
    in the wild before the fix, so on any internet-facing copy of this store
    Adobe requires rotating, after patching:
      - the encryption key (bin/magento encryption:key:change)
      - every admin password, and all REST/SOAP/GraphQL integration tokens
      - OAuth secrets, payment gateway credentials (Authorize.net CIM),
        shipping / tax / extension API keys, database and SSH credentials

    Rollback:  bash shell/08-security-patches.sh --revert

SUMMARY
