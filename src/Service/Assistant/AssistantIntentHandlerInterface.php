<?php

namespace App\Service\Assistant;

/**
 * Patron Strategy : chaque implémentation traite un type d’intention utilisateur
 * pour l’assistant communauté (réponse métier + suggestions optionnelles).
 */
interface AssistantIntentHandlerInterface
{
    /**
     * $normalizedMessage : message en minuscules, déjà trimé (sauf contrôle longueur en amont).
     */
    public function supports(string $normalizedMessage, ?int $viewerUserId): bool;

    /**
     * @return array{reply: string, suggestions?: list<string>}
     */
    public function handle(string $normalizedMessage, ?int $viewerUserId): array;
}
