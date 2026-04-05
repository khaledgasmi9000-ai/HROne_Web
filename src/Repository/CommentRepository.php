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
     * @return Comment[]
     */
    public function findByPostIdOrdered(int $postId): array
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.post_id = :pid')
            ->setParameter('pid', $postId)
            ->orderBy('c.created_at', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @param int[] $postIds
     * @return array<int, int> post_id => nombre de commentaires actifs
     */
    public function countActiveByPostIds(array $postIds): array
    {
        $postIds = array_values(array_unique(array_filter($postIds)));
        if ($postIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('c')
            ->select('c.post_id AS pid, COUNT(c.id) AS cnt')
            ->andWhere('c.post_id IN (:pids)')
            ->andWhere('c.is_active = true OR c.is_active IS NULL')
            ->setParameter('pids', $postIds)
            ->groupBy('c.post_id');

        $out = [];
        foreach ($postIds as $pid) {
            $out[$pid] = 0;
        }
        foreach ($qb->getQuery()->getArrayResult() as $row) {
            $out[(int) $row['pid']] = (int) $row['cnt'];
        }

        return $out;
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countActivePublic(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.is_active = true OR c.is_active IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countInactive(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.is_active = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countRootComments(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.parent_comment_id IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countReplyComments(): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.parent_comment_id IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return Comment[]
     */
    public function findByUserIdOrdered(int $userId): array
    {
        return $this->findByUserIdOrderedFiltered($userId, null);
    }

    /**
     * @return Comment[]
     */
    public function findByUserIdOrderedFiltered(int $userId, ?string $contentSearch): array
    {
        $qb = $this->createQueryBuilder('c')
            ->andWhere('c.user_id = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('c.created_at', 'DESC');

        if ($contentSearch !== null && $contentSearch !== '') {
            $esc = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $contentSearch);
            $qb->andWhere('LOWER(c.content) LIKE :cq ESCAPE \'\\\\\'')
                ->setParameter('cq', '%'.mb_strtolower($esc).'%');
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Comment[] Returns an array of Comment objects
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

//    public function findOneBySomeField($value): ?Comment
//    {
//        return $this->createQueryBuilder('c')
//            ->andWhere('c.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
