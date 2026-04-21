<?php

namespace App\Service;

use App\Repository\EvenementRepository;

/**
 * Assistant Virtuel HROne - Intelligence Autonome
 * -------------------------------------------------------------------------
 * RÔLE : Ce service simule une IA experte (type Gemini) capable d'aider
 * les utilisateurs à trouver des informations sur les événements.
 * 
 * NOTE TECHNIQUE : Pour garantir la portabilité du projet (pas besoin d'Internet),
 * cette IA utilise une logique de "Mots-clés intelligents" tout en ayant
 * accès à votre vraie base de données.
 * -------------------------------------------------------------------------
 */
class GeminiService
{
    private $evenementRepository;

    public function __construct(EvenementRepository $evenementRepository)
    {
        $this->evenementRepository = $evenementRepository;
    }

    /**
     * Point d'entrée principal pour générer une réponse intelligente.
     */
    public function generateResponse(string $userMessage): string
    {
        // On nettoie le message de l'utilisateur pour faciliter la détection
        $msg = trim(mb_strtolower($userMessage));

        // -------------------------------------------------------------
        // LOGIQUE DE DÉTECTION (Simulateur de Prompt)
        // -------------------------------------------------------------
        if ($this->match($msg, ['salut', 'bonjour', 'hello', 'coucou', 'hi', 'bonsoir'])) {
            return $this->getRandomResponse([
                "Bonjour ! Je suis votre assistant HROne. Ravi de vous aider aujourd'hui. Que puis-je pour vous ?",
                "Bonjour ! Heureux de vous voir sur plateforme HROne. Je suis à votre disposition pour toute question.",
                "Hello ! Je suis l'assistant virtuel de HROne. Comment se passe votre journée de travail ?"
            ]);
        }

        // 2. Présentation du Projet HROne
        if ($this->match($msg, ['c\'est quoi', 'hrone', 'plateforme', 'qui es-tu', 'presentation', 'presenter'])) {
            return "HROne est votre plateforme centralisée d'engagement collaborateur. Mon rôle est de vous accompagner dans la découverte des événements d'entreprise, la gestion de vos formations et le suivi de vos participations. Nous croyons qu'un employé épanoui est la clé du succès !";
        }

        // 3. Liste Dynamique des Événements (Accès Réel à la Base de Données)
        // C'est ici que l'IA devient "intelligente" : elle va chercher dans
        // la vraie table 'evenement' pour répondre à l'utilisateur.
        if ($this->match($msg, ['événement', 'evenement', 'event', 'activit', 'quoi faire', 'planning'])) {
            $events = $this->evenementRepository->findBy([], ['ID_Evenement' => 'DESC'], 3);
            
            if (empty($events)) {
                return "Le catalogue est actuellement vide, mais de nouveaux événements RH arrivent bientôt ! Restez à l'écoute des annonces de votre département.";
            }

            $response = "Voici les principaux événements en cours sur HROne :\n\n";
            foreach ($events as $event) {
                // On utilise la méthode 'getRealDateDebut' que nous avons créée dans l'entité
                $date = $event->getRealDateDebut();
                $response .= "👉 **" . $event->getTitre() . "** (Le " . $date->format('d/m/Y') . ")\n";
            }
            $response .= "\nVous pouvez consulter la fiche détaillée de chaque événement pour voir les activités spécifiques.";
            return $response;
        }

        // 4. Guide Expert Inscription (Le Workflow)
        if ($this->match($msg, ['inscri', 'partici', 'comment', 'aide', 'etape', 'workflow'])) {
            return "S'inscrire à un événement sur HROne est très simple. Voici la marche à suivre :\n\n" .
                   "🟢 **1. Catalogue** : Parcourez la liste des 'Événements'.\n" .
                   "🔵 **2. Détails** : Cliquez sur 'Voir les détails' pour découvrir le programme.\n" .
                   "🟡 **3. Choix** : Sélectionnez vos activités et votre mode de paiement.\n" .
                   "🟠 **4. Validation** : Confirmez votre demande. Si l'événement est complet, vous passerez en liste d'attente.\n" .
                   "🔴 **5. Ticket** : Une fois validé, téléchargez votre pass PDF directement sur la page de succès ou via l'email reçu !";
        }

        // 5. Questions techniques (Ticket, PDF, Email)
        if ($this->match($msg, ['pdf', 'ticket', 'mail', 'email', 'imprimer', 'recu', 'confirmation'])) {
            return "À la fin de votre inscription, un ticket officiel est automatiquement généré. Vous pouvez :\n" .
                   "- Le télécharger immédiatement au format **PDF**.\n" .
                   "- Le retrouver dans votre boîte mail (pensez à vérifier les spams !).\n" .
                   "Ce ticket contient un récapitulatif précieux de vos activités réservées.";
        }

        // 6. Liste d'attente et Promotion
        if ($this->match($msg, ['liste d\'attente', 'complet', 'attente', 'promotion'])) {
            return "Ne soyez pas déçu si un événement est complet ! HROne gère intelligemment les listes d'attente. Si quelqu'un se désiste, le système 'poussera' automatiquement la prochaine personne de la liste et lui enverra un mail de confirmation. C'est simple et équitable !";
        }

        // 7. Politesse et Remerciements
        if ($this->match($msg, ['merci', 'ok', 'merci beaucoup', 'cool', 'parfait', 'top'])) {
            return "Je vous en prie ! Je reste à votre entière disposition si vous avez besoin d'autre chose pour profiter pleinement de HROne. Bonne continuation !";
        }

        // 8. Réponse par défaut "Smart Generic"
        return "C'est une demande intéressante concernant HROne. Bien que je sois spécialisé dans les événements et les inscriptions, je vous recommande d'explorer les différents onglets de notre plateforme ou de consulter votre profil pour plus de détails. Souhaitez-vous que je vous liste nos événements actuels ?";
    }

    /**
     * Vérifie si le message contient au moins un des mots-clés.
     */
    private function match(string $msg, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (str_contains($msg, $keyword)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Retourne une réponse aléatoire parmi une liste.
     */
    private function getRandomResponse(array $responses): string
    {
        return $responses[array_rand($responses)];
    }
}
