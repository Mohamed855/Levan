<?php

namespace App\Http\Controllers\Admin\Versions;


use App\DataTables\Admin\Versions\VersionDataTable;
use App\Enums\Admin\Version\VersionStage;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Versions\VersionRequest;
use App\Models\Admin\Version\Version;
use App\Services\Admin\Versions\VersionService;
use Illuminate\Support\Facades\Log;

class VersionController extends Controller
{
    private $page   = "admin.versions";
    private $route  = "admin.versions";


    public function __construct(private VersionService $versionService)
    {
        $this->middleware('auth:admin');
        $this->middleware('can:الإصدارات')->only(['index']);
        $this->middleware('can:إضافة إصدارة')->only(['create', 'store']);
        $this->middleware('can:تعديل إصدارة')->only(['edit', 'update']);
        $this->middleware('can:حذف إصدارة')->only(['destroy']);
    }


    public function index(VersionDataTable $dataTable)
    {
        try {
            $statistics = $this->versionService->getVersionStatistics();

            return $dataTable->render($this->page . '.index', [
                'total'       => $statistics['total'],
                // 'activePlans'      => $statistics['active'],
                // 'inactivePlans'    => $statistics['inactive'],
            ]);
        } catch (\Exception $e) {
            Log::error($e);
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


    public function destroy(Version $version)
    {
        $version->delete();

        return redirect()->route($this->route . '.index')->with('success', 'تم حذف الإصدارة بنجاح');
    }
}
