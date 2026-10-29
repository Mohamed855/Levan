<?php

namespace App\Http\Controllers\Tenants\Auth\Fortify;

use App\Actions\Auth\PersistAuthenticationStateAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Auth\ConfirmTenantTwoFactorRequest;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Models\Tenants\Settings\Settings;
use App\Services\Tenants\Shared\CentralTenantSecurityGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\RecoveryCode;

class TenantTwoFactorSettingsController extends Controller
{
    public function __construct(
        protected CentralTenantSecurityGateway $securityGateway
    ) {}

    public function show(Request $request): View
    {
        $user = Auth::guard('tenant')->user();

        return view('tenant.auth.two-factor-settings', [
            'enabled' => (bool) ($user->two_factor_secret && ! is_null($user->two_factor_confirmed_at)),
            'enforced' => $this->isEnforced(),
        ]);
    }

    public function enable(TenantAuthenticatedRequest $request, EnableTwoFactorAuthentication $enable): JsonResponse
    {
        $enable(Auth::guard('tenant')->user());

        return response()->json(['status' => 'ok']);
    }

    public function qrCode(Request $request): JsonResponse
    {
        $user = Auth::guard('tenant')->user();

        return response()->json(['svg' => $user->twoFactorQrCodeSvg()]);
    }

    public function confirm(ConfirmTenantTwoFactorRequest $request, ConfirmTwoFactorAuthentication $confirm): JsonResponse
    {
        $confirm(Auth::guard('tenant')->user(), $request->validated('code'));

        return response()->json(['status' => 'confirmed']);
    }

    public function disable(TenantAuthenticatedRequest $request, DisableTwoFactorAuthentication $disable): JsonResponse
    {
        if ($this->isEnforced()) {
            return response()->json([
                'message' => 'المصادقة الثنائية مفروضة بموجب سياسة النظام ولا يمكن تعطيلها.',
            ], 422);
        }

        $disable(Auth::guard('tenant')->user());

        return response()->json(['status' => 'disabled']);
    }

    protected function isEnforced(): bool
    {
        $companyForced = $this->securityGateway->isTwoFactorRequired();
        $tenantSettingsForced = (bool) (Settings::first()?->force_two_factor_auth ?? false);

        return $companyForced || $tenantSettingsForced;
    }

    public function recoveryCodes(TenantAuthenticatedRequest $request, PersistAuthenticationStateAction $authenticationState): JsonResponse
    {
        $user = Auth::guard('tenant')->user();

        // توليد أكواد جديدة تمامًا وإلغاء القديمة
        $newCodes = Collection::times(
            8,
            fn () => RecoveryCode::generate()
        )->all();

        $authenticationState->replaceRecoveryCodes($user, $newCodes);

        return response()->json($newCodes);
    }
}
