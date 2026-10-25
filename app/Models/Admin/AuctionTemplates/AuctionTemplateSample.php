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

class AuctionTemplateSample extends Model
{
    use ActivityLogTrait, HasFactory, SoftDeletes;

    protected $connection = 'mysql';

    protected $table = 'auction_template_samples';

    protected $fillable = [
        'name',
        'type',
        'description',
        'preview_image_path',
        'schema',
        'is_active',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'type' => AuctionTemplateType::class,
            'schema' => 'array',
            'is_active' => 'boolean',
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

    public function getPreviewImageUrlAttribute(): ?string
    {
        return $this->preview_image_path
            ? Storage::disk('central_public')->url($this->preview_image_path)
            : null;
    }

    public static function getLogName(): string
    {
        return 'auction_template_samples';
    }

    public static function getModelNameArabic(): string
    {
        return 'عينة قالب مزاد';
    }

    public static function getFieldTranslations(): array
    {
        return [
            'name' => 'اسم العينة',
            'type' => 'نوع القالب',
            'description' => 'الوصف',
            'preview_image_path' => 'صورة المعاينة',
            'schema' => 'تصميم العينة',
            'is_active' => 'الحالة',
        ];
    }
}
