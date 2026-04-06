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

    // WRITE
    public function save(Utilisateur $user): void
    {
        $this->_em->persist($user);
        $this->_em->flush();
    }

    // DELETE
    public function remove(Utilisateur $user): void
    {
        $this->_em->remove($user);
        $this->_em->flush();
    }

    // FIND BY EMAIL
    public function findByEmail(string $email): ?Utilisateur
    {
        return $this->findOneBy(['email' => $email]);
    }

    // FIND BY ROLE
    public function findByRole(string $role): array
    {
        return $this->findBy(['role' => $role]);
    }

    // SEARCH BY NAME OR EMAIL
    public function search(string $query): array
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.nom LIKE :q OR u.email LIKE :q')
            ->setParameter('q', '%' . $query . '%')
            ->getQuery()
            ->getResult();
    }

    // CHECK EMAIL EXISTS
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }

    // COUNT ALL
    public function countAll(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}