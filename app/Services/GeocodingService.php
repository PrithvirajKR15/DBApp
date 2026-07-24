<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Resolves a delivery address + pincode into lat/lng via Google Geocoding API.
 * When no API key is configured (local/tests), returns null coordinates so
 * ingestion can still persist the order and mark geocode_status=skipped.
 */
class GeocodingService
{
    /**
     * @return array{lat: float, lng: float, formatted_address: ?string}|null
     */
    public function geocode(string $fullAddress, string $pincode, string $region = 'in'): ?array
    {
        $query = trim($fullAddress.', '.$pincode);
        $apiKey = config('services.google.maps_key') ?: config('services.google.geocoding_key');

        if (! $apiKey || config('services.google.geocoding_enabled') === false) {
            return $this->fallbackCoordinates($pincode);
        }

        try {
            $response = Http::timeout(8)
                ->get('https://maps.googleapis.com/maps/api/geocode/json', [
                    'address' => $query,
                    'components' => 'postal_code:'.$pincode.'|country:'.strtoupper($region),
                    'key' => $apiKey,
                    'region' => $region,
                ]);

            if (! $response->successful()) {
                Log::warning('Geocoding HTTP failure', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return null;
            }

            $payload = $response->json();
            $status = $payload['status'] ?? 'UNKNOWN';

            if ($status !== 'OK' || empty($payload['results'][0]['geometry']['location'])) {
                Log::info('Geocoding returned no result', [
                    'status' => $status,
                    'query' => $query,
                ]);

                return null;
            }

            $location = $payload['results'][0]['geometry']['location'];

            return [
                'lat' => (float) $location['lat'],
                'lng' => (float) $location['lng'],
                'formatted_address' => $payload['results'][0]['formatted_address'] ?? null,
            ];
        } catch (\Throwable $e) {
            Log::error('Geocoding exception', [
                'message' => $e->getMessage(),
                'query' => $query,
            ]);

            return null;
        }
    }

    /**
     * Prefer mapped zone_pincode / zone centroids; otherwise a stable
     * pseudo-point derived from the pin digits (local/dev without Google).
     *
     * @return array{lat: float, lng: float, formatted_address: ?string}|null
     */
    protected function fallbackCoordinates(string $pincode): ?array
    {
        if (! config('services.google.geocoding_fallback')) {
            return null;
        }

        $fromZone = app(ZoneCoverageService::class)->coordinatesForPincode($pincode);
        if ($fromZone) {
            return [
                'lat' => $fromZone[0],
                'lng' => $fromZone[1],
                'formatted_address' => null,
            ];
        }

        $digits = preg_replace('/\D/', '', $pincode) ?: '0';
        $offset = ((int) substr($digits, -3) ?: 0) / 10000;
        $center = ZoneCoverageService::DEFAULT_MAP_CENTER;

        return [
            'lat' => $center[0] + $offset,
            'lng' => $center[1] + ($offset / 2),
            'formatted_address' => null,
        ];
    }

    /**
     * @throws RuntimeException when coordinates are required and geocoding failed
     */
    public function geocodeOrFail(string $fullAddress, string $pincode): array
    {
        $result = $this->geocode($fullAddress, $pincode);

        if ($result === null) {
            throw new RuntimeException('Unable to geocode address for pincode '.$pincode);
        }

        return $result;
    }
}
