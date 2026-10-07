<?php

namespace App\Http\Controllers\Tenants\GeneralSetting\ContactResult;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Tenants\Traits\CommonSettingController;
use App\Http\Requests\Tenants\GeneralSetting\SaveGeneralSettingRequest;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use Illuminate\View\View;
use Yajra\DataTables\Facades\DataTables;

class ContactResultController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:contact_results.view');
    }

    use CommonSettingController;

    protected $model = ContactResult::class;

    public function index()
    {
        return $this->index_trait($this->model, 'tenants.general_setting.contact_results.index', 'general-settings.contact-results');
    }

    public function edit($id)
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

    public function destroy($id)
    {
        try {
            $ContactResult = ContactResult::findOrFail($id);

            $ContactResult->delete();
            return redirect()->back()->with('success', 'تم الحذف بنجاح.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
