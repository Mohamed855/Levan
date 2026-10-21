<?php

declare(strict_types=1);

namespace App\Models\Shared;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

final class WizardDraft extends Model
{
    use HasUuids;

    protected $fillable = ['guard', 'user_id', 'resource_key', 'last_step', 'draft_data', 'draft_files'];

    protected function casts(): array { return ['draft_data' => 'array', 'draft_files' => 'array', 'last_step' => 'integer']; }

    protected static function booted(): void
    {
        static::deleted(fn (self $draft) => Storage::disk('local')->deleteDirectory('wizard-drafts/'.$draft->id));
    }
}