<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\Ordre;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Employee>
 */
class EmployeeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Employee::class);
    }

    public function findAllEmployees(): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT * FROM employee JOIN utilisateur on employee.ID_UTILISATEUR = utilisateur.ID_UTILISATEUR";

        $stmt = $conn->executeQuery($sql);
        $array = $stmt->fetchAllAssociative();
        return $array;
    }

    public function getNumberofUsedConge(int $employeeId){
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            SELECT SUM(Nbr_Jour_Demande) AS used_conge
            FROM demande_conge
            WHERE ID_Employe = :employeeId
            AND Num_Ordre_Fin_Conge < :numOrdreNow
            AND STATUS = 1
        ";

        $result = $conn->executeQuery($sql, [
            'employeeId' => $employeeId,
            'numOrdreNow' => Ordre::GetNumOrdreNow()
        ])->fetchOne();
        
        return (int) $result;
    }

    public function deleteEmployee(int $id): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $conn->executeStatement(
            "DELETE FROM outil_employee WHERE ID_EMPLOYEE = :id",
            ['id' => $id]
        );

        $conn->executeStatement(
            "DELETE FROM demande_conge WHERE ID_EMPLOYE = :id",
            ['id' => $id]
        );

        $conn->executeStatement(
            "DELETE FROM employee WHERE ID_EMPLOYE = :id",
            ['id' => $id]
        );

    }
}
