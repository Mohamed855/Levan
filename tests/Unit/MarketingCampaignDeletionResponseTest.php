<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Actions\Tenants\MarketingCampaigns\DeleteMarketingCampaignAction;
use App\Http\Controllers\Tenants\MarketingCampaigns\MarketingCampaignsController;
use App\Models\Tenants\MarketingCampaigns\MarketingCampaign;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

final class MarketingCampaignDeletionResponseTest extends TestCase
{
    public static function jsonHeaders(): array
    {
        return [
            'accept JSON' => [['HTTP_ACCEPT' => 'application/json']],
            'AJAX' => [['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']],
        ];
    }

    #[DataProvider('jsonHeaders')]
    public function test_deleting_a_campaign_returns_json_instead_of_redirecting(array $headers): void
    {
        $this->app->instance('request', Request::create('/campaigns/1', 'DELETE', server: $headers));
        DB::shouldReceive('transaction')->twice()->andReturn([]);
        $campaign = new MarketingCampaign(['auction_id' => 4]);

        $response = (new MarketingCampaignsController)->destroy($campaign, new DeleteMarketingCampaignAction);

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
        self::assertTrue($response->getData(true)['success']);
        self::assertNotEmpty($response->getData(true)['message']);
    }

    public function test_campaign_deletion_keeps_the_html_redirect(): void
    {
        $this->app->instance('request', Request::create('/campaigns/1', 'DELETE'));
        DB::shouldReceive('transaction')->twice()->andReturn([]);

        $response = (new MarketingCampaignsController)->destroy(new MarketingCampaign(['auction_id' => 4]), new DeleteMarketingCampaignAction);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertTrue(session()->has('success'));
    }

    public function test_campaign_lock_errors_are_preserved_for_the_exception_handler(): void
    {
        $this->app->instance('request', Request::create('/campaigns/1', 'DELETE', server: ['HTTP_ACCEPT' => 'application/json']));
        $error = ValidationException::withMessages(['auction' => 'Auction is ended.']);
        DB::shouldReceive('transaction')->once()->andThrow($error);

        $this->expectExceptionObject($error);

        (new MarketingCampaignsController)->destroy(new MarketingCampaign(['auction_id' => 4]), new DeleteMarketingCampaignAction);
    }

    public function test_mass_delete_validates_required_ids(): void
    {
        $this->app->instance('request', Request::create('/campaigns/mass-delete', 'POST', ['ids' => []]));

        $this->expectException(ValidationException::class);

        $request = Request::create('/campaigns/mass-delete', 'POST', ['ids' => []]);
        (new MarketingCampaignsController)->massDelete($request, new DeleteMarketingCampaignAction);
    }

    public function test_mass_delete_validates_non_integer_ids(): void
    {
        $this->expectException(ValidationException::class);

        $request = Request::create('/campaigns/mass-delete', 'POST', ['ids' => 'abc, def']);
        (new MarketingCampaignsController)->massDelete($request, new DeleteMarketingCampaignAction);
    }
}
