<?php

namespace App\Repository;

use App\Entity\DemandeConge;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Ordre;

/**
 * @extends ServiceEntityRepository<DemandeConge>
 */
class DemandeCongeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DemandeConge::class);
    }

    public function findAllConges(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT ID_Demende,Nom_Utilisateur,Num_Ordre_Debut_Conge,Num_Ordre_Fin_Conge,Nbr_Jour_Demande FROM demande_conge JOIN employee ON demande_conge.ID_Employe = employee.ID_Employe JOIN utilisateur on employee.ID_UTILISATEUR = utilisateur.ID_UTILISATEUR WHERE STATUS = 0;";

        $array = $conn->executeQuery($sql)->fetchAllAssociative();
        return $array;
    }

    public function deleteConge(int $id): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            DELETE FROM demande_conge
            WHERE ID_DEMeNDE =  :id
        ";

        $conn->executeStatement($sql, ['id' => $id]);
    }
    
    public function updateCongeStatus(int $id, int $status): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            UPDATE demande_conge
            SET STATUS = :status
            WHERE ID_DEMeNDE = :id
        ";

        $conn->executeStatement($sql, [
            'status' => $status,
            'id' => $id
        ]);
    }

    public function createConge(int $idEmployee, string $start, string $end, int $nbrJours): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            INSERT INTO demande_conge (ID_Employe, Num_Ordre_Debut_Conge, Num_Ordre_Fin_Conge, Nbr_Jour_Demande, STATUS)
            VALUES (:idEmployee, :start, :end, :nbrJours, 0)
        ";

        $conn->executeStatement($sql, [
            'idEmployee' => $idEmployee,
            'start' => Ordre::dateToNumOrdre(new \DateTime($start)),
            'end' => Ordre::dateToNumOrdre(new \DateTime($end)),
            'nbrJours' => $nbrJours
        ]);
    }
}
