<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * @return array<int, array{id: int, label: string, email: string}>
     */
    public function findDemoEmployees(): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT u.ID_UTILISATEUR AS id, u.Nom_Utilisateur AS label, COALESCE(u.Email, '') AS email
             FROM utilisateur u
             LEFT JOIN employee e ON e.ID_UTILISATEUR = u.ID_UTILISATEUR
             ORDER BY e.ID_Employe ASC"
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => (string) $row['label'],
                'email' => (string) $row['email'],
            ],
            $rows
        );
    }

    public function emailExists(string $email): bool
    {
        return $this->findEmployeeByEmail($email) !== null;
    }

    /**
     * @return array{id: int, label: string, email: string}|null
     */
   public function findEmployeeByEmail(string $email): ?array
{
    $row = $this->getEntityManager()->getConnection()->fetchAssociative(
        "SELECT u.ID_UTILISATEUR AS id, u.Nom_Utilisateur AS label, COALESCE(u.Email, '') AS email
         FROM utilisateur u
         WHERE LOWER(COALESCE(u.Email, '')) = LOWER(:email)
         LIMIT 1",
        ['email' => $email]
    );

    if (!is_array($row)) {
        return null;
    }

    return [
        'id' => (int) $row['id'],
        'label' => (string) $row['label'],
        'email' => (string) $row['email'],
    ];
}

    /**
     * @param list<int> $participantIds
     *
     * @return array<int, array{id: int, label: string, email: string}>
     */
    public function findEmployeesByIds(array $participantIds): array
    {
        if ($participantIds === []) {
            return [];
        }

        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            "SELECT u.ID_UTILISATEUR AS id, u.Nom_Utilisateur AS label, COALESCE(u.Email, '') AS email
             FROM utilisateur u
             WHERE u.ID_UTILISATEUR IN (:ids)",
            ['ids' => array_map('intval', $participantIds)],
            ['ids' => \Doctrine\DBAL\ArrayParameterType::INTEGER]
        );

        $mapped = [];

        foreach ($rows as $row) {
            $mapped[(int) $row['id']] = [
                'id' => (int) $row['id'],
                'label' => (string) $row['label'],
                'email' => (string) $row['email'],
            ];
        }

        return $mapped;
    }
}
