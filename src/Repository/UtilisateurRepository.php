<?php

namespace App\Repository;

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    public function updateUser(Utilisateur $user, array $data): void
    {
        $user->setNomUtilisateur($data['name']);

        $this->getEntityManager()->flush();
    }

    public function emailExistsForOther(string $email, ?int $excludeUserId): bool
    {      
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.ID_UTILISATEUR)')
            ->where('u.Email = :email')
            ->setParameter('email', $email);

        if ($excludeUserId) {
            $qb->andWhere('u.ID_UTILISATEUR != :id')
            ->setParameter('id', $excludeUserId);
        }

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }

    
    public function cinExistsForOther(string $cin, ?int $excludeUserId): bool
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.ID_UTILISATEUR)')
            ->where('u.CIN = :cin')
            ->setParameter('cin', $cin);

        if ($excludeUserId) {
            $qb->andWhere('u.ID_UTILISATEUR != :id')
            ->setParameter('id', $excludeUserId);
        }

        return (int)$qb->getQuery()->getSingleScalarResult() > 0;
    }
}
