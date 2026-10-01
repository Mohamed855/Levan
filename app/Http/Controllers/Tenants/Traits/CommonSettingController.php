<?php

namespace App\Http\Controllers\Tenants\Traits;


use Carbon\Carbon;
use Illuminate\Http\Request;

trait CommonSettingController
{
    public function index_trait($model, $view, $route, $with = null)
    {
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
                    ->addIndexColumn() // إضافة الترقيم التلقائي
                    ->addColumn('checkbox', function ($row) {
                        return '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">';
                    })
                    ->addColumn('status', function ($row) use ($route) {
                        $checked = $row->status === 'active' ? 'checked' : '';
                        $url = tenant_route_name($route . '.toggle-status', $row->id);

                        return '<div class="d-flex justify-content-center"><div class="form-check form-switch mb-0"><input type="checkbox" class="form-check-input js-table-status-toggle" id="status_' . $row->id . '" data-url="' . e($url) . '" ' . $checked . ' aria-label="تبديل الحالة"></div></div>';
                    })
                    ->addColumn('action', function ($row) use ($route) {

                        $buttons = '<div class="d-flex justify-content-center">';

                        $buttons .= '
                            <button type="button" class="btn btn-icon btn-label-primary btn-edit me-1" data-id="' . $row->id . '" data-name="' . htmlspecialchars($row->name, ENT_QUOTES, 'UTF-8') . '">
                                <i class="ti ti-edit"></i>
                            </button>';


                        $buttons .= '
                            <button type="button" class="btn btn-icon btn-label-primary" onclick="confirmDelete(' . $row->id . ')">
                                <i class="ti ti-trash"></i>
                            </button>

                            <form id="delete-form-' . $row->id . '" action="' . tenant_route_name($route . '.destroy', $row->id) . '" method="POST" style="display: none;">
                                ' . csrf_field() . '
                                ' . method_field('DELETE') . '
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

        // تغيير الحالة
        if ($setting->status === 'active') {
            $setting->status = 'inactive';
        } else {
            $setting->status = 'active';
        }

        $setting->save();

        return response()->json([
            'success' => true,
            'status' => $setting->status,
        ]);
    }

    public function store_trait(Request $request, $model)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'حقل الاسم مطلوب.',
            'name.string' => 'يجب أن يكون الاسم نصاً.',
            'name.max' => 'يجب ألا يتجاوز الاسم 255 حرفاً.',
        ]);

        $model::create([
            'name' => $request->name,
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'تمت الإضافة بنجاح.');
    }

    public function update_trait(Request $request, $model, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'حقل الاسم مطلوب.',
            'name.string' => 'يجب أن يكون الاسم نصاً.',
            'name.max' => 'يجب ألا يتجاوز الاسم 255 حرفاً.',
        ]);

        $item = $model::findOrFail($id);
        $item->update([
            'name' => $request->name,
            'user_id' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'تم التحديث بنجاح.');
    }

    public function destroy_trait($model, $id)
    {
        $item = $model::findOrFail($id);
        $item->delete();

        return redirect()->back()->with('success', 'تم الحذف بنجاح.');
    }
}
