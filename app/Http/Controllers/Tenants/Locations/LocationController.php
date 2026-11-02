<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenants\Locations;

use App\Http\Controllers\Controller;
use App\Services\Tenants\Shared\CentralCityOptionsGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function __construct(
        protected CentralCityOptionsGateway $gateway
    ) {}

    public function districts(Request $request): JsonResponse
    {
        $city = (string) $request->query('city', '');
        $districts = $this->gateway->districtsListForSelect2($city);

        return response()->json([
            'success' => true,
            'districts' => $districts,
        ]);
    }
}
