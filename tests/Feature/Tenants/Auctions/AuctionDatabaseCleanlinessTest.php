<?php

declare(strict_types=1);

namespace Tests\Feature\Tenants\Auctions;

use App\Models\Tenants\Assignments\Assignment;
use App\Models\Tenants\Auctions\Auction;
use App\Models\Tenants\Auctions\AuctionReportGeneration;
use Tests\TestCase;

class AuctionDatabaseCleanlinessTest extends TestCase
{
    public function test_auction_model_has_no_dead_template_columns(): void
    {
        $auction = new Auction();
        $fillable = $auction->getFillable();

        $this->assertNotContains('template_versions', $fillable);
        $this->assertNotContains('template_snapshot', $fillable);
        $this->assertNotContains('template_bindings', $fillable);
        $this->assertNotContains('brochure_template_id', $fillable);
        $this->assertNotContains('billboard_template_id', $fillable);
        $this->assertNotContains('social_template_id', $fillable);
        $this->assertNotContains('status', $fillable);
    }

    public function test_assignment_model_has_no_entity_logo_in_fillable(): void
    {
        $assignment = new Assignment();
        $fillable = $assignment->getFillable();

        $this->assertNotContains('entity_logo', $fillable);
    }

    public function test_auction_report_generation_has_progress_field(): void
    {
        $generation = new AuctionReportGeneration();
        $this->assertContains('progress', $generation->getFillable());
        $this->assertSame('integer', $generation->getCasts()['progress'] ?? null);
    }

    public function test_auction_has_promotional_advertisements_relation(): void
    {
        $auction = new Auction();
        $relation = $auction->promotionalAdvertisements();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class, $relation);
        $this->assertInstanceOf(\App\Models\Tenants\PromotionalAdvertisements\PromotionalAdvertisement::class, $relation->getRelated());

        $ad = new \App\Models\Tenants\PromotionalAdvertisements\PromotionalAdvertisement();
        $this->assertContains('auction_id', $ad->getFillable());
        $this->assertContains('auction_template_id', $ad->getFillable());
        $this->assertContains('template_type', $ad->getFillable());
        $this->assertContains('template_name', $ad->getFillable());
        $this->assertContains('status', $ad->getFillable());
        $this->assertContains('output_path', $ad->getFillable());
    }
}
