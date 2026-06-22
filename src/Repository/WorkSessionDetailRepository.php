<?php

namespace App\Repository;

use App\Entity\WorkSessionDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkSessionDetail>
 */
class WorkSessionDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkSessionDetail::class);
    }

    public function getGlobalToolUsage(): array
    {
        return $this->createQueryBuilder('d')
            ->select('LOWER(d.app) as app, SUM(d.duration) as totalDuration')
            ->groupBy('app')
            ->orderBy('totalDuration', 'DESC')
            ->getQuery()
            ->getArrayResult();
    }

}
