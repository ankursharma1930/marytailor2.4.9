# Local environment setup

How this checkout is served at `http://marytailor.local/`, and the two faults
found while bringing it up. This precedes the 2.4.9 upgrade work in
`01-pre-upgrade-assessment.md` and `02-upgrade-plan.md`.

## As-built

| | Value |
|---|---|
| Hostname | `marytailor.local` (`/etc/hosts` → 127.0.0.1) |
| Vhost | `/etc/apache2/sites-available/marytailor.local.conf` |
| DocumentRoot | `/var/www/html/marytailor2` (project root) |
| PHP handler | `php8.3-fpm.sock` |
| Database | `marytylor` — 618 tables |
| MAGE_MODE | `production` |

Two things differ from what the codebase declares. Neither is currently
breaking anything, both are worth knowing:

- **`env.php` sets `directories.document_root_is_pub = true`**, which says
  `pub/` is the docroot — but Apache points at the project root. It works
  because the root `.htaccess` rewrites everything into `pub/`, and files
  outside `pub/` are unreachable as a result (`/info.php` → 404,
  `/app/etc/env.php` → 403, verified). Pointing DocumentRoot at
  `/var/www/html/marytailor2/pub` would remove the extra rewrite hop.
- **Magento 2.4.6 requires PHP `~8.1||~8.2`; this serves on 8.3.** It renders,
  PHP 8.3 carries every required extension, and `var/log/` shows no
  deprecation noise — but it is outside the supported matrix, and Composer
  will object during the upgrade. `php8.1-fpm` is installed and running on
  this box if you need to move back — but note its 8.1 build is missing
  `bcmath`, `gd`, `intl`, `soap` and `zip`, so moving back is not just a
  vhost edit:

  ```bash
  sudo apt-get install php8.1-bcmath php8.1-gd php8.1-intl php8.1-soap php8.1-zip
  ```

## Fault 1 — unstyled storefront, CSS 404s

**Symptom.** Pages rendered, assets 404'd. `styles.css` returned HTTP 404 with
`Content-Type: text/html` and a 243 KB body — Magento's 404 page, not CSS.

**Cause.** `pub/static/.htaccess` was missing. With `dev/static/sign = 1`
Magento emits cache-busted URLs:

```
/static/version1789211733/frontend/Aureate/hyva/en_US/css/styles.css
```

Nothing is at that path on disk. The rule that strips the version segment
lives in `pub/static/.htaccess`:

```apache
RewriteRule ^version.+?/(.+)$ $1 [L]
```

That file ships in the Magento distribution — `setup:static-content:deploy`
does **not** create it, and `pub/static` is not in this repo's git index. So
anything that removes `pub/static` (a disk reclaim, an `rm -rf` before a
redeploy, a fresh checkout) takes it with it, every asset request falls
through to `index.php`, and the storefront loads unstyled.

The give-away is that the file is plainly on disk and the unversioned URL
works:

| URL | Result |
|---|---|
| `/static/frontend/Aureate/hyva/en_US/css/styles.css` | 200, `text/css`, 169745 B |
| `/static/version1789211733/frontend/.../styles.css` | 404, `text/html`, 243170 B |

**Fix.** One copy — the distribution keeps a canonical copy in `vendor/`:

```bash
cp vendor/magento/magento2-base/pub/static/.htaccess pub/static/.htaccess
```

All 15 homepage assets returned 200 afterwards.

**This will recur.** Any future `setup:static-content:deploy` preceded by
deleting `pub/static` drops the file again, with the same unstyled-storefront
symptom. If the storefront loses its CSS, check this first.

## Fault 2 — every storefront link pointed at a dead host

**Symptom.** Page loaded and looked correct; clicking any category or product
went nowhere. 126 of 130 links on the homepage pointed at
`http://local.marytylor.com`, which is not in `/etc/hosts`.

**Cause — two layers.** First, the two URL config keys disagreed:
`web/unsecure/base_url` was `http://marytailor.local/` while
`web/unsecure/base_link_url` was still `http://local.marytylor.com/`.
`base_url` drives asset URLs — which is why CSS worked once fault 1 was
fixed — and `base_link_url` drives every `<a href>`.

Second, and the part that made it look unfixed: **correcting the value in the
database is not enough.** `core_config_data` is cached, so the old hostname
kept rendering after the row was already correct. This is the trap with any
direct SQL or admin edit to config — the write lands, the site ignores it.

**Fix.** Correct `base_link_url`, then:

```bash
php bin/magento cache:flush
```

Verified afterwards: all 130 links render as `marytailor.local`.

A note for next time — Magento's stock value for this key
(`module-store/etc/config.xml`) is the placeholder `{{unsecure_base_url}}`,
not a literal hostname. The literal works, but the placeholder tracks
`base_url` automatically, so a future rename would only need one key changed
instead of two. This site has already been renamed once.

One remaining `https://marytylor.com` reference is hard-coded in CMS content,
not config — that needs editing in the admin.

## Still outstanding

- `pub/static/` is owned `root:root` — `setup:static-content:deploy` was run
  under sudo. It is 0777 so the CLI user can still write, but ownership
  should be repaired so later deploys don't run as root:

  ```bash
  sudo chown -R payless:www-data var generated pub/static pub/media app/etc
  sudo find var generated pub/static -type d -exec chmod 775 {} +
  ```
- `mod_headers` and `mod_expires` are not enabled, so static assets are served
  without the far-future `Cache-Control`/`Expires` headers that
  `pub/static/.htaccess` would otherwise set. Not fatal — the blocks are
  `<IfModule>`-guarded. `sudo a2enmod headers expires`.
- `catalog/search/engine` is unset, so Magento defaults to `elasticsearch7`.
  Elasticsearch is **not running**; OpenSearch 2.19.6 is, on `:9200`, under
  the cluster name `payless-upgrade`. Catalog search and reindexing will fail
  until the engine is pointed at OpenSearch. That is a DB change and belongs
  in its own script.
