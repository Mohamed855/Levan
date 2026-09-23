<?php

namespace App\Http\Controllers\Tenants\UserMangment\Team;

use App\DataTables\Tenants\UserManagement\Team\TeamsDataTable;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\UserManagement\Team\StoreTeamRequest;
use App\Http\Requests\Tenants\UserManagement\Team\UpdateTeamRequest;
use App\Models\Tenants\UserMangment\Team\Team;
use App\Services\Tenants\UserManagement\Team\TeamService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class TeamController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Constructor & Dependencies
    |--------------------------------------------------------------------------
    */
    protected TeamService $teamService;

    public function __construct(TeamService $teamService)
    {
        $this->teamService = $teamService;
        $this->middleware('auth');
    }

    /*
    |--------------------------------------------------------------------------
    | Display Teams List
    |--------------------------------------------------------------------------
    */
    public function index(TeamsDataTable $dataTable)
    {
        $statistics = $this->teamService->getTeamStatistics();
        $branches = $this->teamService->getActiveBranches();

        return $dataTable->render('tenants.users_management.team.index', [
            'totalTeams' => $statistics['total'],
            'activeTeams' => $statistics['active'],
            'inactiveTeams' => $statistics['inactive'],
            'branches' => $branches,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | Show Create Form
    |--------------------------------------------------------------------------
    */
    public function create(): View
    {
        $branches = $this->teamService->getActiveBranches();
        return view('tenants.users_management.team.create', compact('branches'));
    }

    /*
    |--------------------------------------------------------------------------
    | Store New Team
    |--------------------------------------------------------------------------
    */
    public function store(StoreTeamRequest $request): RedirectResponse
    {
        try {
            $this->teamService->createTeam($request->validated());

            return redirect()->to(tenant_route_name('teams.index'))
                ->with('success', 'تم إضافة المجموعة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء إضافة المجموعة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Edit Form
    |--------------------------------------------------------------------------
    */
    public function edit(Team $team): View
    {
        $team->load(['members', 'branch']);
        $branches = $this->teamService->getActiveBranches();
        $users = $this->teamService->getUsersByBranch($team->branch_id);

        return view('tenants.users_management.team.edit', compact(
            'team',
            'branches',
            'users'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | Update Team
    |--------------------------------------------------------------------------
    */
    public function update(UpdateTeamRequest $request, Team $team): RedirectResponse
    {
        try {
            $this->teamService->updateTeam($team, $request->validated());

            return redirect()->to(tenant_route_name('teams.index'))
                ->with('success', 'تم تحديث المجموعة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'حدث خطأ أثناء تحديث المجموعة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Show Team Details
    |--------------------------------------------------------------------------
    */
    public function show(Team $team): View
    {
        $team->load(['members', 'leaders', 'branch']);
        return view('tenants.users_management.team.show', compact('team'));
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Team
    |--------------------------------------------------------------------------
    */
    public function destroy(Team $team): RedirectResponse
    {
        try {
            $this->teamService->deleteTeam($team);

            return redirect()
                ->back()
                ->with('success', 'تم حذف المجموعة بنجاح');
        } catch (\Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'حدث خطأ أثناء حذف المجموعة');
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Users by Branch (AJAX)
    |--------------------------------------------------------------------------
    */
    public function getUsersByBranch($branchId): JsonResponse
    {
        try {
            $teamId = request()->query('team_id');

            $users = $this->teamService->getUsersByBranch($branchId, $teamId);

            return response()->json([
                'success' => true,
                'users' => $users
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب المستخدمين'
            ], 500);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Team Status
    |--------------------------------------------------------------------------
    */
    public function toggleStatus(Team $team): JsonResponse
    {
        try {
            $newStatus = $team->status === 'active' ? 'inactive' : 'active';
            $team->update(['status' => $newStatus]);

            $statusText = $newStatus === 'active' ? 'نشط' : 'غير نشط';

            return response()->json([
                'success' => true,
                'message' => 'تم تبديل الحالة بنجاح',
                'new_status' => $newStatus,
                'status_text' => $statusText
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء تبديل الحالة'
            ], 500);
        }
    }
}
