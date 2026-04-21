<?php

namespace App\Repository;

use App\Entity\Evenement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Query;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Evenement>
 */
class EvenementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Evenement::class);
    }

//    /**
//     * @return Evenement[] Returns an array of Evenement objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('e')
//            ->andWhere('e.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('e.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    public function getNextId(): int
    {
        $maxId = $this->createQueryBuilder('e')
            ->select('MAX(e.ID_Evenement)')
            ->getQuery()
            ->getSingleScalarResult();

        return ($maxId ?: 0) + 1;
    }


    /**
     * RECHERCHE & TRI : Méthode dynamique pour le Front-End (Pagination)
     */
    public function findBySearchAndSort(?string $search, ?string $sort): Query
    {
        $qb = $this->createQueryBuilder('e');

        // 1. Recherche par mot-clé (Titre UNIQUEMENT)
        if ($search) {
            $qb->andWhere('e.Titre LIKE :query')
               ->setParameter('query', '%' . $search . '%');
        }

        // 2. Logique de Tri
        switch ($sort) {
            case 'price_asc':
                $qb->orderBy('e.prix', 'ASC');
                break;
            case 'price_desc':
                $qb->orderBy('e.prix', 'DESC');
                break;
            case 'name_asc':
                $qb->orderBy('e.Titre', 'ASC');
                break;
            default:
                // Par défaut, on trie par ID décroissant (le plus récent en premier)
                $qb->orderBy('e.ID_Evenement', 'DESC');
                break;
        }

        // On retourne l'objet Query directement (requis par KnpPaginator) au lieu de faire getResult()
        return $qb->getQuery();
    }
}
