<?php

namespace App\Service;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;

class FormationRecommendationService
{
    private const LEVEL_SCORES = [
        'debutant' => 1,
        'intermediaire' => 2,
        'avance' => 3,
    ];

    public function __construct(
        private readonly ParticipationFormationRepository $participationFormationRepository,
        private readonly FormationRepository $formationRepository,
        private readonly AiFormationRecommendationExplanationService $aiFormationRecommendationExplanationService,
    ) {
    }

    /**
     * @param Formation[] $catalogFormations
     *
     * @return array{
     *     profile: array<string, mixed>|null,
     *     recommendations: array<int, array{formation: Formation, score: int, reasons: string[], explanation: string, explanation_source: string}>
     * }
     */
    public function recommendForParticipant(?int $participantId, array $catalogFormations): array
    {
        if ($participantId === null) {
            return [
                'profile' => null,
                'recommendations' => [],
            ];
        }

        $participations = $this->participationFormationRepository->findByParticipantOrdered($participantId);
        $historyIds = array_values(array_unique(array_filter(array_map(
            static fn (ParticipationFormation $participation): ?int => $participation->getIDFormation(),
            $participations
        ))));
        $historyById = $this->formationRepository->findByIds(array_map('intval', $historyIds));
        $history = $this->buildHistory($participations, $historyById);
        $popularity = $this->buildPopularityIndex($catalogFormations);
        $recommendations = [];

        foreach ($catalogFormations as $formation) {
            if (!$formation instanceof Formation) {
                continue;
            }

            $formationId = $formation->getIDFormation();

            if ($formationId === null || isset($history['seen_ids'][$formationId])) {
                continue;
            }

            $scored = $this->scoreFormation($formation, $history, $popularity);

            if ($scored['score'] <= 0) {
                continue;
            }

            $recommendations[] = [
                'formation' => $formation,
                'score' => $scored['score'],
                'reasons' => $scored['reasons'],
                'explanation' => '',
                'explanation_source' => 'none',
            ];
        }

        usort($recommendations, static function (array $left, array $right): int {
            if ($left['score'] === $right['score']) {
                return strcmp((string) ($left['formation']->getTitre() ?? ''), (string) ($left['formation']->getTitre() ?? ''));
            }

            return $right['score'] <=> $left['score'];
        });

        $recommendations = array_map(function (array $recommendation) use ($history): array {
            $explanation = $this->aiFormationRecommendationExplanationService->buildExplanation(
                [
                    'history_count' => $history['history_count'],
                    'preferred_level' => $history['preferred_level_label'],
                    'preferred_mode' => $history['preferred_mode_label'],
                    'interest_keywords' => $history['interest_keywords'],
                ],
                $recommendation['formation'],
                $recommendation['reasons']
            );
            $recommendation['explanation'] = (string) ($explanation['text'] ?? '');
            $recommendation['explanation_source'] = (string) ($explanation['source'] ?? 'none');

            return $recommendation;
        }, array_slice($recommendations, 0, 4));

        return [
            'profile' => [
                'history_count' => $history['history_count'],
                'preferred_level' => $history['preferred_level_label'],
                'preferred_mode' => $history['preferred_mode_label'],
                'interest_keywords' => $history['interest_keywords'],
                'strategy' => $history['history_count'] > 0 ? 'personnalisee par historique' : 'popularite du catalogue',
            ],
            'recommendations' => $recommendations,
        ];
    }

    /**
     * @param ParticipationFormation[] $participations
     * @param array<int, Formation> $historyById
     *
     * @return array<string, mixed>
     */
    private function buildHistory(array $participations, array $historyById): array
    {
        $levelCounts = [];
        $modeCounts = [];
        $interestCounts = [];
        $seenIds = [];

        foreach ($participations as $participation) {
            $formationId = $participation->getIDFormation();

            if ($formationId === null) {
                continue;
            }

            $seenIds[$formationId] = true;
            $historyFormation = $historyById[$formationId] ?? null;

            if (!$historyFormation instanceof Formation) {
                continue;
            }

            $level = $this->normalizeLevel($historyFormation->getNiveau());

            if ($level !== null) {
                $levelCounts[$level] = ($levelCounts[$level] ?? 0) + 1;
            }

            $mode = trim((string) ($historyFormation->getMode() ?? ''));

            if ($mode !== '') {
                $modeCounts[$mode] = ($modeCounts[$mode] ?? 0) + 1;
            }

            foreach ($this->extractTokensFromFormation($historyFormation) as $token) {
                $interestCounts[$token] = ($interestCounts[$token] ?? 0) + 1;
            }
        }

        arsort($levelCounts);
        arsort($modeCounts);
        arsort($interestCounts);

        $preferredLevel = array_key_first($levelCounts);
        $preferredMode = array_key_first($modeCounts);

        return [
            'history_count' => count($seenIds),
            'preferred_level' => $preferredLevel,
            'preferred_level_label' => $this->denormalizeLevel($preferredLevel),
            'preferred_mode' => $preferredMode,
            'preferred_mode_label' => $this->formatMode($preferredMode),
            'interest_keywords' => array_slice(array_keys($interestCounts), 0, 4),
            'interest_tokens' => array_keys(array_slice($interestCounts, 0, 8, true)),
            'seen_ids' => $seenIds,
        ];
    }

    /**
     * @param Formation[] $catalogFormations
     *
     * @return array<int, int>
     */
    private function buildPopularityIndex(array $catalogFormations): array
    {
        $popularity = [];

        foreach ($catalogFormations as $formation) {
            if (!$formation instanceof Formation || $formation->getIDFormation() === null) {
                continue;
            }

            $formationId = $formation->getIDFormation();
            $popularity[$formationId] = count($this->participationFormationRepository->findByFormationOrdered($formationId));
        }

        return $popularity;
    }

    /**
     * @param array<string, mixed> $history
     * @param array<int, int> $popularity
     *
     * @return array{score: int, reasons: string[]}
     */
    private function scoreFormation(Formation $formation, array $history, array $popularity): array
    {
        $score = 0;
        $reasons = [];
        $formationId = $formation->getIDFormation();

        if ($formationId === null) {
            return ['score' => 0, 'reasons' => []];
        }

        $formationLevel = $this->normalizeLevel($formation->getNiveau());
        $formationMode = trim((string) ($formation->getMode() ?? ''));

        if (($formation->getPlacesRestantes() ?? 0) > 0) {
            $score += 14;
            $reasons[] = 'places encore disponibles';
        } else {
            $score -= 18;
        }

        $historyCount = (int) ($history['history_count'] ?? 0);

        if ($historyCount > 0) {
            $similarity = $this->countTokenMatches($history['interest_tokens'] ?? [], sprintf(
                '%s %s',
                (string) ($formation->getTitre() ?? ''),
                (string) ($formation->getDescription() ?? '')
            ));

            if ($similarity > 0) {
                $score += min(36, 14 + ($similarity * 5));
                $reasons[] = 'similaire aux formations deja suivies';
            }

            $levelScore = $this->scoreLevelCompatibility((string) ($history['preferred_level'] ?? ''), $formationLevel);

            if ($levelScore > 0) {
                $score += $levelScore;
                $reasons[] = 'niveau coherent avec son historique';
            }

            if (($history['preferred_mode'] ?? null) !== null && $history['preferred_mode'] === $formationMode) {
                $score += 8;
                $reasons[] = sprintf('meme mode que ses inscriptions precedentes (%s)', $this->formatMode($formationMode));
            }
        } else {
            if ($formationLevel === 'debutant') {
                $score += 12;
                $reasons[] = 'bon point de depart pour un premier choix';
            }
        }

        $popularityScore = (int) ($popularity[$formationId] ?? 0);

        if ($popularityScore > 0) {
            $score += min(20, $popularityScore * 4);
            $reasons[] = 'formation populaire dans le catalogue';
        }

        $reasons = array_values(array_unique(array_slice($reasons, 0, 3)));

        return [
            'score' => $score,
            'reasons' => $reasons,
        ];
    }

    private function scoreLevelCompatibility(string $historyLevel, ?string $formationLevel): int
    {
        if ($historyLevel === '' || $formationLevel === null) {
            return 0;
        }

        $historyValue = self::LEVEL_SCORES[$historyLevel] ?? null;
        $formationValue = self::LEVEL_SCORES[$formationLevel] ?? null;

        if ($historyValue === null || $formationValue === null) {
            return 0;
        }

        $diff = $formationValue - $historyValue;

        return match (true) {
            $diff === 0 => 16,
            $diff === 1 => 12,
            $diff === -1 => 6,
            default => 0,
        };
    }

    /**
     * @return string[]
     */
    private function extractTokensFromFormation(Formation $formation): array
    {
        return $this->tokenize(sprintf(
            '%s %s',
            (string) ($formation->getTitre() ?? ''),
            (string) ($formation->getDescription() ?? '')
        ));
    }

    /**
     * @return string[]
     */
    private function tokenize(string $value): array
    {
        $normalized = mb_strtolower($this->stripAccents($value));
        $parts = preg_split('/[^a-z0-9]+/', $normalized) ?: [];
        $stopWords = [
            'avec', 'dans', 'pour', 'vous', 'leur', 'plus', 'moins', 'formation', 'formations',
            'niveau', 'ligne', 'presentiel', 'cours', 'module', 'modules', 'session', 'sessions',
            'des', 'les', 'une', 'sur', 'par', 'aux', 'est', 'ses', 'son', 'nos', 'notre',
        ];

        $tokens = array_values(array_filter($parts, static function (string $part) use ($stopWords): bool {
            return strlen($part) >= 4 && !in_array($part, $stopWords, true);
        }));

        return array_values(array_unique($tokens));
    }

    /**
     * @param string[] $tokens
     */
    private function countTokenMatches(array $tokens, string $haystack): int
    {
        $count = 0;
        $normalizedHaystack = $this->stripAccents($haystack);

        foreach ($tokens as $token) {
            if ($token !== '' && str_contains($normalizedHaystack, $token)) {
                $count++;
            }
        }

        return $count;
    }

    private function normalizeLevel(?string $value): ?string
    {
        $normalized = mb_strtolower($this->stripAccents(trim((string) $value)));

        return match (true) {
            $normalized === 'debutant' => 'debutant',
            $normalized === 'intermediaire' => 'intermediaire',
            $normalized === 'avance' => 'avance',
            default => null,
        };
    }

    private function denormalizeLevel(?string $value): string
    {
        return match ($value) {
            'intermediaire' => 'Intermediaire',
            'avance' => 'Avance',
            default => 'Variable',
        };
    }

    private function formatMode(?string $value): string
    {
        return match ($value) {
            'en_ligne' => 'en ligne',
            'presentiel' => 'presentiel',
            default => 'mixte',
        };
    }

    private function stripAccents(string $value): string
    {
        $converted = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $converted !== false ? mb_strtolower($converted) : mb_strtolower($value);
    }
}
