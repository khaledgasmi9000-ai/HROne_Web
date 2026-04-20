<?php

namespace App\Service;

class CandidateAiScoringService
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $model = 'llama-3.1-8b-instant',
    ) {
    }

    public function isConfigured(): bool
    {
        return trim($this->apiKey) !== '';
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

        $llmScore = (float) ($parsed['score'] ?? 0);
        $llmScore = max(0.0, min(100.0, $llmScore));

        // Blend model output with deterministic requirement matching so offers produce distinct scores.
        $deterministicScore = $this->computeDeterministicScore($candidate, $offer);
        $score = round(($llmScore * 0.45) + ($deterministicScore * 0.55), 2);
        $score = max(0.0, min(100.0, $score));

        $recommendation = $this->recommendationFromScore($score);

        $summary = trim((string) ($parsed['summary'] ?? 'Evaluation IA effectuee.'));
        if ($summary === '') {
            $summary = 'Evaluation IA effectuee.';
        }

        $summary = sprintf('%s (score combine IA + matching: %.1f%%)', $summary, $score);

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
     * @param array<string, mixed> $candidate
     * @param array<string, mixed> $offer
     */
    private function computeDeterministicScore(array $candidate, array $offer): float
    {
        $candidateText = trim((string) ($candidate['cvExtractedText'] ?? '')) . ' ' . trim((string) ($candidate['motivationLetter'] ?? ''));
        $candidateTokens = $this->tokenize($candidateText);
        if ($candidateTokens === []) {
            return 50.0;
        }

        $offerText = implode(' ', [
            (string) ($offer['title'] ?? ''),
            (string) ($offer['description'] ?? ''),
            (string) ($offer['workType'] ?? ''),
            (string) ($offer['location'] ?? ''),
            (string) ($offer['experience'] ?? ''),
        ]);
        $offerTokens = $this->tokenize($offerText);

        $skills = $this->normalizeStringArray($offer['skills'] ?? []);
        $languages = $this->normalizeStringArray($offer['languages'] ?? []);
        $background = $this->normalizeStringArray($offer['background'] ?? []);

        $textOverlap = $this->jaccard($candidateTokens, $offerTokens);
        $skillsCoverage = $this->coverageAgainstText($skills, $candidateText);
        $languagesCoverage = $this->coverageAgainstText($languages, $candidateText);
        $backgroundCoverage = $this->coverageAgainstText($background, $candidateText);

        $weighted =
            ($textOverlap * 0.30) +
            ($skillsCoverage * 0.45) +
            ($languagesCoverage * 0.15) +
            ($backgroundCoverage * 0.10);

        return round($weighted * 100.0, 2);
    }

    /**
     * @param list<string> $tokensA
     * @param list<string> $tokensB
     */
    private function jaccard(array $tokensA, array $tokensB): float
    {
        if ($tokensA === [] || $tokensB === []) {
            return 0.0;
        }

        $setA = array_values(array_unique($tokensA));
        $setB = array_values(array_unique($tokensB));

        $intersection = array_intersect($setA, $setB);
        $union = array_unique(array_merge($setA, $setB));

        if (count($union) === 0) {
            return 0.0;
        }

        return count($intersection) / count($union);
    }

    /**
     * @return list<string>
     */
    private function tokenize(string $text): array
    {
        $text = mb_strtolower($text);
        $parts = preg_split('/[^\p{L}\p{N}]+/u', $text) ?: [];
        $tokens = [];
        foreach ($parts as $part) {
            $part = trim($part);
            if ($part === '' || mb_strlen($part) < 3) {
                continue;
            }
            $tokens[] = $part;
        }

        return $tokens;
    }

    /**
     * @param array<int, mixed> $items
     * @return list<string>
     */
    private function normalizeStringArray(array $items): array
    {
        $out = [];
        foreach ($items as $item) {
            $value = trim((string) $item);
            if ($value !== '') {
                $out[] = $value;
            }
        }

        return $out;
    }

    /**
     * @param list<string> $requirements
     */
    private function coverageAgainstText(array $requirements, string $candidateText): float
    {
        if ($requirements === []) {
            return 0.5;
        }

        $candidateText = mb_strtolower($candidateText);
        $matched = 0;
        foreach ($requirements as $requirement) {
            $needle = mb_strtolower(trim($requirement));
            if ($needle !== '' && str_contains($candidateText, $needle)) {
                $matched++;
            }
        }

        return $matched / count($requirements);
    }

    private function recommendationFromScore(float $score): string
    {
        if ($score >= 75.0) {
            return 'strong_match';
        }

        if ($score >= 45.0) {
            return 'medium_match';
        }

        return 'weak_match';
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
