<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AdminUser;
use App\Models\Admin\AuctionTemplate;
use App\Models\Admin\AuctionTemplateVersion;
use Illuminate\Support\Facades\DB;

final class CreateAuctionTemplateAction
{
    /** @param array<string, mixed> $data */
    public function execute(AdminUser $user, array $data): AuctionTemplate
    {
        return DB::transaction(function () use ($user, $data): AuctionTemplate {
            $template = AuctionTemplate::query()->create([
                'name' => $data['name'],
                'type' => $data['type'],
                'description' => $data['description'] ?? null,
                'status' => 'draft',
                'is_active' => (bool) ($data['is_active'] ?? true),
                'created_by' => $user->id,
            ]);

            AuctionTemplateVersion::query()->create([
                'auction_template_id' => $template->id,
                'version' => 1,
                'schema' => $data['schema'],
                'status' => 'draft',
                'created_by' => $user->id,
            ]);

            return $template->load('versions');
        });
    }
}