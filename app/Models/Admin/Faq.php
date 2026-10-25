<?php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Faq extends Model
{
    use SoftDeletes;

    protected $connection = 'mysql';
    protected $fillable = ['question', 'answer', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function scopeActive(Builder $query): Builder { return $query->where('is_active', true); }
}
