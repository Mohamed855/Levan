<?php

declare(strict_types=1);

namespace App\Models\Tenants\Properties;

use App\Enums\Tenants\Properties\PropertyBoundaryDirection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyBoundary extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'direction',
        'border_type',
        'border_description',
        'length',
        'sort_order',
    ];

    protected $casts = [
        'length' => 'decimal:2',
        'sort_order' => 'integer',
        'direction' => PropertyBoundaryDirection::class,
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }

    public function getDirectionLabelAttribute(): string
    {
        return $this->direction instanceof PropertyBoundaryDirection
            ? $this->direction->label()
            : (string) $this->direction;
    }
}
