<?php

// PermissionController.php
namespace App\Http\Controllers\Tenants\UserMangment\Permission;

use Spatie\Permission\Models\Permission;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->input('search');
        $rowsPerPage = $request->input('rows', 10);

        $permissionsQuery = Permission::query();

        if ($search) {
            $permissionsQuery->where('name', 'like', "%$search%");
        }

        $permissions = $permissionsQuery->paginate($rowsPerPage);

        if ($request->ajax()) {
            return response()->json([
                'view' => view('permissions.index', compact('permissions'))->render()
            ]);
        }

        return view('tenants.permissions.index', compact('permissions'));
    }



    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name',
        ]);

        Permission::create(['name' => $request->name]);

        return redirect()->to(tenant_route_name('permissions.index'))->with('success', 'تم إضافة الصلاحية بنحاج');
    }

    public function update(Request $request, Permission $permission)
    {
        $request->validate([
            'name' => 'required|unique:permissions,name,' . $permission->id,
        ]);

        $permission->update(['name' => $request->name]);

        return redirect()->to(tenant_route_name('permissions.index'))->with('success', 'تم تحديث الصلاحية بنجاح');
    }

    public function destroy(Permission $permission)
    {
        $permission->delete();
        return redirect()->to(tenant_route_name('permissions.index'))->with('success', 'تم حذف الصلاحية بنجاح');
    }
}
