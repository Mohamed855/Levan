<?php

namespace App\Http\Controllers\Admin\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Auth\ConfirmAdminTwoFactorRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;

class AdminTwoFactorSettingsController extends Controller
{
    public function show(Request $request): View
    {
        $user = Auth::guard('admin')->user();

        return view('admin.auth.two-factor-settings', [
            'enabled' => (bool) ($user->two_factor_secret && ! is_null($user->two_factor_confirmed_at)),
        ]);
    }

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $enable(Auth::guard('admin')->user());

        return response()->json(['status' => 'ok']);
    }

    public function qrCode(Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();

        return response()->json(['svg' => $user->twoFactorQrCodeSvg()]);
    }

    public function confirm(ConfirmAdminTwoFactorRequest $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $confirm(Auth::guard('admin')->user(), $request->validated('code'));

        return response()->json(['status' => 'confirmed']);
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        $disable(Auth::guard('admin')->user());

        return response()->json(['status' => 'disabled']);
    }

    public function recoveryCodes(Request $request): JsonResponse
    {
        $user = Auth::guard('admin')->user();

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
