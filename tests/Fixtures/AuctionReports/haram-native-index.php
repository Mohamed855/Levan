<?php

declare(strict_types=1);

$snapshot = require __DIR__.'/haram.php';
$snapshot['cycle_version'] = 1;
$snapshot['closing_saved'] = false;
$snapshot['auction'] = array_replace($snapshot['auction'], [
    'name' => 'مزاد ريف الرياض الزراعي والسكني',
    'project_manager_name' => 'خالد القحطاني',
    'start_date' => '2026-11-11', 'end_date' => '2026-11-14',
    'start_time' => '10:00', 'end_time' => '17:00',
]);
$snapshot['assignment']['name'] = 'إسناد محكمة التنفيذ بالرياض - أصول ومزارع الخرج';
$snapshot['properties'] = array_slice($snapshot['properties'], 0, 2);
foreach ($snapshot['properties'] as $index => &$property) {
    $property['number'] = 'PROP-KHJ-00'.($index + 1);
    $property['deed_number'] = ['320102008491', '320105001239'][$index];
    $property['type'] = ['أرض', 'قصر'][$index];
    $property['board'] = [];
    $property['result_status'] = 'pending';
}
unset($property);

return $snapshot;
