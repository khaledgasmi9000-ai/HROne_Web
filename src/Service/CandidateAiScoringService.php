<?php

namespace App\Service;

class CandidateAiScoringService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'llama-3.1-8b-instant',
    ) {
    }

    /**
     * @param array<string, mixed> $candidate
     * @param array<string, mixed> $offer
     * @return array{score:float,recommendation:string,summary:string}
     */
    public function scoreCandidate(array $candidate, array $offer): array
    {
        if (trim($this->apiKey) === '') {
            throw new \RuntimeException('La cle API IA est absente.');
        }

        $cvText = trim((string) ($candidate['cvExtractedText'] ?? ''));
        if ($cvText === '') {
            throw new \RuntimeException('Le texte extrait du CV est vide.');
        }

        $payload = [
            'model' => $this->model,
            'temperature' => 0.2,
            'response_format' => ['type' => 'json_object'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Tu es un assistant RH. Tu dois retourner uniquement un JSON avec les cles: score (0-100), recommendation (strong_match|medium_match|weak_match), summary (phrase courte).',
                ],
                [
                    'role' => 'user',
                    'content' => json_encode([
                        'job' => [
                            'title' => (string) ($offer['title'] ?? ''),
                            'description' => (string) ($offer['description'] ?? ''),
                            'workType' => (string) ($offer['workType'] ?? ''),
                            'location' => (string) ($offer['location'] ?? ''),
                            'experience' => (string) ($offer['experience'] ?? ''),
                            'skills' => $offer['skills'] ?? [],
                            'languages' => $offer['languages'] ?? [],
                        ],
                        'candidate' => [
                            'name' => (string) ($candidate['candidateName'] ?? ''),
                            'motivationLetter' => (string) ($candidate['motivationLetter'] ?? ''),
                            'portfolioUrl' => (string) ($candidate['portfolioUrl'] ?? ''),
                            'cvText' => mb_substr($cvText, 0, 12000),
                        ],
                    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                ],
            ],
        ];

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'timeout' => 20,
                'ignore_errors' => true,
                'header' => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ],
                'content' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            ],
        ]);

        $raw = @file_get_contents('https://api.groq.com/openai/v1/chat/completions', false, $context);
        $statusCode = $this->extractHttpStatusCode($http_response_header ?? []);
        if (!is_string($raw) || trim($raw) === '') {
            throw new \RuntimeException(
                $statusCode > 0
                    ? sprintf('Service IA indisponible (HTTP %d).', $statusCode)
                    : 'Reponse vide du service IA.'
            );
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            throw new \RuntimeException('Reponse IA invalide.');
        }

        if ($statusCode >= 400 || isset($decoded['error'])) {
            $errorMessage = $this->extractApiErrorMessage($decoded);
            $suffix = $statusCode > 0 ? sprintf(' (HTTP %d)', $statusCode) : '';
            throw new \RuntimeException(sprintf('Erreur API IA%s: %s', $suffix, $errorMessage));
        }

        $content = (string) ($decoded['choices'][0]['message']['content'] ?? '');
        $parsed = json_decode($content, true);

        if (!is_array($parsed)) {
            $content = $this->extractJsonBlock($content);
            $parsed = json_decode($content, true);
        }

        if (!is_array($parsed)) {
            throw new \RuntimeException('Impossible de parser la reponse IA.');
        }

        $score = (float) ($parsed['score'] ?? 0);
        $score = max(0.0, min(100.0, $score));

        $recommendation = strtolower(trim((string) ($parsed['recommendation'] ?? 'medium_match')));
        if (!in_array($recommendation, ['strong_match', 'medium_match', 'weak_match'], true)) {
            $recommendation = 'medium_match';
        }

        $summary = trim((string) ($parsed['summary'] ?? 'Evaluation IA effectuee.'));
        if ($summary === '') {
            $summary = 'Evaluation IA effectuee.';
        }

        return [
            'score' => $score,
            'recommendation' => $recommendation,
            'summary' => $summary,
        ];
    }

    private function extractJsonBlock(string $content): string
    {
        $start = strpos($content, '{');
        $end = strrpos($content, '}');
        if ($start === false || $end === false || $end <= $start) {
            return '{}';
        }

        return substr($content, $start, $end - $start + 1);
    }

    /**
     * @param array<int, string> $headers
     */
    private function extractHttpStatusCode(array $headers): int
    {
        if (!isset($headers[0]) || !is_string($headers[0])) {
            return 0;
        }

        if (preg_match('/\s(\d{3})\s?/', $headers[0], $matches) !== 1) {
            return 0;
        }

        return (int) ($matches[1] ?? 0);
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function extractApiErrorMessage(array $decoded): string
    {
        $error = $decoded['error'] ?? null;
        if (is_array($error)) {
            $message = trim((string) ($error['message'] ?? ''));
            if ($message !== '') {
                return $message;
            }
        }

        return 'Erreur inconnue du service IA.';
    }
}
