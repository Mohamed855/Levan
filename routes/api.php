<?php

use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;

Route::prefix('central')->middleware(['web'])->group(function () {
    Route::prefix('coupon')->group(function () {
        Route::post('validate-coupon', [SubscriptionController::class, 'validateCoupon']);
        Route::post('remove-coupon', [SubscriptionController::class, 'removeCoupon']);
    });
});

Route::post('tenants/{tenantId}/upgrade', function (Request $request, string $tenantId) {
    $requestId = (string) $request->attributes->get('tenant_upgrade_request_id');

    try {
        $exitCode = Artisan::call('tenants:migrate', [
            '--tenants' => [$tenantId],
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('فشل تشغيل migrations');
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت migrations بنجاح',
            'tenant_id' => $tenantId,
            'request_id' => $requestId,
        ]);
    } catch (Throwable $e) {
        Log::error('Tenant upgrade migration failed.', [
            'tenant_id' => $tenantId,
            'request_id' => $requestId,
            'auth_mode' => $request->attributes->get('tenant_upgrade_auth_mode'),
            'exception' => $e->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => 'فشلت عملية الترقية. راجع سجل النظام باستخدام معرف الطلب.',
            'request_id' => $requestId,
        ], 500);
    }
})->middleware(['throttle:tenant-upgrade', 'tenant.upgrade.signature']);
