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

    public function existsId(int $id): bool
    {
        if ($id < 1) {
            return false;
        }

        $n = $this->getEntityManager()->getConnection()->fetchOne(
            'SELECT 1 FROM utilisateur WHERE ID_UTILISATEUR = ? LIMIT 1',
            [$id]
        );

        return $n !== false && $n !== null;
    }

    /**
     * @param int[] $ids
     * @return array<int, string> id => Nom_Utilisateur
     */
    public function getDisplayNamesByIds(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids)));
        if ($ids === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('u')
            ->select('u.ID_UTILISATEUR', 'u.Nom_Utilisateur')
            ->where('u.ID_UTILISATEUR IN (:ids)')
            ->setParameter('ids', $ids)
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['ID_UTILISATEUR']] = (string) $row['Nom_Utilisateur'];
        }

        return $out;
    }

//    /**
//     * @return Utilisateur[] Returns an array of Utilisateur objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Utilisateur
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
