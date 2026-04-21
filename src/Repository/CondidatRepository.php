<?php

namespace App\Repository;

use App\Entity\Condidat;
use App\Entity\Utilisateur;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * @extends ServiceEntityRepository<Condidat>
 */
class CondidatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private readonly Security $security)
    {
        parent::__construct($registry, Condidat::class);
    }

    public function fetchCandidateCandidatures(string $search = ''): array
    {
        $candidateId = $this->getActiveCandidateId();
        $search = mb_strtolower(trim($search));

        $params = ['candidateId' => $candidateId];
        $searchCondition = '';
        if ($search !== '') {
            $params['search'] = '%' . $search . '%';
            $searchCondition = ' AND (
                LOWER(COALESCE(o.Titre, \'\')) LIKE :search
                OR LOWER(COALESCE(o.Localisation, \'\')) LIKE :search
                OR LOWER(COALESCE(tc.Description_Contrat, \'\')) LIKE :search
                OR LOWER(COALESCE(tsc.Description_Status_Condidature, \'\')) LIKE :search
            )';
        }

        $rows = $this->getConnection()->fetchAllAssociative(
            'SELECT
                c.ID_Condidature,
                c.ID_Offre,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                c.Code_Type_Status,
                c.CV_Extracted_Text,
                c.AI_Score,
                c.AI_Recommendation,
                c.AI_Summary,
                c.AI_Last_Analyzed_At,
                     cd.CV,
                     u.Nom_Utilisateur,
                     u.Email,
                o.Titre,
                o.Localisation,
                o.Work_Type,
                o.Nbr_Annee_Experience,
                tc.Description_Contrat,
                tsc.Description_Status_Condidature,
                ie.Localisation AS Interview_Localisation,
                ie.Evaluation AS Interview_Evaluation,
                io.AAAA AS Interview_AAAA,
                io.MM AS Interview_MM,
                io.JJ AS Interview_JJ,
                io.HH AS Interview_HH,
                io.MN AS Interview_MN
             FROM condidature c
                 INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
                 INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             LEFT JOIN (
                SELECT e1.ID_Condidat, e1.Num_Ordre_Entretien, e1.Localisation, e1.Evaluation
                FROM entretien e1
                INNER JOIN (
                    SELECT ID_Condidat, MAX(Num_Ordre_Entretien) AS Max_Num_Ordre_Entretien
                    FROM entretien
                    GROUP BY ID_Condidat
                ) latest_e ON latest_e.ID_Condidat = e1.ID_Condidat AND latest_e.Max_Num_Ordre_Entretien = e1.Num_Ordre_Entretien
             ) ie ON ie.ID_Condidat = c.ID_Condidat
             LEFT JOIN ordre io ON io.Num_Ordre = ie.Num_Ordre_Entretien
             WHERE c.ID_Condidat = :candidateId' . $searchCondition . '
             ORDER BY c.ID_Condidature DESC',
            $params
        );

        return array_map(fn (array $row): array => $this->mapCandidatureRow($row), $rows);
    }

    public function getCandidateDashboard(string $search = ''): array
    {
        $items = $this->fetchCandidateCandidatures($search);
        $inProgress = 0;
        foreach ($items as $item) {
            if (in_array($item['statusCode'], [1, 2], true)) {
                $inProgress++;
            }
        }

        return [
            'total' => count($items),
            'inProgress' => $inProgress,
            'items' => $items,
        ];
    }

    public function fetchCandidatureById(int $id): ?array
    {
        $candidateId = $this->getActiveCandidateId();
        $row = $this->getConnection()->fetchAssociative(
            'SELECT
                c.ID_Condidature,
                c.ID_Offre,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                c.Code_Type_Status,
                c.CV_Extracted_Text,
                c.AI_Score,
                c.AI_Recommendation,
                c.AI_Summary,
                c.AI_Last_Analyzed_At,
                     cd.CV,
                     u.Nom_Utilisateur,
                     u.Email,
                o.Titre,
                o.Localisation,
                o.Work_Type,
                o.Nbr_Annee_Experience,
                tc.Description_Contrat,
                tsc.Description_Status_Condidature,
                ie.Localisation AS Interview_Localisation,
                ie.Evaluation AS Interview_Evaluation,
                io.AAAA AS Interview_AAAA,
                io.MM AS Interview_MM,
                io.JJ AS Interview_JJ,
                io.HH AS Interview_HH,
                io.MN AS Interview_MN
             FROM condidature c
                 INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
                 INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             LEFT JOIN (
                SELECT e1.ID_Condidat, e1.Num_Ordre_Entretien, e1.Localisation, e1.Evaluation
                FROM entretien e1
                INNER JOIN (
                    SELECT ID_Condidat, MAX(Num_Ordre_Entretien) AS Max_Num_Ordre_Entretien
                    FROM entretien
                    GROUP BY ID_Condidat
                ) latest_e ON latest_e.ID_Condidat = e1.ID_Condidat AND latest_e.Max_Num_Ordre_Entretien = e1.Num_Ordre_Entretien
             ) ie ON ie.ID_Condidat = c.ID_Condidat
             LEFT JOIN ordre io ON io.Num_Ordre = ie.Num_Ordre_Entretien
             WHERE c.ID_Condidature = :id AND c.ID_Condidat = :candidateId',
            ['id' => $id, 'candidateId' => $candidateId]
        );

        if ($row === false) {
            return null;
        }

        return $this->mapCandidatureRow($row);
    }

    public function validateCandidaturePayload(array $payload): array
    {
        $errors = [];

        $offerId = $this->sanitizeNullableInt($payload['offerId'] ?? null);
        $candidateName = trim((string) ($payload['candidateName'] ?? ''));
        $candidateEmail = trim((string) ($payload['candidateEmail'] ?? ''));
        $portfolioUrl = trim((string) ($payload['portfolioUrl'] ?? ''));
        $motivationLetter = trim((string) ($payload['motivationLetter'] ?? ''));
        $cvFileName = trim((string) ($payload['cvFileName'] ?? ''));
        $cvMimeType = trim((string) ($payload['cvMimeType'] ?? ''));
        $cvFileContentBase64 = trim((string) ($payload['cvFileContentBase64'] ?? ''));
        $cvExtractedText = trim((string) ($payload['cvExtractedText'] ?? ''));
        $recommendationFileName = trim((string) ($payload['recommendationFileName'] ?? ''));

        if ($offerId === null) {
            $errors['offerId'] = 'Selectionnez une offre.';
        } elseif (!$this->offerExists($offerId)) {
            $errors['offerId'] = 'Offre introuvable.';
        }

        if ($candidateName === '') {
            $errors['candidateName'] = 'Le nom complet est obligatoire.';
        }

        if ($candidateEmail === '') {
            $errors['candidateEmail'] = "L'email est obligatoire.";
        } elseif (!filter_var($candidateEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['candidateEmail'] = "Le format de l'email est invalide.";
        }

        if ($portfolioUrl === '') {
            $errors['portfolioUrl'] = 'Le lien du portfolio est obligatoire.';
        } elseif (!filter_var($portfolioUrl, FILTER_VALIDATE_URL)) {
            $errors['portfolioUrl'] = 'Le lien du portfolio est invalide.';
        }

        if ($cvFileName === '') {
            $errors['cvFileName'] = 'Le CV est obligatoire.';
        }

        // Fields below are optional for backward compatibility with older clients.
        // If available, they are used for AI analysis.

        if ($motivationLetter === '') {
            $errors['motivationLetter'] = 'La lettre de motivation est obligatoire.';
        }

        if ($recommendationFileName === '') {
            $errors['recommendationFileName'] = 'La lettre de recommandation est obligatoire.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'offerId' => $offerId,
                'candidateName' => $candidateName,
                'candidateEmail' => $candidateEmail,
                'portfolioUrl' => $portfolioUrl,
                'motivationLetter' => $motivationLetter,
                'cvFileName' => $cvFileName,
                'cvMimeType' => $cvMimeType,
                'cvFileContentBase64' => $cvFileContentBase64,
                'cvExtractedText' => $cvExtractedText,
                'recommendationFileName' => $recommendationFileName,
            ],
        ];
    }

    public function createCandidature(array $data): array
    {
        $connection = $this->getConnection();
        $candidateId = $this->getActiveCandidateId();
        $statusCode = $this->getDefaultStatusCode();

        $exists = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM condidature WHERE ID_Condidat = :candidateId AND ID_Offre = :offerId',
            ['candidateId' => $candidateId, 'offerId' => $data['offerId']]
        );
        if ($exists > 0) {
            throw new \RuntimeException('Vous avez deja postule a cette offre.');
        }

        $connection->beginTransaction();
        try {
            $this->updateCandidateProfile($candidateId, $data['candidateName'], $data['candidateEmail'], $data['cvFileName']);

            $connection->insert('condidature', [
                'ID_Condidat' => $candidateId,
                'ID_Offre' => $data['offerId'],
                'Lettre_Motivation' => $data['motivationLetter'],
                'Portfolio' => $data['portfolioUrl'],
                'Lettre_Recomendation' => $data['recommendationFileName'],
                'CV_Extracted_Text' => $data['cvExtractedText'],
                'Code_Type_Status' => $statusCode,
            ]);

            $id = (int) $connection->lastInsertId();
            $connection->commit();

            return $this->fetchCandidatureById($id) ?? [];
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    public function updateCandidature(int $id, array $data): ?array
    {
        $existing = $this->fetchCandidatureById($id);
        if ($existing === null) {
            return null;
        }

        $connection = $this->getConnection();
        $candidateId = $this->getActiveCandidateId();
        $duplicate = (int) $connection->fetchOne(
            'SELECT COUNT(*) FROM condidature WHERE ID_Condidat = :candidateId AND ID_Offre = :offerId AND ID_Condidature <> :id',
            ['candidateId' => $candidateId, 'offerId' => $data['offerId'], 'id' => $id]
        );
        if ($duplicate > 0) {
            throw new \RuntimeException('Vous avez deja une candidature pour cette offre.');
        }

        $connection->beginTransaction();
        try {
            $this->updateCandidateProfile($candidateId, $data['candidateName'], $data['candidateEmail'], $data['cvFileName']);

            $connection->update('condidature', [
                'ID_Offre' => $data['offerId'],
                'Lettre_Motivation' => $data['motivationLetter'],
                'Portfolio' => $data['portfolioUrl'],
                'Lettre_Recomendation' => $data['recommendationFileName'],
                'CV_Extracted_Text' => $data['cvExtractedText'],
            ], ['ID_Condidature' => $id, 'ID_Condidat' => $candidateId]);

            $connection->commit();

            return $this->fetchCandidatureById($id);
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteCandidature(int $id): bool
    {
        $candidateId = $this->getActiveCandidateId();
        $deleted = $this->getConnection()->delete('condidature', [
            'ID_Condidature' => $id,
            'ID_Condidat' => $candidateId,
        ]);

        return $deleted > 0;
    }

    public function saveAiAssessmentForCandidature(int $id, float $score, string $recommendation, string $summary): int
    {
        $candidateId = $this->getActiveCandidateId();

        return $this->getConnection()->update('condidature', [
            'AI_Score' => round($score, 2),
            'AI_Recommendation' => $recommendation,
            'AI_Summary' => $summary,
            'AI_Last_Analyzed_At' => (new \DateTimeImmutable('now'))->format('Y-m-d H:i:s'),
        ], [
            'ID_Condidature' => $id,
            'ID_Condidat' => $candidateId,
        ]);
    }

    private function getConnection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function getActiveCandidateId(): int
    {
        $user = $this->security->getUser();
        if (!$user instanceof Utilisateur) {
            throw new \RuntimeException('Aucun utilisateur connecte.');
        }

        $connection = $this->getConnection();
        $candidateId = $connection->fetchOne(
            'SELECT ID_Condidat FROM condidat WHERE ID_UTILISATEUR = :userId ORDER BY ID_Condidat DESC LIMIT 1',
            ['userId' => $user->getIDUTILISATEUR()]
        );

        if ($candidateId === false || $candidateId === null || $candidateId === '') {
            $connection->insert('condidat', [
                'ID_UTILISATEUR' => $user->getIDUTILISATEUR(),
                'CV' => '',
            ]);
            $candidateId = (int) $connection->lastInsertId();

            if ($candidateId <= 0) {
                throw new \RuntimeException('Aucun profil candidat disponible.');
            }

            return $candidateId;
        }

        return (int) $candidateId;
    }

    private function getDefaultStatusCode(): int
    {
        $connection = $this->getConnection();
        $statusCode = $connection->fetchOne(
            "SELECT Code_Type_Status_Condidature FROM type_status_condidature WHERE UPPER(Description_Status_Condidature) = 'SUBMITTED' LIMIT 1"
        );

        if ($statusCode !== false && $statusCode !== null && $statusCode !== '') {
            return (int) $statusCode;
        }

        $firstStatus = $connection->fetchOne('SELECT MIN(Code_Type_Status_Condidature) FROM type_status_condidature');
        if ($firstStatus === false || $firstStatus === null || $firstStatus === '') {
            throw new \RuntimeException('Aucun status de candidature disponible.');
        }

        return (int) $firstStatus;
    }

    private function updateCandidateProfile(int $candidateId, string $name, string $email, string $cvFileName): void
    {
        $connection = $this->getConnection();
        $utilisateurId = $connection->fetchOne('SELECT ID_UTILISATEUR FROM condidat WHERE ID_Condidat = :id', ['id' => $candidateId]);
        if ($utilisateurId === false || $utilisateurId === null || $utilisateurId === '') {
            throw new \RuntimeException('Utilisateur candidat introuvable.');
        }

        $connection->update('utilisateur', [
            'Nom_Utilisateur' => $name,
            'Email' => $email,
        ], ['ID_UTILISATEUR' => (int) $utilisateurId]);

        $connection->update('condidat', [
            'CV' => $cvFileName,
        ], ['ID_Condidat' => $candidateId]);
    }

    private function offerExists(int $offerId): bool
    {
        $count = $this->getConnection()->fetchOne('SELECT COUNT(*) FROM offre WHERE ID_Offre = :id', ['id' => $offerId]);

        return ((int) $count) > 0;
    }

    private function sanitizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function mapCandidatureRow(array $row): array
    {
        $statusLabel = strtoupper((string) ($row['Description_Status_Condidature'] ?? 'SUBMITTED'));
        $isAccepted = in_array($statusLabel, ['ACCEPTED', 'ACCEPTEE', 'ACCEPTE'], true);
        $statusClass = match ($statusLabel) {
            'REVIEW' => 'status--review',
            'ACCEPTED', 'ACCEPTEE', 'ACCEPTE' => 'status--meet',
            'REJECTED' => 'status--closed',
            default => 'status--sent',
        };

        $interviewDate = $this->formatInterviewDate($row);
        $interviewTime = $this->formatInterviewTime($row);
        $interviewComment = ($isAccepted && $interviewDate !== '' && $interviewTime !== '')
            ? sprintf('Vous avez un entretien le %s a %s.', $interviewDate, $interviewTime)
            : '';

        return [
            'id' => (int) $row['ID_Condidature'],
            'offerId' => isset($row['ID_Offre']) ? (int) $row['ID_Offre'] : null,
            'title' => (string) ($row['Titre'] ?? 'Offre indisponible'),
            'location' => (string) (($row['Localisation'] ?? '') !== '' ? $row['Localisation'] : '-'),
            'contract' => (string) (($row['Description_Contrat'] ?? '') !== '' ? $row['Description_Contrat'] : 'Non defini'),
            'workType' => (string) (($row['Work_Type'] ?? '') !== '' ? $row['Work_Type'] : 'Non precise'),
            'experience' => isset($row['Nbr_Annee_Experience']) && $row['Nbr_Annee_Experience'] !== null
                ? sprintf('%d ans', (int) $row['Nbr_Annee_Experience'])
                : 'Non precisee',
            'statusCode' => (int) ($row['Code_Type_Status'] ?? 0),
            'statusLabel' => $statusLabel,
            'statusClass' => $statusClass,
            'motivationLetter' => (string) ($row['Lettre_Motivation'] ?? ''),
            'portfolioUrl' => (string) ($row['Portfolio'] ?? ''),
            'candidateName' => (string) ($row['Nom_Utilisateur'] ?? ''),
            'candidateEmail' => (string) ($row['Email'] ?? ''),
            'cvFileName' => (string) ($row['CV'] ?? ''),
            'cvExtractedText' => (string) ($row['CV_Extracted_Text'] ?? ''),
            'recommendationFileName' => (string) ($row['Lettre_Recomendation'] ?? ''),
            'aiScore' => isset($row['AI_Score']) ? (float) $row['AI_Score'] : null,
            'aiRecommendation' => (string) ($row['AI_Recommendation'] ?? ''),
            'aiSummary' => (string) ($row['AI_Summary'] ?? ''),
            'aiLastAnalyzedAt' => (string) ($row['AI_Last_Analyzed_At'] ?? ''),
            'hasInterview' => $interviewComment !== '',
            'interviewDate' => $interviewComment !== '' ? $interviewDate : '',
            'interviewTime' => $interviewComment !== '' ? $interviewTime : '',
            'interviewLocation' => $interviewComment !== '' ? (string) ($row['Interview_Localisation'] ?? '') : '',
            'interviewEvaluation' => $interviewComment !== '' ? (string) ($row['Interview_Evaluation'] ?? '') : '',
            'interviewComment' => $interviewComment,
        ];
    }

    /**
     * @param array<string, mixed> $row
     */
    private function formatInterviewDate(array $row): string
    {
        $year = isset($row['Interview_AAAA']) ? (int) $row['Interview_AAAA'] : 0;
        $month = isset($row['Interview_MM']) ? (int) $row['Interview_MM'] : 0;
        $day = isset($row['Interview_JJ']) ? (int) $row['Interview_JJ'] : 0;

        if ($year > 0 && $month > 0 && $day > 0 && checkdate($month, $day, $year)) {
            return sprintf('%02d/%02d/%04d', $day, $month, $year);
        }

        return '';
    }

    /**
     * @param array<string, mixed> $row
     */
    private function formatInterviewTime(array $row): string
    {
        $hour = isset($row['Interview_HH']) ? (int) $row['Interview_HH'] : -1;
        $minute = isset($row['Interview_MN']) ? (int) $row['Interview_MN'] : -1;

        if ($hour >= 0 && $minute >= 0) {
            return sprintf('%02d:%02d', $hour, $minute);
        }

        return '';
    }

//    /**
//     * @return Condidat[] Returns an array of Condidat objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Condidat
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
