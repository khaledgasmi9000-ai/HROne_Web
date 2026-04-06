<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/front/evenement')]
class FrontEvenementController extends AbstractController
{
    /**
     * Affiche la liste des événements côté utilisateur (Front-End)
     */
    #[Route('/', name: 'app_front_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepo): Response
    {
        // 1. Récupération des critères de recherche et de tri depuis l'URL
        $search = $request->query->get('search');
        $sort = $request->query->get('sort');

        // 2. Appel de notre méthode personnalisée dans le repository
        return $this->render('front/evenement/index.html.twig', [
            'evenements' => $evenementRepo->findBySearchAndSort($search, $sort),
            'search' => $search, // On renvoie la recherche pour garder le texte dans le champ
            'sort' => $sort,     // On renvoie le tri pour garder la sélection
        ]);
    }

    /**
     * Affiche le détail d'un événement précis côté (Front-End)
     */
    #[Route('/{id}', name: 'app_front_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('front/evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }
}
