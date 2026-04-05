<?php

namespace App\Service\Assistant;

use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Stratégie par défaut : toujours supportée en dernier dans la chaîne.
 */
final class DefaultIntentHandler implements AssistantIntentHandlerInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(string $normalizedMessage, ?int $viewerUserId): bool
    {
        return true;
    }

    public function handle(string $normalizedMessage, ?int $viewerUserId): array
    {
        return [
            'reply' => $this->translator->trans('assistant.default'),
            'suggestions' => [
                $this->translator->trans('suggestion.stats'),
                $this->translator->trans('suggestion.weather'),
                $this->translator->trans('suggestion.post_help'),
            ],
        ];
    }
}
