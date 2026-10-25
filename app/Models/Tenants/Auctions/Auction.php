<?php

declare(strict_types=1);

namespace App\Models\Tenants\Auctions;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'project_manager_name',
        'platform_name',
        'platform_url',
        'notes',
        'cover_image',
        'report_image',
        'marketing_data',
        'template_versions',
        'template_snapshot',
        'template_bindings',
        'brochure_template_id',
        'billboard_template_id',
        'social_template_id',
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
        'brochure_template_id' => 'integer',
        'billboard_template_id' => 'integer',
        'social_template_id' => 'integer',
        'marketing_data' => 'array',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function properties(): BelongsToMany
    {
        return $this->belongsToMany(Property::class, 'auction_properties', 'auction_id', 'property_id')
            ->withPivot([
                'sort_order',
                'result_status',
                'opening_price',
                'sale_price',
                'participants_count',
                'bids_count',
                'unsold_reason',
                'board_data',
            ])
            ->orderByPivot('sort_order')
            ->withTimestamps();
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AuctionAttachment::class, 'auction_id');
    }

    public function reportGenerations(): HasMany
    {
        return $this->hasMany(AuctionReportGeneration::class)->latest('id');
    }

    public function latestReportGeneration()
    {
        return $this->hasOne(AuctionReportGeneration::class)->latestOfMany('id');
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
        return match ($this->category) {
            'residential' => 'سكني',
            'commercial' => 'تجاري',
            'investment' => 'استثماري',
            'educational' => 'تعليمي',
            'lands' => 'أراضي',
            'mixed_use' => 'متعدد الاستخدام',
            default => $this->category ?? 'أراضي',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'in_preparation' => 'قيد الإعداد',
            'pending_approval' => 'بانتظار الاعتماد',
            'active' => 'نشط',
            'completed' => 'منتهي',
            'suspended' => 'معلق',
            default => 'قيد الإعداد',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'in_preparation' => 'bg-label-warning',
            'pending_approval' => 'bg-label-info',
            'active' => 'bg-label-success',
            'completed' => 'bg-label-dark',
            'suspended' => 'bg-label-danger',
            default => 'bg-label-primary',
        };
    }
}
