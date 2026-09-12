# CLAUDE.md

Guidance for Claude Code when working in this repository.

## What this is

Magento 2 Community Edition storefront for **marytylor.com**, running the
**Hyvä** frontend theme. This checkout is a local copy served at
`local.marytylor.com` (Apache vhost), not the production host — but it carries
**real customer and order data** (10,470 orders, 5,003 customers), so treat the
database as production-sensitive.

## Stack

| | Current | Notes |
|---|---|---|
| Magento | 2.4.6 CE | upgrade to 2.4.9 in progress — see `doc/` |
| PHP | 8.1.29 | 2.4.9 needs 8.3+ |
| Composer | 2.1.4 | 2.4.9 needs ≥2.2 |
| Database | MySQL 8.0.42, schema `marytylor4` | 1.77 GB, 619 tables |
| Search | Elasticsearch 7.17 — **installed but not running** | being replaced by OpenSearch 2.19 |
| Web | Apache 2.4.41 + php-fpm | |
| Theme | Hyvä 1.3.9 + child theme `Aureate/hyva` | |
| MAGE_MODE | `production` | static content must be deployed after template changes |

No Redis, Varnish or RabbitMQ — cache and sessions are on the filesystem.

## Repository layout gotchas

These are unusual and will bite you if you assume a standard Magento repo:

- **`vendor/` is committed to git** — 77,914 files, and there is **no `.gitignore`**.
  `generated/`, `pub/static/`, `var/` and `app/etc/env.php` are all tracked too.
- **Never edit files in `vendor/`.** It has happened here before (two Hyvä
  theme files carry hand edits). Composer destroys them on every update.
  Put overrides in `app/design/frontend/Aureate/hyva/` instead.
- **34 extensions are hand-copied into `app/code`** rather than installed via
  Composer — Magefan, Mageplaza, Snowdog, Rootways, Magebees, Mageside,
  Dolphin, Plumrocket. Composer does not manage or validate these. Their
  `composer.json` version constraints are metadata only and are not enforced.
- The project root contains legacy scratch files (`custom.php`, `homepage.php`,
  `change.php`, `.htaccess11`, `composer (copy).json`, …). They are not part of
  the application; leave them alone unless asked.

## Paid extensions

Hyvä, Mirasvit, and Plumrocket are **licensed** and pulled from private Composer
repositories. Their credentials live in `auth.json` and in repository URLs in
`composer.json`. Availability depends on the subscription being current — as of
2026-09, Plumrocket's licence only serves versions *older* than what is
installed.

## Security debt (known, unfixed)

Do not add to this list, and flag it if asked to touch related files:

- Vendor credentials are committed in plaintext — Mirasvit tokens in
  `composer.json`, account passwords in `marytylor_module_details`, a Hyvä token
  in `Require_things_to_upgrade_marytylor`. **These are in git history and should
  be considered compromised.**
- `app/etc/env.php` uses DB user `root` with password `root`.
- `Magento_TwoFactorAuth` is disabled.
- `info.php` is web-reachable in the docroot.

## The 2.4.9 upgrade

Currently in progress. **Read `doc/` before doing anything upgrade-related.**

| Doc | Contents |
|---|---|
| `doc/01-pre-upgrade-assessment.md` | full environment audit + dependency resolution test results |
| `doc/02-upgrade-plan.md` | staged plan, decisions taken, rollback strategy |

Scripts live in `shell/`, numbered in execution order. Every one is
**idempotent and safe to re-run**:

| Script | Stage | Needs root |
|---|---|---|
| `shell/00-reclaim-disk.sh` | free space (archives junk, never deletes) | no |
| `shell/01-php83-install.sh` | install PHP 8.3 — **non-disruptive** | **yes** |
| `shell/02-opensearch-install.sh` | install OpenSearch 2.19 — **non-disruptive** | **yes** |
| `shell/03-backup.sh` | DB + code + git branch safety net | maybe |
| `shell/04-vhost-switch.sh` | activate PHP 8.3 — **breaks 2.4.6, do not run early** | **yes** |
| `shell/04-vhost-rollback.sh` | return to PHP 8.1 | **yes** |

Each script covers **one concern** and is run and verified individually — do not
combine them. `04-vhost-switch.sh` hard-refuses unless a backup exists.

**Magento 2.4.6 requires PHP `~8.1||~8.2` and cannot run on 8.3.** Installing
8.3 is safe; activating it takes the site down until the 2.4.9 Composer upgrade
completes. Never run `switch` outside a maintenance window.

**Current blocker:** the Plumrocket Checkout Success Page licence. Composer
cannot resolve 2.4.9 until it is renewed — it can only satisfy the dependency by
downgrading `module-checkoutspage` 3.3.0 → 2.2.8.

## Working conventions

- **Any DB-mutating operation goes in a numbered script in `shell/`** — never a
  one-off command. It must be re-runnable.
- **Document each upgrade step in `doc/`** as it is performed.
- Disk is tight (was 89% full). Check `df -h` before anything that writes bulk.
- After template or layout changes in `production` mode:
  `bin/magento setup:static-content:deploy -f && bin/magento cache:flush`
- Do not run `setup:upgrade` casually — it rewrites schema against live data.
  Take a backup first (`shell/03-backup.sh`).

## Useful commands

```bash
php bin/magento --version
php bin/magento setup:db:status          # is the schema current?
php bin/magento module:status
php bin/magento indexer:status
tail -f var/log/exception.log var/log/system.log
```
