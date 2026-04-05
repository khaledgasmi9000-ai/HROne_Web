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

    public function findOneByPostAndUser(int $postId, int $userId): ?PostVote
    {
        return $this->findOneBy(['post_id' => $postId, 'user_id' => $userId]);
    }

    /**
     * @param int[] $postIds
     * @return array<int, array{up: int, down: int}>
     */
    public function sumVotesByPostIds(array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter($postIds)));
        if ($postIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('v')
            ->select('v.post_id AS pid', 'v.vote_type AS vt', 'COUNT(v.id) AS cnt')
            ->andWhere('v.post_id IN (:pids)')
            ->setParameter('pids', $postIds)
            ->groupBy('v.post_id')
            ->addGroupBy('v.vote_type')
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($postIds as $pid) {
            $out[$pid] = ['up' => 0, 'down' => 0];
        }
        foreach ($rows as $row) {
            $pid = (int) $row['pid'];
            $cnt = (int) $row['cnt'];
            if ($row['vt'] === 'up') {
                $out[$pid]['up'] = $cnt;
            } elseif ($row['vt'] === 'down') {
                $out[$pid]['down'] = $cnt;
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
     * @param int[] $postIds
     * @return array<int, string> post_id => 'up'|'down'
     */
    public function mapUserVotesOnPosts(int $userId, array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter($postIds)));
        if ($postIds === []) {
            return [];
        }

        $rows = $this->createQueryBuilder('v')
            ->select('v.post_id AS pid', 'v.vote_type AS vt')
            ->andWhere('v.user_id = :uid')
            ->andWhere('v.post_id IN (:pids)')
            ->setParameter('uid', $userId)
            ->setParameter('pids', $postIds)
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['pid']] = (string) $row['vt'];
        }

        return $out;
    }

//    /**
//     * @return PostVote[] Returns an array of PostVote objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?PostVote
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
