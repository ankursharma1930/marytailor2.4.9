<?php
/**
 * FIX: NULL values in core_config_data that abort 2.4.9 data patches.
 *
 *   php shell/fix-null-config-for-data-patches.php            # show what it would do
 *   php shell/fix-null-config-for-data-patches.php --apply    # write the change
 *
 * WHY THIS EXISTS
 *   setup:upgrade to 2.4.9 dies in Magento_Fedex:
 *
 *     vendor/magento/module-fedex/Setup/Patch/Data/UpdateFedexInternationalPriority.php:50
 *     explode(): Argument #2 ($string) must be of type string, null given
 *
 *   The patch rewrites the INTERNATIONAL_PRIORITY method code to
 *   FEDEX_INTERNATIONAL_PRIORITY. It reads every core_config_data row for
 *   carriers/fedex/allowed_methods and carriers/fedex/free_method, then calls
 *   explode(',', $row['value']) with no NULL guard. This database has
 *   carriers/fedex/free_method stored as NULL, and the patch file declares
 *   strict_types=1 -- so NULL is not coerced to '' and PHP raises a
 *   TypeError instead. It is an upstream bug: the column is nullable, the
 *   patch assumes it is not.
 *
 *   Setting the value to '' is behaviour-preserving: both NULL and '' mean
 *   "no free method configured". DELETING the row would NOT be equivalent --
 *   module-fedex/etc/config.xml defines a default of FEDEX_GROUND, so the row
 *   falling back to the default would silently switch on FedEx Ground as a
 *   free shipping method on a live store.
 *
 * SCOPE
 *   Only the paths the blocked patch actually reads (PATHS below). This
 *   database holds 471 NULL-valued config rows; all but this one are
 *   harmless, and blanket-converting NULL to '' across a live config table
 *   risks changing behaviour wherever code distinguishes the two. The other
 *   two 2.4.9 patches that explode() near core_config_data were checked:
 *   module-config RemoveTinymceConfig does not explode the value, and
 *   module-paypal UpdateBmltoPayLater explodes the config *path*, which is
 *   NOT NULL. Neither can fail this way.
 *
 * SAFETY
 *   Touches core_config_data only, and only the listed paths. Writes
 *   replayable rollback SQL to var/backups/ before changing anything.
 *   Values are saved through Magento's config writer, so the write lands in
 *   the right scope and the config cache is invalidated with it.
 *
 * Idempotent: a second run reports nothing to do.
 *
 * AFTER RUNNING, resume the upgrade:
 *   php bin/magento setup:upgrade
 */

declare(strict_types=1);

use Magento\Framework\App\Bootstrap;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\App\ResourceConnection;

require __DIR__ . '/../app/bootstrap.php';

/**
 * Config paths read by UpdateFedexInternationalPriority::apply().
 * Extend this list if a later patch aborts on the same NULL/explode bug.
 */
const PATHS = [
    'carriers/fedex/allowed_methods',
    'carriers/fedex/free_method',
];

$apply = in_array('--apply', $argv, true);

function log_step(string $m): void { printf("\n\033[1;34m==> %s\033[0m\n", $m); }
function log_ok(string $m): void   { printf("    \033[0;32m[ok]\033[0m %s\n", $m); }
function log_warn(string $m): void { printf("    \033[0;33m[warn]\033[0m %s\n", $m); }
function fail(string $m): never    { printf("\n\033[0;31m[FATAL]\033[0m %s\n", $m); exit(1); }

// ------------------------------------------------------------------ bootstrap
log_step('Bootstrapping Magento');

try {
    $om = Bootstrap::create(BP, $_SERVER)->getObjectManager();
} catch (Throwable $e) {
    fail('could not bootstrap: ' . $e->getMessage());
}

/** @var ResourceConnection $resource */
$resource = $om->get(ResourceConnection::class);
/** @var WriterInterface $writer */
$writer = $om->get(WriterInterface::class);

$connection = $resource->getConnection();
$configTable = $resource->getTableName('core_config_data');
log_ok('connected to ' . $connection->getConfig()['dbname']);

// If the patch is already recorded, the upgrade moved past it and this
// script has nothing to contribute -- say so rather than writing anyway.
$patchTable = $resource->getTableName('patch_list');
$patchName = 'Magento\\Fedex\\Setup\\Patch\\Data\\UpdateFedexInternationalPriority';
$alreadyApplied = (bool)$connection->fetchOne(
    $connection->select()->from($patchTable, 'COUNT(*)')->where('patch_name = ?', $patchName)
);
if ($alreadyApplied) {
    log_warn('UpdateFedexInternationalPriority is already recorded in patch_list.');
    log_warn('The upgrade is past this patch; no NULL guard is needed.');
}

// -------------------------------------------------------------------- inspect
log_step('1/3  Inspecting the paths the blocked patch reads');

$rows = $connection->fetchAll(
    $connection->select()
        ->from($configTable, ['config_id', 'scope', 'scope_id', 'path', 'value'])
        ->where('path IN (?)', PATHS)
);

$nulls = array_values(array_filter($rows, static fn(array $r): bool => $r['value'] === null));

foreach ($rows as $row) {
    $isNull = $row['value'] === null;
    printf(
        "    %s config_id=%-5s %s/%s  %s = %s\n",
        $isNull ? "\033[0;31mNULL\033[0m" : ' ok ',
        $row['config_id'],
        $row['scope'],
        $row['scope_id'],
        $row['path'],
        $isNull ? 'NULL' : '"' . substr((string)$row['value'], 0, 60) . '"'
    );
}

if (!$nulls) {
    log_ok('no NULL values in these paths -- the patch will not abort here, nothing to do');
    exit(0);
}
log_warn(count($nulls) . ' NULL value(s) will abort the patch');

if (!$apply) {
    echo "\n    DRY RUN -- nothing was changed.\n\n";
    echo "    Applying would set each NULL above to an empty string, which is\n";
    echo "    what NULL already means for these fields.\n";
    echo "\n    Re-run with:  php shell/fix-null-config-for-data-patches.php --apply\n\n";
    exit(0);
}

// --------------------------------------------------------------------- backup
log_step('2/3  Writing rollback SQL');

$backupDir = BP . '/var/backups';
if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
    fail('could not create ' . $backupDir);
}
$backup = sprintf('%s/core_config_data-null-fix-%s.sql', $backupDir, date('Ymd-His'));

$sql = '-- rollback for shell/fix-null-config-for-data-patches.php, taken ' . date('c') . "\n";
foreach ($nulls as $row) {
    $sql .= sprintf(
        "UPDATE core_config_data SET value = NULL WHERE config_id = %d; -- %s\n",
        (int)$row['config_id'],
        $row['path']
    );
}
if (file_put_contents($backup, $sql) === false) {
    fail('could not write ' . $backup);
}
log_ok('rollback SQL: ' . $backup);

// ---------------------------------------------------------------------- apply
log_step('3/3  Applying');

foreach ($nulls as $row) {
    // Save through Magento rather than a raw UPDATE, so the write lands in
    // the correct scope and the config cache is invalidated with it.
    $writer->save($row['path'], '', $row['scope'], (int)$row['scope_id']);
    printf("    %s/%s  %s  NULL -> \"\"\n", $row['scope'], $row['scope_id'], $row['path']);
}
log_ok(count($nulls) . ' row(s) updated');

// --------------------------------------------------------------------- verify
log_step('Verifying');

$remaining = (int)$connection->fetchOne(
    $connection->select()
        ->from($configTable, 'COUNT(*)')
        ->where('path IN (?)', PATHS)
        ->where('value IS NULL')
);
if ($remaining !== 0) {
    fail($remaining . ' NULL value(s) remain -- update did not take');
}
log_ok('no NULL values remain in these paths');

// Re-run the patch's own logic read-only, to confirm it can now complete.
try {
    foreach ($connection->fetchAll(
        $connection->select()->from($configTable, ['config_id', 'value'])->where('path IN (?)', PATHS)
    ) as $row) {
        explode(',', (string)$row['value']);
    }
    log_ok('explode() over every row now succeeds -- the patch can complete');
} catch (Throwable $e) {
    fail('still failing: ' . $e->getMessage());
}

log_step('done -- resume with: php bin/magento setup:upgrade');
