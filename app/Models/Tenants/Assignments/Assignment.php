<?php

namespace App\Models\Tenants\Assignments;

use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Models\Tenants\UserMangment\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
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
        'end_date'   => 'date',
        'is_active'  => 'boolean',
    ];

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class, 'assignment_id');
    }

    public function auctions(): HasMany
    {
        return $this->hasMany(Auction::class, 'assignment_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCategoryLabelAttribute(): string
    {
        return match ($this->category) {
            'lands'                 => 'أراضي',
            'villas'                => 'فلل سكنية',
            'commercial_buildings'  => 'عمائر تجارية',
            'educational'           => 'مباني تعليمية',
            'industrial'            => 'أراضي صناعية',
            default                 => $this->category ?? 'أراضي',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'active'       => 'نشط',
            'under_review' => 'قيد المراجعة',
            'suspended'    => 'متوقف',
            'auctioned'    => 'محول لمزاد',
            default        => 'نشط',
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'active'       => 'bg-label-success',
            'under_review' => 'bg-label-warning',
            'suspended'    => 'bg-label-danger',
            'auctioned'    => 'bg-label-info',
            default        => 'bg-label-primary',
        };
    }
}
