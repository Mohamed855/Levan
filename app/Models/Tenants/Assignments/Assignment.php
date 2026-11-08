<?php

namespace App\Models\Tenants\Assignments;

use App\Enums\Tenants\Assignments\AssignmentAttachmentCategory;
use App\Models\Shared\Attachment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User\User;
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
        'issuing_entity_id',
        'issuing_entity',
        'city',
        'district',
        'category',
        'start_date',
        'end_date',
        'notes',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'issuing_entity_id' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function issuingEntity(): BelongsTo
    {
        return $this->belongsTo(IssuingEntity::class, 'issuing_entity_id');
    }

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
        if ($this->category instanceof \App\Enums\Tenants\Assignments\AssignmentCategory) {
            return $this->category->label();
        }

        $enum = \App\Enums\Tenants\Assignments\AssignmentCategory::tryFrom((string) $this->category);
        if ($enum) {
            return $enum->label();
        }

        return match ($this->category) {
            'residential', 'lands', 'villas' => 'سكنية',
            'commercial', 'commercial_buildings' => 'تجارية',
            'agricultural' => 'زراعية',
            'industrial' => 'صناعية',
            'mixed_use', 'educational' => 'متعدد الأستخدامات',
            default => (string) ($this->category ?? 'سكنية'),
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->is_active ? 'نشط' : 'متوقف';
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return $this->is_active ? 'bg-success' : 'bg-danger';
    }
}
