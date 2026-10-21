<?php

declare(strict_types=1);

namespace App\Actions\Tenant\Auctions;

use App\Models\Tenant\Auction;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use App\Models\Tenant\User;
use App\Services\Auctions\CentralAuctionTemplateGateway;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class StoreAuctionAction
{
    public function __construct(private readonly CentralAuctionTemplateGateway $templates) {}

    /** @param array<string,mixed> $data */
    public function execute(User $user, array $data): Auction
    {
        return DB::transaction(function () use ($user, $data): Auction {
            [$versions, $snapshots] = $this->snapshots($data['template_versions'] ?? []);
            if (! Assignment::query()->whereKey($data['assignment_id'])->where('is_active', true)->exists()) {
                throw ValidationException::withMessages(['assignment_id' => 'السند المحدد غير متاح لهذا الحساب.']);
            }
            $propertyIds = array_values(array_unique(array_map('intval', (array) ($data['property_ids'] ?? []))));
            if ($propertyIds !== [] && Property::query()->whereKey($propertyIds)->where('assignment_id', $data['assignment_id'])->count() !== count($propertyIds)) {
                throw ValidationException::withMessages(['property_ids' => 'كل العقارات المحددة يجب أن تتبع السند المختار.']);
            }
            unset($data['property_ids']);
            $auction = Auction::query()->create(array_merge($data, ['template_versions' => $versions, 'template_snapshot' => $snapshots, 'created_by' => $user->id]));
            $auction->properties()->sync(collect($propertyIds)->mapWithKeys(fn (int $id, int $index) => [$id => ['sort_order' => $index]])->all());
            return $auction;
        });
    }

    /** @param array<string,mixed> $selected @return array{array<string,int>,array<string,array<string,mixed>>} */
    public function snapshots(array $selected): array
    {
        $versions = []; $snapshots = [];
        foreach ($selected as $type => $versionId) {
            if (blank($versionId)) continue;
            $version = $this->templates->findPublished((int) $versionId, (string) $type);
            if (! $version) throw ValidationException::withMessages(['template_versions.'.$type => 'نسخة القالب غير متاحة للنشر.']);
            $versions[$type] = $version->id;
            $snapshots[$type] = ['template_id'=>$version->auction_template_id, 'template_name'=>$version->template->name, 'type'=>$type, 'version'=>$version->version, 'schema'=>$version->schema];
        }
        return [$versions, $snapshots];
    }
}
