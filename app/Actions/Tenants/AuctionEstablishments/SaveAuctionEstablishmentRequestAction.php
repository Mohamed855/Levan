<?php

declare(strict_types=1);

namespace App\Actions\Tenants\AuctionEstablishments;

use App\Http\Requests\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequest;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use App\Services\Tenants\Auctions\AuctionReportInputService;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
use App\Services\Tenants\Auctions\Reports\AuctionReportRefreshService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

final class SaveAuctionEstablishmentRequestAction
{
    public function execute(Auction $auction, array $input, ?int $actorId): AuctionEstablishmentRequest
    {
        $paths = [];
        $removedPaths = [];
        $storedBoardPaths = [];
        $removedBoardPaths = [];

        try {
            $request = DB::transaction(function () use ($auction, $input, $actorId, &$paths, &$removedPaths, &$storedBoardPaths, &$removedBoardPaths): AuctionEstablishmentRequest {
                $auction = Auction::query()->lockForUpdate()->findOrFail($auction->id);
                $request = AuctionEstablishmentRequest::query()->where('auction_id', $auction->id)->lockForUpdate()->first();
                if ($auction->isClosed() || $request?->isApproved()) {
                    throw ValidationException::withMessages(['data' => 'لا يمكن تعديل طلب معتمد أو طلب مزاد مقفل.']);
                }

                $schema = app(AuctionEstablishmentRequestData::class);
                $missingSettings = $schema->missingSettingsFields();
                if ($missingSettings !== []) {
                    throw ValidationException::withMessages([
                        'data.organization' => 'يجب استكمال بيانات المنشأة في إعدادات النظام أولاً قبل تقديم الطلب: '.implode('، ', $missingSettings).'.',
                    ]);
                }

                $requestRules = new SaveAuctionEstablishmentRequest;
                $requestRules->merge($input);
                $validator = Validator::make($input, $requestRules->rules($auction), $requestRules->messages(), $schema->attributes());
                foreach ($requestRules->after($auction) as $afterHook) {
                    $validator->after($afterHook);
                }
                $validator->validate();

                $data = $schema->snapshot($auction, $input['data']);
                Validator::make(['data' => $data], $schema->rules(), $requestRules->messages(), $schema->attributes())->validate();

                $rights = $data['disclosures']['disposition_rights'];
                if ((in_array('none', $rights, true) && count($rights) > 1) || (in_array('other', $rights, true) && blank($data['disclosures']['disposition_right_other'] ?? null))) {
                    throw ValidationException::withMessages(['data.disclosures.disposition_rights' => 'تحقق من حقوق التصرف وتفاصيلها.']);
                }
                foreach ($data['properties'] as $idx => $prop) {
                    $entry = (float) str_replace(',', '', (string) ($prop['entry_amount'] ?? 0));
                    $opening = (float) str_replace(',', '', (string) ($prop['opening_value'] ?? 0));
                    $estimated = (float) str_replace(',', '', (string) ($prop['estimated_value'] ?? 0));
                    if ($entry > $opening) {
                        throw ValidationException::withMessages(["data.properties.{$idx}.entry_amount" => 'مبلغ الدخول لا يمكن أن يكون أكبر من قيمة افتتاح المزاد.']);
                    }
                    if ($entry > $estimated) {
                        throw ValidationException::withMessages(["data.properties.{$idx}.entry_amount" => 'مبلغ الدخول لا يمكن أن يكون أكبر من القيمة التقديرية للعقار.']);
                    }
                }

                $data['attachments'] = $request?->data['attachments'] ?? [];
                unset($data['attachments']['deed'], $data['attachments']['representative_authority']);
                $previousPaths = collect($data['attachments'])->flatten(1)->pluck('path')->all();
                $removals = $input['remove_attachments'] ?? [];
                foreach (['manager_certificate', 'auctioneer_certificate'] as $type) {
                    if (! empty($input['remove_attachments_'.$type])) {
                        $removals[$type][0] = true;
                    }
                }
                $this->storeAttachments($auction, $data, $input['attachments'] ?? [], $paths, $removals);
                $retainedPaths = collect($data['attachments'])->flatten(1)->pluck('path')->all();
                $removedPaths = array_values(array_diff($previousPaths, $retainedPaths));

                // Handle per-property board_before images (stored in auction_attachments on tenant_public)
                $propertyIds = array_map('intval', array_column($data['properties'], 'property_id'));
                $removedBoardIds = array_values(array_unique(array_map('intval', (array) ($input['remove_board_before_evidence'] ?? []))));
                if ($removedBoardIds !== []) {
                    $boardToRemove = $auction->attachments()
                        ->where('report_section', 'board_before')
                        ->whereNull('marketing_campaign_id')
                        ->whereKey($removedBoardIds)
                        ->get();
                    if ($boardToRemove->count() !== count($removedBoardIds)) {
                        throw ValidationException::withMessages(['remove_board_before_evidence' => 'الصور المحددة لا تخص لوحات هذا المزاد.']);
                    }
                    $removedBoardPaths = $boardToRemove->pluck('file_path')->filter()->all();
                    $auction->attachments()->whereKey($removedBoardIds)->delete();
                }

                app(AuctionReportInputService::class)->storeEvidence(
                    $auction,
                    [],
                    (array) ($input['board_images_before'] ?? []),
                    [],
                    $propertyIds,
                    $storedBoardPaths,
                );

                // Ensure every property has at least one board_before image after removals & uploads
                foreach ($propertyIds as $propId) {
                    $hasBoardImage = $auction->attachments()
                        ->where('report_section', 'board_before')
                        ->where('property_id', $propId)
                        ->exists();
                    if (! $hasBoardImage) {
                        throw ValidationException::withMessages(["board_images_before.{$propId}" => 'صور تركيب اللوحات مطلوبة لكل عقار.']);
                    }
                }

                // Sync board_count, board_dimensions, and opening_value to auction_properties pivot
                foreach ($data['properties'] as $propRow) {
                    $propId = (int) $propRow['property_id'];
                    $auction->properties()->updateExistingPivot($propId, [
                        'opening_price' => is_numeric($propRow['opening_value'] ?? null) ? (float) $propRow['opening_value'] : null,
                        'board_data' => json_encode([
                            'count' => (int) ($propRow['board_count'] ?? 0),
                            'dimensions' => (string) ($propRow['board_dimensions'] ?? ''),
                        ], JSON_UNESCAPED_UNICODE),
                    ]);
                }

                $request ??= new AuctionEstablishmentRequest(['auction_id' => $auction->id, 'created_by' => $actorId]);
                $request->fill(['data' => $data, 'submitted_at' => now(), 'updated_by' => $actorId]);
                $request->save();

                return $request->fresh();
            });
        } catch (Throwable $error) {
            Storage::disk('tenant_private')->delete($paths);
            Storage::disk('tenant_public')->delete($storedBoardPaths);
            throw $error;
        }

        if ($removedPaths !== []) {
            DB::afterCommit(fn () => Storage::disk('tenant_private')->delete($removedPaths));
        }
        if ($removedBoardPaths !== []) {
            DB::afterCommit(fn () => Storage::disk('tenant_public')->delete($removedBoardPaths));
        }

        app(AuctionRegulatoryFormService::class)->requestGeneration($auction->fresh(), 'establishment');
        app(AuctionReportRefreshService::class)->afterMutation([$auction->id]);

        return $request;
    }

    private function storeAttachments(Auction $auction, array &$data, array $uploads, array &$paths, array $removals = []): void
    {
        $required = ['manager_certificate' => [0], 'auctioneer_certificate' => [0], 'valuation' => []];
        $propertyIds = array_column($data['properties'], 'property_id');
        foreach ($removals as $type => $items) {
            $allowedIds = in_array($type, ['manager_certificate', 'auctioneer_certificate'], true) ? [0] : $propertyIds;
            foreach ($items as $id => $remove) {
                if (! $remove) {
                    continue;
                }
                if (! array_key_exists($type, $required) || ! ctype_digit((string) $id) || ! in_array((int) $id, $allowedIds, true)) {
                    throw ValidationException::withMessages(['remove_attachments' => 'المرفق لا يخص عقارات هذا المزاد.']);
                }
                unset($data['attachments'][$type][$id]);
            }
        }
        foreach ($data['properties'] as $row) {
            $required['valuation'][] = $row['property_id'];
        }
        foreach ($required as $type => $ids) {
            $files = in_array($type, ['manager_certificate', 'auctioneer_certificate'], true) ? [0 => $uploads[$type] ?? null] : ($uploads[$type] ?? []);
            foreach ($files as $id => $file) {
                if ($id !== 0 && ! in_array((int) $id, $propertyIds, true)) {
                    throw ValidationException::withMessages(['attachments.'.$type => 'المرفق لا يخص عقارات هذا المزاد.']);
                }
                if (! $file instanceof UploadedFile) {
                    continue;
                }
                $directory = 'auction-establishments/'.hash('sha256', (string) tenant('id')).'/'.$auction->id;
                $path = $file->store($directory, 'tenant_private');
                if ($path === false) {
                    throw new RuntimeException('تعذر حفظ مرفق طلب إقامة المزاد.');
                }
                $paths[] = $path;
                $data['attachments'][$type][$id] = ['path' => $path, 'name' => $file->getClientOriginalName(), 'mime' => $file->getMimeType()];
            }
            foreach ($ids as $id) {
                $path = $data['attachments'][$type][$id]['path'] ?? null;
                if (! is_string($path) || ! Storage::disk('tenant_private')->exists($path)) {
                    throw ValidationException::withMessages(['attachments.'.$type.($id ? '.'.$id : '') => 'أرفق المستند المطلوب لاستكمال طلب إقامة المزاد.']);
                }
            }
        }
    }
}
