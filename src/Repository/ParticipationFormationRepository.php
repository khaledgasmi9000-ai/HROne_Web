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
        return $this->buildActiveQuery($formationId, $participantId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * @return ParticipationFormation[]
     */
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

    public function removeActiveParticipation(int $formationId, int $participantId): void
    {
        $participations = $this->buildActiveQuery($formationId, $participantId)
            ->getQuery()
            ->getResult();

        foreach ($participations as $participation) {
            if ($participation instanceof ParticipationFormation) {
                $this->getEntityManager()->remove($participation);
            }
        }
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
            ->orderBy('p.Num_Ordre_Participation', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function removeByFormationId(int $formationId): void
    {
        $items = $this->findBy(['ID_Formation' => $formationId]);

        foreach ($items as $item) {
            if ($item instanceof ParticipationFormation) {
                $this->getEntityManager()->remove($item);
            }
        }
    }

    private function buildActiveQuery(int $formationId, int $participantId)
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.ID_Formation = :formationId')
            ->andWhere('p.ID_Participant = :participantId')
            ->andWhere('p.Statut IS NULL OR p.Statut IN (:activeStatuses)')
            ->setParameter('formationId', $formationId)
            ->setParameter('participantId', $participantId)
            ->setParameter('activeStatuses', ['inscrit', 'en_cours'])
            ->orderBy('p.Num_Ordre_Participation', 'DESC');
    }
}
