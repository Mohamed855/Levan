<?php

namespace App\Http\Controllers\Admin\Versions;


use App\DataTables\Admin\Versions\VersionDataTable;
use App\Enums\Admin\Version\VersionStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Versions\VersionRequest;
use App\Models\Admin\Version\Version;
use App\Services\Admin\Versions\VersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class VersionController extends Controller
{
    private $page   = "admin.versions";
    private $route  = "admin.versions";


    public function __construct(private VersionService $versionService)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:versions.view')->only(['index']);
        $this->middleware('can:versions.create')->only(['create', 'store']);
        $this->middleware('can:versions.edit')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:versions.delete')->only(['destroy']);
    }


    public function index(VersionDataTable $dataTable)
    {
        try {
            $statistics = $this->versionService->getVersionStatistics();

            $filterOptions = [
                'stages' => [
                    ['value' => 'alpha', 'label' => 'تحت التطوير (Alpha)'],
                    ['value' => 'testing', 'label' => 'قيد الاختبار (Testing)'],
                    ['value' => 'beta', 'label' => 'تجريبية (Beta)'],
                    ['value' => 'stable', 'label' => 'مستقرة (Stable)'],
                ],
                'statuses' => [
                    ['value' => 'active', 'label' => 'نشط'],
                    ['value' => 'inactive', 'label' => 'غير نشط'],
                ],
            ];

            return $dataTable->render($this->page . '.index', [
                'total'         => $statistics['total'],
                'filterOptions' => $filterOptions,
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
        $versionStages = VersionStage::cases();

        return view($this->page . '.create', compact('versionStages'));
    }


    public function store(VersionRequest $request)
    {
        $this->versionService->create($request->validated());

        return redirect()->route($this->route . '.index')->with('success', 'تم إضافة الإصدارة بنجاح');
    }


    // public function show(Version $version)
    // {

    //     return view($this->page . '.show', compact('version'));
    // }

    public function show($id)
    {
        $version = Version::with(['creator', 'updater'])->findOrFail($id);
        return view('admin.versions.show', compact('version'));
    }


    public function edit(Version $version)
    {
        $versionStages = VersionStage::cases();

        return view($this->page . '.edit', compact('version', 'versionStages'));
    }


    public function update(VersionRequest $request, Version $version)
    {
        $this->versionService->update($version, $request->validated());

        return redirect()->route($this->route . '.index')->with('success', 'تم تحديث الإصدارة بنجاح');
    }

    public function toggleStatus(Version $version): JsonResponse
    {
        $version->update(['is_active' => ! $version->is_active]);

        return response()->json([
            'success' => true,
            'message' => $version->is_active ? 'تم تفعيل الإصدار بنجاح' : 'تم تعطيل الإصدار بنجاح',
        ]);
    }


    public function destroy(Version $version)
    {
        $version->delete();

        return redirect()->route($this->route . '.index')->with('success', 'تم حذف الإصدارة بنجاح');
    }
}
