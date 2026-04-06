<?php

namespace App\Repository;

use App\Entity\Certification;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Certification>
 */
class CertificationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Certification::class);
    }

    public function removeByFormationId(int $formationId): int
    {
        return $this->createQueryBuilder('c')
            ->delete()
            ->andWhere('c.ID_Formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->execute();
    }
}
