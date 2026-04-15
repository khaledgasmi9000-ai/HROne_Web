<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/candidat')]
class CandidateSpaceController extends AbstractController
{
    #[Route('', name: 'app_candidate_dashboard')]
    public function offres(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CANDIDAT');

        return $this->render('candidate/offres.html.twig');
    }

    #[Route('/formations', name: 'app_candidate_formations')]
    public function formations(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CANDIDAT');

        return $this->render('candidate/formations.html.twig');
    }

    #[Route('/evenements', name: 'app_candidate_evenements')]
    public function evenements(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CANDIDAT');

        return $this->render('candidate/evenements.html.twig');
    }

    #[Route('/participations', name: 'app_candidate_participations')]
    public function participations(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CANDIDAT');

        return $this->render('candidate/participations.html.twig');
    }

    #[Route('/demande-conge', name: 'app_candidate_demande_conge')]
    public function demandeConge(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CANDIDAT');

        return $this->render('candidate/demande-conge.html.twig');
    }

    #[Route('/communaute', name: 'app_candidate_communaute')]
    public function communaute(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_CANDIDAT');

        return $this->render('candidate/communaute.html.twig');
    }
}
