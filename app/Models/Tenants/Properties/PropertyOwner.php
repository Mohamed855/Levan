<?php

declare(strict_types=1);

namespace App\Models\Tenants\Properties;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyOwner extends Model
{
    use HasFactory;

    protected $fillable = [
        'property_id',
        'owner_name',
        'national_id_or_cr',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
