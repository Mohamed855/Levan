<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\PaymentMethod;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\GeneralSetting\SaveGeneralSettingRequest;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;

class SettingsPaymentMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:payment_methods.view')->only([
            'index',
            'edit',
            'store',
            'update',
            'destroy',
        ]);
    }

    use CommonSettingController;

    protected $model = SettingsPaymentMethod::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.payment_methods.settings_payment_methods', 'general-settings.settings-payment-methods');
    }

    public function create()
    {
        return $this->create_trait('general-settings.settings-payment-methods');
    }

    public function show($id)
    {
        return $this->show_trait($this->model, $id);
    }

    public function edit(TenantAuthenticatedRequest $request, $id) // لتغيير حالة طرق التحصيل
    {
        return $this->toggleStatus_trait($this->model, $id);
    }

    public function store(SaveGeneralSettingRequest $request)
    {
        return $this->store_trait($request, $this->model);
    }

    public function update(SaveGeneralSettingRequest $request, $id)
    {
        return $this->update_trait($request, $this->model, $id);
    }

    public function destroy(TenantAuthenticatedRequest $request, $id)
    {
        return $this->destroy_trait($this->model, $id);
    }
}
