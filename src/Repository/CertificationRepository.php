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

    public function findOneByFormationAndParticipant(int $formationId, int $participantId): ?Certification
    {
        return $this->findOneBy([
            'ID_Formation' => $formationId,
            'ID_Participant' => $participantId,
        ]);
    }

    public function getNextId(): int
    {
        $maxId = (int) ($this->createQueryBuilder('c')
            ->select('MAX(c.ID_Certif)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        return max(1, $maxId + 1);
    }

    public function removeByFormationId(int $formationId): void
    {
        $items = $this->findBy(['ID_Formation' => $formationId]);

        foreach ($items as $item) {
            if ($item instanceof Certification) {
                $this->getEntityManager()->remove($item);
            }
        }
    }
}
