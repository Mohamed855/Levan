<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Roles;

use App\Actions\Tenant\Roles\SaveTenantRoleAction;
use App\DataTables\Tenant\Roles\TenantRoleDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Roles\StoreTenantRoleRequest;
use App\Http\Requests\Tenant\Roles\UpdateTenantRoleRequest;
use App\Models\Access\Permission;
use App\Models\Access\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TenantRoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:roles.view')->only(['index', 'show']);
        $this->middleware('can:roles.create')->only(['create', 'store']);
        $this->middleware('can:roles.update')->only(['edit', 'update']);
        $this->middleware('can:roles.delete')->only('destroy');
    }

    public function index(TenantRoleDataTable $dataTable)
    {
        $statistics = [
            'total'            => Role::query()->where('guard', 'tenant')->count(),
            'roles_with_users' => Role::query()->where('guard', 'tenant')->has('tenantUsers')->count(),
            'empty_roles'      => Role::query()->where('guard', 'tenant')->doesntHave('tenantUsers')->count(),
            'system_roles'     => Role::query()->where('guard', 'tenant')->whereIn('name', ['tenant_owner', 'tenant_manager', 'tenant_user'])->count(),
            'custom_roles'     => Role::query()->where('guard', 'tenant')->whereNotIn('name', ['tenant_owner', 'tenant_manager', 'tenant_user'])->count(),
        ];

        return $dataTable->render('tenant.roles.index', compact('statistics'));
    }

    public function create(): View
    {
        $this->authorize('create', Role::class);

        return view('tenant.roles.create', [
            'role'        => new Role(['guard' => 'tenant']),
            'permissions' => $this->permissions(),
        ]);
    }

    public function show(Role $role): View
    {
        $this->assertTenantRole($role);
        $this->authorize('view', $role);

        return view('tenant.roles.show', [
            'role' => $role->load('permissions', 'tenantUsers'),
        ]);
    }

    public function edit(Role $role): View
    {
        $this->assertTenantRole($role);
        $this->authorize('update', $role);

        return view('tenant.roles.edit', [
            'role'        => $role->load('permissions'),
            'permissions' => $this->permissions(),
        ]);
    }

    public function store(StoreTenantRoleRequest $request, SaveTenantRoleAction $action): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $role = $action->create($request->validated());

        return to_route('tenant.roles.show', $role)->with('success', 'تم إنشاء الدور بنجاح.');
    }

    public function update(UpdateTenantRoleRequest $request, Role $role, SaveTenantRoleAction $action): RedirectResponse
    {
        $this->assertTenantRole($role);
        $this->authorize('update', $role);
        $action->update($role, $request->validated());

        return to_route('tenant.roles.show', $role)->with('success', 'تم حفظ الدور بنجاح.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse|JsonResponse
    {
        $this->assertTenantRole($role);
        $this->authorize('delete', $role);
        abort_if(in_array($role->name, ['tenant_owner', 'tenant_manager', 'tenant_user'], true), 422, 'لا يمكن حذف دور النظام.');
        abort_if($role->tenantUsers()->exists(), 422, 'لا يمكن حذف دور مرتبط بمستخدمين.');

        $role->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الدور بنجاح.',
            ]);
        }

        return to_route('tenant.roles.index')->with('success', 'تم حذف الدور بنجاح.');
    }

    private function assertTenantRole(Role $role): void
    {
        abort_unless($role->guard === 'tenant', 404);
    }

    private function permissions()
    {
        return Permission::query()->where('guard', 'tenant')->orderBy('display_name')->get();
    }
}
