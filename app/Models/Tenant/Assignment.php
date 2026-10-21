<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Assignment extends Model
{
    use SoftDeletes;

    protected $fillable = ['assignment_number', 'name', 'issuing_entity', 'entity_logo', 'city', 'district', 'category', 'start_date', 'end_date', 'notes', 'status', 'is_active', 'created_by'];
    protected function casts(): array { return ['start_date' => 'date', 'end_date' => 'date', 'is_active' => 'boolean']; }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
    public function properties(): HasMany { return $this->hasMany(Property::class); }
    public function auctions(): HasMany { return $this->hasMany(Auction::class); }
}