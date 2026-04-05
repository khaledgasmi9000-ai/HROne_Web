<?php

namespace App\Repository;

use App\Entity\OutilsDeTravail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<OutilsDeTravail>
 */
class OutilsDeTravailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OutilsDeTravail::class);
    }

    public function findAllTools(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT *
            FROM outils_de_travail
        ";

        $array = $conn->executeQuery($sql)->fetchAllAssociative();
        return $array; 
    }
    
    public function deleteTool(int $id): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeStatement(
            "DELETE FROM outils_de_travail WHERE ID_OUTIL = :id",
            ['id' => $id]
        );

        $conn->executeStatement(
            "DELETE FROM outil_employee WHERE ID_OUTIL = :id",
            ['id' => $id]
        );

    }
}
