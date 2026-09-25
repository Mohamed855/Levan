<?php

namespace App\Http\Controllers\Admin\VersionTester;

use App\DataTables\Admin\VersionTester\VersionTesterDataTable;
use App\Http\Controllers\Controller;
use App\Models\Admin\Tenants\Tenant;
use App\Models\Admin\Version\VersionTester;
use App\Enums\Admin\Version\VersionTesterType;
use App\Http\Requests\Admin\VersionTesters\VersionTesterRequest;
use App\Services\Admin\VersionTesters\VersionTestersService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VersionTesterController extends Controller
{
    private $page   = "admin.version_testers";
    private $route  = "admin.version_testers";



    public function __construct(private VersionTestersService $versionTestersService)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:فريق إختبار الإصدارات')->only(['index']);
        $this->middleware('can:إضافة عضو')->only(['create', 'store']);
        $this->middleware('can:تعديل عضو')->only(['edit', 'update']);
        $this->middleware('can:حذف عضو')->only(['destroy']);
    }


    public function index(VersionTesterDataTable $dataTable)
    {
        try {
            $statistics = $this->versionTestersService->getVersionTesterStatistics();

            return $dataTable->render($this->page . '.index', [
                'total'       => $statistics['total'],
                // 'activePlans'      => $statistics['active'],
                // 'inactivePlans'    => $statistics['inactive'],
            ]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات');
        }
    }


    public function create()
    {
        $tenants    = Tenant::select('id', 'company_name')->get();
        $types      = VersionTesterType::cases();

        return view($this->page . '.create', compact('tenants', 'types'));
    }


    public function store(VersionTesterRequest $request)
    {
        $this->versionTestersService->create($request->validated());

        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة العميل للفريق بنجاح');
    }


    public function edit(VersionTester $versionTester)
    {
        $tenants    = Tenant::select('id', 'company_name')->get();
        $types      = VersionTesterType::cases();

        return view($this->page . '.edit', compact('versionTester', 'tenants', 'types'));
    }


    public function update(VersionTesterRequest $request, VersionTester $versionTester)
    {
        $this->versionTestersService->update($versionTester, $request->validated());

        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث العميل  بنجاح');
    }


    public function destroy(VersionTester $versionTester)
    {
        $versionTester->delete();

        return redirect()->route($this->route . '.index')->with('success', 'تم حذف العميل من الفريق بنجاح');
    }
}
