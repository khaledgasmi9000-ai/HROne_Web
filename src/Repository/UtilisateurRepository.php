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
     * @return Utilisateur[]
     */
    public function searchUsers(?string $search = null, ?int $profilId = null, ?bool $isActive = null): array
    {
        $qb = $this->createQueryBuilder('u')
            ->leftJoin('u.profil', 'p')
            ->leftJoin('u.entreprise', 'e')
            ->addSelect('p')
            ->addSelect('e')
            ->orderBy('u.ID_UTILISATEUR', 'DESC');

        if ($search !== null && $search !== '') {
            $qb
                ->andWhere('LOWER(u.Nom_Utilisateur) LIKE :term OR LOWER(u.Email) LIKE :term OR LOWER(u.CIN) LIKE :term')
                ->setParameter('term', '%' . mb_strtolower($search) . '%');
        }

        if ($profilId !== null) {
            $qb
                ->andWhere('p.ID_Profil = :profilId')
                ->setParameter('profilId', $profilId);
        }

        if ($isActive !== null) {
            $qb
                ->andWhere('u.isActive = :isActive')
                ->setParameter('isActive', $isActive);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * @return array{
     *     total:int,
     *     active:int,
     *     inactive:int,
     *     candidats:int,
     *     rh:int,
     *     employees:int
     * }
     */
    public function getUserStats(): array
    {
        $connection = $this->getEntityManager()->getConnection();

        $row = $connection->fetchAssociative(
            'SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN COALESCE(is_active, 1) = 1 THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN COALESCE(is_active, 1) = 0 THEN 1 ELSE 0 END) AS inactive_count,
                SUM(CASE WHEN ID_Profil = 4 THEN 1 ELSE 0 END) AS candidats_count,
                SUM(CASE WHEN ID_Profil = 2 THEN 1 ELSE 0 END) AS rh_count,
                SUM(CASE WHEN ID_Profil = 3 THEN 1 ELSE 0 END) AS employees_count
            FROM utilisateur'
        ) ?: [];

        return [
            'total' => (int) ($row['total'] ?? 0),
            'active' => (int) ($row['active_count'] ?? 0),
            'inactive' => (int) ($row['inactive_count'] ?? 0),
            'candidats' => (int) ($row['candidats_count'] ?? 0),
            'rh' => (int) ($row['rh_count'] ?? 0),
            'employees' => (int) ($row['employees_count'] ?? 0),
        ];
    }

    /**
     * @return array<int, array{id: int, label: string, email: string}>
     */
    public function findDemoEmployees(int $limit = 25): array
    {
        $rows = $this->createQueryBuilder('u')
            ->select('u.ID_UTILISATEUR AS id', 'u.Nom_Utilisateur AS label', 'u.Email AS email')
            ->orderBy('u.ID_UTILISATEUR', 'ASC')
            ->setMaxResults(max(1, $limit))
            ->getQuery()
            ->getArrayResult();

        return array_map(fn (array $row): array => $this->normalizeEmployeeRow($row), $rows);
    }

    /**
     * @return array{id: int, label: string, email: string}|null
     */
    public function findEmployeeByEmail(string $email): ?array
    {
        $normalizedEmail = mb_strtolower(trim($email));
        if ($normalizedEmail === '') {
            return null;
        }

        $row = $this->createQueryBuilder('u')
            ->select('u.ID_UTILISATEUR AS id', 'u.Nom_Utilisateur AS label', 'u.Email AS email')
            ->andWhere('LOWER(u.Email) = :email')
            ->setParameter('email', $normalizedEmail)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!is_array($row)) {
            return null;
        }

        return $this->normalizeEmployeeRow($row);
    }

    /**
     * @param int[] $ids
     *
     * @return array<int, array{id: int, label: string, email: string}>
     */
    public function findEmployeesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_map(static fn (mixed $id): int => (int) $id, $ids)));
        $ids = array_values(array_filter($ids, static fn (int $id): bool => $id > 0));

        if ($ids === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('u')
            ->select('u.ID_UTILISATEUR AS id', 'u.Nom_Utilisateur AS label', 'u.Email AS email')
            ->andWhere('u.ID_UTILISATEUR IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        $employeesById = [];

        foreach ($rows as $row) {
            $employee = $this->normalizeEmployeeRow($row);
            $employeesById[$employee['id']] = $employee;
        }

        return $employeesById;
    }

    /**
     * @param array{id?: mixed, label?: mixed, email?: mixed} $row
     *
     * @return array{id: int, label: string, email: string}
     */
    private function normalizeEmployeeRow(array $row): array
    {
        $id = (int) ($row['id'] ?? 0);
        $label = trim((string) ($row['label'] ?? ''));
        $email = trim((string) ($row['email'] ?? ''));

        if ($label === '') {
            $label = $id > 0 ? sprintf('Utilisateur #%d', $id) : 'Utilisateur inconnu';
        }

        return [
            'id' => $id,
            'label' => $label,
            'email' => $email !== '' ? $email : 'Email indisponible',
        ];
    }
}
