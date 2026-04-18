<?php

namespace App\Repository;

use App\Entity\CommunityChatMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommunityChatMessage>
 */
class CommunityChatMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommunityChatMessage::class);
    }

    /**
     * Get recent active chat messages
     *
     * @return CommunityChatMessage[]
     */
    public function findRecentActive(int $limit = 40): array
    {
        return $this->createQueryBuilder('m')
            ->where('m.is_active = true')
            ->orderBy('m.created_at', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a message by ID
     */
    public function findOneById(int $id): ?CommunityChatMessage
    {
        return $this->createQueryBuilder('m')
            ->where('m.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
