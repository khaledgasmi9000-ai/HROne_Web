<?php

namespace App\Repository;

use App\Entity\Activite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Activite>
 */
class ActiviteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    public function getNextId(): int
    {
        $maxId = $this->createQueryBuilder('a')
            ->select('MAX(a.ID_Activite)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxId ?: 0) + 1;
    }
}
