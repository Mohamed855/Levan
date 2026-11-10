<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\Tenants\Properties\GoogleMapsUrlResolver;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;
use Tests\TestCase;

final class GoogleMapsUrlResolverTest extends TestCase
{
    public function test_it_expands_google_short_links_without_following_the_final_map_page(): void
    {
        Http::fake([
            'https://maps.app.goo.gl/example' => Http::response('', 302, ['Location' => 'https://www.google.com/maps/place/Test/data=!3d24.5!4d46.5']),
        ]);

        $url = app(GoogleMapsUrlResolver::class)->resolve('https://maps.app.goo.gl/example');

        $this->assertSame('https://www.google.com/maps/place/Test/data=!3d24.5!4d46.5', $url);
        Http::assertSentCount(1);
    }

    public function test_it_rejects_untrusted_redirects_before_sending_a_request_to_them(): void
    {
        Http::fake(['https://goo.gl/maps/example' => Http::response('', 302, ['Location' => 'http://127.0.0.1/private'])]);

        try {
            app(GoogleMapsUrlResolver::class)->resolve('https://goo.gl/maps/example');
            $this->fail('An untrusted redirect must be rejected.');
        } catch (InvalidArgumentException) {
            Http::assertSentCount(1);
        }
    }

    public function test_it_rejects_credentials_ports_and_lookalike_hosts_without_network_requests(): void
    {
        Http::fake();

        foreach (['https://maps.app.goo.gl.evil.example/a', 'https://user@maps.app.goo.gl/a', 'https://maps.app.goo.gl:8443/a', 'http://maps.app.goo.gl/a', 'https://www.google.com/url?q=http://127.0.0.1'] as $url) {
            try {
                app(GoogleMapsUrlResolver::class)->resolve($url);
                $this->fail('Invalid Maps URL accepted: '.$url);
            } catch (InvalidArgumentException) {
                Http::assertNothingSent();
            }
        }
    }

    public function test_redirect_loops_stop_after_five_requests(): void
    {
        Http::fake(['https://maps.app.goo.gl/*' => Http::response('', 302, ['Location' => 'https://maps.app.goo.gl/loop'])]);

        try {
            app(GoogleMapsUrlResolver::class)->resolve('https://maps.app.goo.gl/loop');
            $this->fail('Redirect loop accepted.');
        } catch (InvalidArgumentException) {
            Http::assertSentCount(5);
        }
    }
}
