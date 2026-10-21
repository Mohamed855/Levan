<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Users;

use App\Actions\Tenant\Users\SaveTenantUserAction;
use App\DataTables\Tenant\Users\TenantUserDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Users\StoreTenantUserRequest;
use App\Http\Requests\Tenant\Users\UpdateTenantUserRequest;
use App\Models\Access\Role;
use App\Models\Tenant\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class TenantUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('can:users.view')->only(['index', 'show']);
        $this->middleware('can:users.create')->only(['create', 'store']);
        $this->middleware('can:users.update')->only(['edit', 'update', 'toggleStatus']);
        $this->middleware('can:users.delete')->only('destroy');
    }

    public function index(TenantUserDataTable $dataTable)
    {
        $statistics = [
            'total'    => User::query()->count(),
            'active'   => User::query()->where('status', 'active')->count(),
            'inactive' => User::query()->where('status', 'inactive')->count(),
            'roles'    => Role::query()->where('guard', 'tenant')->count(),
            'archived' => User::onlyTrashed()->count(),
        ];

        $roles = $this->roles();
        $filterOptions = [
            'status' => [
                ['value' => 'active', 'label' => 'نشط'],
                ['value' => 'inactive', 'label' => 'معطل'],
            ],
            'roles' => $roles->map(fn ($r) => [
                'value' => (string) $r->id,
                'label' => $r->display_name ?: $r->name,
            ])->toArray(),
        ];

        return $dataTable->render('tenant.users.index', compact('statistics', 'filterOptions'));
    }

    public function create(): View
    {
        return view('tenant.users.create', ['user' => new User(), 'roles' => $this->roles()]);
    }

    public function store(StoreTenantUserRequest $request, SaveTenantUserAction $action): RedirectResponse
    {
        $user = $action->create($request->validated());

        return to_route('tenant.users.show', $user)->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function show(User $user): View
    {
        return view('tenant.users.show', ['user' => $user->load('roles')]);
    }

    public function edit(User $user): View
    {
        return view('tenant.users.edit', ['user' => $user->load('roles'), 'roles' => $this->roles()]);
    }

    public function update(UpdateTenantUserRequest $request, User $user, SaveTenantUserAction $action): RedirectResponse
    {
        if ($user->is($request->user('tenant')) && $request->validated('status') !== 'active') {
            return back()->withInput()->withErrors(['status' => 'لا يمكنك تعطيل حسابك أثناء تسجيل الدخول.']);
        }

        $action->update($user, $request->validated());

        return to_route('tenant.users.show', $user)->with('success', 'تم حفظ بيانات المستخدم بنجاح.');
    }

    public function toggleStatus(Request $request, User $user): JsonResponse
    {
        abort_if($user->is($request->user('tenant')), 422, 'لا يمكنك تعطيل حسابك أثناء تسجيل الدخول.');
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        $user->update(['status' => $newStatus]);

        return response()->json([
            'success'   => true,
            'status'    => $newStatus,
            'is_active' => $newStatus === 'active',
            'message'   => $newStatus === 'active' ? 'تم تفعيل حساب المستخدم بنجاح.' : 'تم تعطيل حساب المستخدم بنجاح.',
        ]);
    }

    public function destroy(Request $request, User $user): RedirectResponse|JsonResponse
    {
        abort_if($user->is($request->user('tenant')), 422, 'لا يمكنك أرشفة حسابك أثناء تسجيل الدخول.');
        $user->delete();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف المستخدم بنجاح.',
            ]);
        }

        return redirect()->back()->with('success', 'تمت أرشفة المستخدم بنجاح.');
    }

    /** @return \Illuminate\Database\Eloquent\Collection<int, Role> */
    private function roles()
    {
        return Role::query()->where('guard', 'tenant')->orderBy('display_name')->get();
    }
}
