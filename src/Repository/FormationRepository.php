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
        $qb = $this->createQueryBuilder('f');

        $normalizedMode = mb_strtolower(trim((string) $mode));
        if ($normalizedMode !== '') {
            $qb->andWhere('LOWER(f.Mode) = :mode')
                ->setParameter('mode', $normalizedMode);
        }

        $normalizedLevel = mb_strtolower(trim((string) $level));
        if ($normalizedLevel !== '') {
            $qb->andWhere('LOWER(f.Niveau) = :level')
                ->setParameter('level', $normalizedLevel);
        }

        $normalizedKeyword = mb_strtolower(trim((string) $keyword));
        if ($normalizedKeyword !== '') {
            $qb->andWhere(
                'LOWER(f.Titre) LIKE :keyword OR LOWER(COALESCE(f.Description, \'\')) LIKE :keyword OR LOWER(COALESCE(f.Niveau, \'\')) LIKE :keyword'
            )->setParameter('keyword', '%' . $normalizedKeyword . '%');
        }

        return $qb
            ->orderBy('f.Date_Debut', 'ASC')
            ->addOrderBy('f.Titre', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findFeaturedFormation(): ?Formation
    {
        $featured = $this->createQueryBuilder('f')
            ->andWhere('COALESCE(f.PlacesRestantes, 0) > 0')
            ->orderBy('f.Date_Debut', 'ASC')
            ->addOrderBy('f.PlacesRestantes', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if ($featured instanceof Formation) {
            return $featured;
        }

        return $this->createQueryBuilder('f')
            ->orderBy('f.Date_Debut', 'ASC')
            ->addOrderBy('f.Titre', 'ASC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function getDefaultEnterpriseId(): int
    {
        $enterpriseId = (int) ($this->createQueryBuilder('f')
            ->select('MIN(f.ID_Entreprise)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        if ($enterpriseId > 0) {
            return $enterpriseId;
        }

        try {
            $fromEnterpriseTable = (int) ($this->getEntityManager()->getConnection()->fetchOne('SELECT MIN(ID_Entreprise) FROM entreprise') ?? 0);
            if ($fromEnterpriseTable > 0) {
                return $fromEnterpriseTable;
            }
        } catch (\Throwable) {
            // Fallback below keeps the form usable even if entreprise table lookup fails.
        }

        return 1;
    }

    public function getNextOrderNumber(): int
    {
        $maxOrder = (int) ($this->createQueryBuilder('f')
            ->select('MAX(f.Num_Ordre_Creation)')
            ->getQuery()
            ->getSingleScalarResult() ?? 0);

        return max(1, $maxOrder + 1);
    }
}
