<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Settlement;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CustomerSettlement extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'created_by',
        'settlement_date',
        'settlement_amount',
        'settlement_number',
        'settlement_type',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'settlement_amount' => 'decimal:2',
        'file_size' => 'integer',
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
            ->logOnly(['settlement_amount', 'settlement_type', 'settlement_date'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('customer_settlement')
            ->setDescriptionForEvent(fn (string $eventName) => $this->getEventDescription($eventName));
    }

    /*
    |--------------------------------------------------------------------------
    | Get Event Description
    |--------------------------------------------------------------------------
    */
    private function getEventDescription(string $eventName): string
    {
        $customerName = $this->customer->name ?? "عميل #{$this->customer_id}";

        $amount = number_format($this->settlement_amount, 2).' ر.س';
        $date = $this->settlement_date ? $this->settlement_date->format('Y-m-d') : '-';
        $number = $this->settlement_number ?: 'غير محدد';

        $type = match ($this->settlement_type) {
            'cash_with_update' => 'كاش بتحديث',
            'cash_without_update' => 'كاش بدون تحديث',
            'purchase_debt_with_update' => 'شراء مديونية بتحديث',
            'purchase_debt_without_update' => 'شراء مديونية بدون تحديث',
            default => 'غير محدد'
        };
        $hasFile = $this->file_path ? 'نعم' : 'لا';
        $details = " (المبلغ: {$amount} – التاريخ: {$date} – النوع: {$type} – رقم التسوية: {$number} – يوجد ملف مرفق؟ {$hasFile})";

        return match ($eventName) {
            'created' => "تم إنشاء طلب تسوية جديد للعميل {$customerName}{$details}",
            'updated' => "تم تعديل طلب تسوية للعميل {$customerName}{$details}",
            'deleted' => "تم حذف طلب تسوية للعميل {$customerName} (المبلغ: {$amount})",
            default => "نشاط على تسوية العميل {$customerName}{$details}",
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Get Changes Summary
    |--------------------------------------------------------------------------
    */
    private function getChangesSummary(): string
    {
        if (! $this->isDirty()) {
            return '';
        }

        $changes = [];

        if ($this->isDirty('settlement_amount')) {
            $oldAmount = number_format($this->getOriginal('settlement_amount'), 2);
            $newAmount = number_format($this->settlement_amount, 2);
            $changes[] = "المبلغ: {$newAmount} ← {$oldAmount} ر.س";
        }

        if ($this->isDirty('settlement_type')) {
            $oldType = $this->getSettlementTypeText($this->getOriginal('settlement_type'));
            $newType = $this->settlement_type_text;
            $changes[] = "النوع: {$newType} ← {$oldType}";
        }

        if ($this->isDirty('settlement_date')) {
            $oldDate = date('Y-m-d', strtotime($this->getOriginal('settlement_date')));
            $newDate = $this->settlement_date->format('Y-m-d');
            $changes[] = "التاريخ: {$newDate} ← {$oldDate}";
        }

        return empty($changes) ? '' : ' ('.implode(' | ', $changes).')';
    }

    /*
    |--------------------------------------------------------------------------
    | Get Settlement Type Text
    |--------------------------------------------------------------------------
    */
    private function getSettlementTypeText($type): string
    {
        return match ($type) {
            'cash_with_update' => 'كاش بتحديث',
            'cash_without_update' => 'كاش بدون تحديث',
            'purchase_debt_with_update' => 'شراء مديونية بتحديث',
            'purchase_debt_without_update' => 'شراء مديونية بدون تحديث',
            default => $type ?? '---'
        };
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
    public function getSettlementAmountFormattedAttribute(): string
    {
        return number_format($this->settlement_amount, 2).' ر.س';
    }

    public function getSettlementDateFormattedAttribute(): string
    {
        return $this->settlement_date->locale('ar')->translatedFormat('d F Y');
    }

    public function getSettlementTypeTextAttribute(): string
    {
        return match ($this->settlement_type) {
            'cash_with_update' => 'كاش بتحديث',
            'cash_without_update' => 'كاش بدون تحديث',
            'purchase_debt_with_update' => 'شراء مديونية بتحديث',
            'purchase_debt_without_update' => 'شراء مديونية بدون تحديث',
            default => $this->settlement_type
        };
    }

    public function getFileSizeFormattedAttribute(): string
    {
        if (! $this->file_size) {
            return '';
        }

        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' B';
        }
    }

    public function getFileUrlAttribute(): string
    {
        return $this->file_path ? Storage::disk('tenant_public')->url($this->file_path) : '';
    }

    public function getHasFileAttribute(): bool
    {
        return ! empty($this->file_path);
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

    public function scopeByType($query, $type)
    {
        return $query->where('settlement_type', $type);
    }

    public function scopeByDateRange($query, $from, $to)
    {
        return $query->whereBetween('settlement_date', [$from, $to]);
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        // حذف الملف عند الحذف النهائي
        static::forceDeleted(function ($settlement) {
            if ($settlement->file_path && Storage::disk('tenant_public')->exists($settlement->file_path)) {
                Storage::disk('tenant_public')->delete($settlement->file_path);
            }
        });

        // تسجيل المستخدم الذي أنشأ التسوية تلقائياً
        static::creating(function ($settlement) {
            if (! $settlement->created_by && auth()->check()) {
                $settlement->created_by = auth()->id();
            }
        });
    }

    /**
     * التحقق من وجود الملف فعلياً
     */
    public function fileExists(): bool
    {
        return $this->file_path && Storage::disk('tenant_public')->exists($this->file_path);
    }
}
