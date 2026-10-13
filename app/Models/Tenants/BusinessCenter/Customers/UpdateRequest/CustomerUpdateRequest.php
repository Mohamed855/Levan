<?php

namespace App\Models\Tenants\BusinessCenter\Customers\UpdateRequest;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

class CustomerUpdateRequest extends Model
{
    use LogsActivity, SoftDeletes;

    protected $table = 'customer_update_requests';

    protected $fillable = [
        'customer_id',
        'requested_by',
        'approved_by',
        'status',
        'requested_data',
        'rejection_reason',
        'approved_at',
        'request_type',

    ];

    protected $casts = [
        'requested_data' => 'json',
        'approved_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';

    const STATUS_APPROVED = 'approved';

    const STATUS_REJECTED = 'rejected';

    const REQUEST_TYPE_UPDATE = 'update';

    const REQUEST_TYPE_ADD_PHONE = 'add_phone';

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
            ->useLogName('customer_update_requests')
            ->setDescriptionForEvent(fn (string $eventName) => $this->getEventDescription($eventName));
    }

    private function getEventDescription(string $eventName): string
    {
        $customerName = $this->customer?->name ?? 'عميل غير محدد';
        $descriptions = [
            'created' => "تم إنشاء طلب تحديث بيانات العميل: {$customerName}",
            'updated' => "تم تحديث طلب تحديث بيانات العميل: {$customerName}",
            'deleted' => "تم حذف طلب تحديث بيانات العميل: {$customerName}",
        ];

        return $descriptions[$eventName] ?? "تم {$eventName} طلب تحديث بيانات العميل: {$customerName}";
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */
    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            self::STATUS_PENDING => '<span class="badge bg-label-warning">معلق</span>',
            self::STATUS_APPROVED => '<span class="badge bg-label-success">موافق عليه</span>',
            self::STATUS_REJECTED => '<span class="badge bg-label-danger">مرفوض</span>',
        ];

        return $statuses[$this->status] ?? '<span class="badge bg-secondary">'.$this->status.'</span>';
    }

    public function getStatusClassAttribute(): string
    {
        $classes = [
            self::STATUS_PENDING => 'bg-label-warning',
            self::STATUS_APPROVED => 'bg-label-success',
            self::STATUS_REJECTED => 'bg-label-danger',
        ];

        return $classes[$this->status] ?? 'bg-secondary';
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::STATUS_REJECTED);
    }
}
