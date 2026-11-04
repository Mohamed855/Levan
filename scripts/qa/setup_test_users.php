<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use PragmaRX\Google2FA\Google2FA;

$google2fa = new Google2FA();

// 1. Admin SuperAdmin (Already has 2FA, ensure it is active and confirmed)
$admin = AdminUser::where('email', 'superadmin@gmail.com')->first();
if ($admin) {
    if (!$admin->two_factor_secret) {
        $secret = $google2fa->generateSecretKey();
        $admin->two_factor_secret = encrypt($secret);
        $admin->two_factor_recovery_codes = encrypt(json_encode([
            'ADM-REC-0001', 'ADM-REC-0002', 'ADM-REC-0003', 'ADM-REC-0004',
            'ADM-REC-0005', 'ADM-REC-0006', 'ADM-REC-0007', 'ADM-REC-0008'
        ]));
        $admin->two_factor_confirmed_at = now();
        $admin->save();
    }
    echo "Admin SuperAdmin 2FA confirmed: " . ($admin->two_factor_confirmed_at ? 'YES' : 'NO') . "\n";
}

// 2. Tenant 'test' - ahmed@lot.com (2FA confirmed for challenge tests)
tenancy()->initialize('test');
$ahmed = User::where('email', 'ahmed@lot.com')->first();
if ($ahmed) {
    $secret = $google2fa->generateSecretKey();
    $ahmed->two_factor_secret = encrypt($secret);
    $ahmed->two_factor_recovery_codes = encrypt(json_encode([
        'AHM-REC-0001', 'AHM-REC-0002', 'AHM-REC-0003', 'AHM-REC-0004',
        'AHM-REC-0005', 'AHM-REC-0006', 'AHM-REC-0007', 'AHM-REC-0008'
    ]));
    $ahmed->two_factor_confirmed_at = now();
    $ahmed->save();
    echo "Tenant 'test' ahmed@lot.com 2FA confirmed: YES\n";
}

// demo@lot.com stays unconfirmed for 2FA lifecycle/setup tests (2FA-13, 14, 15, 17, 18)
$demo = User::where('email', 'demo@lot.com')->first();
if ($demo) {
    $demo->two_factor_secret = null;
    $demo->two_factor_recovery_codes = null;
    $demo->two_factor_confirmed_at = null;
    $demo->save();
    echo "Tenant 'test' demo@lot.com 2FA unconfirmed: YES\n";
}
tenancy()->end();

// 3. Tenant 'test2' - khalid@lot.com for cross-tenant isolation tests
tenancy()->initialize('test2');
$khalid2 = User::where('email', 'khalid@lot.com')->first();
if ($khalid2) {
    $secret2 = $google2fa->generateSecretKey();
    $khalid2->two_factor_secret = encrypt($secret2);
    $khalid2->two_factor_recovery_codes = encrypt(json_encode([
        'T2-REC-0001', 'T2-REC-0002', 'T2-REC-0003', 'T2-REC-0004',
        'T2-REC-0005', 'T2-REC-0006', 'T2-REC-0007', 'T2-REC-0008'
    ]));
    $khalid2->two_factor_confirmed_at = now();
    $khalid2->save();
    echo "Tenant 'test2' khalid@lot.com 2FA confirmed: YES\n";
}
tenancy()->end();

echo "All test users configured successfully!\n";
