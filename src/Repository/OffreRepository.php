<?php

namespace App\Repository;

use App\Entity\Offre;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offre>
 */
class OffreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offre::class);
    }

    public function fetchOffersForManagement(): array
    {
        $rows = $this->getConnection()->fetchAllAssociative(
            'SELECT
                o.ID_Offre,
                o.Titre,
                o.Work_Type,
                o.Nbr_Annee_Experience,
                o.Min_Salaire,
                o.Max_Salaire,
                o.Description,
                     o.Localisation,
                o.Code_Type_Contrat,
                o.Code_Type_Niveau_Etude,
                tc.Description_Contrat,
                     COUNT(c.ID_Condidature) AS Applications_Count,
                oe.AAAA AS Exp_AAAA,
                oe.MM AS Exp_MM,
                oe.JJ AS Exp_JJ
             FROM offre o
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
                 LEFT JOIN condidature c ON c.ID_Offre = o.ID_Offre
             LEFT JOIN ordre oe ON oe.Num_Ordre = o.Num_Ordre_Expiration
                 GROUP BY
                     o.ID_Offre,
                     o.Titre,
                     o.Work_Type,
                     o.Nbr_Annee_Experience,
                     o.Min_Salaire,
                     o.Max_Salaire,
                     o.Description,
                     o.Localisation,
                     o.Code_Type_Contrat,
                     o.Code_Type_Niveau_Etude,
                     tc.Description_Contrat,
                     oe.AAAA,
                     oe.MM,
                     oe.JJ
             ORDER BY o.ID_Offre DESC'
        );

        return array_map(fn (array $row): array => $this->normalizeOfferRow($row, false), $rows);
    }

    public function fetchOffersForCandidate(string $search = '', string $location = ''): array
    {
        $search = mb_strtolower(trim($search));
        $location = mb_strtolower(trim($location));

        return array_values(array_filter(
            $this->fetchOffersForManagement(),
            static function (array $offer) use ($search, $location): bool {
                if (($offer['statusClass'] ?? '') !== 'status-live') {
                    return false;
                }

                $title = mb_strtolower((string) ($offer['title'] ?? ''));
                $description = mb_strtolower((string) ($offer['description'] ?? ''));
                $workType = mb_strtolower((string) ($offer['workType'] ?? ''));
                $experience = mb_strtolower((string) ($offer['experience'] ?? ''));
                $offerLocation = mb_strtolower((string) ($offer['location'] ?? ''));

                $matchesSearch = $search === ''
                    || str_contains($title, $search)
                    || str_contains($description, $search)
                    || str_contains($workType, $search)
                    || str_contains($experience, $search);

                $matchesLocation = $location === '' || str_contains($offerLocation, $location);

                return $matchesSearch && $matchesLocation;
            }
        ));
    }

    public function fetchOfferForManagement(int $id): ?array
    {
        $connection = $this->getConnection();
        $row = $connection->fetchAssociative(
            'SELECT
                o.ID_Offre,
                o.Titre,
                o.Work_Type,
                o.Nbr_Annee_Experience,
                o.Min_Salaire,
                o.Max_Salaire,
                o.Description,
                     o.Localisation,
                o.Code_Type_Contrat,
                o.Code_Type_Niveau_Etude,
                tc.Description_Contrat,
                oe.AAAA AS Exp_AAAA,
                oe.MM AS Exp_MM,
                oe.JJ AS Exp_JJ
             FROM offre o
             LEFT JOIN type_contrat tc ON tc.Code_Type_Contrat = o.Code_Type_Contrat
             LEFT JOIN ordre oe ON oe.Num_Ordre = o.Num_Ordre_Expiration
             WHERE o.ID_Offre = :id',
            ['id' => $id]
        );

        if ($row === false) {
            return null;
        }

        $offer = $this->normalizeOfferRow($row, true);
        $offer['skills'] = $this->fetchOfferDetailCodes('detail_offre_competence', 'Code_Type_Competence', $id);
        $offer['languages'] = $this->fetchOfferDetailCodes('detail_offre_langue', 'Code_Type_Langue', $id);
        $offer['background'] = $this->fetchOfferDetailCodes('detail_offre_background', 'Code_Type_Background_Etude', $id);

        return $offer;
    }

    public function validateOfferPayload(array $payload): array
    {
        $errors = [];

        $title = trim((string) ($payload['title'] ?? ''));
        $location = trim((string) ($payload['location'] ?? ''));
        $workType = trim((string) ($payload['workType'] ?? ''));
        $description = trim((string) ($payload['description'] ?? ''));

        $contractCode = $this->sanitizeNullableInt($payload['contractCode'] ?? null);
        $educationCode = $this->sanitizeNullableInt($payload['educationCode'] ?? null);
        $minSalary = $this->sanitizeNullableInt($payload['minSalary'] ?? null);
        $maxSalary = $this->sanitizeNullableInt($payload['maxSalary'] ?? null);
        $experienceYears = $this->parseExperienceYears($payload['experience'] ?? null);
        $expirationDate = $this->parseDateOnly($payload['expirationDate'] ?? null);

        $skillCodes = $this->parseCodeList($payload['skills'] ?? []);
        $languageCodes = $this->parseCodeList($payload['languages'] ?? []);
        $backgroundCodes = $this->parseCodeList($payload['background'] ?? []);

        if ($title === '') {
            $errors['title'] = 'Le titre du poste est obligatoire.';
        }
        if ($location === '') {
            $errors['location'] = 'Le lieu est obligatoire.';
        }

        $allowedWorkTypes = ['En ligne', 'Sur site', 'Hybride'];
        if (!in_array($workType, $allowedWorkTypes, true)) {
            $errors['workType'] = 'Le type de travail est invalide.';
        }

        if ($contractCode === null) {
            $errors['contractCode'] = 'Le type de contrat est obligatoire.';
        } elseif (!$this->existsByCode('type_contrat', 'Code_Type_Contrat', $contractCode)) {
            $errors['contractCode'] = 'Le type de contrat selectionne est introuvable.';
        }

        if ($educationCode === null) {
            $errors['educationCode'] = "Le niveau d'etude est obligatoire.";
        } elseif (!$this->existsByCode('type_niveau_etude', 'Code_Type_Niveau_Etude', $educationCode)) {
            $errors['educationCode'] = "Le niveau d'etude selectionne est introuvable.";
        }

        if ($experienceYears === null || $experienceYears < 0) {
            $errors['experience'] = "L'experience est obligatoire et doit etre valide.";
        }

        if ($minSalary === null || $minSalary < 0) {
            $errors['minSalary'] = 'Le salaire minimum est obligatoire et doit etre positif.';
        }

        if ($maxSalary === null || $maxSalary < 0) {
            $errors['maxSalary'] = 'Le salaire maximum est obligatoire et doit etre positif.';
        }

        if ($minSalary !== null && $maxSalary !== null && $minSalary >= $maxSalary) {
            $errors['salaryRange'] = 'Le salaire minimum doit etre strictement inferieur au salaire maximum.';
        }

        if ($expirationDate === null) {
            $errors['expirationDate'] = "La date d'expiration est obligatoire et invalide.";
        } else {
            $today = new DateTimeImmutable('today');
            if ($expirationDate <= $today) {
                $errors['expirationDate'] = "La date d'expiration doit etre posterieure a la date du jour.";
            }
        }

        if ($description === '') {
            $errors['description'] = 'La description est obligatoire.';
        }

        if (count($skillCodes) < 2) {
            $errors['skills'] = 'Selectionnez au moins 2 competences.';
        } elseif (!$this->allCodesExist('type_competence', 'Code_Type_Competence', $skillCodes)) {
            $errors['skills'] = 'Au moins une competence selectionnee est invalide.';
        }

        if (count($languageCodes) < 1) {
            $errors['languages'] = 'Selectionnez au moins 1 langue.';
        } elseif (!$this->allCodesExist('type_langue', 'Code_Type_Langue', $languageCodes)) {
            $errors['languages'] = 'Au moins une langue selectionnee est invalide.';
        }

        if (count($backgroundCodes) < 1) {
            $errors['background'] = "Selectionnez au moins 1 background d'etude.";
        } elseif (!$this->allCodesExist('type_background_etude', 'Code_Type_Background_Etude', $backgroundCodes)) {
            $errors['background'] = 'Au moins un background selectionne est invalide.';
        }

        return [
            'errors' => $errors,
            'data' => [
                'title' => $title,
                'location' => $location,
                'workType' => $workType,
                'description' => $description,
                'contractCode' => $contractCode,
                'educationCode' => $educationCode,
                'experienceYears' => $experienceYears,
                'minSalary' => $minSalary,
                'maxSalary' => $maxSalary,
                'expirationDate' => $expirationDate,
                'skillCodes' => $skillCodes,
                'languageCodes' => $languageCodes,
                'backgroundCodes' => $backgroundCodes,
            ],
        ];
    }

    public function createOfferFromData(array $data): array
    {
        $connection = $this->getConnection();
        $entrepriseId = $this->getFirstEntrepriseId();
        if ($entrepriseId === null) {
            throw new \RuntimeException("Impossible d'ajouter une offre: aucune entreprise disponible.");
        }

        $connection->beginTransaction();
        try {
            $creationOrderId = $this->findOrCreateOrdre(new DateTimeImmutable());
            $expirationOrderId = $this->findOrCreateOrdre($data['expirationDate']->setTime(23, 59, 59));

            $connection->insert('offre', [
                'Titre' => $data['title'],
                'Description' => $data['description'],
                'Localisation' => $data['location'],
                'ID_Entreprise' => $entrepriseId,
                'Work_Type' => $data['workType'],
                'Code_Type_Contrat' => $data['contractCode'],
                'Nbr_Annee_Experience' => $data['experienceYears'],
                'Code_Type_Niveau_Etude' => $data['educationCode'],
                'Min_Salaire' => $data['minSalary'],
                'Max_Salaire' => $data['maxSalary'],
                'Num_Ordre_Creation' => $creationOrderId,
                'Num_Ordre_Expiration' => $expirationOrderId,
            ]);

            $id = (int) $connection->lastInsertId();
            $this->syncOfferDetails($id, $data['skillCodes'], $data['languageCodes'], $data['backgroundCodes']);
            $connection->commit();

            return $this->fetchOfferForManagement($id) ?? [];
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    public function updateOfferFromData(int $id, array $data): ?array
    {
        $connection = $this->getConnection();
        $existing = $this->fetchOfferForManagement($id);
        if ($existing === null) {
            return null;
        }

        $creationOrderId = $connection->fetchOne('SELECT Num_Ordre_Creation FROM offre WHERE ID_Offre = :id', ['id' => $id]);
        if ($creationOrderId === false || $creationOrderId === null || $creationOrderId === '') {
            throw new \RuntimeException('Ordre de creation introuvable pour cette offre.');
        }

        $connection->beginTransaction();
        try {
            $expirationOrderId = $this->findOrCreateOrdre($data['expirationDate']->setTime(23, 59, 59));

            $connection->update('offre', [
                'Titre' => $data['title'],
                'Description' => $data['description'],
                'Localisation' => $data['location'],
                'Work_Type' => $data['workType'],
                'Nbr_Annee_Experience' => $data['experienceYears'],
                'Min_Salaire' => $data['minSalary'],
                'Max_Salaire' => $data['maxSalary'],
                'Code_Type_Contrat' => $data['contractCode'],
                'Code_Type_Niveau_Etude' => $data['educationCode'],
                'Num_Ordre_Creation' => (int) $creationOrderId,
                'Num_Ordre_Expiration' => $expirationOrderId,
            ], ['ID_Offre' => $id]);

            $this->syncOfferDetails($id, $data['skillCodes'], $data['languageCodes'], $data['backgroundCodes']);
            $connection->commit();

            return $this->fetchOfferForManagement($id);
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    public function deleteOfferWithDependencies(int $id): bool
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
        try {
            $connection->delete('detail_offre_competence', ['ID_Offre' => $id]);
            $connection->delete('detail_offre_langue', ['ID_Offre' => $id]);
            $connection->delete('detail_offre_background', ['ID_Offre' => $id]);
            $connection->delete('condidature', ['ID_Offre' => $id]);

            $deleted = $connection->delete('offre', ['ID_Offre' => $id]);
            if ($deleted === 0) {
                $connection->rollBack();
                return false;
            }

            $connection->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($connection->isTransactionActive()) {
                $connection->rollBack();
            }
            throw $exception;
        }
    }

    private function getConnection(): Connection
    {
        return $this->getEntityManager()->getConnection();
    }

    private function normalizeOfferRow(array $row, bool $includeEmptyDetails = false): array
    {
        $experienceYears = isset($row['Nbr_Annee_Experience']) ? (int) $row['Nbr_Annee_Experience'] : null;
        $contractCode = isset($row['Code_Type_Contrat']) ? (int) $row['Code_Type_Contrat'] : null;
        $educationCode = isset($row['Code_Type_Niveau_Etude']) ? (int) $row['Code_Type_Niveau_Etude'] : null;

        $expYear = isset($row['Exp_AAAA']) ? (int) $row['Exp_AAAA'] : null;
        $expMonth = isset($row['Exp_MM']) ? (int) $row['Exp_MM'] : null;
        $expDay = isset($row['Exp_JJ']) ? (int) $row['Exp_JJ'] : null;
        $expirationDate = null;
        if ($expYear !== null && $expMonth !== null && $expDay !== null && checkdate($expMonth, $expDay, $expYear)) {
            $expirationDate = sprintf('%04d-%02d-%02d', $expYear, $expMonth, $expDay);
        }

        $status = 'En ligne';
        $statusClass = 'status-live';
        if ($expirationDate !== null) {
            $today = new DateTimeImmutable('today');
            $expDate = DateTimeImmutable::createFromFormat('Y-m-d', $expirationDate);
            if ($expDate instanceof DateTimeImmutable && $expDate < $today) {
                $status = 'Expiree';
                $statusClass = 'status-draft';
            }
        }

        $offer = [
            'id' => (int) $row['ID_Offre'],
            'title' => (string) ($row['Titre'] ?? ''),
            'location' => (string) (($row['Localisation'] ?? '') !== '' ? $row['Localisation'] : '-'),
            'contractCode' => $contractCode,
            'contract' => (string) (($row['Description_Contrat'] ?? '') !== '' ? $row['Description_Contrat'] : 'Non defini'),
            'workType' => (string) ($row['Work_Type'] ?? 'En ligne'),
            'experienceYears' => $experienceYears,
            'experience' => $experienceYears !== null ? sprintf('%d ans', $experienceYears) : 'Non precisee',
            'minSalary' => isset($row['Min_Salaire']) ? (int) $row['Min_Salaire'] : null,
            'maxSalary' => isset($row['Max_Salaire']) ? (int) $row['Max_Salaire'] : null,
            'educationCode' => $educationCode,
            'description' => (string) ($row['Description'] ?? ''),
            'expirationDate' => $expirationDate,
            'status' => $status,
            'statusClass' => $statusClass,
            'applicationsCount' => isset($row['Applications_Count']) ? (int) $row['Applications_Count'] : 0,
        ];

        if ($includeEmptyDetails) {
            $offer['skills'] = [];
            $offer['languages'] = [];
            $offer['background'] = [];
        }

        return $offer;
    }

    private function parseExperienceYears(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        if (is_numeric($value)) {
            return (int) $value;
        }

        if (!is_string($value)) {
            return null;
        }

        if (preg_match('/\d+/', $value, $matches) !== 1) {
            return null;
        }

        return (int) $matches[0];
    }

    private function sanitizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function parseCodeList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        $codes = [];
        foreach ($value as $entry) {
            if (is_numeric($entry)) {
                $intValue = (int) $entry;
                if ($intValue > 0) {
                    $codes[] = $intValue;
                }
            }
        }

        return array_values(array_unique($codes));
    }

    private function parseDateOnly(mixed $value): ?DateTimeImmutable
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('Y-m-d', $value);
        if (!$date instanceof DateTimeImmutable) {
            return null;
        }

        return $date->setTime(0, 0, 0);
    }

    private function existsByCode(string $table, string $column, int $value): bool
    {
        $count = $this->getConnection()->fetchOne(
            sprintf('SELECT COUNT(*) FROM %s WHERE %s = :value', $table, $column),
            ['value' => $value]
        );

        return ((int) $count) > 0;
    }

    private function allCodesExist(string $table, string $column, array $codes): bool
    {
        if ($codes === []) {
            return true;
        }

        $count = $this->getConnection()->createQueryBuilder()
            ->select('COUNT(*)')
            ->from($table)
            ->where(sprintf('%s IN (:codes)', $column))
            ->setParameter('codes', $codes, ArrayParameterType::INTEGER)
            ->executeQuery()
            ->fetchOne();

        return (int) $count === count($codes);
    }

    private function findOrCreateOrdre(DateTimeInterface $dateTime): int
    {
        $connection = $this->getConnection();
        $params = [
            'AAAA' => (int) $dateTime->format('Y'),
            'MM' => (int) $dateTime->format('m'),
            'JJ' => (int) $dateTime->format('d'),
            'HH' => (int) $dateTime->format('H'),
            'MN' => (int) $dateTime->format('i'),
            'SS' => (int) $dateTime->format('s'),
        ];

        $existing = $connection->fetchOne(
            'SELECT Num_Ordre FROM ordre WHERE AAAA = :AAAA AND MM = :MM AND JJ = :JJ AND HH = :HH AND MN = :MN AND SS = :SS LIMIT 1',
            $params
        );

        if ($existing !== false && $existing !== null && $existing !== '') {
            return (int) $existing;
        }

        $candidate = max(1, (int) $dateTime->format('U'));
        $maxInt = 2147483647;

        for ($offset = 0; $offset < 100000; $offset++) {
            $numOrdre = $candidate + $offset;
            if ($numOrdre > $maxInt) {
                break;
            }

            $inserted = $connection->executeStatement(
                'INSERT IGNORE INTO ordre (Num_Ordre, AAAA, MM, JJ, HH, MN, SS)
                 VALUES (:Num_Ordre, :AAAA, :MM, :JJ, :HH, :MN, :SS)',
                [
                    'Num_Ordre' => $numOrdre,
                    ...$params,
                ]
            );

            if ($inserted > 0) {
                return $numOrdre;
            }
        }

        throw new \RuntimeException('Impossible de generer une cle Num_Ordre valide.');
    }

    private function syncOfferDetails(int $offerId, array $skills, array $languages, array $backgrounds): void
    {
        $connection = $this->getConnection();
        $connection->delete('detail_offre_competence', ['ID_Offre' => $offerId]);
        $connection->delete('detail_offre_langue', ['ID_Offre' => $offerId]);
        $connection->delete('detail_offre_background', ['ID_Offre' => $offerId]);

        foreach ($skills as $skillCode) {
            $connection->insert('detail_offre_competence', [
                'ID_Offre' => $offerId,
                'Code_Type_Competence' => $skillCode,
            ]);
        }

        foreach ($languages as $languageCode) {
            $connection->insert('detail_offre_langue', [
                'ID_Offre' => $offerId,
                'Code_Type_Langue' => $languageCode,
            ]);
        }

        foreach ($backgrounds as $backgroundCode) {
            $connection->insert('detail_offre_background', [
                'ID_Offre' => $offerId,
                'Code_Type_Background_Etude' => $backgroundCode,
            ]);
        }
    }

    private function fetchOfferDetailCodes(string $table, string $column, int $offerId): array
    {
        $values = $this->getConnection()->fetchFirstColumn(
            sprintf('SELECT %s FROM %s WHERE ID_Offre = :id ORDER BY %s ASC', $column, $table, $column),
            ['id' => $offerId]
        );

        return array_values(array_map(static fn (mixed $value): int => (int) $value, $values));
    }

    private function getFirstEntrepriseId(): ?int
    {
        $value = $this->getConnection()->fetchOne('SELECT MIN(ID_Entreprise) FROM entreprise');

        if ($value === false || $value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

//    /**
//     * @return Offre[] Returns an array of Offre objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('o.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Offre
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
