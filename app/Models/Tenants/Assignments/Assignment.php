<?php

namespace App\Models\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
use App\Models\Shared\Attachment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Assignment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_number',
        'name',
        'issuing_entity',
        'entity_logo',
        'city',
        'district',
        'category',
        'start_date',
        'end_date',
        'notes',
        'status',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'assignment_id');
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'assignment_id');
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable');
    }

    public function assignmentImage(): MorphOne
    {
        return $this->morphOne(Attachment::class, 'attachable')
            ->ofMany(['id' => 'max'], fn (Builder $query): Builder => $query->where(
                'category',
                AssignmentAttachmentCategory::IMAGE->value,
            ));
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'lands' => 'أراضي',
            'villas' => 'فلل سكنية',
            'commercial_buildings' => 'عمائر تجارية',
            'educational' => 'مباني تعليمية',
            'industrial' => 'أراضي صناعية',
            default => $this->category ?? 'أراضي',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active' => 'نشط',
            'under_review' => 'قيد المراجعة',
            'suspended' => 'متوقف',
            'auctioned' => 'محول لمزاد',
            default => 'نشط',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active' => 'bg-success',
            'under_review' => 'bg-warning',
            'suspended' => 'bg-danger',
            'auctioned' => 'bg-info',
            default => 'bg-primary',
        };
    }
}
