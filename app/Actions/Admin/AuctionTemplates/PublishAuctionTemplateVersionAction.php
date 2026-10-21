<?php

declare(strict_types=1);

namespace App\Actions\Admin\AuctionTemplates;

use App\Models\Admin\AuctionTemplate;
use App\Models\Admin\AuctionTemplateVersion;
use Illuminate\Support\Facades\DB;

final class PublishAuctionTemplateVersionAction
{
    public function execute(AuctionTemplate $template, AuctionTemplateVersion $version): void
    {
        DB::transaction(function () use ($template, $version): void {
            abort_unless($version->auction_template_id === $template->id, 404);
            $version->update(['status' => 'published', 'published_at' => now()]);
            $template->update(['status' => 'published', 'current_version' => $version->version]);
        });
    }
}