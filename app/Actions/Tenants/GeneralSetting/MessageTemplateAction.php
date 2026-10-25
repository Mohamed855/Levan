<?php

declare(strict_types=1);

namespace App\Actions\Tenants\GeneralSetting;

use App\Models\Tenants\GeneralSetting\MessageTemplate\MessageTemplate;
use Illuminate\Support\Facades\DB;

final class MessageTemplateAction
{
    public function create(array $data, int|string $userId): MessageTemplate
    {
        return DB::transaction(fn (): MessageTemplate => MessageTemplate::query()->create($data + ['created_by' => $userId]));
    }

    public function update(MessageTemplate $template, array $data): MessageTemplate
    {
        return DB::transaction(function () use ($template, $data): MessageTemplate {
            $template->update($data);

            return $template->refresh();
        });
    }

    public function delete(MessageTemplate $template): void
    {
        DB::transaction(fn () => $template->delete());
    }

    public function toggleStatus(MessageTemplate $template): bool
    {
        return DB::transaction(function () use ($template): bool {
            $active = ! $template->is_active;
            $template->update(['is_active' => $active]);

            return $active;
        });
    }
}
