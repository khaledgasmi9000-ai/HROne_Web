<?php

namespace App\Repository;

use App\Entity\Employee;
use App\Entity\Ordre;
use App\Entity\Utilisateur;
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
            ->andWhere('dc.Status = 1')
            ->andWhere('IDENTITY(dc.ordreFin) < :now')
            ->setParameter('id', $employeeId)
            ->setParameter('now', Ordre::GetNumOrdreNow())
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
        $employee = new Employee();

        $employee->setUtilisateur($user);
        $employee->setSoldeConge($data['solde']);
        $employee->setSalaire($data['salaire']);
        $employee->setNbrHeureDeTravail($data['heures']);

        $em = $this->getEntityManager();
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

}
