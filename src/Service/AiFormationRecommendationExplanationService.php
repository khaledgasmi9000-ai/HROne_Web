<?php

namespace App\Service;

use App\Entity\Formation;

class AiFormationRecommendationExplanationService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'llama-3.1-8b-instant',
    ) {
    }

    /**
     * @param array<string, mixed> $profile
     * @param string[] $reasons
     *
     * @return array{text: string, source: string}
     */
    public function buildExplanation(array $profile, Formation $formation, array $reasons): array
    {
        if (!$this->isConfigured()) {
            return [
                'text' => '',
                'source' => 'none',
            ];
        }

        try {
            $payload = [
                'model' => $this->model,
                'temperature' => 0.3,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Tu es un assistant RH. Ecris une seule phrase courte en francais simple pour expliquer pourquoi une formation est recommandee. Pas de liste, pas de markdown, maximum 22 mots.',
                    ],
                    [
                        'role' => 'user',
                        'content' => json_encode([
                            'employee_profile' => [
                                'history_count' => (int) ($profile['history_count'] ?? 0),
                                'preferred_level' => (string) ($profile['preferred_level'] ?? ''),
                                'preferred_mode' => (string) ($profile['preferred_mode'] ?? ''),
                                'interest_keywords' => $profile['interest_keywords'] ?? [],
                            ],
                            'formation' => [
                                'title' => (string) ($formation->getTitre() ?? ''),
                                'description' => mb_substr((string) ($formation->getDescription() ?? ''), 0, 500),
                                'mode' => (string) ($formation->getMode() ?? ''),
                                'level' => (string) ($formation->getNiveau() ?? ''),
                                'places_restantes' => (int) ($formation->getPlacesRestantes() ?? 0),
                            ],
                            'reasons' => array_values($reasons),
                        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                    ],
                ],
            ];

            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'timeout' => 12,
                    'ignore_errors' => true,
                    'header' => [
                        'Content-Type: application/json',
                        'Authorization: Bearer ' . $this->apiKey,
                    ],
                    'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ]);

            $raw = @file_get_contents('https://api.groq.com/openai/v1/chat/completions', false, $context);

            if (!is_string($raw) || trim($raw) === '') {
                return [
                    'text' => '',
                    'source' => 'none',
                ];
            }

            $decoded = json_decode($raw, true);
            if (!is_array($decoded) || isset($decoded['error'])) {
                return [
                    'text' => '',
                    'source' => 'none',
                ];
            }

            $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));
            if ($content === '') {
                return [
                    'text' => '',
                    'source' => 'none',
                ];
            }

            return [
                'text' => $this->sanitizeExplanation($content),
                'source' => 'ai',
            ];
        } catch (\Throwable) {
            return [
                'text' => '',
                'source' => 'none',
            ];
        }
    }

    private function isConfigured(): bool
    {
        $key = trim($this->apiKey);

        return $key !== '' && $key !== 'your_groq_api_key_here';
    }

    private function sanitizeExplanation(string $value): string
    {
        $value = trim(preg_replace('/\s+/', ' ', strip_tags($value)) ?? '');
        $value = trim($value, " \t\n\r\0\x0B\"'");

        if ($value === '') {
            return '';
        }

        if (!str_ends_with($value, '.')) {
            $value .= '.';
        }

        return mb_substr($value, 0, 220);
    }
}
