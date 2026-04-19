<?php

namespace App\Repository;

use App\Entity\Condidature;
use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use RuntimeException;

/**
 * @extends ServiceEntityRepository<Condidature>
 */
class CondidatureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Condidature::class);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchForRhManagement(): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT
                c.ID_Condidature,
                c.ID_Offre,
                c.ID_Condidat,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                c.AI_Score,
                c.AI_Recommendation,
                c.AI_Summary,
                c.AI_Last_Analyzed_At,
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
                o.Work_Type,
                o.Localisation,
                o.Min_Salaire,
                o.Max_Salaire,
                tc.Description_Contrat,
                o.Nbr_Annee_Experience,
                tsc.Description_Status_Condidature,
                GROUP_CONCAT(DISTINCT tcomp.Description_Competence ORDER BY tcomp.Description_Competence SEPARATOR ", ") AS Competences,
                     MAX(ie.Num_Ordre_Entretien) AS Interview_Num_Ordre,
                     MAX(ie.Localisation) AS Interview_Localisation,
                     MAX(ie.Evaluation) AS Interview_Evaluation,
                     MAX(io.AAAA) AS Interview_AAAA,
                     MAX(io.MM) AS Interview_MM,
                     MAX(io.JJ) AS Interview_JJ,
                     MAX(io.HH) AS Interview_HH,
                     MAX(io.MN) AS Interview_MN,
                     CASE WHEN MAX(ie.Num_Ordre_Entretien) IS NOT NULL THEN 1 ELSE 0 END AS Has_Interview
             FROM condidature c
             INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
             INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             LEFT JOIN detail_offre_competence doc ON doc.ID_Offre = o.ID_Offre
             LEFT JOIN type_competence tcomp ON tcomp.Code_Type_Competence = doc.Code_Type_Competence
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
             GROUP BY
                c.ID_Condidature,
                c.ID_Offre,
                c.ID_Condidat,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                c.AI_Score,
                c.AI_Recommendation,
                c.AI_Summary,
                c.AI_Last_Analyzed_At,
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
                o.Work_Type,
                o.Localisation,
                o.Min_Salaire,
                o.Max_Salaire,
                tc.Description_Contrat,
                o.Nbr_Annee_Experience,
                tsc.Description_Status_Condidature
             ORDER BY c.ID_Condidature DESC'
        );

        return array_map(fn (array $row): array => $this->mapRhCandidateRow($row), $rows);
    }

    /**
     * @return array<int, array{id:int,title:string}>
     */
    public function fetchOfferOptionsForRh(): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT DISTINCT o.ID_Offre, o.Titre
             FROM condidature c
             INNER JOIN offre o ON o.ID_Offre = c.ID_Offre
             ORDER BY o.Titre ASC'
        );

        return array_map(static function (array $row): array {
            return [
                'id' => (int) $row['ID_Offre'],
                'title' => (string) $row['Titre'],
            ];
        }, $rows);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function fetchRhCandidateByCandidatureId(int $id): ?array
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT
                c.ID_Condidature,
                c.ID_Offre,
                c.ID_Condidat,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                c.AI_Score,
                c.AI_Recommendation,
                c.AI_Summary,
                c.AI_Last_Analyzed_At,
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
                o.Work_Type,
                o.Localisation,
                o.Min_Salaire,
                o.Max_Salaire,
                tc.Description_Contrat,
                o.Nbr_Annee_Experience,
                tsc.Description_Status_Condidature,
                GROUP_CONCAT(DISTINCT tcomp.Description_Competence ORDER BY tcomp.Description_Competence SEPARATOR ", ") AS Competences,
                     MAX(ie.Num_Ordre_Entretien) AS Interview_Num_Ordre,
                     MAX(ie.Localisation) AS Interview_Localisation,
                     MAX(ie.Evaluation) AS Interview_Evaluation,
                     MAX(io.AAAA) AS Interview_AAAA,
                     MAX(io.MM) AS Interview_MM,
                     MAX(io.JJ) AS Interview_JJ,
                     MAX(io.HH) AS Interview_HH,
                     MAX(io.MN) AS Interview_MN,
                     CASE WHEN MAX(ie.Num_Ordre_Entretien) IS NOT NULL THEN 1 ELSE 0 END AS Has_Interview
             FROM condidature c
             INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
             INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             LEFT JOIN detail_offre_competence doc ON doc.ID_Offre = o.ID_Offre
             LEFT JOIN type_competence tcomp ON tcomp.Code_Type_Competence = doc.Code_Type_Competence
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
             WHERE c.ID_Condidature = :id
             GROUP BY
                c.ID_Condidature,
                c.ID_Offre,
                c.ID_Condidat,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                c.AI_Score,
                c.AI_Recommendation,
                c.AI_Summary,
                c.AI_Last_Analyzed_At,
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
                o.Work_Type,
                o.Localisation,
                o.Min_Salaire,
                o.Max_Salaire,
                tc.Description_Contrat,
                o.Nbr_Annee_Experience,
                tsc.Description_Status_Condidature',
            ['id' => $id]
        );

        if ($row === false) {
            return null;
        }

        return $this->mapRhCandidateRow($row);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function updateStatusForRh(int $id, string $requestedStatus): ?array
    {
        $target = strtoupper(trim($requestedStatus));
        $target = match ($target) {
            'ACCEPTEE', 'ACCEPTE', 'ACCEPTED' => 'ACCEPTED',
            'REJETEE', 'REJETE', 'REJECTED' => 'REJECTED',
            default => '',
        };

        if ($target === '') {
            throw new RuntimeException('Statut invalide.');
        }

        $statusCode = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT Code_Type_Status_Condidature
             FROM type_status_condidature
             WHERE UPPER(Description_Status_Condidature) = :target
             LIMIT 1',
            ['target' => $target]
        );

        if ($statusCode === false) {
            throw new RuntimeException('Le statut cible est introuvable en base.');
        }

        $candidatureRow = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT ID_Condidat FROM condidature WHERE ID_Condidature = :id LIMIT 1',
            ['id' => $id]
        );

        if ($candidatureRow === false) {
            return null;
        }

        $candidateId = (int) ($candidatureRow['ID_Condidat'] ?? 0);

        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE condidature SET Code_Type_Status = :statusCode WHERE ID_Condidature = :id',
            [
                'statusCode' => (int) $statusCode,
                'id' => $id,
            ]
        );

        if ($target === 'REJECTED' && $candidateId > 0) {
            // A rejected candidature must not keep a planned interview.
            $this->getEntityManager()->getConnection()->executeStatement(
                'DELETE FROM entretien WHERE ID_Condidat = :candidateId',
                ['candidateId' => $candidateId]
            );
        }

        return $this->fetchRhCandidateByCandidatureId($id);
    }

    public function scheduleInterviewForRh(int $candidatureId, DateTimeInterface $scheduledAt, string $location, string $evaluation = ''): bool
    {
        $row = $this->getEntityManager()->getConnection()->fetchAssociative(
            'SELECT
                c.ID_Condidat,
                tsc.Description_Status_Condidature
             FROM condidature c
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             WHERE c.ID_Condidature = :id
             LIMIT 1',
            ['id' => $candidatureId]
        );

        if ($row === false) {
            return false;
        }

        $status = strtoupper((string) ($row['Description_Status_Condidature'] ?? ''));
        if (!in_array($status, ['ACCEPTED', 'ACCEPTEE', 'ACCEPTE'], true)) {
            throw new RuntimeException('La candidature doit etre acceptee avant de planifier un entretien.');
        }

        $rhId = $this->resolveRhId();
        if ($rhId === null) {
            throw new RuntimeException('Impossible de determiner le RH pour planifier lentretien.');
        }

        $numOrdre = $this->ensureOrdreExists($scheduledAt);

        $candidateId = (int) $row['ID_Condidat'];

        // Keep one interview plan per candidate to avoid cross-update side effects.
        $this->getEntityManager()->getConnection()->executeStatement(
            'DELETE FROM entretien WHERE ID_Condidat = :candidateId',
            ['candidateId' => $candidateId]
        );

        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO entretien (ID_Condidat, ID_RH, Num_Ordre_Entretien, Localisation, Status_Entretien, Evaluation)
             VALUES (:candidateId, :rhId, :numOrdre, :location, :statusEntretien, :evaluation)',
            [
                'candidateId' => $candidateId,
                'rhId' => $rhId,
                'numOrdre' => $numOrdre,
                'location' => trim($location) !== '' ? trim($location) : 'A definir',
                'statusEntretien' => 1,
                'evaluation' => trim($evaluation) !== '' ? trim($evaluation) : null,
            ]
        );

        return true;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function mapRhCandidateRow(array $row): array
    {
        $statusRaw = strtoupper((string) ($row['Description_Status_Condidature'] ?? ''));
        [$statusLabel, $statusClass] = $this->mapStatusForRh($statusRaw);
        $isAccepted = in_array($statusRaw, ['ACCEPTED', 'ACCEPTEE', 'ACCEPTE'], true);
        $interviewDate = $this->formatInterviewDate($row);
        $interviewTime = $this->formatInterviewTime($row);
        $interviewDateTimeIso = $this->formatInterviewDateTimeIso($row);
        $hasInterview = $isAccepted && ((int) ($row['Has_Interview'] ?? 0)) > 0;
        $interviewLocation = $hasInterview ? (string) ($row['Interview_Localisation'] ?? '') : '';
        $interviewEvaluation = $hasInterview ? (string) ($row['Interview_Evaluation'] ?? '') : '';
        $safeInterviewDate = $hasInterview ? $interviewDate : '';
        $safeInterviewTime = $hasInterview ? $interviewTime : '';
        $safeInterviewDateTimeIso = $hasInterview ? $interviewDateTimeIso : '';

        return [
            'id' => (int) ($row['ID_Condidature'] ?? 0),
            'offerId' => (int) ($row['ID_Offre'] ?? 0),
            'offerTitle' => (string) ($row['Titre'] ?? '-'),
            'workType' => (string) ($row['Work_Type'] ?? ''),
            'location' => (string) ($row['Localisation'] ?? ''),
            'contract' => (string) ($row['Description_Contrat'] ?? ''),
            'minSalary' => isset($row['Min_Salaire']) ? (int) $row['Min_Salaire'] : null,
            'maxSalary' => isset($row['Max_Salaire']) ? (int) $row['Max_Salaire'] : null,
            'name' => (string) ($row['Nom_Utilisateur'] ?? ''),
            'email' => (string) ($row['Email'] ?? ''),
            'experience' => ((string) ($row['Nbr_Annee_Experience'] ?? '') !== '')
                ? ((string) $row['Nbr_Annee_Experience']) . ' ans'
                : 'Non precise',
            'skills' => (string) ($row['Competences'] ?? ''),
            'status' => $statusLabel,
            'statusClass' => $statusClass,
            'statusRaw' => $statusRaw,
            'motivationLetter' => (string) ($row['Lettre_Motivation'] ?? ''),
            'portfolio' => (string) ($row['Portfolio'] ?? ''),
            'recommendationFileName' => (string) ($row['Lettre_Recomendation'] ?? ''),
            'cvFileName' => (string) ($row['CV'] ?? ''),
            'aiScore' => isset($row['AI_Score']) && $row['AI_Score'] !== null ? (float) $row['AI_Score'] : null,
            'aiRecommendation' => (string) ($row['AI_Recommendation'] ?? ''),
            'aiSummary' => (string) ($row['AI_Summary'] ?? ''),
            'aiLastAnalyzedAt' => (string) ($row['AI_Last_Analyzed_At'] ?? ''),
            'hasInterview' => $hasInterview,
            'interviewLocation' => $interviewLocation,
            'interviewEvaluation' => $interviewEvaluation,
            'interviewDate' => $safeInterviewDate,
            'interviewTime' => $safeInterviewTime,
            'interviewDateTimeIso' => $safeInterviewDateTimeIso,
            'interviewComment' => $this->buildInterviewComment($safeInterviewDate, $safeInterviewTime),
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapStatusForRh(string $statusRaw): array
    {
        return match ($statusRaw) {
            'ACCEPTED', 'ACCEPTEE', 'ACCEPTE' => ['Acceptee', 'status-live'],
            'REJECTED', 'REJETEE', 'REJETE' => ['Rejetee', 'status-draft'],
            default => ['En attente', 'status-review'],
        };
    }

    private function resolveRhId(): ?int
    {
        $rhId = $this->getEntityManager()->getConnection()->fetchOne('SELECT MIN(ID_UTILISATEUR) FROM utilisateur');
        if ($rhId === false || $rhId === null) {
            return null;
        }

        return (int) $rhId;
    }

    private function ensureOrdreExists(DateTimeInterface $scheduledAt): int
    {
        $connection = $this->getEntityManager()->getConnection();
        $params = [
            'year' => (int) $scheduledAt->format('Y'),
            'month' => (int) $scheduledAt->format('m'),
            'day' => (int) $scheduledAt->format('d'),
            'hour' => (int) $scheduledAt->format('H'),
            'minute' => (int) $scheduledAt->format('i'),
            'second' => (int) $scheduledAt->format('s'),
        ];

        $existing = $connection->fetchOne(
            'SELECT Num_Ordre
             FROM ordre
             WHERE AAAA = :year AND MM = :month AND JJ = :day AND HH = :hour AND MN = :minute AND SS = :second
             LIMIT 1',
            $params
        );

        if ($existing !== false && $existing !== null && $existing !== '') {
            return (int) $existing;
        }

        $candidate = max(1, (int) $scheduledAt->format('U'));
        $maxInt = 2147483647;

        for ($offset = 0; $offset < 100000; $offset++) {
            $numOrdre = $candidate + $offset;
            if ($numOrdre > $maxInt) {
                break;
            }

            $inserted = $connection->executeStatement(
                'INSERT IGNORE INTO ordre (Num_Ordre, AAAA, MM, JJ, HH, MN, SS)
                 VALUES (:numOrdre, :year, :month, :day, :hour, :minute, :second)',
                [
                    'numOrdre' => $numOrdre,
                    ...$params,
                ]
            );

            if ($inserted > 0) {
                return $numOrdre;
            }
        }

        throw new RuntimeException('Impossible de generer une cle Num_Ordre valide pour lentretien.');
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

    /**
     * @param array<string, mixed> $row
     */
    private function formatInterviewDateTimeIso(array $row): string
    {
        $year = isset($row['Interview_AAAA']) ? (int) $row['Interview_AAAA'] : 0;
        $month = isset($row['Interview_MM']) ? (int) $row['Interview_MM'] : 0;
        $day = isset($row['Interview_JJ']) ? (int) $row['Interview_JJ'] : 0;
        $hour = isset($row['Interview_HH']) ? (int) $row['Interview_HH'] : -1;
        $minute = isset($row['Interview_MN']) ? (int) $row['Interview_MN'] : -1;

        if ($year > 0 && $month > 0 && $day > 0 && checkdate($month, $day, $year) && $hour >= 0 && $minute >= 0) {
            return sprintf('%04d-%02d-%02dT%02d:%02d', $year, $month, $day, $hour, $minute);
        }

        return '';
    }

    private function buildInterviewComment(string $date, string $time): string
    {
        if ($date === '' || $time === '') {
            return '';
        }

        return sprintf('Entretien planifie le %s a %s.', $date, $time);
    }

//    /**
//     * @return Condidature[] Returns an array of Condidature objects
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

//    public function findOneBySomeField($value): ?Condidature
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
