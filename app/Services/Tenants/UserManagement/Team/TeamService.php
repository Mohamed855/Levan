<?php

namespace App\Services\Tenants\UserManagement\Team;

use App\Models\Tenants\UserMangment\Team\Team;
use App\Models\Tenants\UserMangment\User\User;
use Exception;
use Illuminate\Support\Facades\DB;

class TeamService
{
    /*
    |--------------------------------------------------------------------------
    | Create Team
    |--------------------------------------------------------------------------
    */
    public function createTeam(array $data): Team
    {
        return DB::transaction(function () use ($data) {
            $team = Team::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => 'active', // ✅ دائماً active
            ]);

            if (isset($data['members']) && is_array($data['members'])) {
                $this->assignMembers($team, $data['members'], $data['leaders'] ?? []);
            }

            return $team->fresh(['members', 'leaders']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Update Team
    |--------------------------------------------------------------------------
    */
    public function updateTeam(Team $team, array $data): Team
    {
        return DB::transaction(function () use ($team, $data) {
            $team->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? $team->status,
            ]);

            if (isset($data['members'])) {
                $this->assignMembers($team, $data['members'], $data['leaders'] ?? []);
            }

            return $team->fresh(['members', 'leaders']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Assign Members
    |--------------------------------------------------------------------------
    */
    protected function assignMembers(Team $team, array $memberIds, array $leaderIds = []): void
    {
        User::where('team_id', $team->id)->update([
            'team_id' => null,
            'is_team_leader' => false,
        ]);

        $usersWithOtherTeams = User::whereIn('id', $memberIds)
            ->whereNotNull('team_id')
            ->where('team_id', '!=', $team->id)
            ->exists();

        if ($usersWithOtherTeams) {
            throw new Exception('بعض المستخدمين مرتبطون بالفعل بفرق أخرى');
        }

        // إضافة الأعضاء الجدد
        foreach ($memberIds as $userId) {
            $isLeader = in_array($userId, $leaderIds);

            User::where('id', $userId)->update([
                'team_id' => $team->id,
                'is_team_leader' => $isLeader,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Team
    |--------------------------------------------------------------------------
    */
    public function deleteTeam(Team $team): bool
    {
        return DB::transaction(function () use ($team) {
            User::where('team_id', $team->id)->update([
                'team_id' => null,
                'is_team_leader' => false,
            ]);

            return $team->delete();
        });
    }

    public function updateStatus(Team $team, string $status): Team
    {
        return DB::transaction(function () use ($team, $status): Team {
            $team->update(['status' => $status]);

            return $team->refresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Users
    |--------------------------------------------------------------------------
    */
    public function getAvailableUsers(?int $excludeTeamId = null)
    {
        $query = User::where('status', 'active')
            ->select('id', 'name', 'email', 'team_id', 'is_team_leader');

        if ($excludeTeamId) {
            $query->where(function ($q) use ($excludeTeamId) {
                $q->whereNull('team_id')
                    ->orWhere('team_id', $excludeTeamId);
            });
        } else {
            $query->whereNull('team_id');
        }

        return $query->orderBy('name')->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Team Statistics
    |--------------------------------------------------------------------------
    */
    public function getTeamStatistics(): array
    {
        return [
            'total' => Team::count(),
            'active' => Team::where('status', 'active')->count(),
            'inactive' => Team::where('status', 'inactive')->count(),
        ];
    }
}
