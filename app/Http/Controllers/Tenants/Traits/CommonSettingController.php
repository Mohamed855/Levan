<?php

namespace App\Http\Controllers\Tenants\Traits;

use App\Actions\Tenants\GeneralSetting\GeneralSettingAction;
use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;

trait CommonSettingController
{
    public function create_trait(string $route): RedirectResponse
    {
        return redirect(tenant_route_name($route.'.index'));
    }

    public function show_trait(string $model, int|string $id): JsonResponse
    {
        $item = $model::query()->findOrFail($id);
        $this->authorize('view', $item);

        return response()->json([
            'success' => true,
            'data' => $item,
        ]);
    }

    public function index_trait($model, $view, $route, $with = null)
    {
        $this->authorize('viewAny', $model);
        $request = request();

        try {
            if ($request->ajax()) {
                $setting = $model::select([
                    'id',
                    'name',
                    'status',
                    'created_at',
                ]);

                return datatables()->of($setting)
                    ->addColumn('checkbox', function ($row) {
                        return '<input type="checkbox" class="row-checkbox" value="'.$row->id.'">';
                    })
                    ->addColumn('status', function ($row) use ($route) {
                        $checked = $row->status === 'active' ? 'checked' : '';
                        $url = tenant_route_name($route.'.toggle-status', $row->id);

                        return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_'.$row->id.'" data-url="'.e($url).'" '.$checked.' aria-label="تبديل الحالة"></div></div>';
                    })
                    ->addColumn('action', function ($row) use ($route) {

                        $buttons = '<div class="d-flex justify-content-center">';

                        $buttons .= '
                            <button type="button" class="btn btn-icon btn-primary btn-edit me-1" data-id="'.$row->id.'" data-name="'.htmlspecialchars($row->name, ENT_QUOTES, 'UTF-8').'">
                                <i class="ti ti-edit"></i>
                            </button>';

                        $buttons .= '
                            <button type="button" class="btn btn-icon btn-primary" onclick="confirmDelete('.$row->id.')">
                                <i class="ti ti-trash"></i>
                            </button>

                            <form id="delete-form-'.$row->id.'" action="'.tenant_route_name($route.'.destroy', $row->id).'" method="POST" style="display: none;">
                                '.csrf_field().'
                                '.method_field('DELETE').'
                            </form>';

                        $buttons .= '</div>';

                        return $buttons;
                    })
                    ->editColumn('created_at', function ($row) {
                        return $row->created_at
                            ? Carbon::parse($row->created_at)->translatedFormat('d F Y')
                            : '';
                    })
                    ->rawColumns(['action', 'status'])
                    ->make(true);
            }

            return view($view, compact('route'));
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'حدث خطأ أثناء جلب البيانات. يرجى المحاولة لاحقاً.');
        }
    }

    public function toggleStatus_trait($model, $id) // لتغيير حالة الاعدادات
    {
        $setting = $model::findOrFail($id);
        $this->authorize('toggleStatus', $setting);
        $status = app(GeneralSettingAction::class)->toggleStatus($setting);

        return response()->json([
            'success' => true,
            'message' => 'تم تحديث الحالة بنجاح.',
            'status' => $status,
        ]);
    }

    public function store_trait(FormRequest $request, $model)
    {
        $this->authorize('create', $model);
        $validated = $request->validated();

        app(GeneralSettingAction::class)->create($model, ['name' => $validated['name']], auth('tenant')->id());

        return redirect()->back()->with('success', 'تمت الإضافة بنجاح.');
    }

    public function update_trait(FormRequest $request, $model, $id)
    {
        $validated = $request->validated();

        $item = $model::findOrFail($id);
        $this->authorize('update', $item);
        app(GeneralSettingAction::class)->update($item, ['name' => $validated['name']], auth('tenant')->id());

        return redirect()->back()->with('success', 'تم التحديث بنجاح.');
    }

    public function destroy_trait($model, $id)
    {
        $item = $model::findOrFail($id);
        $this->authorize('delete', $item);
        app(GeneralSettingAction::class)->delete($item);

        return redirect()->back()->with('success', 'تم الحذف بنجاح.');
    }
}
