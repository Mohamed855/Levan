<?php

declare(strict_types=1);

namespace App\Models\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyAttachmentCategory;
use App\Enums\Tenants\Properties\PropertyDataStatus;
use App\Enums\Tenants\Properties\PropertyOwnershipType;
use App\Enums\Tenants\Properties\PropertyType;
use App\Enums\Tenants\Properties\PropertyUsageClassification;
use App\Models\Shared\Attachment;
use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'property_number',
        'name',
        'type',
        'usage_classification',
        'short_description',
        'city',
        'district',
        'administrative_region',
        'plan_number',
        'parcel_number',
        'google_maps_url',
        'latitude',
        'longitude',
        'area',
        'deed_number',
        'deed_date',
        'issuing_authority',
        'ownership_type',
        'agent_name',
        'agent_national_id',
        'agency_number',
        'agency_date',
        'estimated_value',
        'data_status',
        'status',
        'is_active',
        'main_image',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'area' => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'is_active' => 'boolean',
        'deed_date' => 'date',
        'agency_date' => 'date',
        'type' => PropertyType::class,
        'usage_classification' => PropertyUsageClassification::class,
        'ownership_type' => PropertyOwnershipType::class,
        'data_status' => PropertyDataStatus::class,
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class, 'assignment_id');
    }

    public function auctions(): BelongsToMany
    {
        return $this->belongsToMany(Auction::class, 'auction_properties', 'property_id', 'auction_id')
            ->withPivot('sort_order')
            ->withTimestamps();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function boundaries(): HasMany
    {
        return $this->hasMany(PropertyBoundary::class, 'property_id')->orderBy('sort_order')->orderBy('id');
    }

    public function owners(): HasMany
    {
        return $this->hasMany(PropertyOwner::class, 'property_id')->orderBy('sort_order')->orderBy('id');
    }

    public function units(): HasMany
    {
        return $this->hasMany(PropertyUnit::class, 'property_id')->orderBy('sort_order')->orderBy('id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function deedAttachment(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('category', PropertyAttachmentCategory::DEED_DOCUMENT->value);
    }

    public function cadastralPlanAttachment(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('category', PropertyAttachmentCategory::CADASTRAL_PLAN->value);
    }

    public function buildingPermitAttachment(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('category', PropertyAttachmentCategory::BUILDING_PERMIT->value);
    }

    public function coordinatesAttachment(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('category', PropertyAttachmentCategory::COORDINATES_STATEMENT->value);
    }

    public function additionalDocuments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->where('category', PropertyAttachmentCategory::ADDITIONAL_DOCUMENT->value);
    }

    public function galleryAttachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->where('category', PropertyAttachmentCategory::GALLERY_IMAGE->value);
    }

    public function assignmentLetterAttachment(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->where('category', PropertyAttachmentCategory::ASSIGNMENT_LETTER_IMAGE->value);
    }

    public function getIsLinkedAttribute(): bool
    {
        return ! is_null($this->assignment_id);
    }

    public function getTypeLabelAttribute(): string
    {
        if ($this->type instanceof PropertyType) {
            return $this->type->label();
        }

        return match ($this->type) {
            'residential_land' => 'أرض سكنية',
            'villa' => 'فيلا',
            'residential_building' => 'عمارة سكنية',
            'commercial_building' => 'مبنى تجاري',
            'commercial_land' => 'أرض تجارية',
            'mixed_use' => 'متعدد الاستخدام',
            default => (string) ($this->type ?? 'أرض سكنية'),
        };
    }

    public function getUsageClassificationLabelAttribute(): string
    {
        if ($this->usage_classification instanceof PropertyUsageClassification) {
            return $this->usage_classification->label();
        }

        return match ($this->usage_classification) {
            'residential' => 'سكني',
            'commercial' => 'تجاري',
            'agricultural' => 'زراعي',
            'industrial' => 'صناعي',
            'mixed_use' => 'متعدد الاستخدام',
            'other' => 'أخرى',
            default => (string) ($this->usage_classification ?? '-'),
        };
    }

    public function getOwnershipTypeLabelAttribute(): string
    {
        if ($this->ownership_type instanceof PropertyOwnershipType) {
            return $this->ownership_type->label();
        }

        return match ($this->ownership_type) {
            'sole_ownership' => 'ملك',
            'shared_ownership' => 'مشاع / ملك مشترك',
            'corporate' => 'شركة / جهة اعتبارية',
            'waqf' => 'وقف',
            'other' => 'أخرى',
            default => (string) ($this->ownership_type ?? '-'),
        };
    }

    public function getDataStatusLabelAttribute(): string
    {
        if ($this->data_status instanceof PropertyDataStatus) {
            return $this->data_status->label();
        }

        return match ($this->data_status) {
            'completed' => 'مكتمل البيانات',
            'needs_review' => 'تحتاج للمراجعة',
            'needs_images' => 'يحتاج صور',
            'incomplete' => 'غير مكتمل',
            default => 'غير مكتمل',
        };
    }

    public function getDataStatusBadgeClassAttribute(): string
    {
        if ($this->data_status instanceof PropertyDataStatus) {
            return $this->data_status->badgeClass();
        }

        return match ($this->data_status) {
            'completed' => 'bg-label-success',
            'needs_review' => 'bg-label-warning',
            'needs_images' => 'bg-label-info',
            'incomplete' => 'bg-label-danger',
            default => 'bg-secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available' => 'متاح',
            'assigned_to_auction' => 'مدرج بمزاد',
            'sold' => 'تم البيع',
            default => 'متاح',
        };
    }
}
