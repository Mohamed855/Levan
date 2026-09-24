<?php

use App\Http\Controllers\Api\Integration\Customer\CustomerApiController;
use App\Http\Controllers\Api\SubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;


Route::prefix('central')->middleware(['api'])->group(function () {
    //خاص للتحقق من الكوبون
    Route::prefix('coupon')->group(function () {
        Route::post('validate-coupon', [SubscriptionController::class, 'validateCoupon']);
    });
});


// API التكامل الخارجي: عرض العملاء الخاصة بالتينانت عبر Token
Route::prefix('v1')->middleware(['tenant.token'])->group(function () {
    Route::get('customers', [CustomerApiController::class, 'index']);
    Route::get('customers/{id}', [CustomerApiController::class, 'show']);
});


// الترقية
Route::post('tenants/{tenantId}/upgrade', function (Request $request, $tenantId) {

    if ($request->header('X-Secret-Key') !== config('app.upgrade_secret')) {
        abort(403, 'غير مصرح');
    }

    try {

        $exitCode = Artisan::call('tenants:migrate', [
            '--tenants' => [$tenantId],
            '--force' => true,
        ]);

        if ($exitCode !== 0) {
            throw new \RuntimeException('فشل تشغيل migrations');
        }

        return response()->json([
            'success' => true,
            'message' => 'تمت migrations بنجاح',
            'tenant_id' => $tenantId,
        ]);
    } catch (\Throwable $e) {

        return response()->json([
            'success' => false,
            'message' => $e->getMessage(),
        ], 500);
    }
});