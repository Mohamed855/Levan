<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\SecurityHeadersMiddleware;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Tests\TestCase;

class SecurityHeadersMiddlewareTest extends TestCase
{
    public function test_it_allows_blob_urls_in_frames_for_local_pdf_previews(): void
    {
        $response = app(SecurityHeadersMiddleware::class)->handle(
            Request::create('/'),
            static fn (): Response => response('<html></html>'),
        );

        $this->assertStringContainsString("frame-src 'self' blob:", (string) $response->headers->get('Content-Security-Policy'));
    }
}
