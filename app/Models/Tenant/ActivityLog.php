<?php

declare(strict_types=1);

namespace App\Models\Tenant;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ActivityLog extends Model
{
    protected $table = 'activity_log';

    protected $fillable = [
        'log_name',
        'description',
        'subject_type',
        'subject_id',
        'event',
        'causer_type',
        'causer_id',
        'batch_uuid',
        'attribute_changes',
        'properties',
    ];

    protected function casts(): array
    {
        return [
            'attribute_changes' => 'array',
            'properties' => 'array',
        ];
    }

    public function causer(): MorphTo
    {
        return $this->morphTo();
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public function getEventBadge(): string
    {
        $color = match ($this->event) {
            'created' => 'success',
            'updated' => 'info',
            'deleted' => 'danger',
            'restored' => 'warning',
            default => 'secondary',
        };

        $label = match ($this->event) {
            'created' => 'إنشاء',
            'updated' => 'تعديل',
            'deleted' => 'حذف',
            'restored' => 'استرجاع',
            default => (string) $this->event,
        };

        return "<span class=\"badge bg-label-{$color}\">{$label}</span>";
    }
}
