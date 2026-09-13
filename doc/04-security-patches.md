# 2.4.9 security patches

Applied 2026-09-13 with `shell/08-security-patches.sh`. Patch files are kept in
`patches/adobe/2.4.9/` and pinned by SHA-256 in the script.

**Outcome:**
- All four Adobe security patches for 2.4.9 are applied.
- Adobe's own `vendor/bin/patch-status` reports 23 CVEs `PROTECTED`, 7
  `NOT_APPLICABLE`, and none missing or unknown.
- Storefront and admin both return HTTP 200.

## What Adobe has released for 2.4.9

**There is no `2.4.9-p1`.** As of 2026-09-13 repo.magento.com lists `2.4.9` as
the newest `magento/product-community-edition`. Since July 2026 Adobe ships
security fixes as monthly **isolated patch files** with no Composer package, plus
out-of-band hotfixes.

**Downloading 2.4.9 later does not include the fixes.** Composer versions never
change once published, so a 2.4.9 installed in September is the same code
released on 2026-05-12. The bulletins' "2.4.9-2026-aug" means 2.4.9 with the
July and August files applied. It is not a separate version you can download.
This was confirmed on disk: before patching, none of the four patches would
reverse-apply and all four applied forward cleanly, so none of the fixes were
present.

| Patch file | Bulletin | Released | Files | Notes |
|---|---|---|---|---|
| `249-2026-07-001-CE.patch` | APSB26-73 | 2026-07-14 | 32 | creates `vendor/bin/patch-status` |
| `249-2026-08-001-CE.patch` | APSB26-92 | 2026-08-11 | 10 | requires July |
| `249-2026-09-001-CE.patch` | APSB26-138 | 2026-09-08 | 8 | requires July and August |
| `VULN-39341_249.patch` | APSB26-146 | 2026-09-07 | 9 | **CVE-2026-75650: critical, unauthenticated RCE, exploited in the wild.** Not in the September file. |

Source zips, downloaded from `https://repo.magento.com/patch/`:

```
650e8e34e925653a1b3980ce053b9cb993dc2b22e72ee80b5557e03f36848984  2-4-9-jul-2026.zip
0f300d043b5f7f9558d2d1ffe13b91ec84b5766549adbaec1fc73278ff72b0e1  2-4-9-aug-2026.zip
9fe17a9681d94deaaeb2ca9245e1e1bcd9fad9b3478fbcaaa8ab1a94ee4cd686  2-4-9-sep-2026.zip
5f98d409e627c0ac77fb9cbff4bf139c17ad69550dc06411a4b4d0aeadd669e1  VULN-39341-composer-patches.zip
```

Only the CE files were taken. The zips also carry EE and B2B files, and neither
`magento2-ee-base` nor `magento2-b2b-base` is installed.

## Relationship to the Quality Patches Tool

`magento/quality-patches` 1.1.82 and `magento/magento-cloud-patches` 1.1.18 are
installed, and both are the latest release.

- **QPT `MCLOUD-15066` and `MCLOUD-15306` are byte-identical to the July and
  August files.** They are not additional patches. Adobe warns that applying a
  fix a second time breaks installs, so **never `magento-patches apply` them**.
- **After patching, QPT shows `MCLOUD-15306` as Applied but `MCLOUD-15066` as
  `N/A`.** That is expected. August rewrites `vendor/bin/patch-status`, so QPT
  can no longer recognise July's version of that file. `patch-status` and
  `shell/08-security-patches.sh --check` both confirm July is in.
- The September file and the VULN-39341 hotfix are not in any QPT release yet.
- **`magento-patches status` shows garbage for MCLOUD-15306.** Its "Affected
  components" field contains PHP code fragments. This looked like tampering and
  was checked: both packages in `vendor/` are byte-identical to the Composer
  download zips, and those zips match the SHA-1 in `composer.lock`. The garbage
  comes from QPT's component parser reading the PHP source inside the diff for
  `patch-status`, which is a minified one-file tool.

**The other seven "Not applied" QPT entries were deliberately left alone.** They
are optional quality fixes, not security fixes, and Adobe's guidance is to apply
one only when you have the problem it fixes. Two are worth checking against this
store:

| ID | Fixes |
|---|---|
| ACP2E-4682 | storefront pages that check `quote.is_active` create an empty quote record on every load |
| ACP2E-4875 | viewing a customer with a large address book can log the admin out |

The rest (ACP2E-5132, MDVA-30106, MDVA-12304, ACP2E-4808, ACP2E-4786) cover
Symfony L2 cache, S3 remote storage, a cookie limit, weight-unit display and a
2020-era checkout JS error. Apply one with
`php vendor/bin/magento-patches apply <ID>`, then recompile.

## How the script works

- **Tooling.** It applies patches with `git apply`, which uses no fuzz and applies
  each patch file atomically. It refuses to run unless the installed version is
  2.4.9, the install is not EE, and every file matches its pinned SHA-256.
- **Detecting applied patches.** A patch counts as applied if it reverse-applies
  cleanly. July is checked **without `vendor/bin/patch-status`**, because August
  rewrites that file.
- **Test first.** Every pending patch is applied, in order, to a scratch copy of
  the files involved. The live tree is only changed once that passes.
- **Snapshot.** Before changing anything it archives the affected files to
  `var/backups/security-patches-<stamp>.tar.gz`, and keeps a copy of
  `app/etc/config.php`.
- **Executable bit.** This repo sets `core.fileMode=false`, so `git apply` ignores
  the `new file mode 100755` that July declares for `vendor/bin/patch-status`.
  The script sets the executable bit itself.
- **Module order.** It re-sorts the module list in `app/etc/config.php`, as
  covered in the next section.
- **Rebuild, under maintenance mode:**
  - `setup:di:compile`: three patches change constructors, and July adds a plugin
    in `module-catalog-url-rewrite-graph-ql/etc/di.xml`.
  - Deployed copies of patched web assets are deleted, redeployed and compared
    byte-for-byte with the patched source: `underscore.js` in all five themes,
    admin `Magento_Sales/order/create/scripts.js`, and `js-translation.json`.
    They must be deleted first because `static-content:deploy -f` does not
    replace deployed files (see `03`, fault 3e).
  - `cache:flush`, then `var/cache/*` is cleared so php-fpm can recreate the tag
    files (see `03`, fault 3f).
- **Resuming.** A marker file `var/.security-patches-rebuild-pending` stays in
  place until verification passes. It records whether this script turned
  maintenance mode on, so a re-run after a failure also turns it back off.
- **`--check`** changes nothing. Exit code 0 means all applied and the rebuild
  finished, 2 means work is left, 1 means a conflict.
- **`--revert`** removes the patches newest first, then rebuilds.

## Module order in `config.php`

The August patch adds `<sequence><module name="Magento_MediaGalleryUiApi"/>` to
`Magento_Cms`. After that, `module:config:status` reports config.php as
*outdated* and says to run `setup:upgrade`. The comparison is strict and
includes order.

**`setup:upgrade` was not used.** It also runs schema and data patches against
live data, `CLAUDE.md` requires a backup first, and no DB backup exists. The
script performs only its first step, `Installer::createModulesConfig`: it
rewrites the `modules` list in Magento's computed order. Unlike `setup:upgrade`,
it refuses to run if that would enable a module not already listed.

Checked after the rewrite:
- the same 442 modules, each still on or off as before, with the same 5
  disabled;
- no key outside `modules` changed;
- 20 positions moved: `Magento_MediaGalleryUiApi` went from 222 to 13, and the
  only other module that now loads on the other side of `Magento_Cms` is
  `Magento_Security`;
- MediaGalleryUiApi ships only an `acl.xml`, so the merged configuration is
  otherwise unchanged.

Previous copy: `var/backups/security-patches-20260913-063918.config.php`.

## Checked, no action needed

- **nginx rule for `/media/customer_address/`.** July adds a `deny all` to
  `nginx.conf.sample`. This host runs Apache, which ignores that file.
  `pub/media/customer_address/.htaccess` is byte-identical to the one Magento
  ships (`Require all denied`), and the vhost has `AllowOverride All`.
- **Admin permissions for image management.** August changes the WYSIWYG image
  controllers (upload, delete, new folder, insert) to require
  `Magento_MediaGalleryUiApi::*` instead of the Cms permission. Administrators
  has full access, and both "blog + recipes" (role 88) and "Sales API" (role
  113) already grant all six resources, so nobody loses access. Check again when
  adding roles.
- **July removes `customer_address`** from the customer module's allowed media
  storage resources. This is intended hardening.
- **Root-owned `generated/`** (16,849 files) does not block `setup:di:compile`.
  Every root-owned directory is mode 0777 with no sticky bit.
- **`vendor/bin/patch-status`**, Adobe's version tool, contacts
  `repo.magento.com/patch/patch-registry.json` using the credentials in
  `auth.json`. If none are found it prompts on stdin. Every patch run it makes
  against the live tree is `--dry-run`, and real applies happen only in a temp
  copy. The script does not call it. To run it by hand:
  `php vendor/bin/patch-status < /dev/null`.

## Run results

**First run (06:30 IST) — patches applied, then stopped at verification.** All
four patches applied, the DI compile finished, 11 deployed assets were
redeployed and matched their source, and caches were cleared. Then
`module:config:status` returned non-zero (the module order issue above),
`pipefail` stopped the script, and **maintenance mode was left on**. The rebuild
marker had already been deleted, so a plain re-run would have skipped the
rebuild. Both gaps are fixed in the script: the marker now stays until
verification passes and records maintenance ownership. Maintenance was turned
off by hand in the meantime, and the storefront and admin returned 200.

One exception was logged during the compile:
`ReflectionException: Class "Magento\Framework\App\Http\Interceptor" does not
exist` at 06:31:02 IST. A request arrived while `generated/` was empty. Magento
creates the application object before it checks maintenance mode, so this is
expected during a compile, and there has been no recurrence.

**Second run (06:39 IST) — clean, exit 0.** It set the executable bit on
`vendor/bin/patch-status`, re-sorted `config.php`, recompiled, redeployed and
verified:

| Check | Result |
|---|---|
| `shell/08-security-patches.sh --check` | exit 0 — 4/4 applied, module order current |
| `vendor/bin/patch-status` | 4 applied, 0 missing, 0 unknown; 23 CVEs `PROTECTED`, 7 `NOT_APPLICABLE` |
| `module:config:status` | up to date |
| Deployed `underscore.js` ×5, `scripts.js` | byte-identical to patched source |
| Storefront / admin | HTTP 200 / 200 |
| `var/log/exception.log` | no new entries during the second run |
| Maintenance mode | off |

**Files changed:**
- `vendor/` (untracked): 52 files modified and 3 created.
- Tracked in git: `lib/web/underscore.js`, `pub/errors/processor.php`,
  `nginx.conf.sample`, and `app/etc/config.php` (module order only).
- Added: `patches/adobe/2.4.9/`, `shell/08-security-patches.sh`, this document.

Snapshots:
- `var/backups/security-patches-20260913-063021.tar.gz`, with its `.files` list
  and `.config.php` copy
- `var/backups/security-patches-20260913-063918.config.php`

## Re-apply after every Composer operation

`vendor/` is untracked, and `composer install` / `composer update` restore stock
files. They do the same to `lib/web/underscore.js`, `pub/errors/processor.php`
and `nginx.conf.sample`, which are re-copied from `magento2-base`. **The patches
disappear without warning.** Run `bash shell/08-security-patches.sh --check`
after any Composer operation or deploy. A non-zero exit means re-run the script
without `--check`.

## Outstanding — needs a person

- **Production.** CVE-2026-75650 is an unauthenticated RCE that was exploited
  before the fix, and it affects 2.4.4 through 2.4.9. Patching this local copy
  does nothing for `marytylor.com`. The VULN-39341 zip only has files for
  2.4.4-p18, 2.4.5-p17, 2.4.6-p15, 2.4.7-p10, 2.4.8-p5 and 2.4.9. Production
  running plain 2.4.6 would first need to reach 2.4.6-p15, or finish this
  upgrade.
- **APSB26-146 credential rotation**, on any internet-facing copy:
  - encryption key
  - all admin passwords
  - REST/SOAP/GraphQL integration tokens
  - OAuth secrets
  - payment gateway credentials (Authorize.net CIM)
  - shipping, tax and extension API keys
  - database and SSH credentials

  This overlaps the security debt already in `CLAUDE.md`: `root`/`root` DB
  credentials, and vendor tokens in git history.
- **No DB backup exists.** Nothing here touched the database, but
  `setup:upgrade` will be needed eventually and must not run without one:
  `bash shell/db-backup.sh`.
