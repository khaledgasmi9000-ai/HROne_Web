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

        $sql = "SELECT * FROM outils_de_travail";

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

    public function findToolById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT * FROM outils_de_travail WHERE ID_OUTIL = :id";

        $result = $conn->executeQuery($sql, [
            'id' => $id
        ])->fetchAssociative();

        return $result ?: null;
    }

    public function createTool(array $data): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "INSERT INTO outils_de_travail (NOM_OUTIL,Identifiant_Universelle,Hash_App) VALUES (:name,:exe,:hash)";

        $conn->executeStatement($sql, [
            'name' => $data['name'],
            'exe'  => $data['exe'],
            'hash' => $data['hash'],
        ]);
    }

    public function updateTool(int $id, array $data): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "UPDATE outils_de_travail SET NOM_OUTIL = :name,Identifiant_Universelle = :exe,Hash_App = :hash WHERE ID_OUTIL = :id";

        $conn->executeStatement($sql, [
            'id'   => $id,
            'name' => $data['name'],
            'exe'  => $data['exe'],
            'hash' => $data['hash'],
        ]);
    }
}
