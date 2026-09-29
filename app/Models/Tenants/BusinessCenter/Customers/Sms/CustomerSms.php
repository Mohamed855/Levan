<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Sms;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerSms extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'customer_sms';

    protected $fillable = [
        'customer_id',
        'message_template_id',
        'phone_number',
        'phone_type',
        'template_name',
        'message_content',
        'status',
        'failure_reason',
        'sent_at',
        'sent_by',
    ];

    protected $casts = [
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    // العلاقات
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function messageTemplate(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class);
    }

    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    // Accessors
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending' => 'في الانتظار',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل الإرسال',
            'delivered' => 'تم التسليم',
            default => $this->status
        };
    }

    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'pending' => 'bg-label-warning',
            'sent' => 'bg-label-primary',
            'failed' => 'bg-label-danger',
            'delivered' => 'bg-label-success',
            default => 'bg-label-secondary'
        };
    }

    public function getPhoneTypeLabelAttribute(): string
    {
        return match($this->phone_type) {
            'primary' => 'الهاتف الأساسي',
            'mobile' => 'جوال',
            'home' => 'منزل',
            'work' => 'عمل',
            'other' => 'أخرى',
            default => $this->phone_type
        };
    }

    public function getSentAtFormattedAttribute(): ?string
    {
        return $this->sent_at?->locale('ar')->translatedFormat('d F Y - H:i');
    }

    public function getCreatedAtFormattedAttribute(): string
    {
        return $this->created_at->locale('ar')->translatedFormat('d F Y - H:i');
    }

    public function getMessagePreviewAttribute(): string
    {
        return strlen($this->message_content) > 100
            ? substr($this->message_content, 0, 100) . '...'
            : $this->message_content;
    }

    // Scopes
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    // Boot method
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($sms) {
            if (!$sms->sent_by && auth()->check()) {
                $sms->sent_by = auth()->id();
            }
        });
    }
}
