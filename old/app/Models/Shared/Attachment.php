<?php

declare(strict_types=1);

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Attachment extends Model
{
    protected $fillable = [
        'disk',
        'file_path',
        'original_name',
        'mime_type',
        'extension',
        'file_size',
        'category',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }
}
