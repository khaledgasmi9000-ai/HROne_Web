<?php

namespace App\Service;

use Doctrine\ORM\EntityManagerInterface;

class ShadowUserService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * Crée un nouvel utilisateur technique (Shadow User) pour CHAQUE inscription.
     * Cela permet d'avoir un ID_Participant unique par événement même pour le même email.
     */
    public function createShadowUser(string $email, string $nomComplet): int
    {
        $conn = $this->em->getConnection();
        
        // On calcule le prochain ID (logique MAX+1 du projet)
        $nextId = (int) $conn->fetchOne("SELECT MAX(ID_UTILISATEUR) FROM utilisateur") + 1;
        
        $sqlInsert = "INSERT INTO utilisateur (ID_UTILISATEUR, ID_Entreprise, ID_Profil, Nom_Utilisateur, Mot_Passe, Email, Num_Ordre_Sign_In) 
                      VALUES (:id, 1, 1, :nom, 'shadow_password', :email, 1)";
        
        $conn->executeStatement($sqlInsert, [
            'id' => $nextId,
            'nom' => $nomComplet,
            'email' => $email
        ]);

        return $nextId;
    }
}
