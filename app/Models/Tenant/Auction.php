<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use App\Models\Shared\Attachment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Auction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'name',
        'description',
        'start_date',
        'start_time',
        'end_date',
        'end_time',
        'type',
        'category',
        'city',
        'expected_properties_count',
        'supervising_entity',
        'platform_name',
        'platform_url',
        'notes',
        'cover_image',
        'template_versions',
        'template_snapshot',
        'template_bindings',
        'status',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'expected_properties_count' => 'integer',
        'template_versions' => 'array',
        'template_snapshot' => 'array',
        'template_bindings' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'auction_properties', 'auction_id', 'property_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        $type = $this->type instanceof \BackedEnum ? $this->type->value : (string) ($this->type ?? '');

        return match ($type) {
            'electronic' => 'إلكتروني',
            'in_person' => 'حضوري',
            'hybrid' => 'هجين',
            default => 'إلكتروني',
        };
    }

    public function getCategoryLabelAttribute(): string
    {
        $category = $this->category instanceof \BackedEnum ? $this->category->value : (string) ($this->category ?? '');

        return match ($category) {
            'residential' => 'سكني',
            'commercial' => 'تجاري',
            'investment' => 'استثماري',
            'educational' => 'تعليمي',
            'industrial' => 'صناعي',
            'mixed_use' => 'متعدد الاستخدام',
            default => 'عقارات متنوعة',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'نشط',
            'in_preparation' => 'قيد التجهيز',
            'pending_approval' => 'بانتظار الموافقة',
            'completed' => 'مكتمل',
            'suspended' => 'معلق',
            default => 'قيد التجهيز',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-label-success',
            'in_preparation' => 'bg-label-warning',
            'pending_approval' => 'bg-label-info',
            'completed' => 'bg-label-primary',
            'suspended' => 'bg-label-danger',
            default => 'bg-secondary',
        };
    }
}