<?php

namespace App\Repository;

use App\Entity\ListeAttente;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeAttente>
 */
class ListeAttenteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeAttente::class);
    }

    public function getNextId(): int
    {
        $maxId = $this->createQueryBuilder('l')
            ->select('MAX(l.ID_Attente)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxId ?: 0) + 1;
    }
}
