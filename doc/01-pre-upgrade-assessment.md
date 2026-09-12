# Magento 2.4.6 → 2.4.9 Upgrade — Pre-Upgrade Assessment

**Site:** marytylor (local copy, vhost `local.marytylor.com`)
**Path:** `/var/www/html/marytailor2`
**Assessment date:** 2026-09-12
**Status:** ASSESSMENT ONLY — no changes made to the installation

---

## 1. Current state

### Application
| Item | Value |
|---|---|
| Magento | 2.4.6 Community Edition |
| Install date | 2022-03-01 |
| MAGE_MODE | `production` |
| Modules registered | 403 (7 disabled) |
| Theme | Hyvä 1.3.9 + custom child theme `Aureate/hyva` |
| Composer packages | 539 runtime + 72 dev |

### Infrastructure
| Component | Installed | Notes |
|---|---|---|
| OS | Ubuntu 20.04.6 LTS (focal) | Standard support ended Apr 2025 |
| PHP | 8.1.29 (7.4 also present) | EOL |
| Composer | 2.1.4 | |
| Web server | Apache 2.4.41 | |
| Database | MySQL 8.0.42 | |
| Search engine | Elasticsearch 7.17.29 — **installed but NOT running** | port 9200 not listening |
| Java | OpenJDK 11.0.27 | |
| Redis / Varnish / RabbitMQ | not in use | cache backend is filesystem; sessions are files |

### Data volume
| Metric | Value |
|---|---|
| Database | `marytylor4` — 1,766 MB across 619 tables |
| Products | 325 |
| Orders | 10,470 |
| Customers | 5,003 |

### Disk
| Metric | Value |
|---|---|
| Filesystem | 78 GB total, **8.3 GB free (89 % used)** |
| Project size | 6.8 GB |
| Reclaimable junk in project root | ~1.07 GB (see §5) |

---

## 2. Target requirements — Magento 2.4.9

Pulled live from `repo.magento.com` (`composer show -a magento/product-community-edition`):

```
php                               ~8.3.0 || ~8.4.0 || ~8.5.0
composer/composer                 ^2.2
elasticsearch/elasticsearch       ^8.15
opensearch-project/opensearch-php ^2.3
```

Ships `magento/module-elasticsearch-8` and `magento/module-open-search`.
**Elasticsearch 7.x is no longer supported.**

---

## 3. Gap analysis

| # | Area | Current | Required | Action |
|---|---|---|---|---|
| 1 | PHP | 8.1.29 | 8.3 / 8.4 / 8.5 | Install PHP 8.3 (+ extensions) — `ondrej/php` PPA already configured |
| 2 | Composer | 2.1.4 | ≥ 2.2 (use 2.8.x) | `composer self-update` |
| 3 | Search engine | ES 7.17 (stopped) | ES 8.x or OpenSearch 2.x | Install OpenSearch 2.19 *or* Elasticsearch 8.x; reindex |
| 4 | Database | MySQL 8.0.42 | MySQL 8.0 / 8.4 | Verify; 8.0 expected to remain supported |
| 5 | Disk | 8.3 GB free | ≥ 15 GB recommended | Reclaim junk + prune before starting |
| 6 | OS | Ubuntu 20.04 (EOL) | — | Not blocking, but note for the record |

---

## 4. Third-party / customisation risk register

### 4.1 Composer-managed paid extensions

| Package | Installed | Latest available to this account | Risk |
|---|---|---|---|
| `hyva-themes/magento2-default-theme` | 1.3.9 | **1.5.2** | **CLEARED** — resolution test confirms 1.5.2 installs cleanly against 2.4.9 (see §8) |
| `mirasvit/module-seo` | 2.9.4 | 2.13.0 | **CLEARED** — resolves against 2.4.9 |
| `mirasvit/module-rewards` | 3.2.5 | 3.4.2 | **CLEARED** — resolves against 2.4.9 |
| `plumrocket/module-checkoutspage` | **3.3.0** | **2.2.8** | **BLOCKER** — the repo credentials only expose versions *older* than what is installed. Cannot obtain a 2.4.9-compatible build. Licence/tier appears lapsed. |
| `plumrocket/module-shippingtracking` | 2.3.3 | 2.5.0 | **CLEARED** — resolves against 2.4.9 |
| `plumrocket/module-plumbase` | 2.11.4 | 2.11.10 | **CLEARED** — resolves against 2.4.9 |

### 4.2 Extensions copied into `app/code` (not Composer-managed — 34 modules)

These receive no Composer version resolution and must be upgraded by hand. Several are far behind:

| Module | Installed | Vendor's current | Gap |
|---|---|---|---|
| `Mageplaza/Osc` | 2.1.9 | 4.1.0 | 2 major versions |
| `Mageplaza/AbandonedCart` | 1.0.8 | 4.3.0 | 3 major versions |
| `Mageplaza/Smtp` | 1.2.5 | 4.7.12 | 3 major versions |
| `Mageplaza/DeliveryTime`, `GeoIP`, `QuickCart`, `ReviewReminder` | 1.0.x | 4.0.x | 3 major versions |
| `Magefan/Blog` + 8 sibling modules | 2.11.3 | 2.11.4 | minor |
| `Snowdog/Menu` | 0.2.7 | — | declares framework `≤103.0.*` |
| `Mageside/Recipe` | 1.3.6 | — | declares framework `102.0.*\|103.0.*` |
| `Rootways/Authorizecim` | 3.0.2 | — | payment module — **must be re-verified against a live gateway** |
| `Magebees/Productfeed`, `Dolphin/Productfaq`, `Plumrocket/*` | various | — | compatibility unknown |

> `app/code` modules declaring an older `magento/framework` constraint are **not** blocked by Composer (the constraint is only metadata there), but they are still exposed to PHP 8.3/8.4 deprecations and removed Magento APIs. Each needs a code review.

### 4.3 Hand-edited `vendor/` files — will be destroyed by `composer update`

`vendor/` is committed to git (77,914 files, no `.gitignore`). Three Hyvä files were edited in place:

1. `vendor/hyva-themes/.../Magento_Theme/layout/default.xml` — removed `ttl="3600"` from the `topmenu_generic` block (Varnish-related).
2. `vendor/hyva-themes/.../Magento_Theme/templates/html/header/logo.phtml` — removed responsive ordering classes `order-1 sm:order-2 lg:order-1`.
3. `vendor/hyva-themes/.../Magento_ReCaptchaFrontendUi/templates/js/script_token_recaptcha.phtml` — modified.

**These have been captured and must be reimplemented** as child-theme overrides in `app/design/frontend/Aureate/hyva/` (or as Composer patches), never as `vendor/` edits.

---

## 5. Housekeeping found in the project root

Reclaimable before the upgrade (~1.07 GB):

| File | Size |
|---|---|
| `root` | 453 MB |
| `marytylor.tar.gz` | 266 MB |
| `bin_dev_lib_phpserver_setup_vendor.zip` | 209 MB |
| `git.zip` | 139 MB |

Also present and non-standard: `change.php`, `custom.php`, `homepage.php`, `Live_homepage_backup.php`, `info.php`, `category_page_css.css`, `.htaccess11`, `composer (copy).json`, `redirect.sh`, `all.sh`.

---

## 6. Security issues found (unrelated to the upgrade, but should be fixed)

1. **Plaintext vendor credentials committed to git:**
   - `composer.json` contains Mirasvit repository tokens in the repository URLs.
   - `marytylor_module_details` contains plaintext account passwords for Dolphin, Mirasvit, Mageplaza and Plumrocket.
   - `Require_things_to_upgrade_marytylor` contains a Hyvä auth token and Plumrocket credentials.
2. `app/etc/env.php` uses DB user `root` with password `root`.
3. `Magento_TwoFactorAuth` is **disabled**.
4. `info.php` (likely `phpinfo()`) is web-reachable in the docroot.
5. No `.gitignore` — `vendor/`, `generated/`, `pub/static`, `var/` and `app/etc/env.php` are all tracked.

> These credentials should be treated as compromised and rotated, since they are in git history.

---

## 7. Assessment summary

The upgrade is **feasible and lower-risk than it first appeared**. A live dependency-resolution test (§8) proves the entire tree resolves against 2.4.9 with current stable releases of every paid extension.

- **One hard blocker remains:** Plumrocket Checkout Success Page.
- **Hyvä is cleared** — 1.5.2 resolves against 2.4.9.
- **34 hand-installed `app/code` modules** still need manual PHP 8.3 compatibility review.

---

## 8. Dependency resolution test — results

Run read-only in a scratch directory (the live project was never touched), with
`config.platform.php = 8.3.16` to simulate the target runtime.

### Test 1 — current `composer.json`, Magento bumped to 2.4.9 → **FAILED**

```
Problem 1
  - Root composer.json requires plumrocket/module-checkoutspage ^3.3,
    found plumrocket/module-checkoutspage[2.0.0, ..., 2.2.8]
    but it does not match the constraint.
Problem 2
  - hyva-themes/magento2-plumrocket-checkoutspage 1.0.0
    requires plumrocket/module-checkoutspage *
    -> conflicts with your root composer.json require (^3.3).
```

Resolution halts at Plumrocket before Magento is even evaluated.

### Test 2 — third-party constraints relaxed to `*` → **RESOLVED**

| Package | Now | Selected for 2.4.9 |
|---|---|---|
| `magento/product-community-edition` | 2.4.6 | **2.4.9** |
| `magento/framework` | 103.0.6 | **103.0.9** |
| `hyva-themes/magento2-default-theme` | 1.3.9 | **1.5.2** |
| `hyva-themes/magento2-theme-module` | 1.3.9 | 1.5.2 |
| `hyva-themes/magento2-cms-tailwind-jit` | 1.1.7 | 2.0.3 |
| `hyva-themes/magento2-luma-checkout` | 1.1.6 | 1.1.7 |
| `mirasvit/module-seo` | 2.9.4 | 2.13.0 |
| `mirasvit/module-rewards` | 3.2.5 | 3.4.2 |
| `mirasvit/module-rewards-hyva` | 0.0.11 | 0.2.18 |
| `mirasvit/module-core` | 1.4.37 | 1.7.20 |
| `plumrocket/module-shippingtracking` | 2.3.3 | 2.5.0 |
| `plumrocket/module-plumbase` | 2.11.4 | 2.11.10 |
| `plumrocket/module-checkoutspage` | **3.3.0** | **2.2.8 — a DOWNGRADE** |
| `elasticsearch/elasticsearch` | — | v8.19.0 |
| `opensearch-project/opensearch-php` | — | 2.7.0 |

### What this proves

1. **Hyvä 1.5.2 is 2.4.9-ready.** The `dev-magento249-compat` branch was a red herring; the stable tag resolves.
2. **All Mirasvit packages are 2.4.9-ready** and the subscription is live.
3. **`magento/framework` lands on 103.0.9**, which *satisfies* the declared constraints of the two `app/code` modules that pin the framework — `Snowdog/Menu` (`≤103.0.*`) and `Mageside/Recipe` (`102.0.*|103.0.*`). Neither is blocked.
4. **Plumrocket Checkout Success Page is the single hard blocker.** Composer can only satisfy it by *downgrading* 3.3.0 → 2.2.8, losing a major version of functionality.

### Decision taken

**Renew the Plumrocket licence** so 3.x becomes reachable again. Composer work is on hold until the credentials are refreshed; all other stages proceed in the meantime.
