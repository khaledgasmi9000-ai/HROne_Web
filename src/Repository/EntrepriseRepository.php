<?php
// src/Repository/EntrepriseRepository.php
namespace App\Repository;

use App\Entity\Entreprise;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class EntrepriseRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Entreprise::class);
    }

    /**
     * Save an Entreprise entity
     */
    public function save(Entreprise $entreprise, bool $flush = true): void
    {
        $this->_em->persist($entreprise);

        if ($flush) {
            $this->_em->flush();
        }
    }
}