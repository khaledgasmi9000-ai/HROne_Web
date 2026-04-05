<?php

namespace App\Service;

use App\Service\Assistant\AssistantIntentHandlerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Assistant communauté : délègue à une chaîne de stratégies (patron Strategy + chaîne de responsabilité).
 *
 * @see AssistantIntentHandlerInterface
 */
class CommunityAssistant
{
    /**
     * @param iterable<AssistantIntentHandlerInterface> $intentHandlers
     *                                                    Ordre : intentions spécifiques d’abord, DefaultIntentHandler en dernier.
     */
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly iterable $intentHandlers,
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

        foreach ($this->intentHandlers as $handler) {
            if ($handler->supports($m, $viewerUserId)) {
                return $handler->handle($m, $viewerUserId);
            }
        }

        throw new \LogicException('Aucun AssistantIntentHandler (vérifiez que DefaultIntentHandler est enregistré en dernier).');
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
