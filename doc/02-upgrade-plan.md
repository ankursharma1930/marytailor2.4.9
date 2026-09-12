# Magento 2.4.6 → 2.4.9 — Upgrade Plan

**Companion to:** `doc/01-pre-upgrade-assessment.md`
**Created:** 2026-09-12

## Decisions taken

| Question | Decision |
|---|---|
| Plumrocket Checkout Success Page (hard blocker) | **Renew the licence** so 3.x is reachable again |
| PHP / OpenSearch install (needs root) | **Claude writes the scripts, the site owner runs them** |
| Search engine replacing Elasticsearch 7.17 | **OpenSearch 2.19** |

**Target PHP: 8.3.** 2.4.9 also accepts 8.4/8.5, but 8.3 is the conservative pick given 34 hand-installed `app/code` modules of unknown compatibility.

---

## Current blocker

> **Composer work is ON HOLD until the Plumrocket licence is renewed.**
> Until then `composer update` can only satisfy `module-checkoutspage` by downgrading 3.3.0 → 2.2.8.
> Stages 0–2 and 6 are independent of this and proceed now.

---

## Critical ordering constraint (discovered 2026-09-12)

**Magento 2.4.6 requires PHP `~8.1.0||~8.2.0`. It cannot run on PHP 8.3.**

So *activating* PHP 8.3 breaks the running 2.4.6 site, and it stays broken
until the Composer upgrade to 2.4.9 finishes — which is itself blocked on the
Plumrocket licence. Activating 8.3 now would leave the site down indefinitely.

The platform work is therefore split into **separate, single-purpose scripts**
rather than one combined run — so a failure in one concern does not cascade
into the others, and each can be verified before the next begins:

| Script | Concern | Disruptive |
|---|---|---|
| `01-php83-install.sh` | install PHP 8.3 only — 8.1 stays active | no |
| `02-opensearch-install.sh` | install OpenSearch only | no |
| `03-backup.sh` | safety net | no |
| `04-vhost-switch.sh` | **activate PHP 8.3** | **yes** |
| `04-vhost-rollback.sh` | undo the activation | no |

---

## Stage map

| Step | What | Command | Blocked by | Disruptive |
|---|---|---|---|---|
| 0 | Reclaim disk | `bash shell/00-reclaim-disk.sh` | — | no |
| 1 | Install PHP 8.3 | `sudo bash shell/01-php83-install.sh` | — | no |
| 2 | Install OpenSearch 2.19 | `sudo bash shell/02-opensearch-install.sh` | — | no |
| 3 | Backup DB + code + git | `bash shell/03-backup.sh` | — | no |
| 4 | **Activate PHP 8.3** | `sudo bash shell/04-vhost-switch.sh` | **Plumrocket licence** | **yes** |
| 5 | Preserve `vendor/` customisations | `shell/05-preserve-customisations.sh` | — | no |
| 6 | Composer upgrade to 2.4.9 | `shell/06-composer-upgrade.sh` | **Plumrocket licence** | yes |
| 7 | Schema + data upgrade | `shell/07-magento-upgrade.sh` | step 6 | yes |
| 8 | Repo hygiene | `shell/08-repo-hygiene.sh` | — | no |
| 9 | Verification | `doc/09-verification-checklist.md` | step 7 | — |

**Steps 0–3 are safe to run right now** and leave the 2.4.6 site serving
normally. Everything from step 4 onward happens in one maintenance window once
the licence is sorted, because the site is down for the whole of 4 → 7.

Run each script on its own and check its output before starting the next.
`04-vhost-switch.sh` refuses to run unless step 3 has produced a backup.

## Stage 0 — Reclaim disk

**Why first:** 8.3 GB free is not enough for a 6.8 GB project plus a 1.8 GB DB dump plus a fresh `vendor/`.

Moves ~1.07 GB of junk archives out of the project. They are **moved to `/var/backups/marytylor-junk/`, not deleted**, so nothing is lost.

| File | Size |
|---|---|
| `root` | 453 MB |
| `marytylor.tar.gz` | 266 MB |
| `bin_dev_lib_phpserver_setup_vendor.zip` | 209 MB |
| `git.zip` | 139 MB |

Also clears regenerable caches: `var/cache`, `var/page_cache`, `var/view_preprocessed`, `generated/*`.

---

## Step 1 — Install PHP 8.3 (non-disruptive)

`sudo bash shell/01-php83-install.sh`

1. Adds the `ondrej/php` PPA if absent, installs PHP 8.3 + 17 packages
2. Verifies **every** extension 2.4.9 requires, one by one, and fails loudly listing any gap
3. Writes `99-magento.ini` to **PHP 8.3 only** — `/etc/php/8.1` is never touched
4. Starts `php8.3-fpm` so its config is validated now, but Apache is not pointed at it

Refuses to run below 10 GB free. Does not touch `update-alternatives` or Apache.

## Step 2 — Install OpenSearch 2.19 (non-disruptive)

`sudo bash shell/02-opensearch-install.sh`

1. Stops and disables Elasticsearch 7.17 — **never removes it**, so rollback works
2. Adds the OpenSearch 2.x apt repo with a keyring-scoped signing key
3. Installs 2.19.1, single-node, bound to `127.0.0.1:9200`, security plugin off
4. Heap sized to ¼ RAM, clamped 1–4 GB (OpenSearch ships its own JDK)
5. Waits for the port, then asserts cluster health is green or yellow

Costs nothing operationally: Elasticsearch was already stopped and Magento has
no `catalog/search/engine` row, so catalogue search is already dead here.
**Magento is not reconfigured** — that waits until after the 2.4.9 upgrade.

## Step 4 — Activate PHP 8.3 (DISRUPTIVE)

`sudo bash shell/04-vhost-switch.sh`

Flips `update-alternatives` and Apache to PHP 8.3, then stops `php8.1-fpm`
— in that order, so Apache is never left pointing at a dead handler.

Guards before it will run:
- PHP 8.3 installed and `php8.3-fpm` healthy
- **a backup exists in `/var/backups/marytylor-2.4.9/`** — hard refusal otherwise
- `apache2ctl configtest` passes *before* the reload; aborts without touching the running server if not
- operator types `switch` to confirm

**From here the storefront is down until step 7 completes.**
Undo with `sudo bash shell/04-vhost-rollback.sh`.

---

## Stage 2 — Backup (the point of no return)

Nothing in stage 4+ proceeds without this.

- `mysqldump --single-transaction --routines --triggers` of `marytylor4`, gzipped
- `app/etc/env.php` + `app/etc/config.php` copied aside
- git branch `pre-2.4.9-upgrade` tagging the exact pre-upgrade tree
- `composer.json` / `composer.lock` snapshot
- Written to `/var/backups/marytylor-2.4.9/` — **outside the project**, so a bad `rm` in the docroot can't take the backup with it

---

## Stage 3 — Preserve `vendor/` customisations

Three Hyvä files were edited directly in `vendor/`. `composer update` destroys them. Each is reimplemented in the child theme `app/design/frontend/Aureate/hyva/`, which survives upgrades:

| Original `vendor/` file | Change | New home |
|---|---|---|
| `Magento_Theme/layout/default.xml` | removed `ttl="3600"` on `topmenu_generic` | child-theme layout override |
| `Magento_Theme/templates/html/header/logo.phtml` | dropped `order-1 sm:order-2 lg:order-1` | child-theme template override |
| `Magento_ReCaptchaFrontendUi/.../script_token_recaptcha.phtml` | modified | child-theme template override |

Diffs are archived to `doc/artifacts/vendor-customisations.patch` before anything is overwritten.

---

## Stage 4 — Composer upgrade *(blocked on Plumrocket)*

```bash
composer require magento/product-community-edition 2.4.9 --no-update
# third-party moved in lockstep, per the resolution test in assessment §8
composer update --with-all-dependencies
```

Expected target versions are pinned in `doc/01-pre-upgrade-assessment.md` §8. Run `--dry-run` first, every time.

---

## Stage 5 — Schema and data upgrade

All DB-touching work lives in `shell/05-magento-upgrade.sh`, written to be **re-runnable**:

```
bin/magento maintenance:enable
bin/magento setup:upgrade --keep-generated
bin/magento setup:db:status
bin/magento setup:di:compile
bin/magento setup:static-content:deploy -f <locales>
bin/magento indexer:reindex
bin/magento cache:flush
bin/magento maintenance:disable
```

Plus the search-engine cutover, which **must** happen before reindexing:

```
bin/magento config:set catalog/search/engine opensearch
bin/magento config:set catalog/search/opensearch_server_hostname 127.0.0.1
bin/magento config:set catalog/search/opensearch_server_port 9200
```

> Today the site has **no `catalog/search/engine` row at all** — it has been running on the 2.4.6 default (`elasticsearch7`) against a stopped server. This must be set explicitly.

---

## Stage 6 — Repo hygiene

`vendor/` is tracked across 77,914 files with no `.gitignore`, and `app/etc/env.php` (containing DB credentials) is committed.

1. Add a proper Magento `.gitignore`
2. `git rm -r --cached vendor/ generated/ pub/static/ var/`
3. Untrack `app/etc/env.php`, keep `env.php.sample`
4. Rotate the credentials exposed in git history (assessment §6)

> Untracking does **not** purge git history. The committed secrets stay retrievable in old commits, which is exactly why rotation is required rather than optional.

---

## Stage 7 — Verification

Full checklist in `doc/07-verification-checklist.md`. Minimum bar before calling it done:

- Storefront renders; Hyvä theme intact; logo and top menu correct
- Catalogue search returns results from OpenSearch
- **Full checkout to order placement**
- `Rootways/Authorizecim` — payment against the gateway sandbox
- Admin login with 2FA, order grid, product save
- `bin/magento setup:db:status` reports "All modules are up to date"
- No new entries in `var/log/exception.log`

---

## Rollback

| Failure point | Recovery |
|---|---|
| Stage 1 | rollback block printed by the script |
| Stage 4 | `git checkout pre-2.4.9-upgrade` + `composer install` |
| Stage 5 | restore DB from stage 2, then the stage 4 rollback |

Because stage 5 rewrites the schema, **a stage-5 failure always means a DB restore** — code rollback alone is not enough.

---

## Known remaining risk

The 34 `app/code` modules are the least predictable part. Composer never validates them, so they fail at runtime rather than at install time. `magento/framework` landing on 103.0.9 satisfies the two that pin a version (`Snowdog/Menu`, `Mageside/Recipe`), but none are verified against **PHP 8.3**.

Highest concern: **`Rootways/Authorizecim`** — a payment module, where a silent failure costs real money. It gets explicit gateway testing in stage 7.
