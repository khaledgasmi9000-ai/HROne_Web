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
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
                o.Nbr_Annee_Experience,
                tsc.Description_Status_Condidature,
                GROUP_CONCAT(DISTINCT tc.Description_Competence ORDER BY tc.Description_Competence SEPARATOR ", ") AS Competences,
                CASE WHEN COUNT(e.ID_Condidat) > 0 THEN 1 ELSE 0 END AS Has_Interview
             FROM condidature c
             INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
             INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             LEFT JOIN detail_offre_competence doc ON doc.ID_Offre = o.ID_Offre
             LEFT JOIN type_competence tc ON tc.Code_Type_Competence = doc.Code_Type_Competence
             LEFT JOIN entretien e ON e.ID_Condidat = c.ID_Condidat
             GROUP BY
                c.ID_Condidature,
                c.ID_Offre,
                c.ID_Condidat,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
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
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
                o.Nbr_Annee_Experience,
                tsc.Description_Status_Condidature,
                GROUP_CONCAT(DISTINCT tc.Description_Competence ORDER BY tc.Description_Competence SEPARATOR ", ") AS Competences,
                CASE WHEN COUNT(e.ID_Condidat) > 0 THEN 1 ELSE 0 END AS Has_Interview
             FROM condidature c
             INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
             INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             LEFT JOIN detail_offre_competence doc ON doc.ID_Offre = o.ID_Offre
             LEFT JOIN type_competence tc ON tc.Code_Type_Competence = doc.Code_Type_Competence
             LEFT JOIN entretien e ON e.ID_Condidat = c.ID_Condidat
             WHERE c.ID_Condidature = :id
             GROUP BY
                c.ID_Condidature,
                c.ID_Offre,
                c.ID_Condidat,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                cd.CV,
                u.Nom_Utilisateur,
                u.Email,
                o.Titre,
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

        $exists = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT 1 FROM condidature WHERE ID_Condidature = :id LIMIT 1',
            ['id' => $id]
        );

        if ($exists === false) {
            return null;
        }

        $this->getEntityManager()->getConnection()->executeStatement(
            'UPDATE condidature SET Code_Type_Status = :statusCode WHERE ID_Condidature = :id',
            [
                'statusCode' => (int) $statusCode,
                'id' => $id,
            ]
        );

        return $this->fetchRhCandidateByCandidatureId($id);
    }

    public function scheduleInterviewForRh(int $candidatureId, DateTimeInterface $scheduledAt, string $location): bool
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
        if ($status !== 'ACCEPTED') {
            throw new RuntimeException('La candidature doit etre acceptee avant de planifier un entretien.');
        }

        $rhId = $this->resolveRhId();
        if ($rhId === null) {
            throw new RuntimeException('Impossible de determiner le RH pour planifier lentretien.');
        }

        $numOrdre = (int) $scheduledAt->format('U');
        $this->ensureOrdreExists($numOrdre, $scheduledAt);

        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT INTO entretien (ID_Condidat, ID_RH, Num_Ordre_Entretien, Localisation, Status_Entretien, Evaluation)
             VALUES (:candidateId, :rhId, :numOrdre, :location, :statusEntretien, :evaluation)
             ON DUPLICATE KEY UPDATE
               Localisation = VALUES(Localisation),
               Status_Entretien = VALUES(Status_Entretien),
               Evaluation = VALUES(Evaluation)',
            [
                'candidateId' => (int) $row['ID_Condidat'],
                'rhId' => $rhId,
                'numOrdre' => $numOrdre,
                'location' => trim($location) !== '' ? trim($location) : 'A definir',
                'statusEntretien' => 1,
                'evaluation' => null,
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

        return [
            'id' => (int) ($row['ID_Condidature'] ?? 0),
            'offerId' => (int) ($row['ID_Offre'] ?? 0),
            'offerTitle' => (string) ($row['Titre'] ?? '-'),
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
            'hasInterview' => ((int) ($row['Has_Interview'] ?? 0)) > 0,
        ];
    }

    /**
     * @return array{0:string,1:string}
     */
    private function mapStatusForRh(string $statusRaw): array
    {
        return match ($statusRaw) {
            'ACCEPTED' => ['Acceptee', 'status-live'],
            'REJECTED' => ['Rejetee', 'status-draft'],
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

    private function ensureOrdreExists(int $numOrdre, DateTimeInterface $scheduledAt): void
    {
        $this->getEntityManager()->getConnection()->executeStatement(
            'INSERT IGNORE INTO ordre (Num_Ordre, AAAA, MM, JJ, HH, MN, SS)
             VALUES (:numOrdre, :year, :month, :day, :hour, :minute, :second)',
            [
                'numOrdre' => $numOrdre,
                'year' => (int) $scheduledAt->format('Y'),
                'month' => (int) $scheduledAt->format('m'),
                'day' => (int) $scheduledAt->format('d'),
                'hour' => (int) $scheduledAt->format('H'),
                'minute' => (int) $scheduledAt->format('i'),
                'second' => (int) $scheduledAt->format('s'),
            ]
        );
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
