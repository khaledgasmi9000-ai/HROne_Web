<?php

namespace App\Repository;

use App\Entity\ParticipationFormation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipationFormation>
 */
class ParticipationFormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationFormation::class);
    }

    public function findActiveParticipation(int $formationId, int $participantId): ?ParticipationFormation
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.ID_Formation = :formationId')
            ->andWhere('p.ID_Participant = :participantId')
            ->andWhere('p.Statut = :status')
            ->setParameter('formationId', $formationId)
            ->setParameter('participantId', $participantId)
            ->setParameter('status', 'inscrit')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function hasActiveParticipation(int $formationId, int $participantId): bool
    {
        return $this->findActiveParticipation($formationId, $participantId) instanceof ParticipationFormation;
    }

    /**
     * @return ParticipationFormation[]
     */
    public function findByParticipantOrdered(int $participantId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.ID_Participant = :participantId')
            ->setParameter('participantId', $participantId)
            ->orderBy('p.Num_Ordre_Participation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getNextOrderNumber(): int
    {
        $max = $this->createQueryBuilder('p')
            ->select('MAX(p.Num_Ordre_Participation)')
            ->getQuery()
            ->getSingleScalarResult();

        return ((int) $max) + 1;
    }

    public function removeActiveParticipation(int $formationId, int $participantId): int
    {
        return $this->createQueryBuilder('p')
            ->delete()
            ->andWhere('p.ID_Formation = :formationId')
            ->andWhere('p.ID_Participant = :participantId')
            ->andWhere('p.Statut = :status')
            ->setParameter('formationId', $formationId)
            ->setParameter('participantId', $participantId)
            ->setParameter('status', 'inscrit')
            ->getQuery()
            ->execute();
    }
}
