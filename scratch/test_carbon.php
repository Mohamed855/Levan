<?php
require 'vendor/autoload.php';

$ref = new ReflectionMethod(\Yajra\DataTables\Processors\DataProcessor::class, 'process');
$filename = $ref->getFileName();
$start = $ref->getStartLine();
$end = $ref->getEndLine();

$lines = file($filename);
for ($i = $start - 1; $i < $end; $i++) {
    echo $lines[$i];
}
