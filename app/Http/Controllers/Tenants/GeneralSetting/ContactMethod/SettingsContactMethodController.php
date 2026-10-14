<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\ContactMethod;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Http\Requests\Tenants\Auth\TenantAuthenticatedRequest;
use App\Http\Requests\Tenants\GeneralSetting\SaveGeneralSettingRequest;
use App\Models\Tenants\GeneralSetting\ContactMethod\SettingsContactMethod;

class SettingsContactMethodController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:contact_methods.view')->only([
            'index',
            'edit',
            'store',
            'update',
            'destroy',
        ]);
    }

    use CommonSettingController;

    protected $model = SettingsContactMethod::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.contact_methods.settings_contact_methods', 'general-settings.settings-contact-methods');
    }

    public function create()
    {
        return $this->create_trait('general-settings.settings-contact-methods');
    }

    public function show($id)
    {
        return $this->show_trait($this->model, $id);
    }

    public function edit(TenantAuthenticatedRequest $request, $id)
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
