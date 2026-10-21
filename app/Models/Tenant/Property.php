<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Property extends Model
{
    use SoftDeletes;

    protected $fillable = ['assignment_id','property_number','name','type','usage_classification','short_description','city','district','administrative_region','plan_number','parcel_number','google_maps_url','latitude','longitude','boundary_geojson','aerial_image','area','deed_number','deed_date','issuing_authority','ownership_type','agent_name','agent_national_id','agency_number','agency_date','estimated_value','data_status','status','is_active','main_image','notes','created_by'];
    protected function casts(): array { return ['boundary_geojson' => 'array', 'is_active' => 'boolean', 'deed_date' => 'date', 'agency_date' => 'date', 'area' => 'decimal:2', 'estimated_value' => 'decimal:2']; }
    public function assignment(): BelongsTo { return $this->belongsTo(Assignment::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function boundaries(): HasMany { return $this->hasMany(PropertyBoundary::class)->orderBy('sort_order'); }
    public function owners(): HasMany { return $this->hasMany(PropertyOwner::class)->orderBy('sort_order'); }
    public function units(): HasMany { return $this->hasMany(PropertyUnit::class)->orderBy('sort_order'); }
    public function auctions(): BelongsToMany { return $this->belongsToMany(Auction::class, 'auction_properties')->withPivot('sort_order')->withTimestamps(); }
}