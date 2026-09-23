<?php

namespace App\Models\Tenants\BusinessCenter\Contract;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\GeneralSetting\DebtType\SettingsDebtType;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Contract extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /*
    |--------------------------------------------------------------------------
    | Model Configuration
    |--------------------------------------------------------------------------
    */

    protected $fillable = [
        'company_id',
        'contract_number',
        'start_date',
        'duration_months',
        'end_date',
        'auto_renewal',
        'commission_type',
        'status',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'auto_renewal' => 'boolean',
        'duration_months' => 'integer',
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
            ->useLogName('contracts')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /*
    |--------------------------------------------------------------------------
    | Get Event Description
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        try {
            $contractInfo = $this->contract_number ?? "العقد #{$this->id}";
            $companyName = $this->company->name ?? 'شركة غير محددة';

            $descriptions = [
                'created' => "تم إنشاء عقد جديد: {$contractInfo} للشركة: {$companyName}",
                'updated' => "تم تحديث العقد: {$contractInfo} للشركة: {$companyName}",
                'deleted' => "تم حذف العقد: {$contractInfo} للشركة: {$companyName}",
                'restored' => "تم استعادة العقد: {$contractInfo} للشركة: {$companyName}",
            ];

            $description = $descriptions[$eventName] ?? "تم {$eventName} العقد: {$contractInfo}";

            // إضافة تفاصيل التغييرات للتحديث
            if ($eventName === 'updated' && $this->isDirty()) {
                $description .= $this->getChangesSummary();
            }

            return $description;
        } catch (\Exception $e) {
            return "تم {$eventName} عقد";
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Get Changes Summary
    |--------------------------------------------------------------------------
    */
    private function getChangesSummary(): string
    {
        $changes = [];
        $fieldNames = [
            'company_id' => 'الشركة',
            'contract_number' => 'رقم العقد',
            'start_date' => 'تاريخ البداية',
            'duration_months' => 'المدة بالأشهر',
            'end_date' => 'تاريخ النهاية',
            'auto_renewal' => 'التجديد التلقائي',
            'commission_type' => 'نوع العمولة',
            'status' => 'الحالة',
            'notes' => 'الملاحظات',
        ];

        foreach ($this->getDirty() as $field => $newValue) {
            if (in_array($field, ['updated_at', 'updated_by'])) continue;

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
    | Format Value for Logging
    |--------------------------------------------------------------------------
    */
    private function formatValue(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '---';
        }

        switch ($field) {
            case 'status':
                $statuses = [
                    'active' => 'نشط',
                    'inactive' => 'غير نشط',
                    'expired' => 'منتهي',
                    'suspended' => 'معلق',
                ];
                return $statuses[$value] ?? $value;

            case 'auto_renewal':
                return $value ? 'نعم' : 'لا';

            case 'commission_type':
                $types = [
                    'procedure' => 'حسب الإجراء',
                    'duration' => 'حسب المدة',
                    'delinquency' => 'حسب مدة التعثر',
                ];
                return $types[$value] ?? $value;

            case 'start_date':
            case 'end_date':
                return \Carbon\Carbon::parse($value)->format('Y-m-d');

            case 'company_id':
                $company = Company::find($value);
                return $company?->name ?? "شركة #{$value}";

            default:
                return (string) $value;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Company
    |--------------------------------------------------------------------------
    */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Creator
    |--------------------------------------------------------------------------
    */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Updater
    |--------------------------------------------------------------------------
    */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Debt Types
    |--------------------------------------------------------------------------
    */
    public function debtTypes()
    {
        return $this->belongsToMany(
            SettingsDebtType::class,
            'contract_debt_types',
            'contract_id',
            'debt_type_id'
        )->withPivot('is_active')->withTimestamps();
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Contract Commissions
    |--------------------------------------------------------------------------
    */
    public function commissions()
    {
        return $this->hasMany(ContractCommission::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships - Contract Debt Types
    |--------------------------------------------------------------------------
    */
    public function contractDebtTypes()
    {
        return $this->hasMany(ContractDebtType::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    public function getFormattedStartDateAttribute(): ?string
    {
        return $this->start_date?->locale('ar')->translatedFormat('d F Y');
    }

    public function getFormattedEndDateAttribute(): ?string
    {
        return $this->end_date?->locale('ar')->translatedFormat('d F Y');
    }

    public function getStatusLabelAttribute(): string
    {
        $labels = [
            'active' => 'نشط',
            'inactive' => 'غير نشط',
            'expired' => 'منتهي',
            'suspended' => 'معلق',
        ];

        return $labels[$this->status] ?? 'غير محدد';
    }

    public function getStatusClassAttribute(): string
    {
        $classes = [
            'active' => 'bg-label-success',
            'inactive' => 'bg-label-secondary',
            'expired' => 'bg-label-danger',
            'suspended' => 'bg-label-warning',
        ];

        return $classes[$this->status] ?? 'bg-label-secondary';
    }

    public function getCommissionTypeLabelAttribute(): string
    {
        $labels = [
            'procedure' => 'حسب الإجراء',
            'duration' => 'حسب المدة',
            'delinquency' => 'حسب مدة التعثر',
        ];

        return $labels[$this->commission_type] ?? 'غير محدد';
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

    public function scopeExpired($query)
    {
        return $query->where('status', 'expired');
    }
    

    public function scopeExpiringSoon($query, $days = 30)
    {
        return $query->where('status', 'active')
            ->where('end_date', '<=', now()->addDays($days))
            ->where('end_date', '>=', now());
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSearch($query, $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('contract_number', 'like', "%{$term}%")
                ->orWhereHas('company', function ($companyQuery) use ($term) {
                    $companyQuery->where('name', 'like', "%{$term}%");
                });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods
    |--------------------------------------------------------------------------
    */
    public function isExpired(): bool
    {
        return $this->end_date && $this->end_date->isPast();
    }

    public function isExpiringSoon($days = 30): bool
    {
        return $this->end_date &&
            $this->end_date->isFuture() &&
            $this->end_date->diffInDays(now()) <= $days;
    }

    public function getDaysUntilExpiryAttribute(): ?int
    {
        return $this->end_date?->diffInDays(now());
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($contract) {
            $contract->created_by = auth()->id();

            if ($contract->start_date && $contract->duration_months) {
                $contract->end_date = $contract->start_date->copy()->addMonths($contract->duration_months);
            }
        });

        static::updating(function ($contract) {
            $contract->updated_by = auth()->id();

            if ($contract->isDirty(['start_date', 'duration_months'])) {
                if ($contract->start_date && $contract->duration_months) {
                    $contract->end_date = $contract->start_date->copy()->addMonths($contract->duration_months);
                }
            }
        });
    }
}
