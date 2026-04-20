<?php

namespace App\Repository;

use App\Entity\CommentVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CommentVote>
 */
class CommentVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CommentVote::class);
    }

    /**
     * Get vote counts for a single comment
     *
     * @return array{up: int, down: int}
     */
    public function countVotesByCommentId(int $commentId): array
    {
        $result = $this->createQueryBuilder('cv')
            ->select('cv.vote_type, COUNT(cv.id) as cnt')
            ->where('cv.comment = :commentId')
            ->setParameter('commentId', $commentId)
            ->groupBy('cv.vote_type')
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
     * Get vote counts for multiple comments
     *
     * @param int[] $commentIds
     * @return array<int, array{up: int, down: int}>
     */
    public function getVoteCountsByCommentIds(array $commentIds): array
    {
        if (empty($commentIds)) {
            return [];
        }

        $result = $this->createQueryBuilder('cv')
            ->select('IDENTITY(cv.comment) as commentId, cv.vote_type, COUNT(cv.id) as cnt')
            ->where('IDENTITY(cv.comment) IN (:commentIds)')
            ->setParameter('commentIds', $commentIds)
            ->groupBy('commentId, cv.vote_type')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($commentIds as $commentId) {
            $counts[$commentId] = ['up' => 0, 'down' => 0];
        }

        foreach ($result as $row) {
            $commentId = (int)$row['commentId'];
            $voteType = $row['vote_type'];
            $count = (int)$row['cnt'];
            if ($voteType === 'up' || $voteType === 'down') {
                $counts[$commentId][$voteType] = $count;
            }
        }

        return $counts;
    }

    /**
     * Get all votes for a specific comment
     *
     * @return CommentVote[]
     */
    public function findByComment(int $commentId): array
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.comment = :commentId')
            ->setParameter('commentId', $commentId)
            ->orderBy('cv.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find a vote by user and comment
     */
    public function findByUserAndComment(int $userId, int $commentId): ?CommentVote
    {
        return $this->createQueryBuilder('cv')
            ->where('cv.user = :userId')
            ->andWhere('cv.comment = :commentId')
            ->setParameter('userId', $userId)
            ->setParameter('commentId', $commentId)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
