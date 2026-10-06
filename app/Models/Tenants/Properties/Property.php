<?php

namespace App\Models\Tenants\Properties;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\UserMangment\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'assignment_id',
        'property_number',
        'name',
        'type',
        'city',
        'district',
        'area',
        'deed_number',
        'estimated_value',
        'data_status',
        'status',
        'is_active',
        'main_image',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'area'            => 'decimal:2',
        'estimated_value' => 'decimal:2',
        'is_active'       => 'boolean',
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

    public function getTypeLabelAttribute(): string
    {
        return match ($this->type) {
            'residential_land'     => 'أرض سكنية',
            'villa'                => 'فيلا',
            'residential_building' => 'عمارة سكنية',
            'commercial_building'  => 'مبنى تجاري',
            'commercial_land'      => 'أرض تجارية',
            'mixed_use'            => 'متعدد الاستخدام',
            default                => $this->type ?? 'أرض سكنية',
        };
    }

    public function getDataStatusLabelAttribute(): string
    {
        return match ($this->data_status) {
            'completed'    => 'مكتمل',
            'needs_images' => 'يحتاج صور',
            'incomplete'   => 'ناقص بيانات',
            default        => 'ناقص بيانات',
        };
    }

    public function getDataStatusBadgeClassAttribute(): string
    {
        return match ($this->data_status) {
            'completed'    => 'bg-label-success',
            'needs_images' => 'bg-label-warning',
            'incomplete'   => 'bg-label-danger',
            default        => 'bg-label-secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'available'            => 'متاح',
            'assigned_to_auction'  => 'مدرج بمزاد',
            'sold'                 => 'تم البيع',
            default                => 'متاح',
        };
    }
}
