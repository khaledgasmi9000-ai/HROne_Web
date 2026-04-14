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

    public function findOneByFormationAndParticipant(int $formationId, int $participantId): ?Certification
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.ID_Formation = :formationId')
            ->andWhere('c.ID_Participant = :participantId')
            ->setParameter('formationId', $formationId)
            ->setParameter('participantId', $participantId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNextId(): int
    {
        $max = $this->createQueryBuilder('c')
            ->select('MAX(c.ID_Certif)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }
}
