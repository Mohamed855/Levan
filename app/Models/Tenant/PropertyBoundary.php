<?php

declare(strict_types=1);
namespace App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class PropertyBoundary extends Model { protected $fillable = ['property_id','direction','border_type','border_description','length','sort_order']; protected function casts(): array { return ['length'=>'decimal:2']; } public function property(): BelongsTo { return $this->belongsTo(Property::class); } }