<?php

namespace App\Http\Controllers\Admin\Settings\Domain;

use App\DataTables\Admin\Setting\SettingDomainDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\ControlDomain\ControlDomain;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SettingsDomainController extends Controller
{
    private $page  = "admin.settings.domains";
    private $route = "admin.settings.domains";

    public function __construct()
    {
        $this->middleware('can:domain_extension_prices.settings');
    }

    public function index(SettingDomainDataTable $dataTable)
    {
        try {
            $filterOptions = [
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                ],
            ];

            return $dataTable->render($this->page . '.index', compact('filterOptions'));
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }

    public function create()
    {
        return view($this->page . '.create');
    }

    // Store extension
    public function store(Request $request)
    {
        $request->validate([
            'domain_extension' => 'required|string|unique:control_domains,domain_extension',
            'price' => 'required|numeric|min:0'
        ]);

        ControlDomain::create($request->only('domain_extension', 'price'));

        return redirect()->route('admin.settings.domains.index')->with('success', 'تم إضافة الامتداد بنجاح');
    }

    // Show edit form
    public function edit($id)
    {
        $domain = ControlDomain::findOrFail($id);
        return view($this->page . '.edit', compact('domain'));
    }

    // Update
    public function update(Request $request, $id)
    {
        $domain = ControlDomain::findOrFail($id);
        $request->validate([
            'domain_extension' => 'required|string|unique:control_domains,domain_extension,' . $domain->id,
            'price' => 'required|numeric|min:0'
        ]);

        $domain->update($request->only('domain_extension', 'price'));

        return redirect()->route('admin.settings.domains.index')->with('success', 'تم التعديل بنجاح');
    }

    // Delete
    public function destroy($id)
    {
        $domain = ControlDomain::findOrFail($id);
        $domain->delete();
        return redirect()->route('admin.settings.domains.index')->with('success', 'تم حذف الامتداد بنجاح');
    }
}
