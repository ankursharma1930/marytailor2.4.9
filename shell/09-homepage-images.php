<?php
/**
 * STEP 9: HOMEPAGE IMAGES -> RESPONSIVE WEBP
 *
 *   php shell/09-homepage-images.php            generate missing or stale variants
 *   php shell/09-homepage-images.php --check    report only; exit 1 if any are missing or stale
 *   php shell/09-homepage-images.php --force    rewrite every variant (after changing QUALITY)
 *
 * WHY THIS EXISTS
 *   The homepage hero banners and category tiles are hard-coded in the child theme
 *   (Magento_Theme/templates/elements/slider-c.phtml, html/homepage.phtml,
 *   html/middle-homepage.phtml) and were served as-is from pub/media/wysiwyg:
 *
 *     8 banners  1933x755  100-180 KB each  (JPEG data, most of them named .png)
 *     4 tiles     570x309   60-69 KB each   (PNG)
 *
 *   A 412px-wide phone downloaded the full 1933px banner. This writes WebP copies
 *   at the widths the templates list in their srcset, so the browser can pick one.
 *
 * WHERE THE OUTPUT GOES
 *   app/design/frontend/Aureate/hyva/web/images/homepage/<name>-<width>.webp
 *
 *   The child theme, not pub/media: the theme is tracked in git and deployed by
 *   setup:static-content:deploy, so the variants exist on every environment the
 *   templates do. pub/media is git-ignored, and a missing file there is answered
 *   with HTTP 200 and Magento's placeholder image, not a 404 - a srcset pointing
 *   at a variant that was never generated would silently show the placeholder.
 *
 * AFTER RUNNING
 *   New files under web/ only reach the storefront through a static deploy:
 *     rm -rf var/view_preprocessed/* pub/static/frontend/Aureate/hyva
 *     php bin/magento setup:static-content:deploy -f --theme Aureate/hyva en_US
 *
 * Idempotent: without --force, a variant is rewritten only when it is missing or
 * older than its source. Reads pub/media, writes only the directory above. No database access.
 * Needs the GD extension with WebP support.
 *
 * Keep SOURCES and the widths in step with the templates' srcset lists.
 */
declare(strict_types=1);

const BANNER_WIDTHS = [640, 768, 960, 1366, 1933];
const TILE_WIDTHS = [320, 400, 570];
// 70: banner label text stays sharp at 960px. These banners are already tightly
// compressed JPEGs, so WebP saves little at equal width; the gain is in the widths.
const QUALITY = 70;

$root = dirname(__DIR__);
$srcDir = $root . '/pub/media/wysiwyg';
$outDir = $root . '/app/design/frontend/Aureate/hyva/web/images/homepage';
$checkOnly = in_array('--check', $argv, true);
$force = in_array('--force', $argv, true);

$sources = [
    '01-dish-soap-banner-mary-tylor-naturals.png' => BANNER_WIDTHS,
    '01-laundry-soap-banner-mary-tylor-naturals.png' => BANNER_WIDTHS,
    '03-baby-soap-sensitive-skin-pet-soap-banner-mary-tylor-naturals.png' => BANNER_WIDTHS,
    '04-all-purpose-cleaner-banner-mary-tylor-naturals.png' => BANNER_WIDTHS,
    '05-handsoap-banner-mary-tylor-naturals.png' => BANNER_WIDTHS,
    '06-the-healthy-candle-collection-banner-mary-tylor-naturals.png' => BANNER_WIDTHS,
    '07-castile-soap-banner-mary-tylor-naturals.png' => BANNER_WIDTHS,
    '08-bodysoap-banner-mary-tylor-naturals.jpeg' => BANNER_WIDTHS,
    'small-icon-img-001.png' => TILE_WIDTHS,
    'small-icon-img-002.png' => TILE_WIDTHS,
    'small-icon-image-003.png' => TILE_WIDTHS,
    'small-icon-image-004.png' => TILE_WIDTHS,
];

if (!function_exists('imagewebp') || !(gd_info()['WebP Support'] ?? false)) {
    fwrite(STDERR, "GD with WebP support is required.\n");
    exit(2);
}
if (!$checkOnly && !is_dir($outDir) && !mkdir($outDir, 0775, true)) {
    fwrite(STDERR, "Cannot create $outDir\n");
    exit(2);
}

$stale = 0;
$written = 0;

foreach ($sources as $file => $widths) {
    $src = "$srcDir/$file";
    if (!is_file($src)) {
        fwrite(STDERR, "missing source: $src\n");
        exit(2);
    }
    $name = pathinfo($file, PATHINFO_FILENAME);
    $image = null;

    foreach ($widths as $width) {
        $dest = "$outDir/$name-$width.webp";
        if (!$force && is_file($dest) && filemtime($dest) >= filemtime($src)) {
            continue;
        }
        $stale++;
        if ($checkOnly) {
            echo "stale    $dest\n";
            continue;
        }

        // imagecreatefromstring, not imagecreatefrompng: most "banner .png" files are JPEG data.
        $image ??= imagecreatefromstring((string) file_get_contents($src));
        if ($image === false) {
            fwrite(STDERR, "cannot decode $src\n");
            exit(2);
        }
        $srcW = imagesx($image);
        $srcH = imagesy($image);
        $width = min($width, $srcW);
        $height = (int) round($srcH * $width / $srcW);

        $resized = imagecreatetruecolor($width, $height);
        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $srcW, $srcH);

        $tmp = "$dest.tmp";
        if (!imagewebp($resized, $tmp, QUALITY) || !rename($tmp, $dest)) {
            fwrite(STDERR, "cannot write $dest\n");
            exit(2);
        }
        imagedestroy($resized);

        $written++;
        printf("wrote    %-72s %4d KB\n", basename($dest), filesize($dest) / 1024);
    }
    if ($image) {
        imagedestroy($image);
    }
}

if ($checkOnly) {
    echo $stale ? "$stale variant(s) missing or stale\n" : "all variants current\n";
    exit($stale ? 1 : 0);
}
echo $written ? "$written variant(s) written\n" : "all variants already current\n";
