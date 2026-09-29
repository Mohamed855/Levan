<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Distribution;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\FinancialCollection\Target\CollectionTarget;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class CustomerDistribution extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'batch_id',
        'collector_id',
        'customers_count',
        'total_debt',
        'distribution_method',
        'distribution_criteria',
        'customer_ids',
        'notes',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'customers_count' => 'integer',
        'total_debt' => 'decimal:2',
        'distribution_criteria' => 'array',
        'customer_ids' => 'array',
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
            ->logFillable()
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('customer_distributions')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    private function getEventDescription(string $eventName): string
    {
        try {
            $collectorName = $this->collector->name ?? 'محصل غير محدد';
            $customersCount = $this->customers_count ?? 0;

            $descriptions = [
                'created' => "تم توزيع {$customersCount} عميل على المحصل: {$collectorName}",
                'updated' => "تم تحديث توزيع العملاء للمحصل: {$collectorName}",
                'deleted' => "تم حذف توزيع العملاء للمحصل: {$collectorName}",
                'restored' => "تم استعادة توزيع العملاء للمحصل: {$collectorName}",
            ];

            $description = $descriptions[$eventName] ?? "تم {$eventName} توزيع العملاء";

            if ($eventName === 'created') {
                $description .= " - المبلغ الإجمالي: " . number_format($this->total_debt, 2) . " ريال";
                // التعديل هنا
                $description .= " - طريقة التوزيع: " . $this->distribution_method_label;
            }


            return $description;
        } catch (\Exception $e) {
            return "تم {$eventName} توزيع العملاء";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the collector (relationship manager) that owns the distribution.
     */
    public function collector()
    {
        return $this->belongsTo(User::class, 'collector_id');
    }

    /**
     * Get the user who created the distribution.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the customers included in this distribution.
     */
    public function customers()
    {
        return Customer::whereIn('id', $this->customer_ids ?? []);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the distribution method label.
     */
    public function getDistributionMethodLabelAttribute(): string
    {
        return self::getDistributionMethodLabel($this->distribution_method);
    }
    /**
     * Get formatted total debt.
     */
    public function getFormattedTotalDebtAttribute(): string
    {
        return number_format($this->total_debt, 2) . ' ريال';
    }

    /**
     * Get formatted distribution date.
     */
    public function getFormattedDistributionDateAttribute(): string
    {
        return $this->created_at?->locale('ar')->translatedFormat('d F Y - H:i') ?? 'غير محدد';
    }

    /**
     * Get distribution efficiency score.
     */
    public function getEfficiencyScoreAttribute(): float
    {
        if ($this->customers_count <= 0 || $this->total_debt <= 0) {
            return 0;
        }

        // Calculate efficiency based on debt per customer ratio
        $avgDebtPerCustomer = $this->total_debt / $this->customers_count;

        // Normalize score (this is a simplified calculation)
        $score = min(100, ($avgDebtPerCustomer / 50000) * 100);

        return round($score, 2);
    }

    /**
     * Get distribution status based on various factors.
     */
    public function getDistributionStatusAttribute(): array
    {
        $status = 'active';
        $statusClass = 'bg-label-success';
        $statusText = 'نشط';

        // Check if any customers have been reassigned
        $currentAssignments = Customer::whereIn('id', $this->customer_ids ?? [])
            ->where('relationship_manager_id', $this->collector_id)
            ->count();

        if ($currentAssignments < $this->customers_count) {
            $reassignedCount = $this->customers_count - $currentAssignments;

            if ($currentAssignments === 0) {
                $status = 'fully_reassigned';
                $statusClass = 'bg-label-danger';
                $statusText = 'تم إعادة التوزيع بالكامل';
            } else {
                $status = 'partially_reassigned';
                $statusClass = 'bg-label-warning';
                $statusText = "تم إعادة توزيع {$reassignedCount} عميل";
            }
        }

        return [
            'status' => $status,
            'class' => $statusClass,
            'text' => $statusText,
            'current_assignments' => $currentAssignments,
            'original_count' => $this->customers_count,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to filter by collector.
     */
    public function scopeByCollector($query, $collectorId)
    {
        return $query->where('collector_id', $collectorId);
    }

    /**
     * Scope a query to filter by distribution method.
     */
    public function scopeByMethod($query, $method)
    {
        return $query->where('distribution_method', $method);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope a query to get recent distributions.
     */
    public function scopeRecent($query, $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope a query to get active distributions (not reassigned).
     */
    public function scopeActive($query)
    {
        return $query->whereHas('collector', function ($collectorQuery) {
            $collectorQuery->whereHas('managedCustomers', function ($customerQuery) {
                // This is a simplified check - in practice, you'd want to check
                // if the specific customers from this distribution are still assigned
            });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */

    /**
     * Get the distribution method label (static method).
     */
    public static function getDistributionMethodLabel(string $method): string
    {
        $methods = [
            'balanced_amount' => 'التوزيع المتوازن حسب المبالغ',
            'balanced_count' => 'التوزيع المتوازن حسب العدد',
            'hybrid_balanced' => 'التوزيع المتوازن المختلط',
        ];

        return $methods[$method] ?? 'غير محدد';
    }

    /**
     * Check if this distribution is still valid (customers not reassigned).
     */
    public function isValid(): bool
    {
        if (empty($this->customer_ids)) {
            return false;
        }

        $currentAssignments = Customer::whereIn('id', $this->customer_ids)
            ->where('relationship_manager_id', $this->collector_id)
            ->count();

        return $currentAssignments === $this->customers_count;
    }

    /**
     * Get customers that have been reassigned since this distribution.
     */
    public function getReassignedCustomers()
    {
        if (empty($this->customer_ids)) {
            return collect();
        }

        return Customer::whereIn('id', $this->customer_ids)
            ->where('relationship_manager_id', '!=', $this->collector_id)
            ->orWhereNull('relationship_manager_id')
            ->with(['company:id,name', 'portfolio:id,name', 'relationshipManager:id,name'])
            ->get();
    }

    /**
     * Get summary statistics for this distribution.
     */
    public function getSummaryStatistics(): array
    {
        $customerIds = $this->customer_ids ?? [];

        if (empty($customerIds)) {
            return [
                'total_customers' => 0,
                'current_assigned' => 0,
                'reassigned' => 0,
                'total_debt' => 0,
                'current_debt' => 0,
            ];
        }

        $allCustomers = Customer::whereIn('id', $customerIds)->get();
        $currentlyAssigned = $allCustomers->where('relationship_manager_id', $this->collector_id);

        return [
            'total_customers' => $allCustomers->count(),
            'current_assigned' => $currentlyAssigned->count(),
            'reassigned' => $allCustomers->count() - $currentlyAssigned->count(),
            'total_debt' => $this->total_debt,
            'current_debt' => $currentlyAssigned->sum('debt_amount'),
            'reassigned_debt' => $allCustomers->sum('debt_amount') - $currentlyAssigned->sum('debt_amount'),
        ];
    }

    /**
     * Create distribution summary for reporting.
     */
    public function createDistributionSummary(): array
    {
        return [
            'id' => $this->id,
            'collector' => [
                'id' => $this->collector_id,
                'name' => $this->collector->name ?? 'غير محدد',
            ],
            'distribution' => [
                'method' => $this->distribution_method,
                'method_label' => $this->distribution_method_label,
                'customers_count' => $this->customers_count,
                'total_debt' => $this->total_debt,
                'formatted_debt' => $this->formatted_total_debt,
                'date' => $this->formatted_distribution_date,
            ],
            'status' => $this->distribution_status,
            'efficiency_score' => $this->efficiency_score,
            'created_by' => [
                'id' => $this->created_by,
                'name' => $this->createdBy->name ?? 'غير محدد',
            ],
            'statistics' => $this->getSummaryStatistics(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Get associated targets created with this distribution
    |--------------------------------------------------------------------------
    */
    public function createdTargets()
    {
        return $this->hasMany(CollectionTarget::class, 'added_by', 'created_by')
            ->where('created_at', '>=', $this->created_at)
            ->where('created_at', '<=', $this->created_at->addMinutes(5));
    }
}
