# 05 — Frontend performance: homepage & category pages

Investigation and fix for slow homepage / category rendering and poor Lighthouse
scores on the Hyvä storefront. Measured on `marytailor.local`, 2026-09-16,
Magento 2.4.9 / PHP 8.3 / Hyvä 1.5.2, `MAGE_MODE=production`.

## Summary

The pages were slow **despite** Hyvä because a third-party module was emitting a
21 KB inline JavaScript function **once per product price render**. On a
30-product category page that is 60 identical copies — 1.28 MB of the 1.99 MB
response, or 64% of the page.

Fixed by declaring the function once per page from layout. No functional change
to the rewards widget.

## Measured impact

Cache-busted renders with `var/page_cache` purged (see *FPC caveat* below).

| | Before | After | Δ |
|---|---:|---:|---:|
| Homepage — HTML | 549,001 B | 399,281 B | **−27%** |
| Homepage — gzip | 81,033 B | 70,442 B | −13% |
| `/oils` — HTML | 1,988,294 B | 726,278 B | **−63%** |
| `/oils` — gzip | 191,377 B | 70,412 B | **−63%** |
| `/oils` — inline JS | 1,452,633 B | 192,128 B | **−87%** |
| `/oils` — cold TTFB | 2.31–2.82 s | 1.51–1.70 s | ~−40% |

The inline-JS reduction is the one that matters for Lighthouse: that payload was
parsed and executed on the main thread on every page view, blocking TBT/TTI.

## Root cause

`Mirasvit_RewardsCatalog` renders `pricing/adjustment.phtml` for every price on
the page. The Hyvä-compat copy of that template
(`vendor/mirasvit/module-rewards-hyva/.../pricing/adjustment.phtml`) emits both
the widget markup *and* a ~21 KB `initPointsBlock<productId>()` Alpine factory,
inline, on every render.

On `/oils`: 30 products × 2 price renders = **60 copies**, 21,365 B each.

The bodies were verified byte-identical apart from the function name:

- the component reads its own product via `data-product-id` on the element;
- `getPointsRequestUrl()` renders `…/rewards_catalog/product/points/` for every
  product (the product is passed as an object and never reaches the URL).

So a single shared factory is sufficient.

## The fix

Everything lives in one new theme directory — no `vendor/` edits:

```
app/design/frontend/Aureate/hyva/Mirasvit_RewardsCatalog/
├── layout/
│   ├── catalog_category_view.xml
│   ├── catalog_product_view.xml
│   ├── catalogsearch_advanced_result.xml
│   ├── catalogsearch_result_index.xml
│   └── cms_index_index.xml
└── templates/pricing/
    ├── adjustment.phtml      # override: widget markup only, factory removed
    └── points-factory.phtml  # the factory, rendered once per page
```

The override goes under **`Mirasvit_RewardsCatalog/`** (the *original* module),
not `Hyva_MirasvitRewardsCatalog/`. Hyvä's `CompatModuleFallback\Plugin\
ViewFileOverride` injects compat-module dirs into the fallback for the original
module name, and theme dirs are searched ahead of both.

`points-factory.phtml` renders from `before.body.end` — the container that
reliably resolves on this install (same reason `head.js` was moved there; see the
note in `Hyva_Theme/layout/default_hyva.xml`). Alpine boots on `DOMContentLoaded`,
after the script has run, so the global is always defined before any
`x-data="initPointsBlock()"` is evaluated.

### Why it is declared per-handle and not globally

It is declared only on the handles that actually render the widget. Measured
component counts: homepage 8, category 60, product 15, search 55; cart, blog,
compare and CMS pages render none. Declaring it globally added 21 KB (≈4 KB
gzipped) of dead JS to every CMS and checkout page.

### A failed first attempt, recorded deliberately

The first version kept the factory in `adjustment.phtml` and guarded it with a
once-per-request PHP constant. **It silently broke category pages**: 60
`x-data="initPointsBlock()"` references with **zero** definitions, because
something renders an adjustment earlier in the request whose output never reaches
the page, tripping the guard. It was deterministic and survived a full cache
flush; disabling `block_html` changed nothing. The exact early render was not
isolated — declaring the block from layout removes the ordering dependency
entirely, so it was not chased further.

Takeaway: do not gate per-page output on a flag set during block rendering.

## Verification

After `cache:flush` **and** `rm -rf var/page_cache/*`, every page type satisfies
"any page with widgets has exactly one factory":

| Page | widgets | factory |
|---|---:|---:|
| homepage | 8 | 1 |
| `/oils`, `/essential-oils` | 60, 61 | 1 |
| product view | 15 | 1 |
| search results | 55 | 1 |
| about-us, faq, blog, cart, compare | 0 | 0 |

The rendered factory passes `node --check`, and the points endpoint is unchanged
from the baseline (`http://marytailor.local/rewards_catalog/product/points/`).
No new entries in `var/log/exception.log`.

No `setup:static-content:deploy` is required: only `.phtml` and layout XML
changed, neither of which is deployed to `pub/static`.

## FPC caveat — important when measuring

`bin/magento cache:flush` and `cache:clean` **do not purge `var/page_cache/`** on
this install. After the change, `/oils` kept returning the old 1,988,294-byte body
(TTFB 0.26 s = cache hit). Only `rm -rf var/page_cache/*` produced a real
re-render (TTFB 1.70 s).

When measuring any template or layout change here, purge that directory and
compare against a cache-busted URL — a clean URL can silently serve the
pre-change page and make a working fix look like it did nothing.

## Outstanding — not fixed, needs root or a decision

Ordered by expected Lighthouse impact. None of these were changed.

1. **No cache headers on any static asset.** `mod_headers` and `mod_expires` are
   not enabled, but `pub/static/.htaccess` and `pub/media/.htaccess` depend on
   them — so their `Cache-Control: max-age=31536000, immutable` and
   `ExpiresDefault "access plus 1 year"` rules are inert. Every CSS, JS and
   product image is re-fetched on each navigation. Fix: `a2enmod headers expires`.
   `brotli` is also available and not enabled.
2. **OPcache is far too small.** 128 MB / 10,000 files against **76,494** PHP
   files, with `validate_timestamps=On`. It thrashes constantly — the first
   uncached category render measured 10.5 s. Recommended: 512 MB,
   `max_accelerated_files=130987`, `validate_timestamps=0` (with a deploy-time
   reset), `interned_strings_buffer=32`.
3. **Images.** 102 `<img>` on `/oils`, **none** with `loading="lazy"` and only one
   with width/height — directly costing LCP and CLS. Hyvä's own
   `product/list/image.phtml` does emit `loading="lazy"`, so the theme is
   bypassing it. Source media includes 11 MB PNGs; 13,011 JPG/PNG vs 6,644 WebP.
4. **All JS is inlined, none cacheable.** Only one external script on the whole
   page (gtag). ~154 KB of Alpine plugins and Hyvä helpers
   (`alpine-snap-slider.js` 16.7 KB, `hyva.js` 15.7 KB, a 29.7 KB Splide build,
   …) are inlined into every response instead of being served as cacheable files.
5. **`pm.max_children = 5`** on php-fpm, with Apache on **prefork + mod_php**
   loaded alongside `proxy_fcgi`. Event MPM without `mod_php` would be better.
6. **Template hints are enabled** — `dev/debug/template_hints_storefront=1` and
   `template_hints_blocks=1` in `core_config_data`. Currently gated behind
   `template_hints_storefront_show_with_parameter=1`, so pages are not bloated
   today, but this should not be on in production.
7. **Flat catalog is enabled** (product and category). Deprecated in 2.4 and
   generally a pessimisation with MSI.
8. No Redis and no Varnish — cache and sessions are on the filesystem.

Items 1, 2 and 5 need root. Per the working conventions they belong in a numbered
`shell/` script rather than ad-hoc commands.
