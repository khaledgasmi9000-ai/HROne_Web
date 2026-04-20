<?php

namespace App\Repository;

use App\Entity\Comment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 */
class CommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    /**
     * Count all comments
     */
    public function countAll(): int
    {
        return (int)$this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Count comments for a specific post
     */
    public function countByPostId(int $postId): int
    {
        return (int)$this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->where('c.post_id = :postId')
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get count of comments for multiple posts
     *
     * @param int[] $postIds
     * @return array<int, int>
     */
    public function getCountsByPostIds(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('c')
            ->select('c.post_id, COUNT(c.id) as cnt')
            ->where('c.post_id IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->groupBy('c.post_id')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($postIds as $postId) {
            $counts[$postId] = 0;
        }

        foreach ($result as $row) {
            $counts[(int)$row['post_id']] = (int)$row['cnt'];
        }

        return $counts;
    }

    /**
     * Get comments by post
     *
     * @return Comment[]
     */
    public function findByPostId(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.post_id = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('c.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get comments by user
     *
     * @return Comment[]
     */
    public function findByUserId(int $userId): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.user_id = :userId')
            ->setParameter('userId', $userId)
            ->orderBy('c.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }
}
