<?php

namespace App\Models\Tenants\Communication\MessageLog;

use App\Models\Tenants\BusinessCenter\Company\Company;
use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\BusinessCenter\Portfolio\Portfolio;
use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenants\UserMangment\User\User;

class MessageIndividualLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'message_individual_logs';

    protected $fillable = [
        'batch_log_id',
        'customer_id',
        'customer_name',
        'phone_numbers',
        'company_id',
        // 'company_name',
        'portfolio_id',
        // 'portfolio_name',
        'message_content',
        'message_type',
        'platform',
        'template_id',
        'template_name',
        'provider_job_id',
        'provider_message_id',
        'provider_response',
        'sender_name',
        'status',
        'status_message',
        'error_message',
        'cost_per_message',
        'total_cost',
        'messages_count',
        'phone_numbers_count',
        'successful_sends',
        'failed_sends',
        'scheduled_at',
        'sent_at',
        'failed_at',
        'delivery_confirmed_at',
        'metadata',
        'retry_count',
        'last_retry_at',
        'sent_by',
    ];

    protected $casts = [
        'phone_numbers' => 'array',
        'provider_response' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'delivery_confirmed_at' => 'datetime',
        'last_retry_at' => 'datetime',
        'cost_per_message' => 'decimal:4',
        'total_cost' => 'decimal:4',
        'retry_count' => 'integer',
        'messages_count' => 'integer',
        'phone_numbers_count' => 'integer',
        'successful_sends' => 'integer',
        'failed_sends' => 'integer',
    ];

    protected $dates = [
        'scheduled_at',
        'sent_at',
        'failed_at',
        'delivery_confirmed_at',
        'last_retry_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /*
    |--------------------------------------------------------------------------
    |  العلاقة مع الـ Batch الرئيسي
    |--------------------------------------------------------------------------
    */
    public function batchLog(): BelongsTo
    {
        return $this->belongsTo(MessageLog::class, 'batch_log_id');
    }


    /*
    |--------------------------------------------------------------------------
    | العلاقة مع العميل
    |--------------------------------------------------------------------------
    */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقة مع المرسل
    |--------------------------------------------------------------------------
    */
    public function sentBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقة مع القالب
    |--------------------------------------------------------------------------
    */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    /*
    |--------------------------------------------------------------------------
    | فلترة بحسب الحالة
    |--------------------------------------------------------------------------
    */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /*
    |--------------------------------------------------------------------------
    |  فلترة بحسب الـ Batch
    |--------------------------------------------------------------------------
    */
    public function scopeByBatch($query, $batchId)
    {
        return $query->where('batch_log_id', $batchId);
    }

    /*
    |--------------------------------------------------------------------------
    | فلترة بحسب العميل
    |--------------------------------------------------------------------------
    */
    public function scopeByCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /*
    |--------------------------------------------------------------------------
    | الرسائل المرسلة بنجاح
    |--------------------------------------------------------------------------
    */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /*
    |--------------------------------------------------------------------------
    | الرسائل الفاشلة
    |--------------------------------------------------------------------------
    */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /*
    |--------------------------------------------------------------------------
    | الرسائل المعلقة
    |--------------------------------------------------------------------------
    */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | فلترة بحسب التاريخ
    |--------------------------------------------------------------------------
    */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /*
    |--------------------------------------------------------------------------
    | حالة الرسالة مع الترجمة
    |--------------------------------------------------------------------------
    */
    public function getStatusLabelAttribute(): string
    {
        $statuses = [
            'pending' => 'في الانتظار',
            'processing' => 'جاري المعالجة',
            'sent' => 'تم الإرسال',
            'failed' => 'فشل الإرسال',
            'cancelled' => 'ملغي',
        ];

        return $statuses[$this->status] ?? $this->status;
    }

    /**
     * لون حالة الرسالة
     */

    /*
    |--------------------------------------------------------------------------
    | لون حالة الرسالة
    |--------------------------------------------------------------------------
    */
    public function getStatusColorAttribute(): string
    {
        $colors = [
            'pending' => 'warning',
            'processing' => 'info',
            'sent' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
        ];

        return $colors[$this->status] ?? 'secondary';
    }

    /*
    |--------------------------------------------------------------------------
    |  أيقونة الحالة
    |--------------------------------------------------------------------------
    */
    public function getStatusIconAttribute(): string
    {
        $icons = [
            'pending' => 'ti-clock',
            'processing' => 'ti-loader',
            'sent' => 'ti-check',
            'failed' => 'ti-x',
            'cancelled' => 'ti-ban',
        ];

        return $icons[$this->status] ?? 'ti-help';
    }

    /*
    |--------------------------------------------------------------------------
    | معدل النجاح للأرقام المتعددة
    |--------------------------------------------------------------------------
    */
    public function getSuccessRateAttribute(): float
    {
        if ($this->phone_numbers_count == 0) {
            return 0;
        }

        return round(($this->successful_sends / $this->phone_numbers_count) * 100, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | تنسيق التكلفة
    |--------------------------------------------------------------------------
    */
    public function getFormattedTotalCostAttribute(): string
    {
        return number_format($this->total_cost, 4) . ' ريال';
    }

    /*
    |--------------------------------------------------------------------------
    | تنسيق أرقام الهواتف للعرض
    |--------------------------------------------------------------------------
    */
    public function getFormattedPhonesAttribute(): string
    {
        if (!$this->phone_numbers || !is_array($this->phone_numbers)) {
            return '-';
        }

        return implode(', ', $this->phone_numbers);
    }

    /*
    |--------------------------------------------------------------------------
    | تنسيق التاريخ
    |--------------------------------------------------------------------------
    */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /*
    |--------------------------------------------------------------------------
    |  تنسيق تاريخ الإرسال
    |--------------------------------------------------------------------------
    */
    public function getFormattedSentAtAttribute(): ?string
    {
        return $this->sent_at ? $this->sent_at->format('Y-m-d H:i:s') : null;
    }


    /*
    |--------------------------------------------------------------------------
    |  مدة الإرسال بالثواني
    |--------------------------------------------------------------------------
    */
    public function getSendDurationAttribute(): ?int
    {
        if (!$this->sent_at) {
            return null;
        }

        return $this->created_at->diffInSeconds($this->sent_at);
    }

    /*
    |--------------------------------------------------------------------------
    | تنسيق مدة الإرسال
    |--------------------------------------------------------------------------
    */
    public function getFormattedSendDurationAttribute(): ?string
    {
        $duration = $this->send_duration;

        if (!$duration) {
            return null;
        }

        if ($duration < 60) {
            return $duration . ' ثانية';
        } elseif ($duration < 3600) {
            return round($duration / 60) . ' دقيقة';
        } else {
            return round($duration / 3600, 1) . ' ساعة';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | تحديث حالة الإرسال
    |--------------------------------------------------------------------------
    */
    public function updateSendStatus(bool $success, array $data = []): self
    {
        $updateData = [
            'status' => $success ? 'sent' : 'failed',
            'successful_sends' => $success ? $this->phone_numbers_count : 0,
            'failed_sends' => $success ? 0 : $this->phone_numbers_count,
        ];

        if ($success) {
            $updateData['sent_at'] = now();
        } else {
            $updateData['failed_at'] = now();
        }

        if (!empty($data['provider_job_id'])) {
            $updateData['provider_job_id'] = $data['provider_job_id'];
        }

        if (!empty($data['provider_response'])) {
            $updateData['provider_response'] = $data['provider_response'];
        }

        if (!empty($data['error_message'])) {
            $updateData['error_message'] = $data['error_message'];
        }

        if (!empty($data['status_message'])) {
            $updateData['status_message'] = $data['status_message'];
        }

        $this->update($updateData);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | إعادة تعيين للإرسال مرة أخرى
    |--------------------------------------------------------------------------
    */
    public function resetForRetry(): self
    {
        $this->update([
            'status' => 'pending',
            'retry_count' => $this->retry_count + 1,
            'last_retry_at' => now(),
            'error_message' => null,
            'status_message' => 'جاري إعادة المحاولة',
            'sent_at' => null,
            'failed_at' => null,
        ]);

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | هل يمكن إعادة الإرسال؟
    |--------------------------------------------------------------------------
    */
    public function canRetry(): bool
    {
        return $this->status === 'failed' && $this->retry_count < 3;
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقة مع الشركة
    |--------------------------------------------------------------------------
    */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    /*
    |--------------------------------------------------------------------------
    | العلاقة مع المحفظة
    |--------------------------------------------------------------------------
    */
    public function portfolio(): BelongsTo
    {
        return $this->belongsTo(Portfolio::class, 'portfolio_id');
    }

    /*
    |--------------------------------------------------------------------------
    | فلترة بحسب الشركة
    |--------------------------------------------------------------------------
    */
    public function scopeByCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    /*
    |--------------------------------------------------------------------------
    | فلترة بحسب المحفظة
    |--------------------------------------------------------------------------
    */
    public function scopeByPortfolio($query, $portfolioId)
    {
        return $query->where('portfolio_id', $portfolioId);
    }
}
