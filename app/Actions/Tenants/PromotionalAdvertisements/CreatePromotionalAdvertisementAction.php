<?php

declare(strict_types=1);

namespace App\Actions\Tenants\PromotionalAdvertisements;

use App\Jobs\Tenants\PromotionalAdvertisements\GeneratePromotionalAdvertisementJob;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\PromotionalAdvertisements\PromotionalAdvertisement;
use App\Services\Tenants\Shared\CentralAuctionTemplatesGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class CreatePromotionalAdvertisementAction
{
    public function __construct(
        private readonly CentralAuctionTemplatesGateway $centralGateway,
    ) {}

    /**
     * @param array{template_type: string, auction_template_id: int, auction_id: int} $data
     */
    public function execute(array $data, ?int $userId = null): PromotionalAdvertisement
    {
        $type = (string) ($data['template_type'] ?? '');
        $templateId = (int) ($data['auction_template_id'] ?? 0);
        $auctionId = (int) ($data['auction_id'] ?? 0);

        if (! in_array($type, ['billboard', 'social'], true)) {
            throw ValidationException::withMessages([
                'template_type' => 'نوع القالب المختار غير صالح.',
            ]);
        }

        $template = $this->centralGateway->findActive($templateId, $type);
        if (! $template) {
            throw ValidationException::withMessages([
                'auction_template_id' => 'القالب المختار غير موجود أو غير نشط في النظام.',
            ]);
        }

        $auction = Auction::query()->findOrFail($auctionId);

        return DB::transaction(function () use ($data, $template, $auction, $userId): PromotionalAdvertisement {
            /** @var PromotionalAdvertisement $ad */
            $ad = PromotionalAdvertisement::query()->create([
                'auction_id' => $auction->id,
                'auction_template_id' => $template->id,
                'template_type' => $template->type instanceof \BackedEnum ? $template->type->value : (string) $template->type,
                'template_name' => $template->name,
                'status' => 'processing',
                'created_by' => $userId ?? auth('tenant')->id(),
            ]);

            $tenantKey = (string) (tenancy()->tenant?->getTenantKey() ?? tenant('id') ?? '');

            // Dispatch generation job
            if (config('auction-renderer.queue_connection') === 'sync' || app()->environment('testing')) {
                dispatch_sync(new GeneratePromotionalAdvertisementJob($tenantKey, $ad->id));
            } else {
                GeneratePromotionalAdvertisementJob::dispatch($tenantKey, $ad->id);
            }

            return $ad->fresh();
        });
    }
}
