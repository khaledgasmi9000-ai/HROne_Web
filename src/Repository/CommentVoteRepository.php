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

    public function findOneByCommentAndUser(int $commentId, int $userId): ?CommentVote
    {
        return $this->findOneBy(['comment_id' => $commentId, 'user_id' => $userId]);
    }

    /**
     * @param int[] $commentIds
     * @return array<int, array{up: int, down: int}>
     */
    public function sumVotesByCommentIds(array $commentIds): array
    {
        $commentIds = array_values(array_unique(array_filter($commentIds)));
        if ($commentIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('v')
            ->select('v.comment_id AS cid', 'v.vote_type AS vt', 'COUNT(v.id) AS cnt')
            ->andWhere('v.comment_id IN (:cids)')
            ->setParameter('cids', $commentIds)
            ->groupBy('v.comment_id')
            ->addGroupBy('v.vote_type')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($commentIds as $cid) {
            $out[$cid] = ['up' => 0, 'down' => 0];
        }
        foreach ($rows as $row) {
            $cid = (int) $row['cid'];
            $cnt = (int) $row['cnt'];
            if ($row['vt'] === 'up') {
                $out[$cid]['up'] = $cnt;
            } elseif ($row['vt'] === 'down') {
                $out[$cid]['down'] = $cnt;
            }
        }

        return $out;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('v')
            ->select('COUNT(v.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param int[] $commentIds
     * @return array<int, string> comment_id => 'up'|'down'
     */
    public function mapUserVotesOnComments(int $userId, array $commentIds): array
    {
        $commentIds = array_values(array_unique(array_filter($commentIds)));
        if ($commentIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('v')
            ->select('v.comment_id AS cid', 'v.vote_type AS vt')
            ->andWhere('v.user_id = :uid')
            ->andWhere('v.comment_id IN (:cids)')
            ->setParameter('uid', $userId)
            ->setParameter('cids', $commentIds)
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['cid']] = (string) $row['vt'];
        }

        return $out;
    }

//    /**
//     * @return CommentVote[] Returns an array of CommentVote objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('c.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?CommentVote
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
