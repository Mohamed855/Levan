<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Properties;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenants\Properties\ResolveGoogleMapsUrlRequest;
use App\Services\Tenants\Properties\GoogleMapsUrlResolver;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use InvalidArgumentException;

final class ResolveGoogleMapsUrlController extends Controller
{
    public function __invoke(ResolveGoogleMapsUrlRequest $request, GoogleMapsUrlResolver $resolver): JsonResponse
    {
        try {
            return response()->json(['success' => true, 'data' => ['url' => $resolver->resolve($request->validated('url'))]]);
        } catch (InvalidArgumentException|ConnectionException) {
            return response()->json(['success' => false, 'message' => 'تعذر قراءة رابط خرائط جوجل. تحقق من الرابط وأعد المحاولة.'], 422);
        }
    }
}
