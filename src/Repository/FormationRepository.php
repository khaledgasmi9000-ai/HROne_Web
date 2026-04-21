<?php

namespace App\Repository;

use App\Entity\Formation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Formation>
 */
class FormationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Formation::class);
    }

    /**
     * @return Formation[]
     */
    public function searchForCatalog(?string $mode = null, ?string $keyword = null, ?string $level = null): array
    {
        $qb = $this->createQueryBuilder('f')
            ->orderBy('f.Num_Ordre_Creation', 'ASC')
            ->addOrderBy('f.Titre', 'ASC');

        if ($mode && \in_array($mode, ['presentiel', 'en_ligne'], true)) {
            $qb->andWhere('f.Mode = :mode')
                ->setParameter('mode', $mode);
        }

        if ($level && \in_array($level, ['Debutant', 'Intermediaire', 'Avance'], true)) {
            $qb->andWhere('f.Niveau = :level')
                ->setParameter('level', $level);
        }

        if ($keyword) {
            $terms = preg_split('/\s+/', mb_strtolower(trim($keyword))) ?: [];
            $terms = array_values(array_filter($terms, static fn (string $term): bool => $term !== ''));

            foreach ($terms as $index => $term) {
                $parameter = 'keyword_' . $index;
                $qb->andWhere(sprintf(
                    '(LOWER(f.Titre) LIKE :%1$s OR LOWER(f.Description) LIKE :%1$s OR LOWER(f.Niveau) LIKE :%1$s)',
                    $parameter
                ))
                ->setParameter($parameter, '%' . $term . '%');
            }
        }

        return $qb->getQuery()->getResult();
    }

  public function findFeaturedFormation(): ?Formation
    {
        $available = $this->createQueryBuilder('f')
            ->andWhere('f.PlacesRestantes > 0')
            ->orderBy('f.Num_Ordre_Creation', 'ASC')
            ->addOrderBy('f.Titre', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($available instanceof Formation) {
            return $available;
        }

        return $this->createQueryBuilder('f')
            ->orderBy('f.Num_Ordre_Creation', 'ASC')
            ->addOrderBy('f.Titre', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

   public function getDefaultEnterpriseId(): int
    {
        $min = $this->createQueryBuilder('f')
            ->select('MIN(f.ID_Entreprise)')
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, (int) $min);
    }

    /**
     * @param list<int> $formationIds
     *
     * @return array<int, Formation>
     */

    public function getNextOrderNumber(): int
    {
        $maxOrder = (int) ($this->createQueryBuilder('f')
            ->select('MAX(f.Num_Ordre_Creation)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        return max(1, $maxOrder + 1);
    }

    public function getNextId(): int
    {
        $maxId = (int) ($this->createQueryBuilder('f')
            ->select('MAX(f.ID_Formation)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        return max(1, $maxId + 1);
    }
     public function findByIds(array $formationIds): array
    {
        if ($formationIds === []) {
            return [];
        }

        $formations = $this->createQueryBuilder('f')
            ->andWhere('f.ID_Formation IN (:ids)')
            ->setParameter('ids', array_map('intval', $formationIds))
            ->getQuery()
            ->getResult();

        $mapped = [];

        foreach ($formations as $formation) {
            if (!$formation instanceof Formation || $formation->getIDFormation() === null) {
                continue;
            }

            $mapped[$formation->getIDFormation()] = $formation;
        }

        return $mapped;
    }
}
