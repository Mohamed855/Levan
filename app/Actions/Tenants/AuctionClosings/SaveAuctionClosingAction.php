<?php

declare(strict_types=1);

namespace App\Actions\Tenants\AuctionClosings;

use App\Models\Tenants\AuctionClosings\AuctionClosing;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;
use App\Services\Tenants\Auctions\AuctionReportInputService;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use App\Services\Tenants\Auctions\Reports\AuctionReportDataBuilder;
use App\Services\Tenants\Auctions\Reports\AuctionReportLock;
use App\Services\Tenants\Auctions\Reports\AuctionReportRefreshService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

final class SaveAuctionClosingAction
{
    public function execute(Auction $auction, array $data, ?int $actorId): AuctionClosing
    {
        $storedPaths = [];
        $removedPaths = [];
        try {
            $closing = DB::transaction(function () use ($auction, $data, $actorId, &$storedPaths, &$removedPaths): AuctionClosing {
                $auction = Auction::query()->lockForUpdate()->findOrFail($auction->id);
                app(AuctionReportLock::class)->assertClosingAllowed($auction);
                $properties = $auction->properties()->get();
                $ids = $properties->modelKeys();
                $results = $data['results'];
                if ($ids === [] || array_diff($ids, array_keys($results)) !== [] || array_diff(array_keys($results), $ids) !== []) {
                    throw ValidationException::withMessages(['results' => 'يجب تسجيل نتيجة لكل عقار من عقارات المزاد فقط.']);
                }
                foreach ($results as $result) {
                    if (! in_array($result['status'] ?? null, ['sold', 'unsold'], true)) {
                        throw ValidationException::withMessages(['results' => 'حدد نتيجة البيع لكل عقار.']);
                    }
                    if ($result['status'] === 'sold' && (blank($result['buyer_name'] ?? null) || ! is_numeric($result['sale_price'] ?? null) || $result['sale_price'] < 0)) {
                        throw ValidationException::withMessages(['results' => 'اسم المشتري وقيمة الترسية مطلوبان للعقار المباع.']);
                    }
                    if ($result['status'] === 'unsold' && blank($result['unsold_reason'] ?? null)) {
                        throw ValidationException::withMessages(['results' => 'سبب عدم البيع مطلوب.']);
                    }
                }
                $normalized = app(AuctionReportInputService::class)->pivotData($ids, $results, $properties);
                foreach ($normalized as $id => &$pivot) {
                    $pivot['buyer_name'] = $results[$id]['status'] === 'sold' ? $results[$id]['buyer_name'] : null;
                    $pivot['result_notes'] = $results[$id]['result_notes'] ?? null;
                    foreach (['opening_price', 'participants_count', 'bids_count'] as $field) {
                        if (! array_key_exists($field, $results[$id])) {
                            $pivot[$field] = $properties->firstWhere('id', $id)->pivot->{$field};
                        }
                    }
                    Property::whereKey($id)->update(['status' => $pivot['result_status'] === 'sold' ? 'sold' : 'available']);
                }
                unset($pivot);
                $auction->properties()->sync($normalized);
                $marketing = $auction->marketing_data ?? [];
                foreach (['results_narrative', 'board_removal_date'] as $field) {
                    if (array_key_exists($field, $data)) {
                        $marketing[$field] = $data[$field];
                    }
                }
                $auction->update(['marketing_data' => $marketing]);
                $removedIds = $data['remove_board_evidence'] ?? [];
                if ($removedIds !== []) {
                    $evidence = $auction->attachments()->where('report_section', 'board_after')->whereNull('marketing_campaign_id')->whereKey($removedIds)->get();
                    if ($evidence->count() !== count(array_unique($removedIds))) {
                        throw ValidationException::withMessages(['remove_board_evidence' => 'الصور المحددة لا تخص فك لوحات هذا المزاد.']);
                    }
                    $removedPaths = $evidence->pluck('file_path')->all();
                    $auction->attachments()->whereKey($removedIds)->delete();
                }
                app(AuctionReportInputService::class)->storeEvidence($auction, [], [], $data['board_images_after'] ?? [], $ids, $storedPaths);
                $closing = AuctionClosing::firstOrNew(['auction_id' => $auction->id]);
                if (! $closing->exists) {
                    $closing->created_by = $actorId;
                }
                $closing->fill(['closing_date' => $data['closing_date'], 'approval_number' => $auction->establishmentRequest->approval_number, 'saved_at' => now(), 'updated_by' => $actorId]);
                $closing->save();
                if (count(array_filter($results, fn ($result) => $result['status'] === 'sold')) === count($ids)) {
                    $closing->update(['finalized_snapshot' => app(AuctionReportDataBuilder::class)->build($auction->fresh())]);
                }

                return $closing->fresh();
            });
        } catch (Throwable $error) {
            Storage::disk('tenant_public')->delete($storedPaths);
            throw $error;
        }
        if ($removedPaths !== []) {
            DB::afterCommit(fn () => Storage::disk('tenant_public')->delete($removedPaths));
        }
        app(AuctionReportRefreshService::class)->afterMutation([$auction->id], true);
        app(AuctionRegulatoryFormService::class)->requestGeneration($auction->fresh(), 'closing');

        return $closing;
    }
}
