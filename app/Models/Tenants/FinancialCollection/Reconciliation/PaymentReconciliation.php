<?php

namespace App\Models\Tenants\FinancialCollection\Reconciliation;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class PaymentReconciliation extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    /*
    |--------------------------------------------------------------------------
    | Table Name
    |--------------------------------------------------------------------------
    */
    protected $table = 'payment_reconciliations';

    /*
    |--------------------------------------------------------------------------
    | Fillable Fields
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'company_id',
        'portfolio_id',
        'reconciliation_month',
        'file_path',
        'total_rows',
        'matched_count',
        'unmatched_count',
        'discrepancy_count',
        'error_count',
        'system_only_count',
        'total_amount',
        'matched_amount',
        'unmatched_amount',
        'discrepancy_amount',
        'status',
        'processed_at',
        'created_by',
    ];

    /*
    |--------------------------------------------------------------------------
    | Casts
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'reconciliation_month' => 'date',
        'total_amount' => 'decimal:2',
        'matched_amount' => 'decimal:2',
        'unmatched_amount' => 'decimal:2',
        'discrepancy_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Configuration
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'company_id',
                'portfolio_id',
                'reconciliation_month',
                'status',
                'total_rows',
                'matched_count',
                'unmatched_count',
                'discrepancy_count',
                'error_count',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('payment_reconciliations')
            ->setDescriptionForEvent(fn ($e) => $this->getEventDescription($e))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Event Description for Activity Log
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        $companyName = $this->company->name ?? 'شركة محذوفة';
        $portfolioName = $this->portfolio->name ?? 'محفظة محذوفة';
        $month = $this->reconciliation_month?->format('Y-m') ?? 'غير محدد';

        return match ($eventName) {
            'created' => "أنشأ مطابقة سدادات للشركة: {$companyName} | المحفظة: {$portfolioName} | الشهر: {$month}",
            'updated' => "عدّل مطابقة سدادات للشركة: {$companyName}".$this->getChangesSummary(),
            'deleted' => "حذف مطابقة سدادات للشركة: {$companyName} | الشهر: {$month}",
            default => "نشاط على مطابقة سدادات للشركة: {$companyName}",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Changes Summary for Activity Log
    |--------------------------------------------------------------------------
    */
    private function getChangesSummary(): string
    {
        $changes = [];

        $fieldNames = [
            'status' => 'الحالة',
            'matched_count' => 'عدد المتطابقات',
            'unmatched_count' => 'عدد غير المسجلة',
            'discrepancy_count' => 'عدد الاختلافات',
            'error_count' => 'عدد الأخطاء',
        ];

        foreach ($this->getDirty() as $field => $newValue) {
            if ($field === 'updated_at') {
                continue;
            }

            if (! isset($fieldNames[$field])) {
                continue;
            }

            $fieldLabel = $fieldNames[$field];
            $oldValue = $this->getOriginal($field);

            $oldFormatted = $this->formatValue($field, $oldValue);
            $newFormatted = $this->formatValue($field, $newValue);

            if ($oldFormatted !== $newFormatted) {
                $changes[] = "{$fieldLabel}: {$newFormatted} ← {$oldFormatted}";
            }
        }

        return $changes ? ' ('.implode(' | ', $changes).')' : '';
    }

    /*
    |--------------------------------------------------------------------------
    | Format Value for Activity Log
    |--------------------------------------------------------------------------
    */
    private function formatValue(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '---';
        }

        return match ($field) {
            'status' => match ($value) {
                'pending' => 'قيد الانتظار',
                'processing' => 'جاري المعالجة',
                'completed' => 'مكتمل',
                'failed' => 'فشل',
                default => 'غير محدد'
            },
            default => (string) $value
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Company Relationship
    |--------------------------------------------------------------------------
    */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Portfolio Relationship
    |--------------------------------------------------------------------------
    */
    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Created By Relationship
    |--------------------------------------------------------------------------
    */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Items Relationship
    |--------------------------------------------------------------------------
    */
    public function items()
    {
        return $this->hasMany(PaymentReconciliationItem::class, 'reconciliation_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Completed
    |--------------------------------------------------------------------------
    */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Title
    |--------------------------------------------------------------------------
    */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Processing
    |--------------------------------------------------------------------------
    */
    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    /*
    |--------------------------------------------------------------------------
    | Scope For Company
    |--------------------------------------------------------------------------
    */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope For Portfolio
    |--------------------------------------------------------------------------
    */
    public function scopeForPortfolio($query, $portfolioId)
    {
        return $query->where('portfolio_id', $portfolioId);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope For Month
    |--------------------------------------------------------------------------
    */
    public function scopeForMonth($query, $month)
    {
        return $query->where('reconciliation_month', $month);
    }

    /*
    |--------------------------------------------------------------------------
    | Scope Recent
    |--------------------------------------------------------------------------
    */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /*
    |--------------------------------------------------------------------------
    | Get Status Label Attribute
    |--------------------------------------------------------------------------
    */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'قيد الانتظار',
            'processing' => 'جاري المعالجة',
            'completed' => 'مكتمل',
            'failed' => 'فشل',
            default => $this->status
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Status Class Attribute
    |--------------------------------------------------------------------------
    */
    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-label-warning',
            'processing' => 'bg-label-info',
            'completed' => 'bg-label-success',
            'failed' => 'bg-label-danger',
            default => 'bg-secondary'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Formatted Month Attribute
    |--------------------------------------------------------------------------
    */
    public function getFormattedMonthAttribute(): string
    {
        return $this->reconciliation_month?->locale('ar')->translatedFormat('F Y') ?? 'غير محدد';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Match Percentage Attribute
    |--------------------------------------------------------------------------
    */
    public function getMatchPercentageAttribute(): float
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        return round(($this->matched_count / $this->total_rows) * 100, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Success Rate Attribute
    |--------------------------------------------------------------------------
    */
    public function getSuccessRateAttribute(): float
    {
        if ($this->total_rows == 0) {
            return 0;
        }

        $successfulRows = $this->matched_count + $this->unmatched_count;

        return round(($successfulRows / $this->total_rows) * 100, 2);
    }
}
