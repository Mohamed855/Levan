<?php

declare(strict_types=1);

namespace Tests\Unit\Auctions;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

final class AuctionEstablishmentRoutingTest extends TestCase
{
    public function test_request_management_exposes_approval_and_edit_without_a_separate_show_route(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        foreach (['index' => 'GET', 'edit' => 'GET', 'update' => 'PUT', 'approve' => 'POST', 'attachment' => 'GET'] as $action => $method) {
            $matching = $routes->filter(fn ($route) => str_ends_with((string) $route->getName(), 'auction-establishment-requests.'.$action));
            self::assertNotEmpty($matching->all(), 'Missing request route '.$action);
            foreach ($matching as $route) {
                self::assertContains($method, $route->methods());
            }
        }
        self::assertEmpty($routes->filter(fn ($route) => str_ends_with((string) $route->getName(), 'auction-establishment-requests.show'))->all());
    }

    public function test_document_start_and_retry_are_post_routes_and_download_is_get(): void
    {
        $routes = collect(Route::getRoutes()->getRoutes());
        foreach (['status' => 'GET', 'start' => 'POST', 'retry' => 'POST', 'download' => 'GET'] as $action => $method) {
            $matching = $routes->filter(fn ($route) => str_ends_with((string) $route->getName(), 'auctions.forms.'.$action));
            self::assertNotEmpty($matching->all(), 'Missing form route '.$action);
            foreach ($matching as $route) {
                self::assertContains($method, $route->methods());
                self::assertContains('auction', $route->parameterNames());
                self::assertContains('type', $route->parameterNames());
            }
        }
    }

    public function test_approval_and_regeneration_have_separate_tenant_permissions(): void
    {
        $permissions = config('auction-report-permissions');
        self::assertArrayHasKey('auction_establishment_requests_management', $permissions);
        self::assertContains('auction_establishment_requests.approve', $permissions['auction_establishment_requests_management']);
        self::assertContains('auction_establishment_requests.regenerate', $permissions['auction_establishment_requests_management']);
        self::assertContains('auction_closings.regenerate', $permissions['auction_closings_management']);
    }
}
