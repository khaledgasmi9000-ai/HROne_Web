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
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) AS active_count,
                SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) AS inactive_count,
                SUM(CASE WHEN ID_Profil = 1 THEN 1 ELSE 0 END) AS candidats_count,
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
}
