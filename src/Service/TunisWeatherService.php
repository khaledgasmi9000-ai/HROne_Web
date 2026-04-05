<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Météo actuelle Tunis via Open-Meteo (sans clé API).
 */
class TunisWeatherService
{
    private const API = 'https://api.open-meteo.com/v1/forecast?latitude=36.8065&longitude=10.1815&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Africa%2FTunis';

    public function __construct(
        private readonly HttpClientInterface $http,
    ) {
    }

    /**
     * @return array{city: string, temperature_c: ?float, humidity_pct: ?int, wind_kmh: ?float, weather_code: ?int, time: ?string}|null
     */
    public function fetchCurrent(): ?array
    {
        try {
            $response = $this->http->request('GET', self::API, [
                'timeout' => 6,
                'headers' => ['Accept' => 'application/json'],
            ]);
            $data = $response->toArray(false);
            $cur = $data['current'] ?? null;
            if (!\is_array($cur)) {
                return null;
            }

            return [
                'city' => 'Tunis',
                'temperature_c' => isset($cur['temperature_2m']) ? (float) $cur['temperature_2m'] : null,
                'humidity_pct' => isset($cur['relative_humidity_2m']) ? (int) $cur['relative_humidity_2m'] : null,
                'wind_kmh' => isset($cur['wind_speed_10m']) ? (float) $cur['wind_speed_10m'] : null,
                'weather_code' => isset($cur['weather_code']) ? (int) $cur['weather_code'] : null,
                'time' => isset($cur['time']) ? (string) $cur['time'] : null,
            ];
        } catch (\Throwable) {
            return null;
        }
    }
}
