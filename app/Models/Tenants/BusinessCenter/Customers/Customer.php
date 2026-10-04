<?php

namespace App\Models\Tenants\BusinessCenter\Customers;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Attachment\CustomerAttachment;
use App\Models\Tenants\BusinessCenter\Customers\Comment\CustomerComment;
use App\Models\Tenants\BusinessCenter\Customers\Distribution\CustomerDistribution;
use App\Models\Tenants\BusinessCenter\Customers\FieldValue\CustomerFieldValue;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\History\CustomerRelationshipHistory;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\BusinessCenter\Customers\Note\CustomerNote;
use App\Models\Tenants\BusinessCenter\Customers\PaymentPlan\CustomerPaymentPlan;
use App\Models\Tenants\BusinessCenter\Customers\Phone\CustomerPhone;
use App\Models\Tenants\BusinessCenter\Customers\Settlement\CustomerSettlement;
use App\Models\Tenants\BusinessCenter\Customers\Sms\CustomerSms;
use App\Models\Tenants\BusinessCenter\Customers\Task\Task;
use App\Models\Tenants\BusinessCenter\Customers\UpdateRequest\CustomerUpdateRequest;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\FinancialCollection\Collection\CustomerTransaction;
use App\Models\Tenants\GeneralSetting\ContactResult\ContactResult;
use App\Models\Tenants\GeneralSetting\Country\SettingsCountry;
use App\Models\Tenants\GeneralSetting\CustomerStatus\SettingsCustomerStatus;
use App\Models\Tenants\UserMangment\User\User;
use App\Traits\Tenants\BelongsToUserBranch;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

class Customer extends Model
{
    use HasFactory, SoftDeletes, LogsActivity, BelongsToUserBranch;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'name',
        'first_name',
        'father_name',
        'grandfather_name',
        'family_name',
        'customer_type',
        'company_id',
        'portfolio_id',
        'identity_number',
        'nationality',
        'relationship_manager_id',
        'customer_status_id',
        'phone',
        'account_number',
        'last_sms_text',
        'last_sms_at',
        'last_contact_result_id',
        'contract_date',
        'default_date',
        'commission_rate',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'contract_date' => 'date',
        'default_date'  => 'date',
        'last_sms_at'   => 'datetime',
        'commission_rate' => 'decimal:2',
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
            ->useLogName('customers')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    private function getEventDescription(string $eventName): string
    {
        $customerName = $this->name ?? 'غير محدد';

        $descriptions = [
            'created' => "تم إنشاء عميل جديد: {$customerName}",
            'updated' => "تم تحديث بيانات العميل: {$customerName}",
            'deleted' => "تم حذف العميل: {$customerName}",
            'restored' => "تم استعادة العميل: {$customerName}",
        ];

        $description = $descriptions[$eventName] ?? "عملية على عميل: {$customerName}";

        if ($eventName === 'updated' && $this->isDirty()) {
            $description .= $this->getChangesSummary();
        }

        return $description;
    }

    private function getChangesSummary(): string
    {
        $changes = [];
        $fieldNames = [
            'name' => 'الاسم',
            'first_name' => 'الاسم الأول',
            'father_name' => 'اسم الأب',
            'grandfather_name' => 'اسم الجد',
            'family_name' => 'اسم العائلة',
            'customer_type' => 'النوع',
            'company_id' => 'الشركة',
            'portfolio_id' => 'المحفظة',
            'identity_number' => 'رقم الهوية',
            'nationality' => 'الجنسية',
            'relationship_manager_id' => 'مدير العلاقات',
            'customer_status_id' => 'الحالة',
            'phone' => 'الهاتف',
            'account_number' => 'رقم الحساب',
            'contract_date' => 'تاريخ العقد',
            'commission_rate' => 'نسبة العمولة',
            'last_contact_result_id' => 'نتيجة الاتصال',
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

    private function formatValue(string $field, $value): string
    {
        if (is_null($value) || $value === '') {
            return '---';
        }

        switch ($field) {
            case 'customer_type':
                return $value === 'individual' ? 'فردي' : 'شركة';

            case 'customer_status_id':
                $status = SettingsCustomerStatus::find($value);
                return $status?->name ?? "حالة #{$value}";

            case 'company_id':
                $company = Company::find($value);
                return $company?->name ?? "شركة #{$value}";

            case 'portfolio_id':
                $portfolio = Portfolio::find($value);
                return $portfolio?->name ?? "محفظة #{$value}";

            case 'relationship_manager_id':
                $manager = User::find($value);
                return $manager?->name ?? "مستخدم #{$value}";

            case 'nationality':
                $country = SettingsCountry::find($value);
                return $country?->name ?? "دولة #{$value}";

            case 'last_contact_result_id':
                $result = ContactResult::find($value);
                return $result?->name ?? "نتيجة #{$value}";

            case 'contract_date':
            case 'default_date':
                return \Carbon\Carbon::parse($value)->format('Y-m-d');

            case 'commission_rate':
                return $value . '%';

            default:
                return (string) $value;
        }
    }
    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the company that owns the customer.
     */
    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the portfolio that owns the customer.
     */
    public function portfolio()
    {
        return $this->belongsTo(Portfolio::class);
    }

    /**
     * Get the nationality country.
     */
    public function nationalityCountry()
    {
        return $this->belongsTo(SettingsCountry::class, 'nationality');
    }

    /**
     * Get the relationship manager.
     */
    public function relationshipManager()
    {
        return $this->belongsTo(User::class, 'relationship_manager_id');
    }

    /**
     * Get the user who created the customer.
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all phone numbers for the customer.
     */
    public function phones()
    {
        return $this->hasMany(CustomerPhone::class);
    }

    /**
     * Get the primary phone number.
     */
    public function primaryPhone()
    {
        return $this->hasOne(CustomerPhone::class)->where('is_primary', true);
    }

    /**
     * Get the customer status.
     */
    public function customerStatus()
    {
        return $this->belongsTo(SettingsCustomerStatus::class, 'customer_status_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the customer type label.
     */
    public function getCustomerTypeLabelAttribute(): string
    {
        return $this->customer_type === 'individual' ? 'فردي' : 'شركة';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return $this->customerStatus ? $this->customerStatus->name : 'غير محدد';
    }

    /**
     * Get the status class for badge.
     */
    public function getStatusClassAttribute(): string
    {
        if (!$this->customerStatus) {
            return 'bg-secondary';
        }

        // إرجاع لون افتراضي دون تحديد حالات معينة
        return 'bg-label-primary';
    }

    /**
     * Get formatted commission rate.
     */
    public function getFormattedCommissionRateAttribute(): ?string
    {
        return $this->commission_rate ? $this->commission_rate . '%' : null;
    }

    /**
     * Get formatted claim date.
     */
    // public function getFormattedClaimDateAttribute(): ?string
    // {
    //     return $this->claim_date?->locale('ar')->translatedFormat('d F Y');
    // }

    /**
     * Get formatted contract date.
     */
    public function getFormattedContractDateAttribute(): ?string
    {
        return $this->contract_date?->locale('ar')->translatedFormat('d F Y');
    }

    /**
     * Get formatted default date.
     */
    public function getFormattedDefaultDateAttribute(): ?string
    {
        return $this->default_date?->locale('ar')->translatedFormat('d F Y');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to filter by customer status.
     */
    public function scopeByStatus($query, $statusId)
    {
        return $query->where('customer_status_id', $statusId);
    }

    /**
     * Scope a query to filter by customer type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }

    /**
     * Scope a query to filter by company.
     */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /**
     * Scope a query to filter by portfolio.
     */
    public function scopeByPortfolio($query, $portfolioId)
    {
        return $query->where('portfolio_id', $portfolioId);
    }



    /*
    |--------------------------------------------------------------------------
    | المرفقات الخاصة بالعميل
    |--------------------------------------------------------------------------
    */
    public function attachments(): HasMany
    {
        return $this->hasMany(CustomerAttachment::class)->latest();
    }

    /**
     * المرفقات النشطة فقط (غير المحذوفة)
     */
    public function activeAttachments(): HasMany
    {
        return $this->hasMany(CustomerAttachment::class)->whereNull('deleted_at')->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | المرفقات الخاصة بالعميل End
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | الملاحظات الخاصة بالعميل
    |--------------------------------------------------------------------------
    */
    public function notes(): HasMany
    {
        return $this->hasMany(CustomerNote::class)->latest();
    }

    /**
     * الملاحظات النشطة فقط (غير المحذوفة)
     */
    public function activeNotes(): HasMany
    {
        return $this->hasMany(CustomerNote::class)->whereNull('deleted_at')->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | الملاحظات الخاصة بالعميل End
    |--------------------------------------------------------------------------
    */



    /*
    |--------------------------------------------------------------------------
    | خطة سداد الخاصة بالعميل
    |--------------------------------------------------------------------------
    */
    public function paymentPlans(): HasMany
    {
        return $this->hasMany(CustomerPaymentPlan::class)->orderBy('due_date');
    }

    /**
     * خطط السداد النشطة فقط (غير المحذوفة)
     */
    public function activePaymentPlans(): HasMany
    {
        return $this->hasMany(CustomerPaymentPlan::class)->whereNull('deleted_at')->orderBy('due_date');
    }

    /*
    |--------------------------------------------------------------------------
    | خطة سداد الخاصة بالعميل
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    |  طلبات التسوية الخاصة بالعميل
    |--------------------------------------------------------------------------
    */
    public function settlements(): HasMany
    {
        return $this->hasMany(CustomerSettlement::class)->orderBy('settlement_date', 'desc');
    }

    /**
     * التسويات النشطة فقط (غير المحذوفة)
     */
    public function activeSettlements(): HasMany
    {
        return $this->hasMany(CustomerSettlement::class)->whereNull('deleted_at')->orderBy('settlement_date', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    |  طلبات التسوية الخاصة بالعميل
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    |  الرسائل النصية الخاصة بالعميل
    |--------------------------------------------------------------------------
    */
    public function smsMessages(): HasMany
    {
        return $this->hasMany(CustomerSms::class)->orderBy('created_at', 'desc');
    }

    /**
     * الرسائل النشطة فقط (غير المحذوفة)
     */
    public function activeSmsMessages(): HasMany
    {
        return $this->hasMany(CustomerSms::class)->whereNull('deleted_at')->orderBy('created_at', 'desc');
    }
    /*
    |--------------------------------------------------------------------------
    |  الرسائل النصية الخاصة بالعميل
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    |  الحقول المتغيرة الخاصة بالعميل
    |--------------------------------------------------------------------------
    */

    /**
     * جميع قيم الحقول المتغيرة.
     */
    public function dynamicValues()
    {
        return $this->hasMany(CustomerFieldValue::class);
    }

    /**
     * مصفوفة [field_id => value] لاستخدامها في الواجهة.
     */
    public function getDynamicAttribute(): array
    {
        return $this->dynamicValues
            ->mapWithKeys(fn($row) => [
                $row->portfolio_field_id => $row->value
            ])->toArray();
    }


    /*
    |--------------------------------------------------------------------------
    | علاقة لجلب كل المعاملات المالية للعميل.
    |--------------------------------------------------------------------------
    */
    public function transactions(): HasMany
    {
        return $this->hasMany(CustomerTransaction::class)->orderBy('transaction_date', 'asc');
    }


    /*
    |--------------------------------------------------------------------------
    | Accessor لحساب الرصيد المتبقي ديناميكيًا.
    |--------------------------------------------------------------------------
    */
    public function getRemainingDebtAttribute(): float
    {
        if (array_key_exists('remaining_debt', $this->attributes)) {
            return (float) round($this->attributes['remaining_debt'], 2);
        }

        $totalDebit = $this->transactions()->where('type', 'debit')->sum('amount');

        $totalCredit = $this->transactions()->where('type', 'credit')->sum('amount');

        $totalSettlement = \Illuminate\Support\Facades\Schema::hasTable('customer_settlements')
            ? $this->settlements()->whereNull('deleted_at')->sum('settlement_amount')
            : 0;

        return (float) round($totalDebit - $totalCredit - $totalSettlement, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | عرض الرصيد في الجدول الخاص بالعملاء 
    |--------------------------------------------------------------------------
    */
    public function getFormattedRemainingDebtAttribute(): string
    {
        return number_format($this->remaining_debt, 2) . ' ريال';
    }


    /**
     * Get distribution history for this customer.
     */
    public function distributionHistory()
    {
        return $this->belongsToMany(CustomerDistribution::class, 'customer_distributions', 'id', 'id')
            ->whereJsonContains('customer_ids', $this->id);
    }

    /**
     * Get the latest distribution for this customer.
     */
    public function latestDistribution()
    {
        return CustomerDistribution::whereJsonContains('customer_ids', $this->id)
            ->where('collector_id', $this->relationship_manager_id)
            ->latest()
            ->first();
    }

    /**
     * Check if customer is currently assigned to a collector.
     */
    public function isAssigned(): bool
    {
        return !is_null($this->relationship_manager_id);
    }

    /**
     * Check if customer is available for distribution.
     */
    public function isAvailableForDistribution(): bool
    {
        return is_null($this->relationship_manager_id);
    }

    /**
     * Get distribution status attribute.
     */
    public function getDistributionStatusAttribute(): array
    {
        if ($this->isAssigned()) {
            $latestDistribution = $this->latestDistribution();

            return [
                'status' => 'assigned',
                'status_text' => 'مُوزع',
                'status_class' => 'bg-label-success',
                'collector' => $this->relationshipManager,
                'distribution_date' => $latestDistribution?->created_at,
            ];
        }

        return [
            'status' => 'unassigned',
            'status_text' => 'غير مُوزع',
            'status_class' => 'bg-label-warning',
            'collector' => null,
            'distribution_date' => null,
        ];
    }

    /**
     * Scope for unassigned customers.
     */
    public function scopeUnassigned($query)
    {
        return $query->whereNull('relationship_manager_id');
    }

    /**
     * Scope for assigned customers.
     */
    public function scopeAssigned($query)
    {
        return $query->whereNotNull('relationship_manager_id');
    }


    /*
    |--------------------------------------------------------------------------
    | طلبات  تحديث البيانات 
    |--------------------------------------------------------------------------
    */
    public function updateRequests()
    {
        return $this->hasMany(CustomerUpdateRequest::class);
    }

    /*
    |--------------------------------------------------------------------------
    | طلبات التحديث المعلقة
    |--------------------------------------------------------------------------
    */
    public function pendingUpdateRequests()
    {
        return $this->updateRequests()->pending();
    }

    /*
    |--------------------------------------------------------------------------
    | هل هناك طلبات تحديث معلقة
    |--------------------------------------------------------------------------
    */
    public function hasPendingUpdateRequests(): bool
    {
        return $this->pendingUpdateRequests()->exists();
    }

    /*
    |--------------------------------------------------------------------------
    | Phone Requests Relationship
    |--------------------------------------------------------------------------
    | علاقة طلبات إضافة أرقام الهواتف الخاصة بالعميل
    */
    public function phoneRequests()
    {
        return $this->hasMany(CustomerUpdateRequest::class, 'customer_id')
            ->where('request_type', CustomerUpdateRequest::REQUEST_TYPE_ADD_PHONE);
    }

    public function getPhoneRequestsAttribute()
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('customer_update_requests')) {
            return collect();
        }
        return $this->getRelationValue('phoneRequests');
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function lastContactResult()
    {
        return $this->belongsTo(ContactResult::class, 'last_contact_result_id');
    }


    /*
    |--------------------------------------------------------------------------
    | Customer Follows Relationship For Process Or Start Collections
    |--------------------------------------------------------------------------
    */
    public function customerFollows()
    {
        return $this->hasMany(CustomerFollow::class)->latest();
    }

    public function latestFollow()
    {
        return $this->hasOne(CustomerFollow::class)->latest();
    }

    /*
    |--------------------------------------------------------------------------
    | علاقة خطابات الشركات
    |--------------------------------------------------------------------------
    */
    public function companyLetters()
    {
        return $this->hasMany(CompanyLetter::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Comments Relationship
    |--------------------------------------------------------------------------
    */
    public function comments(): HasMany
    {
        return $this->hasMany(CustomerComment::class);
    }


    /*
    |--------------------------------------------------------------------------
    | Trait Methods - BelongsToUserBranch  --  last update for the Branch Scopes
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Get User Relation Name
    |--------------------------------------------------------------------------
    | تحديد اسم العلاقة مع المستخدم للفلترة حسب الفرع
    */
    public function getUserRelationName(): string
    {
        return 'relationshipManager';
    }

    /*
    |--------------------------------------------------------------------------
    | User Relationship ---  
    |--------------------------------------------------------------------------
    | علاقة المستخدم المسؤول للفلترة (مطلوبة للـ Scope)
    */
    public function user()
    {
        return $this->relationshipManager();
    }

    /*
    |--------------------------------------------------------------------------
    | Functions to get Customer Tasks
    |--------------------------------------------------------------------------
    */
    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على المهام المعلقة
    |--------------------------------------------------------------------------
    */
    public function pendingTasks()
    {
        return $this->tasks()->pending();
    }

    /*
    |--------------------------------------------------------------------------
    | الحصول على المهام المستحقة اليوم
    |--------------------------------------------------------------------------
    */
    public function dueTodayTasks()
    {
        return $this->tasks()->dueToday();
    }

    /*
    |--------------------------------------------------------------------------
    | Functions to get Customer Tasks
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | سجل السدادات (Collections) الخاص بالعميل
    |--------------------------------------------------------------------------
    */
    public function collections(): HasMany
    {
        return $this->hasMany(Collection::class)->orderBy('collection_date', 'desc');
    }

    /*
    |--------------------------------------------------------------------------
    | Relation Ship History
    |--------------------------------------------------------------------------
    */
    public function relationshipHistory()
    {
        return $this->hasMany(CustomerRelationshipHistory::class)->orderBy('changed_at', 'desc');
    }
}
