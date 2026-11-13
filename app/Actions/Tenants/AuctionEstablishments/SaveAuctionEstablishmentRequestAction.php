<?php

declare(strict_types=1);

namespace App\Actions\Tenants\AuctionEstablishments;

use App\Http\Requests\Tenants\AuctionEstablishments\SaveAuctionEstablishmentRequest;
use App\Models\Tenants\AuctionEstablishments\AuctionEstablishmentRequest;
use App\Models\Tenants\Auctions\Auction;
use App\Services\Tenants\AuctionEstablishments\AuctionEstablishmentRequestData;
use App\Services\Tenants\Auctions\Forms\AuctionRegulatoryFormService;
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
        try {
            $request = DB::transaction(function () use ($auction, $input, $actorId, &$paths): AuctionEstablishmentRequest {
                $auction = Auction::query()->lockForUpdate()->findOrFail($auction->id);
                $request = AuctionEstablishmentRequest::query()->where('auction_id', $auction->id)->lockForUpdate()->first();
                if ($auction->isClosed() || $request?->isApproved()) {
                    throw ValidationException::withMessages(['data' => 'لا يمكن تعديل طلب معتمد أو طلب مزاد مقفل.']);
                }
                $requestRules = new SaveAuctionEstablishmentRequest;
                $schema = app(AuctionEstablishmentRequestData::class);
                Validator::make($input, $requestRules->rules(), $requestRules->messages(), $schema->attributes())->validate();
                $data = $schema->snapshot($auction, $input['data']);
                Validator::make(['data' => $data], $schema->rules(), $requestRules->messages(), $schema->attributes())->validate();
                $rights = $data['disclosures']['disposition_rights'];
                if ((in_array('none', $rights, true) && count($rights) > 1) || (in_array('other', $rights, true) && blank($data['disclosures']['disposition_right_other'] ?? null))) {
                    throw ValidationException::withMessages(['data.disclosures.disposition_rights' => 'تحقق من حقوق التصرف وتفاصيلها.']);
                }
                $data['attachments'] = $request?->data['attachments'] ?? [];
                $this->storeAttachments($auction, $data, $input['attachments'] ?? [], $paths);
                $request ??= new AuctionEstablishmentRequest(['auction_id' => $auction->id, 'created_by' => $actorId]);
                $request->fill(['data' => $data, 'submitted_at' => now(), 'updated_by' => $actorId]);
                $request->save();

                return $request->fresh();
            });
        } catch (Throwable $error) {
            Storage::disk('tenant_private')->delete($paths);
            throw $error;
        }
        app(AuctionRegulatoryFormService::class)->requestGeneration($auction->fresh(), 'establishment');

        return $request;
    }

    private function storeAttachments(Auction $auction, array &$data, array $uploads, array &$paths): void
    {
        $required = ['manager_certificate' => [0], 'auctioneer_certificate' => [0], 'deed' => [], 'valuation' => [], 'representative_authority' => []];
        $propertyIds = array_column($data['properties'], 'property_id');
        foreach ($data['properties'] as $row) {
            $required['deed'][] = $row['property_id'];
            if (preg_match('/[1-9]/', (string) $row['entry_amount'])) {
                $required['valuation'][] = $row['property_id'];
            }
            if (filled($row['agent_name'] ?? null)) {
                $required['representative_authority'][] = $row['property_id'];
            }
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
