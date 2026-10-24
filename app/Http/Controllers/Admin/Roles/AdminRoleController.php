<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Roles;

use App\Actions\Admin\Roles\SaveAdminRoleAction;
use App\DataTables\Admin\Roles\AdminRoleDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Roles\StoreAdminRoleRequest;
use App\Http\Requests\Admin\Roles\UpdateAdminRoleRequest;
use App\Models\Access\Permission;
use App\Models\Access\Role;
use App\Support\Access\AdminPermissionCatalog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class AdminRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:roles.view')->only(['index', 'show']);
        $this->middleware('can:roles.create')->only(['create', 'store']);
        $this->middleware('can:roles.update')->only(['edit', 'update']);
        $this->middleware('can:roles.delete')->only('destroy');
    }

    public function index(AdminRoleDataTable $dataTable)
    {
        $statistics = [
            'total' => Role::where('guard', 'admin')->count(),
            'assigned' => Role::where('guard', 'admin')->has('adminUsers')->count(),
            'unassigned' => Role::where('guard', 'admin')->doesntHave('adminUsers')->count(),
            'permissions' => Permission::where('guard', 'admin')->count(),
            'system' => Role::where('guard', 'admin')->whereIn('name', ['SuperAdmin', 'manager'])->count(),
        ];

        return $dataTable->render('admin.roles.index', [
            'statistics' => $statistics,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', [
            'role' => new Role(['guard' => 'admin']),
            'permissions' => $this->permissions(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function show(Role $role): View
    {
        $this->assertAdminRole($role);
        $this->authorize('view', $role);

        return view('admin.roles.show', [
            'role' => $role->load('permissions', 'adminUsers'),
        ]);
    }

    public function edit(Role $role): View
    {
        $this->assertAdminRole($role);
        $this->authorize('update', $role);

        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => $this->permissions(),
            'permissionGroups' => $this->permissionGroups(),
        ]);
    }

    public function store(StoreAdminRoleRequest $request, SaveAdminRoleAction $action): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = $action->create($request->validated());
        return to_route('admin.roles.show', $role)->with('success', 'تم إنشاء الدور بنجاح.');
    }

    public function update(UpdateAdminRoleRequest $request, Role $role, SaveAdminRoleAction $action): RedirectResponse
    {
        $this->assertAdminRole($role);
        $this->authorize('update', $role);
        abort_if(in_array($role->name, ['manager', 'SuperAdmin'], true), 422, 'لا يمكن تعديل دور النظام الأساسي.');
        $action->update($role, $request->validated());
        return to_route('admin.roles.show', $role)->with('success', 'تم حفظ الدور بنجاح.');
    }

    public function destroy(Request $request, Role $role): JsonResponse|RedirectResponse
    {
        $this->assertAdminRole($role);
        $this->authorize('delete', $role);
        abort_if(in_array($role->name, ['manager', 'SuperAdmin'], true), 422, 'لا يمكن حذف دور النظام الأساسي.');
        abort_if($role->adminUsers()->exists(), 422, 'لا يمكن حذف دور مسند لمستخدمين.');

        $role->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'تم حذف الدور بنجاح.']);
        }

        return to_route('admin.roles.index')->with('success', 'تم حذف الدور بنجاح.');
    }

    private function assertAdminRole(Role $role): void
    {
        abort_unless($role->guard === 'admin', 404);
    }

    private function permissions()
    {
        return Permission::query()->where('guard', 'admin')->orderBy('display_name')->get();
    }

    private function permissionGroups()
    {
        return $this->permissions()->groupBy(
            fn (Permission $permission): string => AdminPermissionCatalog::group($permission)
        );
    }
}
