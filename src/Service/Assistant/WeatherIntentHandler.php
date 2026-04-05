<?php

namespace App\Service\Assistant;

use App\Service\TunisWeatherService;
use Symfony\Contracts\Translation\TranslatorInterface;

final class WeatherIntentHandler implements AssistantIntentHandlerInterface
{
    public function __construct(
        private readonly TunisWeatherService $weather,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(string $normalizedMessage, ?int $viewerUserId): bool
    {
        return (bool) preg_match('/\b(météo|meteo|weather|tunis|température|temperature)\b/u', $normalizedMessage);
    }

    public function handle(string $normalizedMessage, ?int $viewerUserId): array
    {
        $w = $this->weather->fetchCurrent();
        if ($w === null) {
            return ['reply' => $this->translator->trans('assistant.weather_unavailable')];
        }

        $line = $this->translator->trans('assistant.weather_line', [
            '%temp%' => $w['temperature_c'] !== null ? (string) round($w['temperature_c'], 1) : '—',
            '%hum%' => $w['humidity_pct'] !== null ? (string) $w['humidity_pct'] : '—',
            '%wind%' => $w['wind_kmh'] !== null ? (string) round($w['wind_kmh'], 1) : '—',
        ]);

        return [
            'reply' => $line,
            'suggestions' => $this->defaultSuggestions(),
        ];
    }

    /**
     * @return list<string>
     */
    private function defaultSuggestions(): array
    {
        return [
            $this->translator->trans('suggestion.stats'),
            $this->translator->trans('suggestion.weather'),
            $this->translator->trans('suggestion.post_help'),
        ];
    }
}
