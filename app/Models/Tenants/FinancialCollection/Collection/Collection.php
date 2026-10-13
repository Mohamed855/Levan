<?php

namespace App\Models\Tenants\FinancialCollection\Collection;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\BusinessCenter\Customers\Letter\CompanyLetter;
use App\Models\Tenants\FinancialCollection\Reconciliation\PaymentReconciliationItem;
use App\Models\Tenants\GeneralSetting\PaymentMethod\SettingsPaymentMethod;
use App\Models\Tenants\UserMangment\User\User;
use App\Traits\Tenants\HasUserAccessScope;
use App\Traits\Tenants\Scopes\UserAccessScope;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class Collection extends Model
{
    use HasFactory, HasUserAccessScope, LogsActivity, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'customer_id',
        'collected_amount',
        'collection_type',
        'payment_method_id',
        'receipt_number',
        'closing_code',
        'collection_date',
        'payment_type',
        'attachment',
        'status',
        'added_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
        'source',
        'reconciliation_item_id',
        'is_verified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'collection_date' => 'date',
        'collected_amount' => 'decimal:2',
        'approved_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'is_verified' => 'boolean',
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
                'customer_id',
                'collected_amount',
                'collection_type',
                'payment_type',
                'payment_method_id',
                'receipt_number',
                'closing_code',
                'collection_date',
                'status',
                'rejection_reason',
            ])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('collections')
            ->setDescriptionForEvent(fn ($e) => $this->getEventDescription($e))
            ->dontLogIfAttributesChangedOnly(['updated_at']);
    }

    /*
    |--------------------------------------------------------------------------
    | Helper Methods for Activity Log
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        $customerName = $this->customer->name ?? 'عميل محذوف';
        $amount = number_format($this->collected_amount, 0);
        $receiptNumber = $this->receipt_number ?? 'غير محدد';

        return match ($eventName) {
            'created' => "أضاف تحصيل ({$amount} ر.س) للعميل: {$customerName}".$this->getCollectionSummary(),
            'updated' => "عدّل تحصيل للعميل: {$customerName}".$this->getChangesSummary(),
            'deleted' => "حذف تحصيل ({$amount} ر.س | سند: {$receiptNumber}) للعميل: {$customerName}",
            default => "نشاط على تحصيل للعميل: {$customerName}",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Changes Summary for Activity Log
    |--------------------------------------------------------------------------
    */
    private function getCollectionSummary(): string
    {
        $details = [];

        $details[] = match ($this->collection_type) {
            'cash' => 'نقدي',
            'scheduled' => 'مجدول',
            default => 'غير محدد'
        };

        $details[] = match ($this->payment_type) {
            'settlement' => 'بتسوية',
            'without_settlement' => 'بدون تسوية',
            default => 'غير محدد'
        };

        if ($this->receipt_number) {
            $details[] = "سند: {$this->receipt_number}";
        }

        if ($this->collection_date) {
            $date = $this->collection_date->format('Y-m-d');
            $details[] = "تاريخ: {$date}";
        }

        if ($this->paymentMethod) {
            $details[] = "طريقة: {$this->paymentMethod->name}";
        }

        return ' ('.implode(' | ', $details).')';
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
            'customer_id' => 'العميل',
            'collected_amount' => 'المبلغ',
            'collection_type' => 'نوع التحصيل',
            'payment_type' => 'نوع السداد',
            'payment_method_id' => 'طريقة الدفع',
            'receipt_number' => 'رقم السند',
            'closing_code' => 'رمز الإقفال',
            'collection_date' => 'تاريخ التحصيل',
            'status' => 'الحالة',
            'rejection_reason' => 'سبب الرفض',
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

        switch ($field) {
            case 'status':
                return match ($value) {
                    'pending' => 'غير معتمد',
                    'approved' => 'معتمد',
                    'rejected' => 'مرفوض',
                    default => 'غير محدد'
                };

            case 'collection_type':
                return match ($value) {
                    'cash' => 'نقدي',
                    'scheduled' => 'مجدول',
                    default => 'غير محدد'
                };

            case 'payment_type':
                return match ($value) {
                    'settlement' => 'سداد بتسوية',
                    'without_settlement' => 'سداد بدون تسوية',
                    default => 'غير محدد'
                };

            case 'collected_amount':
                return number_format((float) $value, 2).' ر.س';

            case 'customer_id':
                $customer = Customer::find($value);

                return $customer ? $customer->name : 'غير محدد';

            case 'payment_method_id':
                $method = SettingsPaymentMethod::find($value);

                return $method ? $method->name : 'غير محدد';

            case 'collection_date':
                return Carbon::parse($value)->format('Y-m-d');

            case 'rejection_reason':
                return strlen($value) > 50 ? substr($value, 0, 50).'...' : $value;

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
     * Get the customer that owns the collection.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Get the payment method that owns the collection.
     */
    public function paymentMethod()
    {
        return $this->belongsTo(SettingsPaymentMethod::class);
    }

    /**
     * Get the user who added the collection.
     */
    public function addedBy()
    {
        return $this->belongsTo(User::class, 'added_by');
    }

    /**
     * Get the user who approved the collection.
     */
    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the formatted collection date.
     */
    public function getFormattedCollectionDateAttribute(): ?string
    {
        return $this->collection_date?->locale('ar')->translatedFormat('d F Y');
    }

    /**
     * Get the formatted collected amount.
     */
    public function getFormattedCollectedAmountAttribute(): string
    {
        return number_format($this->collected_amount, 2).' <span class="icon-saudi_riyal"></span>';
    }

    /**
     * Get the status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'غير معتمد',
            'approved' => 'معتمد',
            'rejected' => 'مرفوض',
            default => $this->status
        };
    }

    /**
     * Get the status class for badge.
     */
    public function getStatusClassAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'bg-label-warning',
            'approved' => 'bg-label-success',
            'rejected' => 'bg-label-danger',
            default => 'bg-secondary'
        };
    }

    /**
     * Get the collection type label.
     */
    public function getCollectionTypeLabelAttribute(): string
    {
        return $this->collection_type === 'cash' ? 'نقدي' : 'مجدول';
    }

    /**
     * Get the payment type label.
     */
    public function getPaymentTypeLabelAttribute(): string
    {
        return $this->payment_type === 'settlement' ? 'سداد بتسوية' : 'سداد بدون تسوية';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include pending collections.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope a query to only include approved collections.
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Scope a query to only include rejected collections.
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Scope a query to filter by customer.
     */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope a query to filter by payment method.
     */
    public function scopeByPaymentMethod($query, $paymentMethodId)
    {
        return $query->where('payment_method_id', $paymentMethodId);
    }

    /**
     * Scope a query to filter by collection type.
     */
    public function scopeByCollectionType($query, $type)
    {
        return $query->where('collection_type', $type);
    }

    /**
     * Scope a query to filter by payment type.
     */
    public function scopeByPaymentType($query, $type)
    {
        return $query->where('payment_type', $type);
    }

    /**
     * Scope a query to filter by date range.
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('collection_date', [$startDate, $endDate]);
    }

    /*
    |--------------------------------------------------------------------------
    | علاقة لجلب المعاملات المالية المرتبطة بهذا التحصيل
    |--------------------------------------------------------------------------
    */
    public function financialTransactions(): MorphMany
    {
        return $this->morphMany(CustomerTransaction::class, 'related');
    }

    /*
|--------------------------------------------------------------------------
| علاقة خطاب الشركة المرتبط بالتحصيل
|--------------------------------------------------------------------------
*/
    public function companyLetter()
    {
        return $this->hasOne(CompanyLetter::class, 'collection_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Trait Methods - HasUserAccessScope
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | Get User Relation Name
    |--------------------------------------------------------------------------
    | تحديد اسم العلاقة مع المستخدم للفلترة
    */
    public function getUserRelationName(): string
    {
        return 'addedBy';
    }

    /*
    |--------------------------------------------------------------------------
    | User Relationship
    |--------------------------------------------------------------------------
    | علاقة المستخدم الذي أضاف التحصيل للفلترة (مطلوبة للـ Scope)
    */
    public function user()
    {
        return $this->addedBy();
    }

    /*
    |--------------------------------------------------------------------------
    | Customer Relationship without Global Scope
    |--------------------------------------------------------------------------
    */
    public function customerForTable()
    {
        return $this->belongsTo(Customer::class, 'customer_id')
            ->withoutGlobalScope(UserAccessScope::class);
    }

    /*
    |--------------------------------------------------------------------------
    | علاقة المتابعة المرتبطة بالتحصيل
    |--------------------------------------------------------------------------
    */
    public function customerFollow()
    {
        return $this->hasOne(CustomerFollow::class, 'collection_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Reconciliation Item Relationship
    |--------------------------------------------------------------------------
    */
    public function reconciliationItem()
    {
        return $this->belongsTo(PaymentReconciliationItem::class, 'reconciliation_item_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Get Source Label Attribute
    |--------------------------------------------------------------------------
    */
    public function getSourceLabelAttribute(): string
    {
        return match ($this->source) {
            'manual' => 'يدوي',
            'reconciliation_file' => 'من ملف مطابقة',
            default => $this->source
        };
    }
}
