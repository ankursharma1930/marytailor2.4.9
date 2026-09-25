# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this is

Magento 2 Community Edition storefront for **marytylor.com**, running the
**Hyvä** frontend theme. This checkout is a local copy served at
`http://marytailor.local/` (Apache vhost, HTTP only), not the production host —
but it carries **real customer and order data** (10,470 orders, 5,004
customers), so treat the database as production-sensitive.

This copy has been upgraded to 2.4.9. Nothing done here reaches
`marytylor.com`, and that includes the security patches.

## Stack

Verified 2026-09-25.

| | Current | Notes |
|---|---|---|
| Magento | 2.4.9 CE | upgraded from 2.4.6 on 2026-09-12/13 — see `doc/03` |
| PHP | 8.3.33 (CLI and `php8.3-fpm`) | 7.1, 8.1 and 8.4 FPM are also installed; only 8.3 is used |
| Composer | 2.2.25 | |
| Database | MySQL 8.0.46, schema `marytylor` | 1.74 GB, 654 tables |
| Search | OpenSearch 2.19.6 on `127.0.0.1:9200` | `catalog/search/engine = opensearch`. Elasticsearch 7.17 is installed but stopped |
| Web | Apache 2.4.52 → `php8.3-fpm.sock` | vhost `/etc/apache2/sites-available/marytailor.local.conf` |
| Theme | Hyvä 1.5.2 + child theme `Aureate/hyva` | child theme builds Tailwind **v3** |
| MAGE_MODE | `production` | static content must be deployed after template changes |

No Redis, Varnish or RabbitMQ — cache and sessions are on the filesystem.

The vhost comment says "PHP 8.4 … Magento 2.4.8". It is wrong; the handler is
8.3. DocumentRoot is the project root, not `pub/`: the root `.htaccess`
rewrites into `pub/`, so files outside `pub/` are not web-reachable.

## Repository layout gotchas

- **`vendor/`, `generated/`, `pub/static/`, `pub/media/`, `var/`,
  `app/etc/env.php` and `auth.json` are git-ignored** (only their `.htaccess`
  placeholders are tracked). `vendor/` is rebuilt from `composer.lock`, which
  must stay tracked. `app/etc/config.php` *is* tracked.
- Branches: `main-249` holds the upgrade; `pre-2.4.9-upgrade` is the 2.4.6
  code. Remote `origin` is the new `marytailor2.4.9` repo. `old-origin` is the
  old repo, where `vendor/` and credentials were committed.
- **Never edit files in `vendor/`.** Composer destroys vendor edits on every
  update, and since `vendor/` is untracked, git no longer records them either.
  Put overrides in `app/design/frontend/Aureate/hyva/`.
- **33 extensions are hand-copied into `app/code`** rather than installed via
  Composer: Magefan (11), Mageplaza (8), Hyva compat (6), Plumrocket (3),
  Dolphin, Magebees, Mageside, Rootways, Snowdog. Composer does not manage or
  validate them, and none has been verified on 2.4.9 / PHP 8.3 yet.
- The project root contains legacy scratch files (`custom.php`, `homepage.php`,
  `change.php`, `Live_homepage_backup.php`, `all.sh`, `redirect.sh`,
  `.htaccess11`, `composer (copy).json`, `updated modules/`, old `MDVA-*.patch`,
  …). They are not part of the application; leave them alone unless asked.

## Frontend (Hyvä 1.5.2) gotchas

Details in `doc/03` (fault 3) and `doc/05`.

- **The Tailwind CSS is purged.** Rebuild it after any Hyvä upgrade or any
  template change that introduces new classes. If you skip the rebuild, nothing
  errors; elements just render unstyled or invisible:
  `cd app/design/frontend/Aureate/hyva/web/tailwind && npm run build`
- The child theme is Tailwind v3, but the 1.5.2 parent's `.btn` and
  `.snap-track` slider are v4 components. Their v3 ports live in the child
  theme's `web/tailwind/components/`.
- **`setup:static-content:deploy -f` does not overwrite existing files.** To
  actually redeploy the theme:
  ```bash
  rm -rf var/view_preprocessed/* pub/static/frontend/Aureate/hyva
  php bin/magento setup:static-content:deploy -f --theme Aureate/hyva en_US
  ```
- **Never delete `pub/static/.htaccess`.** No deploy regenerates it, and
  without it every asset 404s and the storefront loads unstyled. Restore it
  from `vendor/magento/magento2-base/pub/static/.htaccess`.
- **`cache:flush` does not purge `var/page_cache/`.** Use
  `rm -rf var/cache/* var/page_cache/*`. Deleting `var/cache/*` this way also
  clears the mixed `payless`/`www-data` ownership that 500s the site.
- **`head.additional` does not resolve on this install.** Anything attached to
  it silently disappears. Attach scripts to `before.body.end` instead, as
  `Hyva_Theme/layout/default_hyva.xml` does. Open Graph and Twitter meta tags
  are still missing because of this.
- Override Hyvä compat-module templates under the **original** module name
  (e.g. `Mirasvit_RewardsCatalog/`). `Hyva_*` directories in the theme are
  ignored.
- Run the CLI as `payless`, never with `sudo`. php-fpm runs as `www-data`, and
  root-owned files in `var/` or `generated/` break later runs.

## Paid extensions

Hyvä, Mirasvit and Plumrocket are **licensed** and pulled from private Composer
repositories. Credentials live in `auth.json` (untracked) and in the Mirasvit
repository URLs in `composer.json` (tracked).

**Plumrocket Checkout Success Page (`module-checkoutspage`) was dropped** during
the upgrade instead of having its licence renewed. It is gone from
`composer.json`, `composer.lock` and `config.php`, but its `setup_module` row
and tables remain in the DB. The removal is not recorded anywhere in `doc/`.

## Security incident — checkout skimmer (2026-09-25)

`design/head/includes` (store 1) held a Magecart WebSocket loader targeting
`/securecheckout` (the checkout route). It was **removed from this copy** with
`shell/10-remove-checkout-skimmer.php`, and evidence is in
`var/backups/skimmer-evidence-*`. **Production has not been checked and should
be assumed infected.** Read `doc/07-checkout-skimmer.md` before touching head
scripts, analytics or checkout config.

## Security debt (known, unfixed)

Do not add to this list, and flag it if asked to touch related files:

- Vendor credentials in plaintext:
  - Mirasvit tokens in `composer.json` (still tracked)
  - account passwords in `marytylor_module_details`
  - a Hyvä token in `Require_things_to_upgrade_marytylor`

  The last two are now git-ignored, but all three are in the `old-origin`
  history and **should be considered compromised**.
- The `old-origin` remote URL in `.git/config` embeds a GitHub personal access
  token. It is not in git history, but it sits in plaintext on disk. It should
  be revoked and removed from the URL.
- `app/etc/env.php` uses DB user `root` with password `root`.
- `Magento_TwoFactorAuth` is disabled.
- `dev/debug/template_hints_storefront = 1` (only gated behind a URL parameter).
- **Production is not patched for CVE-2026-75650** (unauthenticated RCE,
  exploited in the wild), and the APSB26-146 credential rotation is still
  outstanding. See `doc/04`.

## The 2.4.9 upgrade

**Done on this copy**: `setup:db:status` is clean, and storefront and admin
return 200. **Read `doc/` before doing anything upgrade-related.**

| Doc | Contents |
|---|---|
| `doc/00-local-environment-setup.md` | how `marytailor.local` is served; the `pub/static/.htaccess` and base-URL faults |
| `doc/01-pre-upgrade-assessment.md` | environment audit + dependency resolution tests |
| `doc/02-upgrade-plan.md` | staged plan, decisions, rollback strategy |
| `doc/03-upgrade-execution-log.md` | what actually happened: the FedEx NULL, CSP and Hyvä 1.5.2 regressions |
| `doc/04-security-patches.md` | Adobe security patches for 2.4.9 and what is outstanding |
| `doc/05-frontend-performance.md` | Mirasvit inline-JS fix, plus the list of unfixed performance items |
| `doc/06-homepage-performance.md` | homepage Lighthouse work: hero CLS, WebP, LCP, deferred menus, `NO_LCP` snap bug |
| `doc/07-checkout-skimmer.md` | the injected checkout skimmer: findings, evidence, production steps |

Scripts live in `shell/`. Each covers **one concern**, is idempotent, and is
run and verified individually. Do not combine them:

| Script | Purpose |
|---|---|
| `shell/db-backup.sh` | DB-only dump (default target `<root>/backup`, deny-all `.htaccess`) |
| `shell/03-backup.sh` | full DB + code backup to `/var/backups/marytylor-2.4.9/` |
| `shell/06-composer-upgrade.sh` | Composer 2.4.6 → 2.4.9; leaves maintenance mode on |
| `shell/07-magento-upgrade.sh` | `setup:upgrade`, schema and search; turns maintenance mode off |
| `shell/08-security-patches.sh` | Adobe isolated patches + rebuild (`--check`, `--revert`) |
| `shell/fix-null-config-for-data-patches.php` | NULL `carriers/fedex/free_method` → `''` so the FedEx data patch runs (`--apply`) |
| `shell/09-homepage-images.php` | responsive WebP homepage images into the child theme (`--check`, `--force`) |
| `shell/10-remove-checkout-skimmer.php` | find the skimmer (exit 1 = found) / `--apply`: evidence, strip, clear caches, verify |
| `shell/11-product-images-webp.php` | WebP copies of cached product images + the `pub/media/.htaccess` rule that serves them (`--check`, `--prune`) |

**There is no database backup.** None was taken before the schema upgrade, and
none exists on disk now. Run `bash shell/db-backup.sh` before any schema or
data work.

**Composer silently removes the security patches.** Adobe ships 2.4.9 security
fixes as patch files, not Composer versions. All four are applied (July, August
and September monthly files, plus the VULN-39341 hotfix). After any
`composer install` or `update`, run `bash shell/08-security-patches.sh --check`;
exit 0 means patched. Never apply QPT `MCLOUD-15066` / `MCLOUD-15306`; they
duplicate the July and August files. Composer also overwrites
`pub/media/.htaccess`, which removes the WebP rule, so run
`php shell/11-product-images-webp.php --check` as well.

**Still outstanding:**
- The `app/code` modules are unverified on 2.4.9, `Rootways_Authorizecim`
  (payment gateway) above all. Checkout has not been exercised.
- Performance items that need root or a decision (`doc/05`):
  - `mod_headers` and `mod_expires` are off, so static assets are sent without
    cache headers
  - OPcache is 128 MB with a 10,000-file limit against about 76k PHP files
  - `pm.max_children = 5`
  - the flat catalog is enabled
- Lighthouse performance baseline (local, 2026-09-25, mobile/desktop): home
  45/72, category 65/99, product 65/100. The homepage's weak spots are a CLS of
  1.07 on desktop from `.generic-product-slider` and a 13.7 s LCP on mobile.

## Working conventions

- **Any DB-mutating operation goes in a script in `shell/`**, never a one-off
  command. It must be re-runnable.
- **Document each step in `doc/`** as it is performed.
- Config edits made directly in the DB do not show until
  `php bin/magento cache:flush`.
- Do not run `setup:upgrade` casually. It rewrites schema against live data,
  so back up first (`shell/db-backup.sh`).
- Disk: 128 GB free (55% used) as of 2026-09-25. Check `df -h` before bulk
  writes anyway.

## Useful commands

```bash
php bin/magento --version
php bin/magento setup:db:status              # is the schema current?
php bin/magento module:status
php bin/magento indexer:status
php bin/magento config:show catalog/search/engine
bash shell/08-security-patches.sh --check    # exit 0 = all patches applied
curl -s localhost:9200                       # OpenSearch up?
tail -f var/log/exception.log var/log/system.log
```
