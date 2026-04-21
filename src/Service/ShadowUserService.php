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
        
        // Récupérer un Num_Ordre valide existant (on prend le premier disponible)
        $numOrdre = $conn->fetchOne("SELECT Num_Ordre FROM ordre LIMIT 1");
        
        if (!$numOrdre) {
            throw new \Exception("Aucun Num_Ordre disponible dans la table ordre");
        }
        
        $sqlInsert = "INSERT INTO utilisateur (ID_UTILISATEUR, ID_Entreprise, ID_Profil, Nom_Utilisateur, Mot_Passe, Email, Num_Ordre_Sign_In) 
                      VALUES (:id, 1, 3, :nom, 'shadow_password', :email, :numOrdre)";
        
        $conn->executeStatement($sqlInsert, [
            'id' => $nextId,
            'nom' => $nomComplet,
            'email' => $email,
            'numOrdre' => $numOrdre
        ]);

        return $nextId;
    }
}
