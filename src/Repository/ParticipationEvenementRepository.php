<?php

namespace App\Repository;

use App\Entity\ParticipationEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipationEvenement>
 */
class ParticipationEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationEvenement::class);
    }

    public function getNextId(): int
    {
        $maxId = $this->createQueryBuilder('p')
            ->select('MAX(p.ID_Participant)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxId ?: 0) + 1;
    }

    public function getNextNumOrdre(): int
    {
        $maxNum = $this->createQueryBuilder('p')
            ->select('MAX(p.Num_Ordre_Participation)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxNum ?: 0) + 1;
    }
}
