<?php

namespace App\Repository;

use App\Entity\PostVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PostVote>
 */
class PostVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PostVote::class);
    }

    /**
     * Get vote counts for a single post
     *
     * @return array{up: int, down: int}
     */
    public function countVotesByPostId(int $postId): array
    {
        $result = $this->createQueryBuilder('pv')
            ->select('pv.vote_type, COUNT(pv.id) as cnt')
            ->where('pv.post = :postId')
            ->setParameter('postId', $postId)
            ->groupBy('pv.vote_type')
            ->getQuery()
            ->getResult();

        $counts = ['up' => 0, 'down' => 0];
        foreach ($result as $row) {
            $voteType = $row['vote_type'];
            $count = (int)$row['cnt'];
            if ($voteType === 'up' || $voteType === 'down') {
                $counts[$voteType] = $count;
            }
        }

        return $counts;
    }

    /**
     * Get vote counts for multiple posts
     *
     * @param int[] $postIds
     * @return array<int, array{up: int, down: int}>
     */
    public function getVoteCountsByPostIds(array $postIds): array
    {
        if (empty($postIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('pv')
            ->select('IDENTITY(pv.post) as postId, pv.vote_type, COUNT(pv.id) as cnt')
            ->where('IDENTITY(pv.post) IN (:postIds)')
            ->setParameter('postIds', $postIds)
            ->groupBy('postId, pv.vote_type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($postIds as $postId) {
            $counts[$postId] = ['up' => 0, 'down' => 0];
        }

        foreach ($result as $row) {
            $postId = (int)$row['postId'];
            $voteType = $row['vote_type'];
            $count = (int)$row['cnt'];
            if ($voteType === 'up' || $voteType === 'down') {
                $counts[$postId][$voteType] = $count;
            }
        }

        return $counts;
    }

    /**
     * Get all votes for a specific post
     *
     * @return PostVote[]
     */
    public function findByPost(int $postId): array
    {
        return $this->createQueryBuilder('pv')
            ->where('pv.post = :postId')
            ->setParameter('postId', $postId)
            ->orderBy('pv.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a vote by user and post
     */
    public function findByUserAndPost(int $userId, int $postId): ?PostVote
    {
        return $this->createQueryBuilder('pv')
            ->where('pv.user = :userId')
            ->andWhere('pv.post = :postId')
            ->setParameter('userId', $userId)
            ->setParameter('postId', $postId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
