<?php
/**
 * Public alias for the application entry point
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\Framework\App\Bootstrap;

try {
    require __DIR__ . '/app/bootstrap.php';
} catch (\Exception $e) {
    echo <<<HTML
<div style="font:12px/1.35em arial, helvetica, sans-serif;">
    <div style="margin:0 0 25px 0; border-bottom:1px solid #ccc;">
        <h3 style="margin:0;font-size:1.7em;font-weight:normal;text-transform:none;text-align:left;color:#2f2f2f;">
        Autoload error</h3>
    </div>
    <p>{$e->getMessage()}</p>
</div>
HTML;
    exit(1);
}

$bootstrap = Bootstrap::create(BP, $_SERVER);
/** @var \Magento\Framework\App\Http $app */
$app = $bootstrap->createApplication(\Magento\Framework\App\Http::class);
$bootstrap->run($app);
/** @var \Magento\Framework\App\ResourceConnection $res */
//$res = \Magento\Framework\App\ObjectManager::getInstance()->get('Magento\Framework\App\ResourceConnection');
///** @var Magento\Framework\DB\Profiler $profiler */
//$profiler = $res->getConnection('read')->getProfiler();
//echo "<table cellpadding='0' cellspacing='0' border='1'>";
//echo "<tr>";
//echo "<th>Time <br/>[Total Time: ".$profiler->getTotalElapsedSecs()." secs]</th>";
//echo "<th>SQL [Total: ".$profiler->getTotalNumQueries()." queries]</th>";
//echo "<th>Query Params</th>";
//echo "</tr>";
//foreach ($profiler->getQueryProfiles() as $query) {
//    /** @var Zend_Db_Profiler_Query $query*/
//    echo '<tr>';
//    echo '<td>', number_format(1000 * $query->getElapsedSecs(), 2), 'ms', '</td>';
//    echo '<td>', $query->getQuery(), '</td>';
//    echo '<td>', json_encode($query->getQueryParams()), '</td>';
//    echo '</tr>';
//}
//echo "</table>";
