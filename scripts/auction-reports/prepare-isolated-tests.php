<?php

declare(strict_types=1);

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (($argv[1] ?? '') !== '--create-isolated-schemas') {
    throw new InvalidArgumentException('Explicit --create-isolated-schemas is required. No shared schema is ever migrated.');
}
$template = config('database.connections.mysql');
$source = DB::connection('mysql');
$definitions = [];
foreach ($source->select('SHOW FULL TABLES WHERE Table_type = ?', ['BASE TABLE']) as $row) {
    $name = array_values((array) $row)[0];
    if (! preg_match('/^[A-Za-z0-9_]+$/D', $name)) {
        throw new RuntimeException('Unexpected table identifier.');
    }
    $definition = array_values((array) $source->selectOne('SHOW CREATE TABLE `'.$name.'`'))[1];
    $definitions[] = preg_replace('/^CREATE TABLE /', 'CREATE TABLE IF NOT EXISTS ', $definition, 1);
}
foreach (['levan_report_ci_central', 'levan_report_ci_tenant'] as $database) {
    $source->statement('CREATE DATABASE IF NOT EXISTS `'.$database.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $connection = $database === 'levan_report_ci_central' ? 'report_ci_central' : 'report_ci_tenant';
    config(['database.connections.'.$connection => array_replace($template, ['database' => $database, 'url' => null])]);
}
$central = DB::connection('report_ci_central');
$central->statement('SET FOREIGN_KEY_CHECKS=0');
try {
    foreach ($definitions as $definition) {
        $central->statement($definition);
    }
} finally {
    $central->statement('SET FOREIGN_KEY_CHECKS=1');
}
$exitCode = Artisan::call('migrate', ['--database' => 'report_ci_tenant', '--path' => ['database/migrations/tenant'], '--force' => true]);
echo Artisan::output();
if ($exitCode !== 0) {
    throw new RuntimeException('Isolated tenant migrations failed; shared schemas were not modified.');
}
echo 'Isolated schemas prepared: structure only, no copied business records or shared seeders.'.PHP_EOL;
