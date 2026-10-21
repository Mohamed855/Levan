<?php

declare(strict_types=1);

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class AuctionTemplate extends Model
{
    use SoftDeletes;

    public const TYPES = ['brochure', 'billboard', 'social'];

    protected $fillable = [
        'name', 'type', 'description', 'status', 'is_active', 'current_version', 'created_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(AdminUser::class, 'created_by');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(AuctionTemplateVersion::class)->orderByDesc('version');
    }

    public function publishedVersions(): HasMany
    {
        return $this->versions()->where('status', 'published');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->where('is_active', true);
    }
}