<?php

declare(strict_types=1);

namespace App\Services\Shared\ActivityLog;

use App\Traits\ActivityLogPresenterTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Activity;

class ActivityLogService
{
    use ActivityLogPresenterTrait;

    /**
     * Retrieve activity log summary counts for stat cards.
     *
     * @return array{total: int, today: int, this_week: int, this_month: int}
     */
    public function getStatistics(): array
    {
        return [
            'total' => Activity::query()->count(),
            'today' => Activity::query()->whereDate('created_at', today())->count(),
            'this_week' => Activity::query()->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])->count(),
            'this_month' => Activity::query()->whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * Build unified filter options for activity log name dropdowns.
     *
     * @return array<int, array{value: string, label: string}>
     */
    public function getLogNameFilterOptions(): array
    {
        return collect($this->activityLogNameMap())
            ->map(fn (string $label, string $value): array => [
                'value' => $value,
                'label' => $label,
            ])
            ->values()
            ->all();
    }

    /**
     * Apply validated/request filters to an Activity query.
     *
     * @param  array<string, mixed>  $filters
     */
    public function applyFilters(Builder $query, array $filters, bool $includeDatesAndSearch = false): Builder
    {
        if (isset($filters['user_id']) && $filters['user_id'] !== '' && $filters['user_id'] !== null) {
            $query->where('causer_id', $filters['user_id']);
        }

        if (isset($filters['log_name']) && $filters['log_name'] !== '' && $filters['log_name'] !== null) {
            $query->where('log_name', $filters['log_name']);
        }

        if (! $includeDatesAndSearch) {
            return $query;
        }

        $from = $filters['date_from'] ?? $filters['created_from'] ?? null;
        if (! empty($from)) {
            $query->whereDate('created_at', '>=', $from);
        }

        $to = $filters['date_to'] ?? $filters['created_to'] ?? null;
        if (! empty($to)) {
            $query->whereDate('created_at', '<=', $to);
        }

        $search = is_array($filters['search'] ?? null)
            ? ($filters['search']['value'] ?? null)
            : ($filters['search'] ?? null);

        if (! empty($search)) {
            $query->where(function (Builder $q) use ($search): void {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('log_name', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    /**
     * Render formatted log name badge HTML.
     */
    public function formatLogNameBadge(?string $logName): string
    {
        return '<span class="badge bg-primary">'.e($this->activityLogNameLabel($logName)).'</span>';
    }

    /**
     * Prepare all view data required for the activity show page.
     *
     * @return array{
     *     activity: Activity,
     *     arabicEvent: string,
     *     activityData: array<string, mixed>,
     *     changes: array<string, mixed>
     * }
     */
    public function getShowViewData(Activity $activity): array
    {
        if ($activity->subject_type && class_exists($activity->subject_type)) {
            $activity->loadMissing('subject');
        }

        $arabicEvent = $this->activityEventMap()[$activity->event] ?? ($activity->event ?? 'غير محدد');

        $activityData = [
            'event_arabic' => $arabicEvent,
            'event_badge_class' => $this->activityEventBadgeClass($activity->event),
            'log_name_arabic' => $this->activityLogNameLabel($activity->log_name),
            'subject_display' => $this->activitySubjectDisplay($activity),
            'connection_info' => $this->getActivityConnectionInfo($activity),
            'user_name' => $activity->causer->name ?? 'النظام',
            'formatted_date' => system_datetime($activity->created_at, 'غير محدد'),
        ];

        $changes = $this->prepareChangesData($activity);

        return [
            'activity' => $activity,
            'arabicEvent' => $arabicEvent,
            'activityData' => $activityData,
            'changes' => $changes,
        ];
    }

    /**
     * Build tenant exported archive listing and summary statistics.
     *
     * @return array{
     *     archives: Collection<int, array<string, mixed>>,
     *     totalCount: int,
     *     totalSizeMb: string,
     *     lastExportDate: string,
     *     lastExportDiff: string
     * }
     */
    public function getTenantArchivesData(): array
    {
        $disk = Storage::disk('tenant_public');
        $files = $disk->files('activity-logs');
        $totalBytes = 0;

        $archives = collect($files)->map(function (string $file) use ($disk, &$totalBytes): array {
            $lastModified = $disk->lastModified($file);
            $bytes = $disk->size($file);
            $totalBytes += $bytes;
            $timestamp = Carbon::createFromTimestamp($lastModified);

            return [
                'name' => basename($file),
                'path' => $file,
                'size' => $this->formatBytes($bytes),
                'bytes' => $bytes,
                'date' => $timestamp->format('Y-m-d H:i'),
                'formatted_date' => system_date($timestamp),
                'formatted_time' => $timestamp->copy()->locale('ar')->translatedFormat('h:i A'),
                'month' => $timestamp->format('F Y'),
            ];
        })->sortByDesc('date')->values();

        $firstArchive = $archives->first();

        return [
            'archives' => $archives,
            'totalCount' => $archives->count(),
            'totalSizeMb' => round($totalBytes / (1024 * 1024), 2).' MB',
            'lastExportDate' => $firstArchive ? system_date($firstArchive['date'], '--') : '--',
            'lastExportDiff' => $firstArchive ? Carbon::parse($firstArchive['date'])->locale('ar')->diffForHumans() : 'لا يوجد',
        ];
    }

    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $value = (float) $bytes;

        for ($i = 0; $value > 1024 && $i < count($units) - 1; $i++) {
            $value /= 1024;
        }

        return round($value, $precision).' '.$units[$i];
    }
}
