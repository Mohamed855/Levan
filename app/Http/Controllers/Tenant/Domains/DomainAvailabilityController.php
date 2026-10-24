<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant\Domains;

use App\Contracts\Domains\DomainAvailabilityProvider;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Domains\CheckDomainAvailabilityRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

final class DomainAvailabilityController extends Controller
{
    public function __invoke(CheckDomainAvailabilityRequest $request, DomainAvailabilityProvider $provider): JsonResponse
    {
        $domains = array_values(array_unique(array_map(static fn (string $domain): string => strtolower(rtrim(trim($domain), '.')), $request->validated('domains'))));
        if (count($domains) !== count($request->validated('domains')) || array_filter($domains, static fn (string $domain): bool => preg_match('/^(?=.{1,253}$)(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $domain) !== 1)) {
            return response()->json(['message' => 'أحد أسماء النطاقات غير صالح.'], 422);
        }

        try {
            $key = 'tenant:'.tenant('id').':domain-check:'.hash('sha256', implode('|', $domains));
            $results = Cache::remember($key, now()->addMinutes(10), fn (): array => $provider->check($domains));
            return response()->json(['results' => array_values($results)]);
        } catch (RuntimeException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }
}
