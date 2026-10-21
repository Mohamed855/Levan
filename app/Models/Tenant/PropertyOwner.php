<?php

declare(strict_types=1);
namespace App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class PropertyOwner extends Model { protected $fillable = ['property_id','owner_name','national_id_or_cr','sort_order']; public function property(): BelongsTo { return $this->belongsTo(Property::class); } }