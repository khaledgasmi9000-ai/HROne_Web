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

    public function findEmployeeById(int $id): ?array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "SELECT * FROM employee JOIN utilisateur on employee.ID_UTILISATEUR = utilisateur.ID_UTILISATEUR WHERE ID_EMPLOYE = :id";

        $stmt = $conn->executeQuery($sql, ['id' => $id]);
        $employee = $stmt->fetchAssociative();

        return $employee ?: null;
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

    public function createEmployee(array $data): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "INSERT INTO UTILISATEUR (Nom_Utilisateur,Mot_Passe, Email,Date_Naissance,Gender,CIN,Num_Tel,Num_Ordre_Sign_In) VALUES (:name, :password, :email, :birthDate, :gender, :cin, :phone, :numOrdreSignIn)";
        $conn->executeStatement($sql, [
            'name' => $data['name'],
            'password' => "TempPassword",
            'email' => $data['email'],
            'birthDate' => $data['birth'],
            'gender' => $data['gender'],
            'cin' => $data['cin'],
            'phone' => $data['phone'],
            'numOrdreSignIn' => Ordre::GetNumOrdreNow()
        ]);

        $sql = "INSERT INTO employee (ID_UTILISATEUR, Solde_Conge, SALAIRE, Nbr_Heure_De_Travail) VALUES (:userId, :solde, :salaire, :heures)";
        $conn->executeStatement($sql, [
            'userId' => $conn->lastInsertId(),
            'solde' => $data['solde'],
            'salaire' => $data['salaire'],
            'heures' => $data['heures']
        ]);
    }

    public function updateEmployee(int $id, array $data): void
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = "
            UPDATE employee SET  
            Solde_Conge = :solde, 
            SALAIRE = :salaire, 
            Nbr_Heure_De_Travail = :heures
            WHERE ID_EMPLOYE = :id";

        $conn->executeStatement($sql, [
            'id' => $id,
            'solde' => $data['solde'],
            'salaire' => $data['salaire'],
            'heures' => $data['heures'],
        ]);

        $sql = "SELECT ID_UTILISATEUR FROM employee WHERE ID_EMPLOYE = :id";
        $userId = $conn->executeQuery($sql, ['id' => $id])->fetchOne();

        $sql = "UPDATE UTILISATEUR SET Nom_Utilisateur = :name, Email = :email WHERE ID_UTILISATEUR = :userId";
        $conn->executeStatement($sql, [
            'userId' => $userId,
            'name' => $data['name'],
            'email' => $data['email']
        ]);
    }
}
