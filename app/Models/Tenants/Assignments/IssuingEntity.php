<?php

declare(strict_types=1);

namespace App\Models\Tenants\Assignments;

use App\Models\Tenants\UserMangment\User\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class IssuingEntity extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'issuing_entities';

    protected $fillable = [
        'name',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function assignments(): HasMany
    {
        return $this->hasMany(Assignment::class, 'issuing_entity_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
