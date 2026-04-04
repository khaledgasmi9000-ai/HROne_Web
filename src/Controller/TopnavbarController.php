<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TopnavbarController extends AbstractController
{
    #[Route('/top/offres', name: 'topnav_offres')]
    public function offres(): Response
    {
        return $this->render('OffresEmplois/OffresEmplois.html.twig');
    }

    #[Route('/top/formations', name: 'topnav_formations')]
    public function formations(): Response
    {
        return $this->render('Topnavbar/formations.html.twig');
    }

    #[Route('/top/evenements', name: 'topnav_evenements')]
    public function evenements(): Response
    {
        return $this->render('Topnavbar/evenements.html.twig');
    }

    #[Route('/top/participations', name: 'topnav_participations')]
    public function participations(): Response
    {
        return $this->render('Topnavbar/participations.html.twig');
    }

    #[Route('/top/demande-conge', name: 'topnav_demande_conge')]
    public function demandeConge(): Response
    {
        return $this->render('Topnavbar/demande-conge.html.twig');
    }

    #[Route('/top/communaute', name: 'topnav_communaute')]
    public function communaute(): Response
    {
        return $this->render('Topnavbar/communaute.html.twig');
    }

    #[Route('/top/mes-candidatures', name: 'topnav_mes_candidatures')]
    public function mesCandidatures(): Response
    {
        return $this->render('Topnavbar/mes-candidatures.html.twig');
    }
}
