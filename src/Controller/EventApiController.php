<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

class EventApiController extends AbstractController
{
    /**
     * Cette route permet d'exposer tes événements en format JSON.
     * C'est une véritable API REST créée par toi-même !
     * URL : /api/evenements
     */
    #[Route('/api/evenements', name: 'api_events_list', methods: ['GET'])]
    public function getEvents(EvenementRepository $repo): JsonResponse
    {
        $events = $repo->findAll();
        $data = [];

        foreach ($events as $event) {
            $data[] = [
                'id' => $event->getIDEvenement(),
                'titre' => $event->getTitre(),
                'description' => $event->getDescription(),
                'prix' => $event->getPrix(),
                'image' => $event->getImage(),
                'nbMax' => $event->getNbMax(),
                // On peut ajouter d'autres champs si besoin
            ];
        }

        // Renvoie une réponse JSON (Standard API REST)
        return new JsonResponse($data);
    }
}
