<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\Admin\AuctionTemplates\AuctionTemplateType;
use App\Models\Admin\AdminMangment\Users\AdminUser;
use App\Models\Admin\AuctionTemplates\AuctionTemplateSample;
use App\Policies\Admin\AuctionTemplates\AuctionTemplateSamplePolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AuctionTemplateSampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_sample_casts_and_scopes_are_available(): void
    {
        $sample = AuctionTemplateSample::factory()->create([
            'type' => AuctionTemplateType::BROCHURE,
            'is_active' => true,
        ]);

        self::assertSame(AuctionTemplateType::BROCHURE, $sample->type);
        self::assertTrue($sample->is_active);
        self::assertSame($sample->id, AuctionTemplateSample::active()->byType('brochure')->first()?->id);
    }

    public function test_policy_uses_independent_sample_permissions(): void
    {
        $user = Mockery::mock(AdminUser::class);
        $sample = new AuctionTemplateSample;
        $policy = new AuctionTemplateSamplePolicy;

        $user->shouldReceive('can')->with('auction_template_samples.view')->andReturn(true);
        self::assertTrue($policy->viewAny($user));
        self::assertTrue($policy->view($user, $sample));

        $user->shouldReceive('can')->with('auction_template_samples.create')->andReturn(true);
        self::assertTrue($policy->create($user));

        $user->shouldReceive('can')->with('auction_template_samples.edit')->andReturn(true);
        self::assertTrue($policy->update($user, $sample));

        $user->shouldReceive('can')->with('auction_template_samples.delete')->andReturn(true);
        self::assertTrue($policy->delete($user, $sample));
    }
}
