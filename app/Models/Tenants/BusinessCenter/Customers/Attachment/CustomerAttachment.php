<?php

namespace App\Models\Tenants\BusinessCenter\Customers\Attachment;

use App\Models\Tenants\BusinessCenter\Customers\Customer;
use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class CustomerAttachment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'customer_id',
        'created_by',
        'description',
        'file_path',
        'file_type',
        'file_size',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
        'file_size' => 'integer', // إضافة cast للحجم
    ];

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
    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size;

        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2).' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' B';
        }
    }

    public function getFileUrlAttribute(): string
    {
        return Storage::disk('tenant_public')->url($this->file_path);
    }

    public function getFileTypeIconAttribute(): string
    {
        return match (strtolower($this->file_type)) {
            'pdf' => 'ti ti-file-type-pdf text-danger',
            'doc', 'docx' => 'ti ti-file-type-doc text-primary',
            'xls', 'xlsx' => 'ti ti-file-type-xls text-success',
            'ppt', 'pptx' => 'ti ti-presentation text-warning', // إضافة PowerPoint
            'jpg', 'jpeg', 'png', 'gif' => 'ti ti-photo text-info',
            'zip', 'rar' => 'ti ti-file-zip text-warning',
            'txt' => 'ti ti-file-text text-muted', // إضافة ملفات نصية
            default => 'ti ti-file text-secondary'
        };
    }

    public function getFileTypeClassAttribute(): string
    {
        return match (strtolower($this->file_type)) {
            'pdf' => 'bg-label-danger',
            'doc', 'docx' => 'bg-label-primary',
            'xls', 'xlsx' => 'bg-label-success',
            'ppt', 'pptx' => 'bg-label-warning', // إضافة PowerPoint
            'jpg', 'jpeg', 'png', 'gif' => 'bg-label-info',
            'zip', 'rar' => 'bg-label-warning',
            'txt' => 'bg-label-dark', // إضافة ملفات نصية
            default => 'bg-secondary'
        };
    }

    // Accessor إضافي لاسم الملف
    public function getFileNameAttribute(): string
    {
        return basename($this->file_path);
    }

    // Accessor للتحقق من كون الملف صورة
    public function getIsImageAttribute(): bool
    {
        return in_array(strtolower($this->file_type), ['jpg', 'jpeg', 'png', 'gif']);
    }

    // Scopes
    public function scopeForCustomer($query, $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByFileType($query, $fileType)
    {
        return $query->where('file_type', strtolower($fileType));
    }

    public function scopeActive($query)
    {
        return $query->whereNull('deleted_at');
    }

    public function scopeRecent($query, $days = 7)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    // Boot method محسن
    protected static function boot()
    {
        parent::boot();

        // حذف الملف عند الحذف النهائي فقط
        static::forceDeleted(function ($attachment) {
            if (Storage::disk('tenant_public')->exists($attachment->file_path)) {
                Storage::disk('tenant_public')->delete($attachment->file_path);
            }
        });

        // تسجيل المستخدم الذي أنشأ المرفق تلقائياً
        static::creating(function ($attachment) {
            if (! $attachment->created_by && auth()->check()) {
                $attachment->created_by = auth()->id();
            }
        });
    }

    /**
     * التحقق من وجود الملف فعلياً
     */
    public function fileExists(): bool
    {
        return Storage::disk('tenant_public')->exists($this->file_path);
    }

    /**
     * الحصول على مسار الملف الكامل
     */
    public function getFullFilePathAttribute(): string
    {
        return Storage::disk('tenant_public')->path($this->file_path);
    }
}
