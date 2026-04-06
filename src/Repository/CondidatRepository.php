<?php

namespace App\Repository;

use App\Entity\Condidat;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Condidat>
 */
class CondidatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Condidat::class);
    }

    public function fetchCandidateCandidatures(): array
    {
        $candidateId = $this->getActiveCandidateId();
        $rows = $this->getConnection()->fetchAllAssociative(
            'SELECT
                c.ID_Condidature,
                c.ID_Offre,
                c.Lettre_Motivation,
                c.Portfolio,
                c.Lettre_Recomendation,
                c.Code_Type_Status,
                     cd.CV,
                     u.Nom_Utilisateur,
                     u.Email,
                o.Titre,
                o.Localisation,
                o.Work_Type,
                o.Nbr_Annee_Experience,
                tc.Description_Contrat,
                tsc.Description_Status_Condidature
             FROM condidature c
                 INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
                 INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
             WHERE c.ID_Condidat = :candidateId
             ORDER BY c.ID_Condidature DESC',
            ['candidateId' => $candidateId]
        );

        return array_map(fn (array $row): array => $this->mapCandidatureRow($row), $rows);
    }

    public function getCandidateDashboard(): array
    {
        $items = $this->fetchCandidateCandidatures();
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
                     cd.CV,
                     u.Nom_Utilisateur,
                     u.Email,
                o.Titre,
                o.Localisation,
                o.Work_Type,
                o.Nbr_Annee_Experience,
                tc.Description_Contrat,
                tsc.Description_Status_Condidature
             FROM condidature c
                 INNER JOIN condidat cd ON cd.ID_Condidat = c.ID_Condidat
                 INNER JOIN utilisateur u ON u.ID_UTILISATEUR = cd.ID_UTILISATEUR
             LEFT JOIN offre o ON o.ID_Offre = c.ID_Offre
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
             LEFT JOIN type_status_condidature tsc ON tsc.Code_Type_Status_Condidature = c.Code_Type_Status
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

    private function getConnection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function getActiveCandidateId(): int
    {
        $candidateId = $this->getConnection()->fetchOne('SELECT MIN(ID_Condidat) FROM condidat');
        if ($candidateId === false || $candidateId === null || $candidateId === '') {
            throw new \RuntimeException('Aucun profil candidat disponible.');
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
        $statusClass = match ($statusLabel) {
            'REVIEW' => 'status--review',
            'ACCEPTED' => 'status--meet',
            'REJECTED' => 'status--closed',
            default => 'status--sent',
        };

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
            'recommendationFileName' => (string) ($row['Lettre_Recomendation'] ?? ''),
        ];
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
