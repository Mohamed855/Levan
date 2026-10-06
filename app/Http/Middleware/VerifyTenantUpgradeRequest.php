<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Support\Security\TenantUpgradeRequestSignature;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

final class VerifyTenantUpgradeRequest
{
    public function handle(Request $request, Closure $next): Response
    {
        $tenantId = (string) $request->route('tenantId');
        $secret = $this->configuredSecret();

        if ($tenantId === '' || str_contains($tenantId, "\n") || str_contains($tenantId, "\r")) {
            return $this->reject($request, 'invalid_tenant_id', 400);
        }

        if ($secret === null) {
            Log::critical('Tenant upgrade authentication is not configured.', $this->context($request, $tenantId));

            return response()->json([
                'success' => false,
                'message' => 'خدمة الترقية غير مهيأة حالياً.',
            ], 503);
        }

        $signature = trim((string) $request->header(TenantUpgradeRequestSignature::SIGNATURE_HEADER));

        if ($signature !== '') {
            return $this->verifyHmac($request, $next, $tenantId, $secret, $signature);
        }

        return $this->verifyLegacySecret($request, $next, $tenantId, $secret);
    }

    private function verifyHmac(Request $request, Closure $next, string $tenantId, string $secret, string $signature): Response
    {
        $timestamp = $this->timestamp($request);
        $requestId = trim((string) $request->header(TenantUpgradeRequestSignature::REQUEST_ID_HEADER));

        if ($timestamp === null || ! Str::isUuid($requestId) || ! preg_match('/^[a-f0-9]{64}$/i', $signature)) {
            return $this->reject($request, 'invalid_hmac_headers', 403, $tenantId);
        }

        $maxAge = $this->maxAgeSeconds();
        if (abs(now()->getTimestamp() - $timestamp) > $maxAge) {
            return $this->reject($request, 'expired_hmac_timestamp', 403, $tenantId, $requestId);
        }

        $expectedSignature = TenantUpgradeRequestSignature::make($tenantId, $timestamp, $requestId, $secret);
        if (! hash_equals($expectedSignature, strtolower($signature))) {
            return $this->reject($request, 'invalid_hmac_signature', 403, $tenantId, $requestId);
        }

        $replayCacheKey = 'tenant-upgrade:request:' . hash('sha256', $tenantId . '|' . $requestId);

        try {
            $claimed = Cache::add($replayCacheKey, true, now()->addSeconds($maxAge + 60));
        } catch (\Throwable $exception) {
            Log::critical('Tenant upgrade replay protection is unavailable.', [
                ...$this->context($request, $tenantId, $requestId),
                'exception' => $exception->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'خدمة الترقية غير متاحة حالياً.',
            ], 503);
        }

        if (! $claimed) {
            return $this->reject($request, 'replayed_hmac_request', 409, $tenantId, $requestId);
        }

        return $this->authorize($request, $next, $tenantId, $requestId, 'hmac');
    }

    private function verifyLegacySecret(Request $request, Closure $next, string $tenantId, string $secret): Response
    {
        if (! TenantUpgradeRequestSignature::legacyCompatibilityIsEnabled()) {
            return $this->reject($request, 'legacy_secret_expired', 403, $tenantId);
        }

        $legacySecret = (string) $request->header('X-Secret-Key');
        if ($legacySecret === '' || ! hash_equals($secret, $legacySecret)) {
            return $this->reject($request, 'invalid_legacy_secret', 403, $tenantId);
        }

        return $this->authorize($request, $next, $tenantId, (string) Str::uuid(), 'legacy_secret');
    }

    private function authorize(Request $request, Closure $next, string $tenantId, string $requestId, string $mode): Response
    {
        $request->attributes->set('tenant_upgrade_request_id', $requestId);
        $request->attributes->set('tenant_upgrade_auth_mode', $mode);

        Log::notice('Tenant upgrade request authorized.', [
            ...$this->context($request, $tenantId, $requestId),
            'auth_mode' => $mode,
        ]);

        return $next($request);
    }

    private function timestamp(Request $request): ?int
    {
        $value = trim((string) $request->header(TenantUpgradeRequestSignature::TIMESTAMP_HEADER));

        if (! preg_match('/^\d{10}$/', $value)) {
            return null;
        }

        return (int) $value;
    }

    private function configuredSecret(): ?string
    {
        $secret = config('app.upgrade_secret');

        return is_string($secret) && trim($secret) !== '' ? $secret : null;
    }

    private function maxAgeSeconds(): int
    {
        return min(max((int) config('app.upgrade_hmac_max_age_seconds', 300), 60), 900);
    }

    private function reject(Request $request, string $reason, int $status, string $tenantId = '', ?string $requestId = null): JsonResponse
    {
        Log::warning('Tenant upgrade request rejected.', [
            ...$this->context($request, $tenantId, $requestId),
            'reason' => $reason,
        ]);

        return response()->json([
            'success' => false,
            'message' => $status === 409 ? 'تمت معالجة هذا الطلب مسبقاً.' : 'غير مصرح بطلب الترقية.',
        ], $status);
    }

    private function context(Request $request, string $tenantId, ?string $requestId = null): array
    {
        return array_filter([
            'tenant_id' => $tenantId,
            'request_id' => $requestId,
            'ip' => $request->ip(),
        ], static fn (mixed $value): bool => $value !== null && $value !== '');
    }
}
