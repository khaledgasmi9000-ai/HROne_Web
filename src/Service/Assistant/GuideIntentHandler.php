<?php

namespace App\Service\Assistant;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Regroupe plusieurs intentions « guide d’usage » (publication, interaction, filtres, PDF).
 */
final class GuideIntentHandler implements AssistantIntentHandlerInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(string $normalizedMessage, ?int $viewerUserId): bool
    {
        return $this->matchKind($normalizedMessage) !== null;
    }

    public function handle(string $normalizedMessage, ?int $viewerUserId): array
    {
        $kind = $this->matchKind($normalizedMessage);
        if ($kind === null) {
            return ['reply' => $this->translator->trans('assistant.default')];
        }

        return match ($kind) {
            'post' => [
                'reply' => $this->translator->trans('assistant.how_post'),
                'suggestions' => [$this->translator->trans('suggestion.new_post_anchor')],
            ],
            'interact' => ['reply' => $this->translator->trans('assistant.how_interact')],
            'filter' => ['reply' => $this->translator->trans('assistant.how_filter')],
            'pdf' => ['reply' => $this->translator->trans('assistant.how_pdf')],
            default => ['reply' => $this->translator->trans('assistant.default')],
        };
    }

    private function matchKind(string $m): ?string
    {
        if (preg_match('/\b(publier|post|nouveau|créer|écrire)\b/u', $m)) {
            return 'post';
        }
        if (preg_match('/\b(comment|commentaire|répondre|repondre|vote|like|dislike)\b/u', $m)) {
            return 'interact';
        }
        if (preg_match('/\b(tag|filtre|filtrer|recherche|titre)\b/u', $m)) {
            return 'filter';
        }
        if (preg_match('/\b(pdf|export)\b/u', $m)) {
            return 'pdf';
        }

        return null;
    }
}
