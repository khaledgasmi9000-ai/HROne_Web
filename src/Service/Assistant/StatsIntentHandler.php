<?php

namespace App\Service\Assistant;

use App\Service\CommunityMetrics;
use Symfony\Contracts\Translation\TranslatorInterface;

final class StatsIntentHandler implements AssistantIntentHandlerInterface
{
    public function __construct(
        private readonly CommunityMetrics $metrics,
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(string $normalizedMessage, ?int $viewerUserId): bool
    {
        return (bool) preg_match('/\b(stat|stats|statistique|indicateur|dashboard|tableau)\b/u', $normalizedMessage);
    }

    public function handle(string $normalizedMessage, ?int $viewerUserId): array
    {
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
}
