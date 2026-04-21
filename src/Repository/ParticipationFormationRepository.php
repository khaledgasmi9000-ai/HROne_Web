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
        /**
     * @var list<string>
     */
    private const ACTIVE_STATUSES = ['inscrit', 'en_cours'];
    
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationFormation::class);
    }

    public function getNextOrderNumber(): int
    {
        $maxOrder = (int) ($this->createQueryBuilder('p')
            ->select('MAX(p.Num_Ordre_Participation)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        return max(1, $maxOrder + 1);
    }

    public function hasActiveParticipation(int $formationId, int $participantId): bool
    {
        return $this->findActiveParticipation($formationId, $participantId) instanceof ParticipationFormation;
    }

     public function findActiveParticipation(int $formationId, int $participantId): ?ParticipationFormation
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.ID_Formation = :formationId')
            ->andWhere('p.ID_Participant = :participantId')
            ->andWhere('p.Statut IS NULL OR p.Statut IN (:statuses)')
            ->setParameter('formationId', $formationId)
            ->setParameter('participantId', $participantId)
            ->setParameter('statuses', self::ACTIVE_STATUSES)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ParticipationFormation[]
     */
     public function findParticipation(int $formationId, int $participantId): ?ParticipationFormation
    {
        return $this->findOneBy([
            'ID_Formation' => $formationId,
            'ID_Participant' => $participantId,
        ]);
    }
    public function findActiveByParticipant(int $participantId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.ID_Participant = :participantId')
            ->andWhere('p.Statut IS NULL OR p.Statut IN (:activeStatuses)')
            ->setParameter('participantId', $participantId)
            ->setParameter('activeStatuses', ['inscrit', 'en_cours'])
            ->orderBy('p.Num_Ordre_Participation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function removeActiveParticipation(int $formationId, int $participantId): int
    {
        return $this->createQueryBuilder('p')
            ->update()
            ->set('p.Statut', ':status')
            ->andWhere('p.ID_Formation = :formationId')
            ->andWhere('p.ID_Participant = :participantId')
            ->andWhere('p.Statut IS NULL OR p.Statut IN (:statuses)')
            ->setParameter('formationId', $formationId)
            ->setParameter('participantId', $participantId)
            ->setParameter('statuses', self::ACTIVE_STATUSES)
            ->setParameter('status', 'annule')
            ->getQuery()
            ->execute();
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

    /**
     * @return ParticipationFormation[]
     */
    public function findByFormationOrdered(int $formationId): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.ID_Formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->orderBy('p.Num_Ordre_Participation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function countActiveByFormation(int $formationId): int
    {
        return (int) $this->createQueryBuilder('p')
            ->select('COUNT(p.ID_Formation)')
            ->andWhere('p.ID_Formation = :formationId')
            ->andWhere('p.Statut IS NULL OR p.Statut IN (:statuses)')
            ->setParameter('formationId', $formationId)
            ->setParameter('statuses', self::ACTIVE_STATUSES)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function removeByFormationId(int $formationId): int
    {
        return $this->createQueryBuilder('p')
            ->delete()
            ->andWhere('p.ID_Formation = :formationId')
            ->setParameter('formationId', $formationId)
            ->getQuery()
            ->execute();
    }
    }
