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

    /**
     * @return array<int, array{id: int, label: string, email: string}>
     */
    public function findDemoEmployees(): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT u.ID_UTILISATEUR AS id, u.Nom_Utilisateur AS label, COALESCE(u.Email, \'\') AS email
             FROM utilisateur u
             INNER JOIN employee e ON e.ID_UTILISATEUR = u.ID_UTILISATEUR
             ORDER BY e.ID_Employe ASC'
        );

        return array_map(
            static fn (array $row): array => [
                'id' => (int) $row['id'],
                'label' => (string) $row['label'],
                'email' => (string) $row['email'],
            ],
            $rows
        );
    }
}
