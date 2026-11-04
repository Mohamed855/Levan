<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Tenants\UserMangment\User\User;
use PragmaRX\Google2FA\Google2FA;

$scope = $argv[1] ?? 'admin';
$email = $argv[2] ?? ($scope === 'admin' ? 'superadmin@gmail.com' : 'ahmed@lot.com');
$tenantId = $argv[3] ?? ($scope === 'tenant2' ? 'test2' : 'test');

$google2fa = new Google2FA();
$secret = null;
$recoveryCodes = [];

if ($scope === 'admin') {
    $user = AdminUser::where('email', $email)->first();
    if ($user && $user->two_factor_secret) {
        $secret = decrypt($user->two_factor_secret);
        if ($user->two_factor_recovery_codes) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
        }
    }
} else {
    tenancy()->initialize($tenantId);
    $user = User::where('email', $email)->first();
    if ($user && $user->two_factor_secret) {
        $secret = decrypt($user->two_factor_secret);
        if ($user->two_factor_recovery_codes) {
            $recoveryCodes = json_decode(decrypt($user->two_factor_recovery_codes), true) ?: [];
        }
    }
    tenancy()->end();
}

$otp = $secret ? $google2fa->getCurrentOtp($secret) : null;

echo json_encode([
    'scope' => $scope,
    'email' => $email,
    'otp' => $otp,
    'recovery_codes' => $recoveryCodes,
]);
