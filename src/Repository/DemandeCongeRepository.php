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
        return $this->createQueryBuilder('dc')
            ->select([
                'dc.ID_Demende',
                'u.Nom_Utilisateur',
                'od.Num_Ordre AS Num_Ordre_Debut_Conge',
                'ofi.Num_Ordre AS Num_Ordre_Fin_Conge',
                'dc.Nbr_Jour_Demande'
            ])
            ->join('dc.employee', 'e')
            ->join('e.utilisateur', 'u')
            ->leftJoin('dc.ordreDebut', 'od')
            ->leftJoin('dc.ordreFin', 'ofi')
            ->where('dc.Status = 0')
            ->getQuery()
            ->getArrayResult();
    }

    public function deleteConge(int $id): bool
    {
        $em = $this->getEntityManager();

        $conge = $this->find($id);
        if (!$conge) {
            return false;
        }

        $em->remove($conge);
        $em->flush();

        return true;
    }
    
    public function updateCongeStatus(int $id, int $status): bool
    {
        $em = $this->getEntityManager();

        $conge = $this->find($id);
        if (!$conge) {
            return false;
        }

        $conge->setStatus($status);

        $em->flush();

        return true;
    }

    public function createConge(int $idEmployee, string $start, string $end, int $nbrJours): DemandeConge
    {
        $em = $this->getEntityManager();

        $employee = $em->getReference(\App\Entity\Employee::class, $idEmployee);

        // $ordreDebut = $em->getRepository(Ordre::class)
        //     ->find(Ordre::dateToNumOrdre(new \DateTime($start)));

        // $ordreFin = $em->getRepository(Ordre::class)
        //     ->find(Ordre::dateToNumOrdre(new \DateTime($end)));
        echo "Start: $start, End: $end\n";
        $ordreDebut = new Ordre();
        $ordreDebut->setNum_Ordre(Ordre::dateToNumOrdre(new \DateTime($start)));
        $ordreFin = new Ordre();
        $ordreFin->setNum_Ordre(Ordre::dateToNumOrdre(new \DateTime($end)));
        
        echo "Ordre Debut Num: " . $ordreDebut->getNum_Ordre() . "\n";
        echo "Ordre Fin Num: " . $ordreFin->getNum_Ordre() . "\n";
        $conge = new DemandeConge();
        $conge->setEmployee($employee);
        $conge->setOrdreDebut($ordreDebut);
        $conge->setOrdreFin($ordreFin);
        $conge->setNbrJourDemande($nbrJours);
        $conge->setStatus(0);

        dump($conge);
        $em->persist($conge);
        $em->flush();

        return $conge;
    }
}
