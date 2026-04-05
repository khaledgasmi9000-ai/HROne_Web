<?php

namespace App\Service\Assistant;

use Symfony\Contracts\Translation\TranslatorInterface;

final class ProfileIntentHandler implements AssistantIntentHandlerInterface
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {
    }

    public function supports(string $normalizedMessage, ?int $viewerUserId): bool
    {
        if ($viewerUserId === null) {
            return false;
        }

        return (bool) preg_match('/\b(mon|mes|profil|compte|id)\b/u', $normalizedMessage);
    }

    public function handle(string $normalizedMessage, ?int $viewerUserId): array
    {
        return [
            'reply' => $this->translator->trans('assistant.your_session', [
                '%id%' => (string) ($viewerUserId ?? 0),
            ]),
        ];
    }
}
