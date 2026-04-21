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

    public function getOrCreateOrdre(int $numOrdre): Ordre
    {

        $em = $this->getEntityManager();

        $ordre = $this->find($numOrdre);

        if (!$ordre) {
            $ordre = new Ordre();
            $ordre->setNum_Ordre($numOrdre);
            $em->persist($ordre);
            // ?? no flush here (important)
        }

        return $ordre;
    }
    
}
