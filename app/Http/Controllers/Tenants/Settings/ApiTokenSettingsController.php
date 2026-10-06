<?php

namespace App\Http\Controllers\Tenants\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Settings\RegenerateApiTokenRequest;
use App\Models\Tenants\Api\ApiRequestLog;

class ApiTokenSettingsController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:system.settings');
    }

    /*
    |--------------------------------------------------------------------------
    | عرض مفتاح API الخاص بالتينانت وسجل استخدامه
    |--------------------------------------------------------------------------
    */
    public function index()
    {
        $logs = ApiRequestLog::latest()->paginate(20);

        return view('tenants.settings.api_token.index', compact('logs'));
    }

    /*
    |--------------------------------------------------------------------------
    | توليد توكن API جديد للتينانت الحالي
    |--------------------------------------------------------------------------
    */
    public function regenerate(RegenerateApiTokenRequest $request)
    {
        $plainTextToken = tenant()->regenerateApiToken();

        return redirect(tenant_route_name('api-token-settings.index'))
            ->with('new_api_token', $plainTextToken)
            ->with('success', 'تم توليد توكن API جديد بنجاح');
    }
}
