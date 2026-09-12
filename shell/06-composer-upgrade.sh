#!/usr/bin/env bash
#
# STEP 6 of the Magento 2.4.6 -> 2.4.9 upgrade: COMPOSER UPGRADE
#
#   bash shell/06-composer-upgrade.sh
#
# One concern: move the Composer dependency tree from 2.4.6 to 2.4.9.
# It does NOT touch the database -- that is step 7.
#
# DISRUPTIVE. Enables maintenance mode and LEAVES IT ON, because once
# vendor/ is swapped the 2.4.6 schema no longer matches the 2.4.9 code.
# shell/07-magento-upgrade.sh is what brings the site back up.
#
# Idempotent: re-running after a successful upgrade detects 2.4.9 and exits 0.
#
set -euo pipefail

MAGENTO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_ROOT="/var/backups/marytylor-2.4.9"
TARGET="2.4.9"
STAMP="$(date +%Y%m%d-%H%M%S)"
ARTIFACTS="${MAGENTO_ROOT}/doc/artifacts"

log()  { printf '\n\033[1;34m==> %s\033[0m\n' "$*"; }
ok()   { printf '    \033[0;32m[ok]\033[0m %s\n' "$*"; }
warn() { printf '    \033[0;33m[warn]\033[0m %s\n' "$*"; }
die()  { printf '\n\033[0;31m[FATAL]\033[0m %s\n' "$*" >&2; exit 1; }

cd "$MAGENTO_ROOT"
[[ -f app/etc/env.php ]] || die "app/etc/env.php not found -- is this a Magento root?"
[[ -f composer.json  ]] || die "composer.json not found"

# ---------------------------------------------------------------- idempotency
installed_version() {
    php -r '
        $f = "vendor/composer/installed.json";
        if (!is_file($f)) { echo "none"; exit; }
        $j = json_decode(file_get_contents($f), true);
        foreach (($j["packages"] ?? $j) as $p) {
            if ($p["name"] === "magento/product-community-edition") { echo $p["version"]; exit; }
        }
        echo "none";
    '
}

CURRENT="$(installed_version)"
if [[ "$CURRENT" == "$TARGET" ]]; then
    ok "magento/product-community-edition is already ${TARGET} -- nothing to do"
    ok "next: bash shell/07-magento-upgrade.sh"
    exit 0
fi
log "Installed: ${CURRENT}  ->  target: ${TARGET}"

# --------------------------------------------------------------------- guards
log "Pre-flight checks"

PHP_MM="$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')"
case "$PHP_MM" in
    8.3|8.4|8.5) ok "PHP ${PHP_MM} (2.4.9 requires ~8.3 || ~8.4 || ~8.5)" ;;
    *) die "PHP ${PHP_MM} cannot run Magento ${TARGET}. Activate PHP 8.3 first." ;;
esac

COMPOSER_V="$(composer --version --no-ansi 2>/dev/null | grep -oE '[0-9]+\.[0-9]+\.[0-9]+' | head -1)"
php -r 'exit(version_compare($argv[1], "2.2.0", ">=") ? 0 : 1);' "$COMPOSER_V" \
    || die "Composer ${COMPOSER_V} is too old -- 2.4.9 requires ^2.2. Run: composer self-update"
ok "Composer ${COMPOSER_V}"

# A backup is not optional. This is the last step before vendor/ is replaced.
shopt -s nullglob
BACKUPS=("${BACKUP_ROOT}"/*/)
shopt -u nullglob
[[ ${#BACKUPS[@]} -gt 0 ]] \
    || die "no backup found in ${BACKUP_ROOT}
       Run:  sudo bash shell/03-backup.sh
       This step replaces vendor/ and cannot be undone without one."
ok "backup present: ${BACKUPS[-1]}"

AVAIL_GB="$(df -BG --output=avail "$MAGENTO_ROOT" | tail -1 | tr -dc '0-9')"
[[ "$AVAIL_GB" -ge 10 ]] || die "only ${AVAIL_GB}GB free -- need at least 10GB"
ok "disk: ${AVAIL_GB}GB free"

# repo.magento.com credentials must be present AND readable by this user
[[ -r auth.json ]] || die "auth.json is not readable by $(whoami)"
php -r '
    $j = json_decode(file_get_contents("auth.json"), true);
    exit(isset($j["http-basic"]["repo.magento.com"]) ? 0 : 1);
' || die "auth.json has no repo.magento.com credentials -- 2.4.9 cannot be downloaded.
       Add your Magento Marketplace access keys (public key = username, private key = password)."
ok "repo.magento.com credentials present"

# ------------------------------------------- archive what composer will destroy
log "Archiving vendor/ customisations before they are overwritten"
mkdir -p "$ARTIFACTS"
if git rev-parse --git-dir >/dev/null 2>&1; then
    if ! git diff --quiet -- vendor/ 2>/dev/null; then
        git diff -- vendor/ > "${ARTIFACTS}/vendor-customisations-${STAMP}.patch"
        ok "saved $(git diff --name-only -- vendor/ | wc -l) modified vendor/ file(s) to"
        ok "  doc/artifacts/vendor-customisations-${STAMP}.patch"
        git diff --name-only -- vendor/ | sed 's/^/        /'
    else
        ok "vendor/ has no uncommitted edits"
    fi
fi

# ------------------------------------------------------ snapshot composer files
cp -a composer.json "${ARTIFACTS}/composer.json.${STAMP}.bak"
[[ -f composer.lock ]] && cp -a composer.lock "${ARTIFACTS}/composer.lock.${STAMP}.bak"
ok "composer.json/lock snapshotted to doc/artifacts/"

# ------------------------------------------------- rewrite composer.json to 2.4.9
log "Rewriting composer.json for ${TARGET}"
php <<'PHP'
<?php
$f = 'composer.json';
$j = json_decode(file_get_contents($f), true);
if (!is_array($j)) { fwrite(STDERR, "composer.json is not valid JSON\n"); exit(1); }

// --- platform packages, per stock magento/project-community-edition 2.4.9
$j['require']['magento/product-community-edition']    = '2.4.9';
$j['require']['magento/composer-root-update-plugin']  = '^2.0.4';
$j['require']['magento/composer']                     = '^1.10.1-beta1';

// --- Plumrocket Checkout Success Page.
// The licence only serves 2.2.x while 3.3.0 is installed, and the extension is
// switched off site-wide (prcheckoutspage/general/enabled = 0), so it is removed
// rather than downgraded. Its 36 core_config_data rows are left untouched, so
// renewing the licence later is a plain `composer require`.
unset($j['require']['plumrocket/module-checkoutspage']);
unset($j['require']['hyva-themes/magento2-plumrocket-checkoutspage']);

// --- require-dev: 2.4.6's block collides with 2.4.9 independently of Plumrocket
// (symfony/finder ^5.4 vs ^7.4, MFTF ^4 vs ^6, phpunit ^9.5 vs ^12).
// Adopt 2.4.9's stock block verbatim, preserving this project's own dev packages.
$stockDev = [
    'allure-framework/allure-phpunit'                => '^3.2',
    'dealerdirect/phpcodesniffer-composer-installer' => '^0.7 || ^1.0',
    'dg/bypass-finals'                               => '^1.4',
    'friendsofphp/php-cs-fixer'                      => '^3.22',
    'magento/magento-coding-standard'                => '*',
    'magento/magento2-functional-testing-framework'  => '^6.0',
    'pdepend/pdepend'                                => '^3@dev',
    'phpmd/phpmd'                                    => '^3@dev',
    'phpstan/phpstan'                                => '^1.9',
    'phpunit/phpunit'                                => '^12.0',
    'symfony/finder'                                 => '^7.4',
];
// Project additions that are not part of stock Magento and must survive.
$keep = array_intersect_key($j['require-dev'] ?? [], ['hyva-themes/hyva-ui' => 1]);
$j['require-dev'] = $stockDev + $keep;

// --- allow-plugins, per stock 2.4.9. Composer 2.2+ refuses to run plugins
// without it, which would silently skip magento/magento-composer-installer.
$j['config']['allow-plugins'] = [
    'dealerdirect/phpcodesniffer-composer-installer' => true,
    'laminas/laminas-dependency-plugin'              => true,
    'magento/*'                                      => true,
    'php-http/discovery'                             => true,
];

file_put_contents($f, json_encode($j, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
echo "    composer.json rewritten\n";
PHP

php -r 'json_decode(file_get_contents("composer.json")); exit(json_last_error() === JSON_ERROR_NONE ? 0 : 1);' \
    || die "composer.json is no longer valid JSON -- restore from doc/artifacts/composer.json.${STAMP}.bak"
composer validate --no-check-publish --no-check-lock --quiet && ok "composer.json validates"

# --------------------------------------------------------------------- dry run
log "Dry run (resolves the full tree, installs nothing)"
if ! composer update --with-all-dependencies --dry-run --no-scripts 2>&1 | tail -25; then
    die "dry run FAILED -- composer.json left rewritten; restore with:
       cp doc/artifacts/composer.json.${STAMP}.bak composer.json"
fi
ok "dry run resolved"

# ------------------------------------------------------------------ confirmation
cat <<CONFIRM

    ----------------------------------------------------------------
    The next step replaces vendor/ and takes the storefront DOWN
    until shell/07-magento-upgrade.sh has run.

      Magento     ${CURRENT}  ->  ${TARGET}
      Backup      ${BACKUPS[-1]}
      Rollback    git checkout pre-2.4.9-upgrade && composer install
    ----------------------------------------------------------------

CONFIRM
read -r -p "    Type 'upgrade' to proceed: " REPLY_TEXT
[[ "$REPLY_TEXT" == "upgrade" ]] || die "aborted -- nothing was installed (composer.json is rewritten; restore from doc/artifacts/ if abandoning)"

# ------------------------------------------------------------------ maintenance
log "Enabling maintenance mode"
php bin/magento maintenance:enable && ok "maintenance mode ON (stays on until step 7)"

# --------------------------------------------------------------------- the work
log "Running composer update -- this takes a while"
composer update --with-all-dependencies

# ----------------------------------------------------------------- verification
log "Verifying"
NEW="$(installed_version)"
[[ "$NEW" == "$TARGET" ]] || die "expected ${TARGET}, got ${NEW} -- investigate before running step 7"
ok "magento/product-community-edition ${NEW}"

FRAMEWORK="$(php -r '
    $j = json_decode(file_get_contents("vendor/composer/installed.json"), true);
    foreach (($j["packages"] ?? $j) as $p) {
        if ($p["name"] === "magento/framework") { echo $p["version"]; exit; }
    }
')"
ok "magento/framework ${FRAMEWORK}"

log "Composer upgrade complete"
cat <<SUMMARY

    The site is in MAINTENANCE MODE and the schema is still 2.4.6.
    It stays down until step 7 runs.

    NEXT:  bash shell/07-magento-upgrade.sh

    Re-check the Hyva customisation that could not be moved to the child
    theme -- the ttl="3600" removal on topmenu_generic. Hyva has been
    replaced, so confirm what the new file does:

      grep -n 'ttl' vendor/hyva-themes/magento2-default-theme/Magento_Theme/layout/default.xml

    See doc/03-upgrade-execution.md for why it was not carried over.

SUMMARY
