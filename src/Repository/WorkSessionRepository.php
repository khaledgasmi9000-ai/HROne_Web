<?php

namespace App\Repository;

use App\Entity\WorkSession;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WorkSession>
 */
class WorkSessionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WorkSession::class);
    }

    public function getTimeStats(): array
    {
        $result = $this->createQueryBuilder('ws')
            ->select(
                'SUM(ws.activeTime) as totalActive',
                'SUM(ws.sessionDuration) as totalSession'
            )
            ->getQuery()
            ->getSingleResult();

        return [
            'totalActive' => (float) ($result['totalActive'] ?? 0),
            'totalSession' => (float) ($result['totalSession'] ?? 0),
        ];
    }

    public function getProductivityBreakdown(): array
    {
        $result = $this->createQueryBuilder('ws')
            ->select(
                'SUM(ws.activeTime) as active',
                'SUM(ws.afkTime) as afk',
                'SUM(ws.unknownTime) as unknown'
            )
            ->getQuery()
            ->getSingleResult();

        return [
            'active' => (float) ($result['active'] ?? 0),
            'afk' => (float) ($result['afk'] ?? 0),
            'unknown' => (float) ($result['unknown'] ?? 0),
        ];
    }
}
