<?php

namespace App\Repository;

use App\Entity\Ordre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ordre>
 */
class OrdreRepository extends ServiceEntityRepository
{
    private const MAX_INT_32 = 2147483647;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ordre::class);
    }

    public function getNextOrdreNumber(): int
    {
        $maxValue = $this->createQueryBuilder('o')
            ->select('MAX(o.Num_Ordre)')
            ->andWhere('o.Num_Ordre < :maxInt32')
            ->setParameter('maxInt32', self::MAX_INT_32)
            ->getQuery()
            ->getSingleScalarResult();

        $nextValue = ((int) ($maxValue ?? 0)) + 1;

        if ($nextValue >= self::MAX_INT_32) {
            throw new \RuntimeException('Impossible de generer un nouveau numero d ordre valide.');
        }

        return $nextValue;
    }

//    /**
//     * @return Ordre[] Returns an array of Ordre objects
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

//    public function findOneBySomeField($value): ?Ordre
//    {
//        return $this->createQueryBuilder('o')
//            ->andWhere('o.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
