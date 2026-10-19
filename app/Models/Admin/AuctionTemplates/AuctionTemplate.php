<?php

declare(strict_types=1);

namespace App\Models\Admin\AuctionTemplates;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Traits\ActivityLogTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

class AuctionTemplate extends Model
{
    use ActivityLogTrait, HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'auction_templates';

    protected $fillable = [
        'name',
        'type',
        'description',
        'file_path',
        'thumbnail_path',
        'field_names',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type'        => AuctionTemplateType::class,
            'field_names' => 'array',
            'is_active'   => 'boolean',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByType(Builder $query, string|AuctionTemplateType $type): Builder
    {
        $value = $type instanceof AuctionTemplateType ? $type->value : $type;
        return $query->where('type', $value);
    }

    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path ? Storage::disk('central_public')->url($this->file_path) : null;
    }

    public function getThumbnailUrlAttribute(): ?string
    {
        return $this->thumbnail_path ? Storage::disk('central_public')->url($this->thumbnail_path) : null;
    }

    /*
    |============================================================================
    |                          Activity Log
    |============================================================================
    */
    public static function getLogName(): string
    {
        return 'قوالب المزادات';
    }

    public static function getModelNameArabic(): string
    {
        return 'قالب مزاد';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'name'           => 'اسم القالب',
            'type'           => 'نوع القالب',
            'description'    => 'الوصف',
            'file_path'      => 'ملف PDF',
            'thumbnail_path' => 'صورة المعاينة',
            'field_names'    => 'حقول القالب',
            'is_active'      => 'الحالة',
        ];
    }
}
