<?php

namespace App\Models\Tenants\Communication\MessageLog;

use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MessageLog extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'message_logs';

    protected $fillable = [
        'sender_id',
        'sender_type',
        'message_content',
        'message_type',
        'platform',
        'template_id',
        'template_name',
        'target_model',
        'recipient_ids',
        'recipients_data',
        'recipients_count',
        'status',
        'status_message',
        'response_data',
        'sent_count',
        'failed_count',
        'cost',
        'total_cost',
        'scheduled_at',
        'sent_at',
        'failed_at',
        'metadata',
        'reference_number',
        'batch_id',
    ];

    protected $casts = [
        'recipient_ids' => 'array',
        'recipients_data' => 'array',
        'response_data' => 'array',
        'metadata' => 'array',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
        'failed_at' => 'datetime',
        'cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    protected $dates = [
        'scheduled_at',
        'sent_at',
        'failed_at',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relations
    |--------------------------------------------------------------------------
    */

    /**
     * العلاقة مع المرسل
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * العلاقة مع القالب
     */
    public function template(): BelongsTo
    {
        return $this->belongsTo(MessageTemplate::class, 'template_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /**
     * فلترة بحسب الحالة
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * فلترة بحسب نوع الرسالة
     */
    public function scopeByMessageType($query, $type)
    {
        return $query->where('message_type', $type);
    }

    /**
     * فلترة بحسب الجدول المستهدف
     */
    public function scopeByTargetModel($query, $model)
    {
        return $query->where('target_model', $model);
    }

    /**
     * فلترة بحسب المرسل
     */
    public function scopeBySender($query, $senderId)
    {
        return $query->where('sender_id', $senderId);
    }

    /**
     * فلترة بحسب التاريخ
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * الرسائل المرسلة بنجاح
     */
    public function scopeSent($query)
    {
        return $query->where('status', 'sent');
    }

    /**
     * الرسائل الفاشلة
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * الرسائل المعلقة
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors & Mutators
    |--------------------------------------------------------------------------
    */

    /**
     * حالة الرسالة مع الترجمة
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

    /**
     * نوع الرسالة مع الترجمة
     */
    public function getMessageTypeLabelAttribute(): string
    {
        $types = [
            'sms' => 'رسالة نصية',
            'email' => 'بريد إلكتروني',
            'push' => 'إشعار',
            'whatsapp' => 'واتساب',
        ];

        return $types[$this->message_type] ?? $this->message_type;
    }

    /**
     * معدل النجاح
     */
    public function getSuccessRateAttribute(): float
    {
        if ($this->recipients_count == 0) {
            return 0;
        }

        return round(($this->sent_count / $this->recipients_count) * 100, 2);
    }

    /**
     * معدل الفشل
     */
    public function getFailureRateAttribute(): float
    {
        if ($this->recipients_count == 0) {
            return 0;
        }

        return round(($this->failed_count / $this->recipients_count) * 100, 2);
    }

    /**
     * تنسيق التاريخ
     */
    public function getFormattedCreatedAtAttribute(): string
    {
        return $this->created_at->format('Y-m-d H:i:s');
    }

    /**
     * تنسيق تاريخ الإرسال
     */
    public function getFormattedSentAtAttribute(): ?string
    {
        return $this->sent_at ? $this->sent_at->format('Y-m-d H:i:s') : null;
    }

    /**
     * مدة الإرسال
     */
    public function getSendDurationAttribute(): ?string
    {
        if (!$this->sent_at) {
            return null;
        }

        $diff = $this->created_at->diffInSeconds($this->sent_at);

        if ($diff < 60) {
            return $diff . ' ثانية';
        } elseif ($diff < 3600) {
            return round($diff / 60) . ' دقيقة';
        } else {
            return round($diff / 3600, 1) . ' ساعة';
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Static Methods
    |--------------------------------------------------------------------------
    */

    /**
     * الحصول على الإحصائيات العامة
     */
    public static function getGeneralStats(): array
    {
        return [
            'total' => self::count(),
            'sent' => self::sent()->count(),
            'failed' => self::failed()->count(),
            'pending' => self::pending()->count(),
            'today' => self::whereDate('created_at', today())->count(),
            'this_week' => self::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
            'this_month' => self::whereMonth('created_at', now()->month)->count(),
        ];
    }

    /**
     * الحصول على إحصائيات بحسب الجدول المستهدف
     */
    public static function getStatsByTargetModel(string $model): array
    {
        return [
            'total' => self::where('target_model', $model)->count(),
            'sent' => self::where('target_model', $model)->sent()->count(),
            'failed' => self::where('target_model', $model)->failed()->count(),
            'pending' => self::where('target_model', $model)->pending()->count(),
        ];
    }

    /**
     * الحصول على إحصائيات بحسب نوع الرسالة
     */
    public static function getStatsByMessageType(string $type): array
    {
        return [
            'total' => self::where('message_type', $type)->count(),
            'sent' => self::where('message_type', $type)->sent()->count(),
            'failed' => self::where('message_type', $type)->failed()->count(),
            'pending' => self::where('message_type', $type)->pending()->count(),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | العلاقة مع الرسائل الفردية
    |--------------------------------------------------------------------------
    */
    public function individualLogs(): HasMany
    {
        return $this->hasMany(MessageIndividualLog::class, 'batch_log_id');
    }


    /*
    |--------------------------------------------------------------------------
    | الرسائل الفردية المرسلة بنجاح
    |--------------------------------------------------------------------------
    */
    public function sentIndividualLogs(): HasMany
    {
        return $this->hasMany(MessageIndividualLog::class, 'batch_log_id')
            ->where('status', 'sent');
    }

    
    /*
    |--------------------------------------------------------------------------
    | الرسائل الفردية الفاشلة
    |--------------------------------------------------------------------------
    */
    public function failedIndividualLogs(): HasMany
    {
        return $this->hasMany(MessageIndividualLog::class, 'batch_log_id')
            ->where('status', 'failed');
    }
}
