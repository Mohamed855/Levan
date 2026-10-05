<?php

namespace App\Services\Tenants\FinancialCollection\Target;

use App\Data\Tenants\FinancialCollection\Target\CreateCollectionTargetDTO;
use App\Data\Tenants\FinancialCollection\Target\UpdateCollectionTargetDTO;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class CollectionTargetService
{
    /*
    |--------------------------------------------------------------------------
    | Collection Target Creation
    |--------------------------------------------------------------------------
    */
   public function createCollectionTarget(CreateCollectionTargetDTO $dto): CollectionTarget
{
    return DB::transaction(function () use ($dto) {
        $data = $this->cleanTargetPeriodData(
            array_merge(
                $dto->toArray(),
                [
                    'status' => $dto->status ?? 'active',
                    'added_by' => Auth::id(),
                ]
            )
        );

        return CollectionTarget::create($data);
    });
}


    /*
    |--------------------------------------------------------------------------
    | Collection Target Update
    |--------------------------------------------------------------------------
    */
    public function updateCollectionTarget(CollectionTarget $collectionTarget, UpdateCollectionTargetDTO $dto): CollectionTarget
    {
        return DB::transaction(function () use ($collectionTarget, $dto) {
            $data = $dto->toArray();
            $data = $this->cleanTargetPeriodData($data);
            $collectionTarget->update($data);
            return $collectionTarget->fresh();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Clean Target Period Data
    |--------------------------------------------------------------------------
    */
    private function cleanTargetPeriodData(array $data): array
    {
        $targetType = $data['target_type'] ?? 'monthly';

        switch ($targetType) {
            case 'monthly':
                $data['custom_start_date'] = null;
                $data['custom_end_date'] = null;
                break;

            case 'yearly':
                $data['target_month'] = null;
                $data['custom_start_date'] = null;
                $data['custom_end_date'] = null;
                break;

            case 'custom':
                $data['target_month'] = null;
                $data['target_year'] = null;
                break;
        }

        return $data;
    }

    /*
    |--------------------------------------------------------------------------
    | Collection Target Deletion
    |--------------------------------------------------------------------------
    */
    public function deleteCollectionTarget(CollectionTarget $collectionTarget): bool
    {
        return DB::transaction(function () use ($collectionTarget) {
            return $collectionTarget->delete();
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Delete Selected Collection Targets
    |--------------------------------------------------------------------------
    */
    public function deleteSelectedCollectionTargets(array $targetIds): int
    {
        return DB::transaction(function () use ($targetIds) {
            $targets = CollectionTarget::whereIn('id', $targetIds)->get();

            if ($targets->isEmpty()) {
                throw new \Exception('لم يتم العثور على أي أهداف للحذف');
            }

            $deletedCount = 0;
            foreach ($targets as $target) {
                if ($target->delete()) {
                    $deletedCount++;
                }
            }

            if ($deletedCount === 0) {
                throw new \Exception('فشل في حذف الأهداف المحددة');
            }

            return $deletedCount;
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Get Unique Users With Latest Target
    |--------------------------------------------------------------------------
    */
    public function getUniqueUsersWithLatestTarget(): EloquentCollection
    {
        // جلب آخر هدف لكل مستخدم
        $latestTargets = DB::table('collection_targets as ct1')
            ->select('ct1.*')
            ->leftJoin('collection_targets as ct2', function ($join) {
                $join->on('ct1.user_id', '=', 'ct2.user_id')
                    ->whereRaw('ct1.created_at < ct2.created_at');
            })
            ->whereNull('ct2.id')
            ->whereNull('ct1.deleted_at')
            ->get();

        $targetIds = $latestTargets->pluck('id')->toArray();

        return CollectionTarget::whereIn('id', $targetIds)
            ->with([
                'user:id,name,email,phone',
                'addedBy:id,name'
            ])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Target Details With Progress
    |--------------------------------------------------------------------------
    */
    public function getUserTargetDetailsWithProgress(int $userId): array
    {
        $currentTarget = $this->getUserCurrentTarget($userId);
        $allTargets = $this->getUserAllTargets($userId);

        $currentProgress = null;
        if ($currentTarget) {
            $currentProgress = $this->calculateTargetProgress($currentTarget);
        }

        $targetsHistory = $allTargets->map(function ($target) {
            return [
                'id' => $target->id,
                'target_type' => $target->target_type_label,
                'period' => $this->getTargetPeriodLabel($target),
                'target_amount' => $target->formatted_target_amount,
                'status' => $target->status_label,
                'progress' => $this->calculateTargetProgress($target),
                'created_at' => $target->created_at->locale('ar')->translatedFormat('d F Y')
            ];
        });

        return [
            'user' => $currentTarget ? $currentTarget->user : null,
            'current_target' => $currentTarget ? [
                'id' => $currentTarget->id,
                'target_type' => $currentTarget->target_type_label,
                'period' => $this->getTargetPeriodLabel($currentTarget),
                'target_amount' => $currentTarget->target_amount,
                'formatted_target_amount' => $currentTarget->formatted_target_amount,
                'status' => $currentTarget->status_label,
                'progress' => $currentProgress,
                'created_at' => $currentTarget->created_at->locale('ar')->translatedFormat('d F Y')
            ] : null,
            'targets_history' => $targetsHistory,
            'statistics' => [
                'total_targets' => $allTargets->count(),
                'active_targets' => $allTargets->where('status', 'active')->count(),
                'total_collected' => $this->getUserTotalCollected($userId),
            ]
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Current Target
    |--------------------------------------------------------------------------
    */
    private function getUserCurrentTarget(int $userId): ?CollectionTarget
    {
        return CollectionTarget::where('user_id', $userId)
            ->with(['user:id,name,email,phone', 'addedBy:id,name'])
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /*
    |--------------------------------------------------------------------------
    | Get User All Targets
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Calculate Target Progress
    |--------------------------------------------------------------------------
    */
    public function calculateTargetProgress(CollectionTarget $target): array
    {
        $startDate = $this->getTargetStartDate($target);
        $endDate = $this->getTargetEndDate($target);

        // جلب التحصيلات المعتمدة للمستخدم في فترة الهدف
        $collectedAmount = Collection::where('added_by', $target->user_id)
            ->where('status', 'approved')
            ->whereBetween('collection_date', [$startDate, $endDate])
            ->sum('collected_amount');

        $targetAmount = $target->target_amount;
        $percentage = $targetAmount > 0 ? min(($collectedAmount / $targetAmount) * 100, 100) : 0;
        $remaining = max($targetAmount - $collectedAmount, 0);

        return [
            'collected_amount' => $collectedAmount,
            'formatted_collected' => number_format($collectedAmount, 2),
            'target_amount' => $targetAmount,
            'formatted_target' => number_format($targetAmount, 2),
            'remaining_amount' => $remaining,
            'formatted_remaining' => number_format($remaining, 2),
            'percentage' => round($percentage, 2),
            'status' => $this->getProgressStatus($percentage)
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target Start Date
    |--------------------------------------------------------------------------
    */
    private function getTargetStartDate(CollectionTarget $target): string
    {
        switch ($target->target_type) {
            case 'monthly':
                return Carbon::create($target->target_year, $target->target_month, 1)->startOfMonth()->toDateString();

            case 'yearly':
                return Carbon::create($target->target_year, 1, 1)->startOfYear()->toDateString();

            case 'custom':
                return $target->custom_start_date;

            default:
                return now()->startOfMonth()->toDateString();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target End Date
    |--------------------------------------------------------------------------
    */
    private function getTargetEndDate(CollectionTarget $target): string
    {
        switch ($target->target_type) {
            case 'monthly':
                return Carbon::create($target->target_year, $target->target_month, 1)->endOfMonth()->toDateString();

            case 'yearly':
                return Carbon::create($target->target_year, 12, 31)->endOfYear()->toDateString();

            case 'custom':
                return $target->custom_end_date;

            default:
                return now()->endOfMonth()->toDateString();
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Progress Status
    |--------------------------------------------------------------------------
    */
    private function getProgressStatus(float $percentage): string
    {
        if ($percentage >= 100) return 'completed';
        if ($percentage >= 75) return 'excellent';
        if ($percentage >= 50) return 'good';
        if ($percentage >= 25) return 'fair';
        return 'poor';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Target Period Label
    |--------------------------------------------------------------------------
    */
    private function getTargetPeriodLabel(CollectionTarget $target): string
    {
        switch ($target->target_type) {
            case 'monthly':
                $months = [
                    '',
                    'يناير',
                    'فبراير',
                    'مارس',
                    'أبريل',
                    'مايو',
                    'يونيو',
                    'يوليو',
                    'أغسطس',
                    'سبتمبر',
                    'أكتوبر',
                    'نوفمبر',
                    'ديسمبر'
                ];
                return $months[$target->target_month] . ' ' . $target->target_year;

            case 'yearly':
                return 'سنة ' . $target->target_year;

            case 'custom':
                return Carbon::parse($target->custom_start_date)->format('Y-m-d') . ' - ' .
                    Carbon::parse($target->custom_end_date)->format('Y-m-d');

            default:
                return 'غير محدد';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get User Total Collected
    |--------------------------------------------------------------------------
    */
    private function getUserTotalCollected(int $userId): float
    {
        return Collection::where('added_by', $userId)
            ->where('status', 'approved')
            ->sum('collected_amount');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Targets Statistics
    |--------------------------------------------------------------------------
    */
    public function getTargetsStatistics(): array
    {
        $uniqueUsers = $this->getUniqueUsersWithLatestTarget();

        return [
            'total_users' => $uniqueUsers->count(),
            'monthly_targets' => $uniqueUsers->where('target_type', 'monthly')->count(),
            'yearly_targets' => $uniqueUsers->where('target_type', 'yearly')->count(),
            'custom_targets' => $uniqueUsers->where('target_type', 'custom')->count(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get Active Users
    |--------------------------------------------------------------------------
    */
    public function getActiveUsers()
    {
        return User::select('id', 'name', 'email', 'phone', 'team_id')
            ->where('status', 'active')
            ->orderBy('name')
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Years
    |--------------------------------------------------------------------------
    */
    public function getAvailableYears(): array
    {
        $currentYear = (int) date('Y');
        $years = [];

        for ($year = $currentYear; $year <= $currentYear + 5; $year++) {
            $years[] = ['value' => $year, 'label' => $year];
        }

        return $years;
    }

    /*
    |--------------------------------------------------------------------------
    | Get Available Months
    |--------------------------------------------------------------------------
    */
    public function getAvailableMonths(): array
    {
        return [
            ['value' => 1, 'label' => 'يناير'],
            ['value' => 2, 'label' => 'فبراير'],
            ['value' => 3, 'label' => 'مارس'],
            ['value' => 4, 'label' => 'أبريل'],
            ['value' => 5, 'label' => 'مايو'],
            ['value' => 6, 'label' => 'يونيو'],
            ['value' => 7, 'label' => 'يوليو'],
            ['value' => 8, 'label' => 'أغسطس'],
            ['value' => 9, 'label' => 'سبتمبر'],
            ['value' => 10, 'label' => 'أكتوبر'],
            ['value' => 11, 'label' => 'نوفمبر'],
            ['value' => 12, 'label' => 'ديسمبر']
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Toggle Collection Target Status
    |--------------------------------------------------------------------------
    */
    public function toggleCollectionTargetStatus(CollectionTarget $collectionTarget): CollectionTarget
    {
        return DB::transaction(function () use ($collectionTarget) {
            $newStatus = $collectionTarget->status === 'active' ? 'inactive' : 'active';
            $collectionTarget->update(['status' => $newStatus]);
            return $collectionTarget->fresh();
        });
    }

    public function getUserAllTargets(int $userId)
{
    return CollectionTarget::where('user_id', $userId)
        ->with(['user:id,name,email,phone', 'addedBy:id,name'])
        ->orderBy('created_at', 'desc')
        ->get();
}
}
