<?php

namespace App\Models\Tenants\BusinessCenter\Customers\PaymentPlan;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CustomerPaymentPlan extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    protected $fillable = [
        'customer_id',
        'customer_follow_id',
        'created_by',
        'due_date',
        'due_amount',
    ];

    protected $casts = [
        'due_date' => 'date',
        'due_amount' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Options
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['due_date', 'due_amount'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('customer_payment_plan')
            ->setDescriptionForEvent(fn(string $eventName) => $this->getEventDescription($eventName));
    }

    /*
    |--------------------------------------------------------------------------
    | Get Event Description
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        $customerName = $this->customer->name ?? "عميل #{$this->customer_id}";
        $amount = number_format($this->due_amount, 2) . ' ر.س';
        $date   = $this->due_date ? $this->due_date->format('Y-m-d') : '-';

        $status = $this->due_date
            ? ($this->due_date->isPast() ? 'متأخرة' : 'غير مستحقة بعد')
            : '-';

        $createdBy = $this->createdBy?->name ?? 'غير محدد';

        $details = " (المبلغ: {$amount} – تاريخ الاستحقاق: {$date} – الحالة: {$status} – المستخدم المنشئ: {$createdBy})";

        return match ($eventName) {
            'created' => "تم إنشاء خطة سداد جديدة للعميل {$customerName}{$details}",
            'updated' => "تم تعديل خطة سداد للعميل {$customerName}{$details}",
            'deleted' => "تم حذف خطة سداد للعميل {$customerName}",
            default   => "نشاط على خطة سداد للعميل {$customerName}{$details}",
        };
    }


    /*
    |--------------------------------------------------------------------------
    | Get Changes Summary
    |--------------------------------------------------------------------------
    */
    private function getChangesSummary(): string
    {
        if (!$this->isDirty()) {
            return '';
        }

        $changes = [];

        if ($this->isDirty('due_amount')) {
            $oldAmount = number_format($this->getOriginal('due_amount'), 2);
            $newAmount = number_format($this->due_amount, 2);
            $changes[] = "المبلغ: {$newAmount} ← {$oldAmount} ر.س";
        }

        if ($this->isDirty('due_date')) {
            $oldDate = date('Y-m-d', strtotime($this->getOriginal('due_date')));
            $newDate = $this->due_date->format('Y-m-d');
            $changes[] = "تاريخ الاستحقاق: {$newDate} ← {$oldDate}";
        }

        return empty($changes) ? '' : ' (' . implode(' | ', $changes) . ')';
    }

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Accessors
    public function getDueAmountFormattedAttribute(): string
    {
        return number_format($this->due_amount, 2) . ' ر.س';
    }

    public function getDueDateFormattedAttribute(): string
    {
        return $this->due_date->locale('ar')->translatedFormat('d F Y');
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date->isPast();
    }

    public function getDaysUntilDueAttribute(): int
    {
        return now()->diffInDays($this->due_date, false);
    }

    // Scopes
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now()->toDateString());
    }

    public function scopeUpcoming($query, $days = 30)
    {
        return $query->whereBetween('due_date', [
            now()->toDateString(),
            now()->addDays($days)->toDateString()
        ]);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // تسجيل المستخدم الذي أنشأ خطة السداد تلقائياً
        static::creating(function ($plan) {
            if (!$plan->created_by && auth()->check()) {
                $plan->created_by = auth()->id();
            }
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Section Title
    |--------------------------------------------------------------------------
    | Description of this section.
    */
    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class);
    }
}
