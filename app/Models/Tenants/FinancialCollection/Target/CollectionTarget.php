<?php

namespace App\Models\Tenants\FinancialCollection\Target;

use App\Models\Tenants\Branch\Branch;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Carbon\Carbon;

class CollectionTarget extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'branch_id',
        'user_id',
        'team_ids',
        'target_type',
        'target_month',
        'target_year',
        'custom_start_date',
        'custom_end_date',
        'target_amount',
        'status',
        'added_by'
    ];

    protected $casts = [
        'target_amount' => 'decimal:2',
        'target_month' => 'integer',
        'target_year' => 'integer',
        'team_ids' => 'array',
        'custom_start_date' => 'date',
        'custom_end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime'
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('collection_targets')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Event Descriptions
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        $userName = $this->user->name ?? 'غير محدد';
        $branchName = $this->branch->name ?? 'غير محدد';
        $targetType = $this->target_type_label;
        $period = $this->getTargetPeriodFormatted();
        $amount = number_format($this->target_amount, 2);

        $descriptions = [
            'created' => "تم إنشاء هدف تحصيل جديد للموظف: {$userName} في الفرع: {$branchName} | نوع الهدف: {$targetType} | الفترة: {$period} | المبلغ المستهدف: {$amount} ريال | الحالة: {$this->status_label}",

            'updated' => "تم تحديث هدف التحصيل للموظف: {$userName} في الفرع: {$branchName} | نوع الهدف: {$targetType} | الفترة: {$period} | المبلغ المستهدف: {$amount} ريال",

            'deleted' => "تم حذف هدف التحصيل للموظف: {$userName} في الفرع: {$branchName} | نوع الهدف: {$targetType} | الفترة: {$period} | المبلغ المستهدف: {$amount} ريال",

            'restored' => "تم استعادة هدف التحصيل للموظف: {$userName} في الفرع: {$branchName} | نوع الهدف: {$targetType} | الفترة: {$period} | المبلغ المستهدف: {$amount} ريال",
        ];

        $description = $descriptions[$eventName] ?? "عملية على هدف التحصيل للموظف: {$userName} في الفرع: {$branchName} | نوع الهدف: {$targetType} | الفترة: {$period}";

        if ($eventName === 'updated' && $this->isDirty()) {
            $description .= $this->getChangesSummary();
        }

        return $description;
    }

    /*
    |--------------------------------------------------------------------------
    | Activity Log Change Details
    |--------------------------------------------------------------------------
    */
    private function getChangesSummary(): string
    {
        $changes = [];
        $fieldNames = [
            'branch_id' => 'الفرع',
            'user_id' => 'الموظف',
            'target_type' => 'نوع الهدف',
            'target_month' => 'الشهر المستهدف',
            'target_year' => 'السنة المستهدفة',
            'custom_start_date' => 'تاريخ البداية',
            'custom_end_date' => 'تاريخ النهاية',
            'target_amount' => 'المبلغ المستهدف',
            'status' => 'الحالة',
        ];

        foreach ($this->getDirty() as $field => $newValue) {
            if ($field === 'updated_at') continue;

            if (!isset($fieldNames[$field])) continue;

            $fieldLabel = $fieldNames[$field];
            $oldValue = $this->getOriginal($field);

            $oldFormatted = $this->formatValue($field, $oldValue);
            $newFormatted = $this->formatValue($field, $newValue);

            if ($oldFormatted !== $newFormatted) {
                $changes[] = "{$fieldLabel}: {$oldFormatted} ← {$newFormatted}";
            }
        }

        return $changes ? ' (' . implode(' | ', $changes) . ')' : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Field Value Formatting
    |--------------------------------------------------------------------------
    */
    private function formatValue(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '---';
        }

        switch ($field) {
            case 'status':
                return $value === 'active' ? 'نشط' : 'غير نشط';

            case 'target_type':
                return match ($value) {
                    'monthly' => 'شهري',
                    'yearly' => 'سنوي',
                    'custom' => 'فترة مخصصة',
                    default => 'غير محدد'
                };

            case 'target_month':
                return $this->getMonthName((int)$value);

            case 'target_amount':
                return number_format((float)$value, 2) . ' ريال';

            case 'branch_id':
                $branch = Branch::find($value);
                return $branch ? $branch->name : 'غير محدد';

            case 'user_id':
                $user = User::find($value);
                return $user ? $user->name : 'غير محدد';

            case 'custom_start_date':
            case 'custom_end_date':
                return Carbon::parse($value)->format('Y-m-d');

            default:
                return (string) $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Helper: Get Target Period Formatted
    |--------------------------------------------------------------------------
    */
    private function getTargetPeriodFormatted(): string
    {
        return match ($this->target_type) {
            'monthly' => $this->target_month && $this->target_year
                ? $this->getMonthName($this->target_month) . ' ' . $this->target_year
                : 'غير محدد',
            'yearly' => $this->target_year ? (string)$this->target_year : 'غير محدد',
            'custom' => $this->custom_start_date && $this->custom_end_date
                ? $this->custom_start_date->format('Y-m-d') . ' إلى ' . $this->custom_end_date->format('Y-m-d')
                : 'غير محدد',
            default => 'غير محدد'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    public function getFormattedTargetAmountAttribute(): string
    {
        return number_format($this->target_amount, 2);
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status === 'active' ? 'نشط' : 'غير نشط';
    }

    public function getStatusClassAttribute(): string
    {
        return $this->status === 'active' ? 'bg-label-success' : 'bg-label-danger';
    }

    public function getTargetTypeLabelAttribute(): string
    {
        return match ($this->target_type) {
            'monthly' => 'شهري',
            'yearly' => 'سنوي',
            'custom' => 'فترة مخصصة',
            default => 'غير محدد'
        };
    }

    public function getTargetPeriodFormattedAttribute(): string
    {
        return match ($this->target_type) {
            'monthly' => $this->target_month && $this->target_year
                ? $this->getMonthName($this->target_month) . ' ' . $this->target_year
                : 'غير محدد',
            'yearly' => $this->target_year ? (string)$this->target_year : 'غير محدد',
            'custom' => $this->custom_start_date && $this->custom_end_date
                ? $this->custom_start_date->format('d/m/Y') . ' - ' . $this->custom_end_date->format('d/m/Y')
                : 'غير محدد',
            default => 'غير محدد'
        };
    }

    public function getTargetPeriodDetailsAttribute(): string
    {
        return $this->target_type_label . ' (' . $this->target_period_formatted . ')';
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'يناير',
            2 => 'فبراير',
            3 => 'مارس',
            4 => 'أبريل',
            5 => 'مايو',
            6 => 'يونيو',
            7 => 'يوليو',
            8 => 'أغسطس',
            9 => 'سبتمبر',
            10 => 'أكتوبر',
            11 => 'نوفمبر',
            12 => 'ديسمبر'
        ];

        return $months[$month] ?? 'غير محدد';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeInactive($query)
    {
        return $query->where('status', 'inactive');
    }

    public function scopeByBranch($query, $branchId)
    {
        return $query->where('branch_id', $branchId);
    }

    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeByTargetType($query, $targetType)
    {
        return $query->where('target_type', $targetType);
    }

    public function scopeMonthly($query)
    {
        return $query->where('target_type', 'monthly');
    }

    public function scopeYearly($query)
    {
        return $query->where('target_type', 'yearly');
    }

    public function scopeCustom($query)
    {
        return $query->where('target_type', 'custom');
    }
}
