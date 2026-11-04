<?php

declare(strict_types=1);

require __DIR__ . '/../../../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../../../bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

config([
    'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
    'database.connections.tenant' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
    'tenancy.bootstrappers' => [],
]);
\Illuminate\Support\Facades\DB::purge('mysql');
\Illuminate\Support\Facades\DB::purge('tenant');

\Illuminate\Support\Facades\Schema::connection('tenant')->create('settings', function ($table) {
    $table->id();
    $table->boolean('maintenance_mode')->default(false);
});
\Illuminate\Support\Facades\Schema::connection('mysql')->create('settings', function ($table) {
    $table->id();
    $table->boolean('maintenance_mode')->default(false);
});

$request = \Illuminate\Http\Request::create('/superadmin/two-factor-challenge', 'GET');
$request->setLaravelSession(app('session')->driver());
app()->instance('request', $request);

view()->share('errors', new \Illuminate\Support\ViewErrorBag());

$htmlAdmin = view('admin.auth.two-factor-challenge')->render();
file_put_contents(__DIR__ . '/admin_challenge.html', $htmlAdmin);

$tenantRequest = \Illuminate\Http\Request::create('/tenant-test/two-factor-challenge', 'GET');
$tenantRequest->setLaravelSession(app('session')->driver());
app()->instance('request', $tenantRequest);

// Create tenant context for view rendering
$tenant = new \App\Models\Admin\Tenants\Tenant(['id' => 'test-tenant', 'company_name' => 'شركة لوت التجريبية']);
tenancy()->initialize($tenant);

$htmlTenant = view('tenant.auth.two-factor-challenge')->render();
file_put_contents(__DIR__ . '/tenant_challenge.html', $htmlTenant);

echo "Rendered views successfully:\n";
echo "Admin HTML size: " . strlen($htmlAdmin) . " bytes\n";
echo "Tenant HTML size: " . strlen($htmlTenant) . " bytes\n";
