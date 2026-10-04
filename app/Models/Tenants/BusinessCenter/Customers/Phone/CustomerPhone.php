<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Phone;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CustomerPhone extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'customer_id',
        'phone',
        'type',
        'is_primary',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'is_primary' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Activity Log Options
    |--------------------------------------------------------------------------
    */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['phone', 'is_primary'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('customer_phone')
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
        $phone  = $this->phone ?? 'غير محدد';

        $type = match ($this->type) {
            'mobile' => 'جوال',
            'home'   => 'منزل',
            'work'   => 'عمل',
            'other'  => 'أخرى',
            default  => 'غير محدد'
        };

        $primary = $this->is_primary ? 'نعم' : 'لا';

        $notes = $this->notes ?: 'لا توجد';

        $details = " (الرقم: {$phone} – النوع: {$type} – افتراضي؟ {$primary} – الملاحظات: {$notes})";

        return match ($eventName) {
            'created' => "تم إضافة رقم هاتف جديد للعميل {$customerName}{$details}",
            'updated' => "تم تعديل رقم هاتف العميل {$customerName}{$details}",
            'deleted' => "تم حذف رقم هاتف العميل {$customerName} (الرقم: {$phone})",
            default   => "نشاط على رقم هاتف العميل {$customerName}{$details}",
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

        if ($this->isDirty('is_primary')) {
            if ($this->is_primary) {
                $changes[] = "تم تعيينه كرقم افتراضي";
            } else {
                $changes[] = "تم إلغاء تعيينه كرقم افتراضي";
            }
        }

        return empty($changes) ? '' : ' (' . implode(' | ', $changes) . ')';
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    /**
     * Get the customer that owns the phone.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * Get the phone type label.
     */
    public function getTypeLabelAttribute(): string
    {
        $types = [
            'mobile' => 'جوال',
            'home' => 'منزل',
            'work' => 'عمل',
            'other' => 'أخرى'
        ];

        return $types[$this->type] ?? 'غير محدد';
    }

    /**
     * Get the type class for badge.
     */
    public function getTypeClassAttribute(): string
    {
        $classes = [
            'mobile' => 'bg-label-primary',
            'home' => 'bg-label-info',
            'work' => 'bg-label-warning',
            'other' => 'bg-secondary'
        ];

        return $classes[$this->type] ?? 'bg-secondary';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * Scope a query to only include primary phones.
     */
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    /**
     * Scope a query to filter by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }
}
