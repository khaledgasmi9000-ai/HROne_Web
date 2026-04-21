<?php

namespace App\Repository;

use App\Entity\ParticipationEvenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ParticipationEvenement>
 */
class ParticipationEvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ParticipationEvenement::class);
    }

    /**
     * Récupère le prochain Num_Ordre_Participation disponible
     * Utilise un Num_Ordre existant de la table ordre
     */
    public function getNextNumOrdre(): int
    {
        $conn = $this->getEntityManager()->getConnection();
        
        // Récupérer un Num_Ordre valide existant de la table ordre
        $numOrdre = $conn->fetchOne("SELECT Num_Ordre FROM ordre ORDER BY Num_Ordre DESC LIMIT 1");
        
        if (!$numOrdre) {
            throw new \Exception("Aucun Num_Ordre disponible dans la table ordre");
        }
        
        return (int) $numOrdre;
    }

//    /**
//     * @return ParticipationEvenement[] Returns an array of ParticipationEvenement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ParticipationEvenement
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
