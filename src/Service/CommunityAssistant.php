<?php

namespace App\Service;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Assistant conversationnel léger (règles métier module communauté, sans LLM externe).
 */
class CommunityAssistant
{
    public function __construct(
        private readonly CommunityMetrics $metrics,
        private readonly TunisWeatherService $weather,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * @return array{reply: string, suggestions?: list<string>}
     */
    public function answer(string $message, ?int $viewerUserId, string $locale): array
    {
        $this->translator->setLocale($locale);
        $m = mb_strtolower(trim($message));
        if ($m === '' || mb_strlen($m) > 2000) {
            return [
                'reply' => $this->translator->trans('assistant.empty_or_long'),
                'suggestions' => $this->defaultSuggestions(),
            ];
        }

        if (preg_match('/\b(météo|meteo|weather|tunis|température|temperature)\b/u', $m)) {
            $w = $this->weather->fetchCurrent();
            if ($w === null) {
                return ['reply' => $this->translator->trans('assistant.weather_unavailable')];
            }
            $line = $this->translator->trans('assistant.weather_line', [
                '%temp%' => $w['temperature_c'] !== null ? (string) round($w['temperature_c'], 1) : '—',
                '%hum%' => $w['humidity_pct'] !== null ? (string) $w['humidity_pct'] : '—',
                '%wind%' => $w['wind_kmh'] !== null ? (string) round($w['wind_kmh'], 1) : '—',
            ]);

            return ['reply' => $line, 'suggestions' => $this->defaultSuggestions()];
        }

        if (preg_match('/\b(stat|stats|statistique|indicateur|dashboard|tableau)\b/u', $m)) {
            $g = $this->metrics->buildGlobalStats();

            return [
                'reply' => $this->translator->trans('assistant.stats_summary', [
                    '%posts%' => (string) $g['posts_total'],
                    '%comments%' => (string) $g['comments_total'],
                    '%tags%' => (string) \count($g['tags_top'] ?? []),
                ]),
                'suggestions' => [$this->translator->trans('suggestion.open_dashboard')],
            ];
        }

        if (preg_match('/\b(publier|post|nouveau|créer|écrire)\b/u', $m)) {
            return [
                'reply' => $this->translator->trans('assistant.how_post'),
                'suggestions' => [$this->translator->trans('suggestion.new_post_anchor')],
            ];
        }

        if (preg_match('/\b(comment|commentaire|répondre|repondre|vote|like|dislike)\b/u', $m)) {
            return [
                'reply' => $this->translator->trans('assistant.how_interact'),
            ];
        }

        if (preg_match('/\b(tag|filtre|filtrer|recherche|titre)\b/u', $m)) {
            return [
                'reply' => $this->translator->trans('assistant.how_filter'),
            ];
        }

        if (preg_match('/\b(pdf|export)\b/u', $m)) {
            return [
                'reply' => $this->translator->trans('assistant.how_pdf'),
            ];
        }

        if ($viewerUserId !== null && preg_match('/\b(mon|mes|profil|compte|id)\b/u', $m)) {
            return [
                'reply' => $this->translator->trans('assistant.your_session', ['%id%' => (string) $viewerUserId]),
            ];
        }

        return [
            'reply' => $this->translator->trans('assistant.default'),
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
