<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Http\Middleware\VerifyTenantUpgradeRequest;
use App\Support\Security\TenantUpgradeRequestSignature;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class VerifyTenantUpgradeRequestTest extends TestCase
{
    private VerifyTenantUpgradeRequest $middleware;
    private string $secret = 'test-upgrade-secret-key-12345';
    private string $tenantId = 'tenant-test-123';

    protected function setUp(): void
    {
        parent::setUp();
        $this->middleware = new VerifyTenantUpgradeRequest();
        config()->set('app.upgrade_secret', $this->secret);
        config()->set('app.upgrade_legacy_secret_until', '2026-11-05T00:00:00+03:00');
        Cache::flush();
    }

    public function test_rejects_missing_secret_configuration(): void
    {
        config()->set('app.upgrade_secret', null);

        $request = Request::create("/api/tenants/{$this->tenantId}/upgrade", 'POST');
        $request->setRouteResolver(fn () => new class($this->tenantId) {
            public function __construct(private string $tenantId) {}
            public function parameter($name, $default = null) { return $this->tenantId; }
        });

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(503, $response->getStatusCode());
    }

    public function test_rejects_request_without_auth_headers(): void
    {
        $request = $this->createUpgradeRequest();

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_rejects_invalid_hmac_signature(): void
    {
        $timestamp = now()->getTimestamp();
        $requestId = (string) Str::uuid();

        $request = $this->createUpgradeRequest();
        $request->headers->set(TenantUpgradeRequestSignature::TIMESTAMP_HEADER, (string) $timestamp);
        $request->headers->set(TenantUpgradeRequestSignature::REQUEST_ID_HEADER, $requestId);
        $request->headers->set(TenantUpgradeRequestSignature::SIGNATURE_HEADER, str_repeat('a', 64));

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_rejects_expired_hmac_timestamp(): void
    {
        $timestamp = now()->subMinutes(10)->getTimestamp();
        $requestId = (string) Str::uuid();
        $signature = TenantUpgradeRequestSignature::make($this->tenantId, $timestamp, $requestId, $this->secret);

        $request = $this->createUpgradeRequest();
        $request->headers->set(TenantUpgradeRequestSignature::TIMESTAMP_HEADER, (string) $timestamp);
        $request->headers->set(TenantUpgradeRequestSignature::REQUEST_ID_HEADER, $requestId);
        $request->headers->set(TenantUpgradeRequestSignature::SIGNATURE_HEADER, $signature);

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_accepts_valid_hmac_request(): void
    {
        $timestamp = now()->getTimestamp();
        $requestId = (string) Str::uuid();
        $signature = TenantUpgradeRequestSignature::make($this->tenantId, $timestamp, $requestId, $this->secret);

        $request = $this->createUpgradeRequest();
        $request->headers->set(TenantUpgradeRequestSignature::TIMESTAMP_HEADER, (string) $timestamp);
        $request->headers->set(TenantUpgradeRequestSignature::REQUEST_ID_HEADER, $requestId);
        $request->headers->set(TenantUpgradeRequestSignature::SIGNATURE_HEADER, $signature);

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_rejects_replayed_hmac_request(): void
    {
        $timestamp = now()->getTimestamp();
        $requestId = (string) Str::uuid();
        $signature = TenantUpgradeRequestSignature::make($this->tenantId, $timestamp, $requestId, $this->secret);

        $request = $this->createUpgradeRequest();
        $request->headers->set(TenantUpgradeRequestSignature::TIMESTAMP_HEADER, (string) $timestamp);
        $request->headers->set(TenantUpgradeRequestSignature::REQUEST_ID_HEADER, $requestId);
        $request->headers->set(TenantUpgradeRequestSignature::SIGNATURE_HEADER, $signature);

        // First attempt succeeds
        $firstResponse = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));
        $this->assertSame(200, $firstResponse->getStatusCode());

        // Replay attempt fails with 409
        $replayResponse = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));
        $this->assertSame(409, $replayResponse->getStatusCode());
    }

    public function test_accepts_valid_legacy_secret_within_window(): void
    {
        $request = $this->createUpgradeRequest();
        $request->headers->set('X-Secret-Key', $this->secret);

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(200, $response->getStatusCode());
    }

    public function test_rejects_invalid_legacy_secret(): void
    {
        $request = $this->createUpgradeRequest();
        $request->headers->set('X-Secret-Key', 'wrong-secret');

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
    }

    public function test_rejects_legacy_secret_after_expiry(): void
    {
        config()->set('app.upgrade_legacy_secret_until', '2020-01-01T00:00:00+03:00');

        $request = $this->createUpgradeRequest();
        $request->headers->set('X-Secret-Key', $this->secret);

        $response = $this->middleware->handle($request, fn () => new JsonResponse(['ok' => true]));

        $this->assertSame(403, $response->getStatusCode());
    }

    private function createUpgradeRequest(): Request
    {
        $request = Request::create("/api/tenants/{$this->tenantId}/upgrade", 'POST');
        $request->setRouteResolver(fn () => new class($this->tenantId) {
            public function __construct(private string $tenantId) {}
            public function parameter($name, $default = null) { return $this->tenantId; }
        });

        return $request;
    }
}
