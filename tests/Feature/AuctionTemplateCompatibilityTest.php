<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Actions\Admin\AuctionTemplates\CreateAuctionTemplateAction;
use App\Actions\Admin\AuctionTemplates\PublishAuctionTemplateVersionAction;
use App\Actions\Tenant\Auctions\StoreAuctionAction;
use App\Models\Admin\AdminUser;
use App\Models\Tenant\Assignment;
use App\Models\Tenant\User;
use App\Services\Auctions\AuctionTemplateRenderer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class AuctionTemplateCompatibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_an_auction_keeps_the_published_template_version_it_was_created_with(): void
    {
        $admin = AdminUser::factory()->create();
        $tenantUser = User::factory()->create();
        $assignment = Assignment::query()->create([
            'assignment_number' => 'ASG-001', 'name' => 'نص تجريبي نص تجريبي', 'issuing_entity' => 'نص تجريبي نص تجريبي',
            'city' => 'نص تجريبي', 'created_by' => $tenantUser->id,
        ]);
        $schema = [
            'canvas' => ['width' => 794, 'height' => 1123],
            'pages' => [[
                'id' => 'cover',
                'background' => ['color' => '#ffffff'],
                'elements' => [[
                    'id' => 'title', 'type' => 'variable', 'binding' => 'auction.name',
                    'required' => true, 'x' => 60, 'y' => 60, 'width' => 400, 'height' => 60,
                    'style' => ['color' => '#173f35', 'font_size' => 24, 'font_weight' => 'bold', 'text_align' => 'right', 'border_radius' => 0],
                ]],
            ]],
        ];

        $template = app(CreateAuctionTemplateAction::class)->execute($admin, [
            'name' => 'نص تجريبي نص تجريبي', 'type' => 'brochure', 'description' => null, 'is_active' => true, 'schema' => $schema,
        ]);
        $firstVersion = $template->versions()->firstOrFail();
        app(PublishAuctionTemplateVersionAction::class)->execute($template, $firstVersion);

        $auction = app(StoreAuctionAction::class)->execute($tenantUser, [
            'assignment_id' => $assignment->id, 'name' => 'نص تجريبي نص تجريبي', 'description' => 'نص تجريبي',
            'start_date' => '2026-11-01', 'start_time' => '10:00', 'end_date' => '2026-11-02', 'end_time' => '11:00',
            'type' => 'electronic', 'category' => 'lands', 'city' => 'نص تجريبي', 'expected_properties_count' => 1,
            'platform_name' => 'نص تجريبي نص تجريبي', 'template_versions' => ['brochure' => $firstVersion->id],
        ]);

        $this->assertSame(1, $auction->template_snapshot['brochure']['version']);
        $this->assertSame('نص تجريبي نص تجريبي', $auction->template_snapshot['brochure']['template_name']);

        $template->versions()->create(['version' => 2, 'schema' => array_replace_recursive($schema, ['pages' => [['elements' => [['style' => ['font_size' => 48]]]]]]), 'status' => 'draft', 'created_by' => $admin->id]);
        $secondVersion = $template->versions()->where('version', 2)->firstOrFail();
        app(PublishAuctionTemplateVersionAction::class)->execute($template, $secondVersion);

        $auction->refresh();
        $this->assertSame(1, $auction->template_snapshot['brochure']['version']);
        $this->assertSame(24, $auction->template_snapshot['brochure']['schema']['pages'][0]['elements'][0]['style']['font_size']);
        $this->assertSame('نص تجريبي نص تجريبي', app(AuctionTemplateRenderer::class)->render($auction, 'brochure')['bindings']['auction.name']);
    }
}
