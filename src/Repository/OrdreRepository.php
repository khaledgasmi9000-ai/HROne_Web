<?php

namespace App\Repository;

use App\Entity\Ordre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Ordre>
 */
class OrdreRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ordre::class);
    }

    public function getOrCreateOrdre(int $numOrdre): Ordre
    {

        $em = $this->getEntityManager();

        $ordre = $this->find($numOrdre);

        if (!$ordre) {
            $ordre = new Ordre();
            $ordre->setNum_Ordre($numOrdre);
            $em->persist($ordre);
            // ⚠️ no flush here (important)
        }

        return $ordre;
    }
}
