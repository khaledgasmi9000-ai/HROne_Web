<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    /**
     * Count active posts
     */
    public function countActive(): int
    {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->where('p.is_active = true OR p.is_active IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count all posts
     */
    public function countAll(): int
    {
        return (int)$this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get recent active posts
     *
     * @return Post[]
     */
    public function findRecentActive(int $limit = 4): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.is_active = true OR p.is_active IS NULL')
            ->orderBy('p.created_at', 'DESC')
            ->addOrderBy('p.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get posts by user
     *
     * @return Post[]
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('p.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
