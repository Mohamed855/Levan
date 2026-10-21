<?php

declare(strict_types=1);
namespace App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
final class PropertyUnit extends Model { protected $fillable = ['property_id','unit_number','unit_type','area','current_use','status','sort_order']; protected function casts(): array { return ['area'=>'decimal:2']; } public function property(): BelongsTo { return $this->belongsTo(Property::class); } }