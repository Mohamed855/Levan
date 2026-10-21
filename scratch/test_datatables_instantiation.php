<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$classes = [
    // Admin (20)
    \App\DataTables\Admin\ActivityLogs\ActivityLogDataTable::class,
    \App\DataTables\Admin\Archive\Bills\BillArchiveDataTable::class,
    \App\DataTables\Admin\Archive\Coupons\CouponArchiveDataTable::class,
    \App\DataTables\Admin\Archive\Plans\PlanArchiveDataTable::class,
    \App\DataTables\Admin\Archive\Tenants\TenantArchiveDataTable::class,
    \App\DataTables\Admin\Archive\Users\AdminUserArchiveDataTable::class,
    \App\DataTables\Admin\AuctionTemplates\AuctionTemplateDataTable::class,
    \App\DataTables\Admin\Banks\BankDataTable::class,
    \App\DataTables\Admin\Bills\BillDataTable::class,
    \App\DataTables\Admin\ControlDomains\ControlDomainDataTable::class,
    \App\DataTables\Admin\Coupons\CouponDataTable::class,
    \App\DataTables\Admin\Faqs\FaqDataTable::class,
    \App\DataTables\Admin\Plans\PlanDataTable::class,
    \App\DataTables\Admin\PublicRequests\ContactRequestsDataTable::class,
    \App\DataTables\Admin\PublicRequests\TrialRequestsDataTable::class,
    \App\DataTables\Admin\Roles\AdminRoleDataTable::class,
    \App\DataTables\Admin\Subscriptions\SubscriptionDataTable::class,
    \App\DataTables\Admin\Tenants\TenantDataTable::class,
    \App\DataTables\Admin\Tickets\TicketDataTable::class,
    \App\DataTables\Admin\Users\AdminUserDataTable::class,

    // Tenant (10)
    \App\DataTables\Tenant\ActivityLog\TenantActivityLogDataTable::class,
    \App\DataTables\Tenant\Archive\Assignments\AssignmentArchiveDataTable::class,
    \App\DataTables\Tenant\Archive\Companies\CompanyArchiveDataTable::class,
    \App\DataTables\Tenant\Archive\Properties\PropertyArchiveDataTable::class,
    \App\DataTables\Tenant\Bills\TenantBillDataTable::class,
    \App\DataTables\Tenant\Companies\CompanyDataTable::class,
    \App\DataTables\Tenant\Notifications\TenantNotificationDataTable::class,
    \App\DataTables\Tenant\Roles\TenantRoleDataTable::class,
    \App\DataTables\Tenant\Tickets\TenantTicketDataTable::class,
    \App\DataTables\Tenant\Users\TenantUserDataTable::class,
];

echo "Testing 30 DataTable instantiations...\n";
$successCount = 0;
$errors = [];

foreach ($classes as $class) {
    try {
        if (!class_exists($class)) {
            $errors[] = "Class does NOT exist: {$class}";
            continue;
        }
        $instance = app($class);
        $reflection = new ReflectionClass($instance);
        $successCount++;
        echo "[OK] " . $reflection->getShortName() . "\n";
    } catch (\Throwable $e) {
        $errors[] = "[ERROR] {$class}: " . $e->getMessage();
    }
}

echo "\nSummary: {$successCount} / " . count($classes) . " DataTables verified successfully.\n";

if (!empty($errors)) {
    echo "Failures:\n" . implode("\n", $errors) . "\n";
    exit(1);
}

exit(0);
