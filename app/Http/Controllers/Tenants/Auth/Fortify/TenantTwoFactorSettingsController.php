<?php

namespace App\Http\Controllers\Tenants\Auth\Fortify;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class TenantTwoFactorSettingsController extends Controller
{
    public function show(Request $request): View
    {
        $user = Auth::guard('tenant')->user();

        return view('tenants.auth.two-factor-settings', [
            'enabled' => (bool) ($user->two_factor_secret && ! is_null($user->two_factor_confirmed_at)),
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $enable(Auth::guard('tenant')->user());

        return response()->json(['status' => 'ok']);
    }

    public function qrCode(Request $request): JsonResponse
    {
        $user = Auth::guard('tenant')->user();

        return response()->json(['svg' => $user->twoFactorQrCodeSvg()]);
    }

    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $request->validate(
            ['code' => 'required|string'],
            [
                'code.required' => 'الرجاء إدخال كود التحقق.',
                'code.string' => 'كود التحقق يجب أن يكون نصًا.',
            ]
        );

        $confirm(Auth::guard('tenant')->user(), $request->code);

        return response()->json(['status' => 'confirmed']);
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $disable(Auth::guard('tenant')->user());

        return response()->json(['status' => 'disabled']);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = Auth::guard('tenant')->user();

        // توليد أكواد جديدة تمامًا وإلغاء القديمة
        $newCodes = \Illuminate\Support\Collection::times(
            8,
            fn() => \Laravel\Fortify\RecoveryCode::generate()
        )->all();

        $user->forceFill([
            'two_factor_recovery_codes' => encrypt(json_encode($newCodes)),
        ])->save();

        return response()->json($newCodes);
    }
}
