<?php
/**
 * PRODUCT IMAGES -> WEBP, served at the same URL
 *
 *   php shell/11-product-images-webp.php            generate missing/stale WebP copies, ensure the Apache rule
 *   php shell/11-product-images-webp.php --check    report only; exit 0 = all current, 1 = work to do
 *   php shell/11-product-images-webp.php --prune    also delete WebP copies (and markers) whose original is gone
 *
 * WHY
 *   Magento's resized product images (pub/media/catalog/product/cache) keep the format of the
 *   upload, and most uploads here are PNG photos: a 360x360 card image is 139 KB. Magento has no
 *   WebP support of its own, and the URLs are built in many places (lists, gallery, cart, JSON).
 *
 * HOW
 *   1. For each cached JPEG/PNG, write "<file>.webp" beside it - only kept when it is smaller.
 *   2. A block in pub/media/.htaccess serves that .webp instead, at the same URL, when the browser
 *      sends "Accept: image/webp" (every current browser) and the copy exists. Otherwise the
 *      original is served, exactly as before. Responses carry "Vary: Accept" (needs mod_headers).
 *   No template, layout or database change.
 *
 * COMPOSER OVERWRITES pub/media/.htaccess (magento2-base maps it), so the rule silently disappears
 * after composer install/update. Run --check afterwards, like shell/08-security-patches.sh.
 *
 * NEW IMAGES: Magento creates cache images on demand, and "Flush Catalog Images Cache" deletes
 * them. Until this runs again those images are served as JPEG/PNG - nothing breaks. Run it from
 * cron (it only converts what is new or changed), or after catalog:images:resize.
 *
 * CDN: behind a CDN that ignores "Vary: Accept" (Cloudflare does, without Polish), one variant
 * would be cached for everyone - including email clients without WebP (abandoned-cart emails
 * embed product images). On production, prefer the CDN's own WebP feature (Cloudflare Polish).
 *
 * Idempotent: only writes what is missing or older than its original. When WebP is no smaller, an
 * empty "<file>.nowebp" marker records that, so the image is not re-encoded on every run.
 * Needs GD with WebP. Writes pub/media only; no database access. "libpng warning: Interlace
 * handling..." lines come from libpng itself for interlaced PNGs and are harmless.
 */
declare(strict_types=1);

const QUALITY = 80;
const EXTENSIONS = ['jpg', 'jpeg', 'png'];
const HTACCESS_BEGIN = '## BEGIN webp-negotiation (shell/11-product-images-webp.php) - do not edit by hand';
const HTACCESS_END = '## END webp-negotiation';
const HTACCESS_BLOCK = <<<'APACHE'
## BEGIN webp-negotiation (shell/11-product-images-webp.php) - do not edit by hand
## Serve catalog/product/cache/<image>.webp in place of <image> when the browser accepts WebP and
## the script has written a copy. Same URL, so nothing else changes. Must precede the get.php rule.
<IfModule mod_rewrite.c>
    RewriteEngine on
    RewriteCond %{HTTP_ACCEPT} image/webp
    RewriteCond %{REQUEST_FILENAME} -f
    RewriteCond %{REQUEST_FILENAME}.webp -f
    RewriteRule ^(catalog/product/cache/.+\.(?:png|jpe?g))$ $1.webp [NC,T=image/webp,END]
</IfModule>
<IfModule mod_headers.c>
    <FilesMatch "\.(png|jpe?g)(\.webp)?$">
        Header merge Vary Accept
    </FilesMatch>
</IfModule>
## END webp-negotiation
APACHE;

$root = dirname(__DIR__);
$cacheDir = $root . '/pub/media/catalog/product/cache';
$htaccess = $root . '/pub/media/.htaccess';
$check = in_array('--check', $argv, true);
$prune = in_array('--prune', $argv, true);

function log_step(string $m): void { printf("\n\033[1;34m==> %s\033[0m\n", $m); }
function log_ok(string $m): void   { printf("    \033[0;32m[ok]\033[0m %s\n", $m); }
function log_warn(string $m): void { printf("    \033[0;33m[warn]\033[0m %s\n", $m); }
function fail(string $m): never    { printf("\n\033[0;31m[FATAL]\033[0m %s\n", $m); exit(2); }

if (!function_exists('imagewebp') || !(gd_info()['WebP Support'] ?? false)) {
    fail('GD with WebP support is required');
}
if (!is_dir($cacheDir)) {
    fail("$cacheDir not found -- not a Magento root, or no product images cached yet");
}
$outOfDate = 0;

// --------------------------------------------------------------- .htaccess
log_step('Apache rule in pub/media/.htaccess');

$conf = (string) file_get_contents($htaccess);
$start = strpos($conf, HTACCESS_BEGIN);
$existingBlock = $start === false ? null
    : substr($conf, $start, strpos($conf, HTACCESS_END, $start) + strlen(HTACCESS_END) - $start);

if ($existingBlock === HTACCESS_BLOCK) {
    log_ok('present');
} elseif ($check) {
    log_warn($existingBlock === null ? 'missing (composer overwrites this file)' : 'present but differs from this script');
    $outOfDate++;
} else {
    // Insert (or replace) the block right before Magento's own rewrite block, whose get.php rule
    // must not see the request first.
    $conf = $existingBlock !== null ? str_replace($existingBlock . "\n", '', $conf) : $conf;
    $anchor = strpos($conf, '<IfModule mod_rewrite.c>');
    if ($anchor === false) {
        fail('no <IfModule mod_rewrite.c> block in pub/media/.htaccess to insert before');
    }
    $conf = substr($conf, 0, $anchor) . HTACCESS_BLOCK . "\n" . substr($conf, $anchor);
    if (file_put_contents($htaccess, $conf) === false) {
        fail("cannot write $htaccess");
    }
    log_ok($existingBlock === null ? 'added' : 'updated');
}

// -------------------------------------------------------------- conversion
log_step('WebP copies of pub/media/catalog/product/cache');

$kept = $skippedLarger = $current = $pruned = $bytesIn = $bytesOut = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($cacheDir, FilesystemIterator::SKIP_DOTS));

foreach ($it as $file) {
    $path = $file->getPathname();
    $ext = strtolower($file->getExtension());

    if ($ext === 'webp' || $ext === 'nowebp') {
        $original = substr($path, 0, -strlen('.' . $file->getExtension()));
        if (!is_file($original) && in_array(strtolower(pathinfo($original, PATHINFO_EXTENSION)), EXTENSIONS, true)) {
            if ($prune && !$check) {
                unlink($path);
                $pruned++;
            } elseif ($prune) {
                $outOfDate++;
            }
        }
        continue;
    }
    if (!in_array($ext, EXTENSIONS, true)) {
        continue;
    }

    $webp = $path . '.webp';
    $noWebp = $path . '.nowebp';
    if ((is_file($webp) && filemtime($webp) >= filemtime($path))
        || (is_file($noWebp) && filemtime($noWebp) >= filemtime($path))) {
        $current++;
        continue;
    }
    if ($check) {
        $outOfDate++;
        continue;
    }

    $image = @imagecreatefromstring((string) file_get_contents($path));
    if ($image === false) {
        log_warn('cannot decode ' . substr($path, strlen($root) + 1));
        continue;
    }
    if (!imageistruecolor($image)) {
        imagepalettetotruecolor($image);
    }
    imagealphablending($image, false);
    imagesavealpha($image, true);

    $tmp = $webp . '.tmp';
    $ok = imagewebp($image, $tmp, QUALITY);
    imagedestroy($image);
    if (!$ok) {
        @unlink($tmp);
        log_warn('cannot encode ' . substr($path, strlen($root) + 1));
        continue;
    }

    // Only worth serving when smaller; otherwise leave no copy and the original is served.
    if (filesize($tmp) >= filesize($path)) {
        unlink($tmp);
        @unlink($webp);
        touch($noWebp);
        $skippedLarger++;
        continue;
    }
    rename($tmp, $webp);
    @unlink($noWebp);
    $kept++;
    $bytesIn += filesize($path);
    $bytesOut += filesize($webp);
}

if ($check) {
    printf("    %d current, %d missing or stale%s\n", $current, $outOfDate, $prune ? ' (incl. orphans)' : '');
    exit($outOfDate ? 1 : 0);
}

printf("    %d already current\n", $current);
if ($kept) {
    printf("    %d written: %.1f MB -> %.1f MB (%d%% smaller)\n", $kept, $bytesIn / 1048576, $bytesOut / 1048576, 100 - round(100 * $bytesOut / $bytesIn));
}
if ($skippedLarger) {
    printf("    %d not kept - WebP was no smaller; the original is served (marked .nowebp)\n", $skippedLarger);
}
if ($pruned) {
    printf("    %d orphaned copies removed\n", $pruned);
}
log_ok('done');
