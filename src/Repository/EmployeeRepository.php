<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\Utilisateur;
use App\Entity\Departement;

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
        return $this->createQueryBuilder('e')
            ->join('e.utilisateur', 'u')
            ->addSelect('u')
            ->getQuery()
            ->getResult();
    }

    public function findEmployeeByUserId(int $userId): ?Employee
    {
        return $this->createQueryBuilder('e')
            ->join('e.utilisateur', 'u')
            ->addSelect('u')
            ->where('u.ID_UTILISATEUR = :userId')
            ->setParameter('userId', $userId)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findEmployeeById(int $id): ?Employee
    {
        return $this->createQueryBuilder('e')
            ->join('e.utilisateur', 'u')
            ->addSelect('u')
            ->where('e.ID_Employe = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getNumberofUsedConge(int $employeeId): int
    {
        $result = $this->getEntityManager()->createQueryBuilder()
            ->select('SUM(dc.Nbr_Jour_Demande)')
            ->from(\App\Entity\DemandeConge::class, 'dc')
            ->join('dc.employee', 'e')
            ->where('e.ID_Employe = :id')
            ->andWhere('dc.Status != -1')
            ->setParameter('id', $employeeId)
            ->getQuery()
            ->getSingleScalarResult();

        return (int) ($result ?? 0);
    }

    public function deleteEmployee(int $id): bool
    {
        $em = $this->getEntityManager();

        $employee = $this->find($id);
        if (!$employee) {
            return false;
        }

        // detach tools
        foreach ($employee->getOutilsDeTravails() as $tool) {
            $employee->removeOutilsDeTravail($tool);
        }

        $em->remove($employee);
        $em->flush();

        return true;
    }

    public function createEmployee(array $data, Utilisateur $user): Employee
    {
        $em = $this->getEntityManager();

        $employee = new Employee();

        $employee->setUtilisateur($user);
        $employee->setSoldeConge($data['solde']);
        $employee->setSalaire($data['salaire']);
        $employee->setNbrHeureDeTravail($data['heures']);

        $departement = $em->getReference(Departement::class, $data['departement']);
        $employee->setDepartement($departement);

        $em->persist($employee);
        $em->flush();

        return $employee;
    }

    public function updateEmployee(int $id, array $data): ?Employee
    {
        $em = $this->getEntityManager();

        $employee = $this->find($id);
        if (!$employee) {
            return null;
        }

        $employee->setSoldeConge($data['solde']);
        $employee->setSalaire($data['salaire']);
        $employee->setNbrHeureDeTravail($data['heures']);

        if (isset($data['departement'])) {
        $departement = $em->getReference(\App\Entity\Departement::class, $data['departement']);
            $employee->setDepartement($departement);
        }
    
        $em->flush();

        return $employee;
    }

    public function getSoldeConge(int $employeeId): int
    {
        return (int) $this->createQueryBuilder('e')
            ->select('e.Solde_Conge')
            ->where('e.ID_Employe = :id')
            ->setParameter('id', $employeeId)
            ->getQuery()
            ->getSingleScalarResult();
    }


    public function updateEmployeeTools(int $employeeId, array $toolIds): void
    {
        $em = $this->getEntityManager();

        $employee = $this->find($employeeId);

        $employee->getOutilsDeTravails()->clear();

        foreach ($toolIds as $toolId) {
            $tool = $em->getReference(\App\Entity\OutilsDeTravail::class, $toolId);
            $employee->addOutilsDeTravail($tool);
        }

        $em->flush();
    }

    public function getEmployeeTools(int $employeeId): array
    {
        return $this->createQueryBuilder('e')
            ->select('o.ID_Outil, o.Nom_Outil')
            ->join('e.outilsDeTravails', 'o')
            ->where('e.ID_Employe = :id')
            ->setParameter('id', $employeeId)
            ->getQuery()
            ->getArrayResult();
    }

    public function getDepartmentProductivity(): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.departement', 'd')
            ->join('e.workSessions', 'ws')
            ->select('d.Nom as departement, AVG(ws.activeTime) as avgActive')
            ->groupBy('d.ID_Departement')
            ->getQuery()
            ->getArrayResult();
    }

}
