<?php

namespace App\Repository;

use App\Entity\OutilsDeTravail;
use App\Entity\Categorie;

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
        return $this->findAll();
    }
    
    public function deleteTool(int $id): bool
    {
        $em = $this->getEntityManager();

        $tool = $this->find($id);
        if (!$tool) {
            return false;
        }

        foreach ($tool->getEmployees() as $employee) {
            $employee->removeOutilsDeTravail($tool);
        }

        $em->remove($tool);
        $em->flush();

        return true;
    }

    public function findToolById(int $id): ?OutilsDeTravail
    {
        return $this->find($id);
    }

    public function createTool(array $data): OutilsDeTravail
    {
        $em = $this->getEntityManager();
        $tool = new OutilsDeTravail();

        $tool->setNomOutil($data['name']);
        $tool->setIdentifiantUniverselle($data['exe']);
        $tool->setHashApp($data['hash']);
        $tool->setMonthly_Cost($data['monthly_cost']);

        $categorie = $em->getReference(Categorie::class, $data['categorie']);
        $tool->setCategorie($categorie);

        $em->persist($tool);
        $em->flush();

        return $tool;
    }

    public function updateTool(int $id, array $data): ?OutilsDeTravail
    {
        $em = $this->getEntityManager();

        $tool = $this->find($id);
        if (!$tool) {
            return null;
        }

        $tool->setNomOutil($data['name']);
        $tool->setIdentifiantUniverselle($data['exe']);
        $tool->setHashApp($data['hash']);
        $tool->setMonthly_Cost($data['monthly_cost']);

        $categorie = $em->getReference(\App\Entity\Categorie::class, $data['categorie']);
        $tool->setCategorie($categorie);

        $em->flush();

        return $tool;
    }

    public function getTotalCost(): float
    {
        $result = $this->createQueryBuilder('o')
            ->select('SUM(o.Monthly_Cost)')
            ->getQuery()
            ->getSingleScalarResult();

        return (float) ($result ?? 0);
    }
}
