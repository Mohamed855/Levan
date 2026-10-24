<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AdminUser;
use App\Models\Admin\AuctionTemplate;
use App\Models\Admin\AuctionTemplateVersion;
use Illuminate\Support\Facades\DB;

final class UpdateAuctionTemplateAction
{
    /** @param array<string, mixed> $data */
    public function execute(AuctionTemplate $template, AdminUser $user, array $data): AuctionTemplate
    {
        return DB::transaction(function () use ($template, $user, $data): AuctionTemplate {
            $template = AuctionTemplate::query()->lockForUpdate()->findOrFail($template->getKey());

            $template->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? false),
            ]);

            if (array_key_exists('schema', $data)) {
                $latestVersion = (int) $template->versions()->max('version');
                AuctionTemplateVersion::query()->create([
                    'auction_template_id' => $template->id,
                    'version' => $latestVersion + 1,
                    'schema' => $data['schema'],
                    'status' => 'draft',
                    'created_by' => $user->id,
                ]);
                $template->update(['status' => 'draft']);
            }

            return $template->refresh()->load('versions');
        });
    }
}
