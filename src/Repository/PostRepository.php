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
     * @return Post[]
     */
    public function findAllOrderedByNewest(): array
    {
        return $this->createQueryBuilder('p')
            ->orderBy('p.created_at', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Fil d’actualité : posts actifs uniquement, filtres optionnels.
     *
     * @return Post[]
     */
    public function findFeedOrdered(?string $tag, ?int $userId, ?string $titleSearch = null): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.is_active = true OR p.is_active IS NULL')
            ->orderBy('p.created_at', 'DESC');

        if ($tag !== null && $tag !== '') {
            $qb->andWhere('p.tag = :tag')->setParameter('tag', $tag);
        }
        if ($userId !== null && $userId > 0) {
            $qb->andWhere('p.user_id = :uid')->setParameter('uid', $userId);
        }
        if ($titleSearch !== null && $titleSearch !== '') {
            $qb->andWhere('LOWER(p.title) LIKE :tq ESCAPE \'\\\\\'')
                ->setParameter('tq', '%'.mb_strtolower($this->escapeLikePattern($titleSearch)).'%');
        }

        return $qb->getQuery()->getResult();
    }

    private function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }

    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /** Visibles sur le fil public (actif ou NULL). */
    public function countActivePublic(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.is_active = true OR p.is_active IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countInactive(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.id)')
            ->andWhere('p.is_active = false')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDistinctAuthors(): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(DISTINCT p.user_id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<array{tag: string, count: int}>
     */
    public function countGroupedByTag(int $limit = 20): array
    {
        $rows = $this->createQueryBuilder('p')
            ->select('p.tag AS t', 'COUNT(p.id) AS c')
            ->groupBy('p.tag')
            ->orderBy('c', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getArrayResult();

        $out = [];
        foreach ($rows as $row) {
            $tag = $row['t'] !== null && $row['t'] !== '' ? (string) $row['t'] : 'General';
            $out[] = ['tag' => $tag, 'count' => (int) $row['c']];
        }

        return $out;
    }

    /**
     * @return Post[]
     */
    public function findByUserIdOrdered(int $userId): array
    {
        return $this->findByUserIdOrderedFiltered($userId, null, null);
    }

    /**
     * @return Post[]
     */
    public function findByUserIdOrderedFiltered(int $userId, ?string $tag, ?string $titleSearch): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.user_id = :uid')
            ->setParameter('uid', $userId)
            ->orderBy('p.created_at', 'DESC');

        if ($tag !== null && $tag !== '') {
            $qb->andWhere('p.tag = :tag')->setParameter('tag', $tag);
        }
        if ($titleSearch !== null && $titleSearch !== '') {
            $qb->andWhere('LOWER(p.title) LIKE :tq ESCAPE \'\\\\\'')
                ->setParameter('tq', '%'.mb_strtolower($this->escapeLikePattern($titleSearch)).'%');
        }

        return $qb->getQuery()->getResult();
    }

//    /**
//     * @return Post[] Returns an array of Post objects
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

//    public function findOneBySomeField($value): ?Post
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
