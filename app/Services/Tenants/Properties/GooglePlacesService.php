<?php

declare(strict_types=1);

namespace App\Services\Tenants\Properties;

use Illuminate\Http\Client\Factory as HttpFactory;
use RuntimeException;

class GooglePlacesService
{
    public function __construct(private readonly HttpFactory $http) {}

    public function search(string $query, ?float $latitude = null, ?float $longitude = null): array
    {
        $apiKey = (string) config('services.google_maps.api_key');
        if ($apiKey === '') {
            throw new RuntimeException('Google Maps API key is not configured.');
        }

        $payload = [
            'textQuery' => $query,
            'languageCode' => 'ar',
            'regionCode' => 'SA',
            'maxResultCount' => 5,
        ];

        if ($latitude !== null && $longitude !== null) {
            $payload['locationBias'] = [
                'circle' => [
                    'center' => ['latitude' => $latitude, 'longitude' => $longitude],
                    'radius' => 50000.0,
                ],
            ];
        }

        $response = $this->http->withHeaders([
            'X-Goog-Api-Key' => $apiKey,
            'X-Goog-FieldMask' => 'places.id,places.displayName,places.formattedAddress,places.location',
        ])->timeout(10)->post('https://places.googleapis.com/v1/places:searchText', $payload)->throw();

        return collect($response->json('places', []))->map(static fn (array $place): array => [
            'id' => (string) ($place['id'] ?? ''),
            'name' => (string) data_get($place, 'displayName.text', ''),
            'address' => (string) ($place['formattedAddress'] ?? ''),
            'latitude' => (float) data_get($place, 'location.latitude'),
            'longitude' => (float) data_get($place, 'location.longitude'),
        ])->filter(static fn (array $place): bool => $place['latitude'] !== 0.0 || $place['longitude'] !== 0.0)->values()->all();
    }
}
