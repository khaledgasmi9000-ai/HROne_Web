<?php

namespace App\Repository;

use App\Entity\PostFavorite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostFavorite>
 */
class PostFavoriteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostFavorite::class);
    }

    /**
     * Get favorites count for a specific post
     */
    public function countByPostId(int $postId): int
    {
        return (int)$this->createQueryBuilder('pf')
            ->select('COUNT(pf.post)')
            ->where('pf.post = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get favorites count for multiple posts
     *
     * @param int[] $postIds
     * @return array<int, int>
     */
    public function getCountsByPostIds(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('pf')
            ->select('pf.post, COUNT(pf.post) as cnt')
            ->where('pf.post IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->groupBy('pf.post')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($postIds as $postId) {
            $counts[$postId] = 0;
        }

        foreach ($result as $row) {
            $postId = $row['post']->getId();
            $counts[$postId] = (int)$row['cnt'];
        }

        return $counts;
    }

    /**
     * Count user's total favorites
     */
    public function countByUserId(int $userId): int
    {
        return (int)$this->createQueryBuilder('pf')
            ->select('COUNT(pf.user)')
            ->where('pf.user = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get user's favorite posts
     *
     * @return PostFavorite[]
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('pf')
            ->where('pf.user = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('pf.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Check if a post is favorited by a user
     */
    public function isFavorited(int $postId, int $userId): bool
    {
        return $this->createQueryBuilder('pf')
            ->select('COUNT(pf.post)')
            ->where('pf.post = :postId')
            ->andWhere('pf.user = :userId')
            ->setParameter('postId', $postId)
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getSingleScalarResult() > 0;
    }
}
