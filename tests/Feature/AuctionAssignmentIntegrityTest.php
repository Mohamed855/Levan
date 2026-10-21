<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Tenant\Auctions\StoreAuctionAction;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\Property;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

final class AuctionAssignmentIntegrityTest extends TestCase
{
    use RefreshDatabase;

    public function test_auction_accepts_only_properties_from_its_assignment(): void
    {
        $user = User::factory()->create();
        $first = Assignment::query()->create(['assignment_number' => 'A-1', 'name' => 'نص تجريبي', 'issuing_entity' => 'نص تجريبي', 'created_by' => $user->id]);
        $second = Assignment::query()->create(['assignment_number' => 'A-2', 'name' => 'نص تجريبي', 'issuing_entity' => 'نص تجريبي', 'created_by' => $user->id]);
        $validProperty = Property::query()->create(['assignment_id' => $first->id, 'property_number' => 'P-1', 'name' => 'نص تجريبي نص تجريبي', 'city' => 'نص تجريبي', 'created_by' => $user->id]);
        $foreignProperty = Property::query()->create(['assignment_id' => $second->id, 'property_number' => 'P-2', 'name' => 'نص تجريبي نص تجريبي', 'city' => 'نص تجريبي', 'created_by' => $user->id]);
        $data = ['assignment_id' => $first->id, 'name' => 'نص تجريبي', 'description' => 'نص تجريبي', 'start_date' => '2026-11-01', 'end_date' => '2026-11-02', 'type' => 'electronic', 'category' => 'lands', 'city' => 'نص تجريبي', 'expected_properties_count' => 1, 'platform_name' => 'نص تجريبي', 'property_ids' => [$validProperty->id]];

        $auction = app(StoreAuctionAction::class)->execute($user, $data);
        $this->assertSame([$validProperty->id], $auction->properties()->pluck('properties.id')->all());

        $this->expectException(ValidationException::class);
        app(StoreAuctionAction::class)->execute($user, array_replace($data, ['property_ids' => [$foreignProperty->id]]));
    }
}