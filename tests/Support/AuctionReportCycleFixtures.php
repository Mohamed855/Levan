<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Properties\Property;

trait AuctionReportCycleFixtures
{
    private function cycleAuction(): Auction
    {
        $assignment = Assignment::create(['assignment_number' => 'CYCLE-'.uniqid(), 'name' => 'إسناد اختبار', 'issuing_entity' => 'جهة', 'is_active' => true]);
        $property = Property::create(['assignment_id' => $assignment->id, 'property_number' => 'CYCLE-P-'.uniqid(), 'name' => 'أرض', 'type' => 'residential_land', 'city' => 'الرياض']);
        $auction = Auction::create(['assignment_id' => $assignment->id, 'name' => 'مزاد الدورة', 'description' => 'وصف', 'start_date' => now()->toDateString(), 'end_date' => now()->addDay()->toDateString(), 'type' => 'electronic', 'category' => 'lands', 'city' => 'الرياض', 'is_active' => true]);
        $auction->properties()->attach($property->id, ['result_status' => 'pending']);

        return $auction;
    }
}
