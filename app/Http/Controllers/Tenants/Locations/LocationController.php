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
        $input = $request->query('cities') ?? $request->query('city') ?? $request->input('cities') ?? $request->input('city');
        $cities = [];

        if (is_array($input)) {
            $cities = $input;
        } elseif (is_string($input) && trim($input) !== '') {
            if (str_contains($input, ',')) {
                $cities = explode(',', $input);
            } else {
                $cities = [$input];
            }
        }

        $districts = $this->gateway->districtsListForSelect2($cities);

        return response()->json([
            'success' => true,
            'districts' => $districts,
        ]);
    }
}
