# 06 — Homepage performance (Lighthouse)

Work to lift the homepage Lighthouse performance score above 90 on mobile and
desktop. Measured on `marytailor.local`, 2026-09-25, Magento 2.4.9, Hyvä 1.5.2,
`MAGE_MODE=production`, Lighthouse 12.8.2. Follows `05-frontend-performance.md`.

**Status: done. Mobile 93 / 94 / 94, desktop 100 / 100 / 100.** Results after
section 8. The analytics tags are unchanged: see *Outstanding* and
`07-checkout-skimmer.md`.

## Results

Performance score, 3 runs each, median in bold:

| | Before | After |
|---|---|---|
| Desktop | 67 / **72** / 74 | 100 / **100** / 100 |
| Mobile | 36 / **45** / 59 | 93 / **94** / 94 |

| Mobile metric | Before | After |
|---|---|---|
| CLS | 0.146–0.832 | 0 |
| LCP (simulated) | 13.7 s | 2.5 s |
| FCP | 3.0 s | 1.6 s |
| Speed Index | 5.8 s | 1.9 s |
| TBT | 560 ms (178–747) | 190 ms |
| Image bytes on load | about 1.9 MB | 156 KB |

**TBT here is noisy.** Lighthouse multiplies the observed main-thread time by 4.
Lighthouse, Chrome, PHP, MySQL, OpenSearch and the editor all share this 4-core
VM. The same code measured 370 ms TBT in one batch and 1,180 ms in another. Load
average rose from 1.2 to 3.0 during a run. Compare medians of at least 3 runs,
and check `uptime` first.

## What was wrong, and the fixes

All changes are in the child theme or `shell/`. Nothing in `vendor/` or
`app/code/` was edited.

### 1. The hero slider moved the whole page twice (CLS ~1.0)

Splide's core CSS is inlined at the **end of `<body>`**, and slides get their
width only when the JS mounts. The first paint therefore showed all 8 banners
stacked, about 4,200 px tall. When the CSS parsed, they collapsed to 0 px. When
Splide mounted, they became one banner tall. Each step moved everything below.

- `web/tailwind/components/hero-slider.css` (new): gives `.hero-slider` its
  mounted geometry from the head stylesheet, so first paint equals the mounted
  slider.
- `ui-slider/slider-php.phtml`: adds the `hero-slider` class. Also switches
  `x-defer` from `intersect` to `interact`: Splide mounts on first
  touch/scroll/hover/key. The layout is already final, so only the dots and
  autoplay wait.

### 2. Every banner was a full-size 100–180 KB JPEG, and all 8 downloaded at once

- `shell/09-homepage-images.php` (new) writes responsive WebP copies into
  `web/images/homepage/`:
  - banners at 640/768/960/1366/1933 px
  - tiles at 320/400/570 px

  The copies go in the theme rather than `pub/media` so they are tracked in
  git. A missing file in `pub/media` returns a 200 placeholder, not a 404.
  Re-run the script after replacing a banner (`--check`, `--force`).
- `slider-c.phtml`, `slider-item.phtml`, `homepage.phtml`,
  `middle-homepage.phtml` serve them with `srcset`/`sizes`.
- Slides 2–8 load through Splide's `lazyLoad: 'nearby'`. Native lazy loading
  fetched them all, since they sit just beside the viewport.
- **Those slides must not also have `loading="lazy"`.** Splide hides a pending
  image (`display: none`) until it has loaded, and Chrome never fetches a
  hidden native-lazy image. The first version did both: every slide after the
  first stayed a loading spinner. Fixed in `slider-php.phtml` and
  `slider-item.phtml`. Checked by clicking through all 8 slides on desktop and
  mobile.

### 3. The mobile LCP element was a CSS background found late

The Featured Products section (`bestseller-slider-container.phtml`) is the LCP
element on phones. Its `bg_category.jpg` background was only discovered after
layout. It is now an `<img fetchpriority="high">` behind the content, so the
preload scanner fetches it with the HTML.

### 4. Other images competed with the LCP image

- The tiles and Featured product images get `fetchpriority="low"`. The first
  product image is a 142 KB PNG from the image cache, just inside a phone's
  viewport.
- Mirasvit's points loader GIF (`Mirasvit_RewardsCatalog/.../adjustment.phtml`)
  is hidden and lazy until Alpine shows it. Before, every price displayed a
  spinner, and the GIF loaded on every product page. This one is site-wide.

### 5. Font Awesome blocked the first paint

It is a third-party render-blocking stylesheet, and the homepage's only icons
are in the footer. On the homepage only, `cms_index_index.xml` removes it from
`<head>`. `page/font-awesome-async.phtml` re-adds it as a non-blocking
`media="print"` stylesheet. Other pages are unchanged, since checkout, CMS
blocks and recipes use icons higher up.

### 6. Alpine start-up was one long task

The three Snowdog menus initialised with the page. Measured at 4× CPU, deferring
them cut Alpine start-up from 323 ms to 155 ms:

- `hyva-menu-footer/menu.phtml`: `x-defer="intersect"`
- `hyva-topmenu-desktop/menu.phtml`: `x-defer="idle"`
- `hyva-topmenu-mobile/menu.phtml` (new child-theme copy of the `app/code`
  template): `x-defer="interact"`

Verified: the mobile burger opens on the first tap straight after load, desktop
hover opens submenus, and footer toggles work on the home, category and product
pages.

### 7. Desktop Lighthouse intermittently failed with `NO_LCP`

The Featured Products track (`.js_slides.snap`) has `md:px-1 xl:px-2` padding,
so its first snap point sat 4–8 px in. Chrome snapped the track as soon as it
laid out, which fired a trusted `scroll` event. Chrome treats that as a user
scroll and stops recording LCP. When the snap came before the first LCP entry,
the page had no LCP at all.

Fixed with matching `md:scroll-px-1 xl:scroll-px-2` in
`bestseller-slider-container.phtml`. Beyond Lighthouse, this also truncated the
LCP that real Chrome users report.

### 8. Below-the-fold images, placeholders, and WebP product images

- **Loaded on the first interaction.** This applies to the Featured Products
  images, the hand-soap banner and the blog images. Native `loading="lazy"`
  still fetched them during page load, because Chrome starts lazy images up to
  about 1,250 px below the fold.

  They now render a same-ratio SVG placeholder, with the real URL in
  `data-interaction-src`/`-srcset`. `page/js/load-on-interaction.phtml`
  (homepage only) swaps them in on the first
  mousemove/touchstart/wheel/scroll/keydown. An image already on screen loads
  at once, which covers a very tall window, and Googlebot, which renders tall
  but never interacts; verified at 1350×9000.

  The product card image is opt-in: a child-theme copy of Hyvä's
  `Magento_Catalog/templates/product/list/image.phtml` honours
  `load_on_interaction` in `image_custom_attributes`, and only the Featured
  slider passes it.
- **Placeholder while loading, site-wide.** `page/js/image-placeholders.phtml`
  (in `default.xml`) and `components/image-placeholder.css` give not-yet-loaded
  lazy images a light grey box, then fade them in. Eager images (the hero and
  the LCP image) and images already in the browser cache are never touched.
  Checked on home and category pages: after scrolling, no visible image stays
  grey.
- **Product images as WebP**, via `shell/11-product-images-webp.php`. It writes
  `<image>.webp` beside each file in `pub/media/catalog/product/cache`, keeping
  it only when smaller: 1,482 files, 72.5 MB → 22.1 MB. A rule it adds to
  `pub/media/.htaccess` serves the copy at the **same URL** to browsers that
  accept WebP. The first Featured image went from 142 KB (PNG) to 19.8 KB.
  - Composer overwrites `pub/media/.htaccess`: run
    `php shell/11-product-images-webp.php --check` after any
    `composer install`/`update`.
  - Magento creates cache images on demand, so run the script from cron, or
    after `catalog:images:resize`.

**Why some images felt slow locally.** This copy's `pub/media` is incomplete:
394 of 793 product source images and 14 of 263 blog featured images are
missing. A missing image is answered by PHP through `get.php`, taking
0.3–2 s, and it returns a 262×262 placeholder. Six homepage images were
affected: 3 blog images and 3 Featured product images. Production presumably
has the files. Syncing `pub/media/catalog/product` and `pub/media/magefan_blog`
from production fixes it locally.

## Deploying

```bash
php shell/09-homepage-images.php --check     # exit 0 = all WebP variants present
php shell/11-product-images-webp.php --check # exit 0 = product WebP copies + .htaccess rule current
npm --prefix app/design/frontend/Aureate/hyva/web/tailwind run build
rm -rf var/view_preprocessed/* pub/static/frontend/Aureate/hyva
php bin/magento setup:static-content:deploy -f --theme Aureate/hyva en_US
rm -rf var/cache/* var/page_cache/*
```

## Outstanding

1. **Analytics: roughly half of the remaining mobile TBT.** `gtag.js` (GA4)
   accounts for about 475–555 ms. `analytics.js` accounts for about 75 ms: it is
   Universal Analytics `UA-124279593-1`, served by `Magento_GoogleAnalytics`,
   and Google shut UA down in 2024. Options, each needing a decision:
   - disable `google/analytics/active`
   - delay GA4 until the first interaction

   Either way it is a DB change and belongs in a `shell/` script. GA4 sits in
   the same `design/head/includes` value as the injected script, so it waits on
   the security response.
2. The site is HTTP/1.1 locally (6 connections per origin). Production over
   HTTPS/HTTP/2 will behave differently.
3. On production behind Cloudflare, prefer Cloudflare Polish over the
   `.htaccess` WebP rule. The CDN ignores `Vary: Accept`, so it could serve WebP
   to email clients that lack it (abandoned-cart emails embed product images).
4. **Browser caching is still off.** `mod_headers` and `mod_expires` are not
   enabled, so images and CSS are sent with no `Cache-Control`/`Expires` and are
   re-checked on every page view. Enabling them also adds `Vary: Accept` to the
   WebP rule. Needs root:
   `sudo a2enmod headers expires && sudo systemctl reload apache2`
5. Pre-existing, not addressed: category-page product images have no reserved
   dimensions and shift the cards (CLS 0.023 on desktop `/soap-supply`). OPcache
   sizing is still as described in `doc/05`.
