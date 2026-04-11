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
        $rows = $this->createQueryBuilder('u')
            ->select('u.ID_UTILISATEUR AS id', 'u.Nom_Utilisateur AS label', 'u.Email AS email')
            ->leftJoin('u.employees', 'e')
            ->orderBy('e.ID_Employe', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return array_map([$this, 'mapEmployeeRow'], $rows);
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
        $row = $this->createQueryBuilder('u')
            ->select('u.ID_UTILISATEUR AS id', 'u.Nom_Utilisateur AS label', 'u.Email AS email')
            ->andWhere('LOWER(COALESCE(u.Email, \'\')) = :email')
            ->setParameter('email', mb_strtolower($email))
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row)) {
            return null;
        }

        return $this->mapEmployeeRow($row);
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

        $rows = $this->createQueryBuilder('u')
            ->select('u.ID_UTILISATEUR AS id', 'u.Nom_Utilisateur AS label', 'u.Email AS email')
            ->andWhere('u.ID_UTILISATEUR IN (:ids)')
            ->setParameter('ids', array_map('intval', $participantIds))
            ->getQuery()
            ->getArrayResult();

        $mapped = [];

        foreach ($rows as $row) {
            $normalized = $this->mapEmployeeRow($row);
            $mapped[$normalized['id']] = $normalized;
        }

        return $mapped;
    }

    /**
     * @param array{id: mixed, label: mixed, email: mixed} $row
     *
     * @return array{id: int, label: string, email: string}
     */
    private function mapEmployeeRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'label' => (string) $row['label'],
            'email' => (string) ($row['email'] ?? ''),
        ];
    }
}
