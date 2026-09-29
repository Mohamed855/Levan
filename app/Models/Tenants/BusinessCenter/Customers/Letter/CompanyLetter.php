<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Letter;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Customers\Follow\CustomerFollow;
use App\Models\Tenants\FinancialCollection\Collection\Collection;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CompanyLetter extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'company_letters';

    /*
    |--------------------------------------------------------------------------
    | الحقول القابلة للتعبئة
    |--------------------------------------------------------------------------
    */
    protected $fillable = [
        'customer_id',
        'customer_follow_id',
        'collection_id',
        'action_type',
        'status',
        'approved_by',
        'approved_at',
        'email_template_id',
        'sent_by',
        'sent_at',
        'sent_to',
        'email_subject',
        'email_body',
        'failure_reason',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | التحويلات
    |--------------------------------------------------------------------------
    */
    protected $casts = [
        'approved_at' => 'datetime',
        'sent_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | العلاقات
    |--------------------------------------------------------------------------
    */
    
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function customerFollow()
    {
        return $this->belongsTo(CustomerFollow::class);
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function sentBy()
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    public function emailTemplate()
    {
        return $this->belongsTo(MessageTemplate::class, 'email_template_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors - تسميات النوع
    |--------------------------------------------------------------------------
    */
    public function getActionTypeLabelAttribute(): string
    {
        return match($this->action_type) {
            'payment_full' => 'سداد كامل',
            'payment_partial' => 'سداد جزئي',
            'document_request' => 'طلب مستندات',
            'recovery_request' => 'حذف مسترد',
            default => 'غير محدد'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors - أيقونة النوع
    |--------------------------------------------------------------------------
    */
    public function getActionTypeIconAttribute(): string
    {
        return match($this->action_type) {
            'payment_full' => '<i class="ti ti-cash ti-sm text-success"></i>',
            'payment_partial' => '<i class="ti ti-coins ti-sm text-info"></i>',
            'document_request' => '<i class="ti ti-file-text ti-sm text-warning"></i>',
            'recovery_request' => '<i class="ti ti-archive ti-sm text-danger"></i>',
            default => '<i class="ti ti-help ti-sm text-secondary"></i>'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors - تسميات الحالة
    |--------------------------------------------------------------------------
    */
    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            'pending_approval' => 'ينتظر الاعتماد',
            'approved_ready_to_send' => 'معتمد - جاهز للإرسال',
            'ready_to_send' => 'جاهز للإرسال',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل الإرسال',
            default => 'غير محدد'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors - ألوان الحالة
    |--------------------------------------------------------------------------
    */
    public function getStatusClassAttribute(): string
    {
        return match($this->status) {
            'pending_approval' => 'bg-label-warning',
            'approved_ready_to_send' => 'bg-label-info',
            'ready_to_send' => 'bg-label-primary',
            'sent' => 'bg-label-success',
            'failed' => 'bg-label-danger',
            default => 'bg-label-secondary'
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes - الحالات
    |--------------------------------------------------------------------------
    */
    public function scopeReadyToSend($query)
    {
        return $query->whereIn('status', ['ready_to_send', 'approved_ready_to_send']);
    }

    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes - أنواع الإجراءات
    |--------------------------------------------------------------------------
    */
    public function scopePayments($query)
    {
        return $query->whereIn('action_type', ['payment_full', 'payment_partial']);
    }

    public function scopeDocumentRequests($query)
    {
        return $query->where('action_type', 'document_request');
    }

    public function scopeRecoveryRequests($query)
    {
        return $query->where('action_type', 'recovery_request');
    }
}