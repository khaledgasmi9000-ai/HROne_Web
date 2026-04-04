<?php

namespace App\Controller;

use App\Repository\TypeBackgroundEtudeRepository;
use App\Repository\TypeCompetenceRepository;
use App\Repository\TypeContratRepository;
use App\Repository\TypeLangueRepository;
use App\Repository\TypeNiveauEtudeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NavbarRHController extends AbstractController
{
    #[Route('/', name: 'rh_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('rh_users');
    }

    #[Route('/rh/users', name: 'rh_users')]
    public function users(): Response
    {
        return $this->render('navbarRH/navbarRH.html.twig');
    }

    #[Route('/rh/historique-actions', name: 'rh_history')]
    public function history(): Response
    {
        return $this->render('navbarRH/historique-actions.html.twig');
    }

    #[Route('/rh/historique-des-actions', name: 'rh_history_des')]
    public function historyDes(): Response
    {
        return $this->redirectToRoute('rh_history');
    }

    #[Route('/rh/activity-watch', name: 'rh_activity_watch')]
    public function activityWatch(): Response
    {
        return $this->render('navbarRH/activity-watch.html.twig');
    }

    #[Route('/rh/gestion-conges', name: 'rh_conges')]
    public function conges(): Response
    {
        return $this->render('navbarRH/gestion-conges.html.twig');
    }

    #[Route('/rh/gestion-administrative', name: 'rh_admin')]
    public function administrative(): Response
    {
        return $this->render('navbarRH/gestion-administrative.html.twig');
    }

    #[Route('/rh/gestion-outils', name: 'rh_outils')]
    public function outils(): Response
    {
        return $this->render('navbarRH/gestion-outils.html.twig');
    }

    #[Route('/rh/gestion-des-outils', name: 'rh_outils_des')]
    public function outilsDes(): Response
    {
        return $this->redirectToRoute('rh_outils');
    }

    #[Route('/rh/gestion-entretiens', name: 'rh_entretiens')]
    public function entretiens(): Response
    {
        return $this->render('GestionEntretiensRH/gestion-entretiens.html.twig');
    }

    #[Route('/rh/gestion-des-entretiens', name: 'rh_entretiens_des')]
    public function entretiensDes(): Response
    {
        return $this->redirectToRoute('rh_entretiens');
    }

    #[Route('/rh/gestion-offres', name: 'rh_offres')]
    public function offres(
        TypeContratRepository $typeContratRepository,
        TypeNiveauEtudeRepository $typeNiveauEtudeRepository,
        TypeCompetenceRepository $typeCompetenceRepository,
        TypeLangueRepository $typeLangueRepository,
        TypeBackgroundEtudeRepository $typeBackgroundEtudeRepository
    ): Response
    {
        return $this->render('GestionDesOffresRH/gestion-offres.html.twig', [
            'typeContrats' => $typeContratRepository->findBy([], ['Description_Contrat' => 'ASC']),
            'typeNiveauxEtude' => $typeNiveauEtudeRepository->findBy([], ['Description_Type_Etude' => 'ASC']),
            'typeCompetences' => $typeCompetenceRepository->findBy([], ['Description_Competence' => 'ASC']),
            'typeLangues' => $typeLangueRepository->findBy([], ['Description_Langue' => 'ASC']),
            'typeBackgroundEtudes' => $typeBackgroundEtudeRepository->findBy([], ['Description_Type_Background_Etude' => 'ASC']),
        ]);
    }

    #[Route('/rh/gestion-des-offres', name: 'rh_offres_des')]
    public function offresDes(): Response
    {
        return $this->redirectToRoute('rh_offres');
    }

    #[Route('/rh/gestion-evenements', name: 'rh_evenements')]
    public function evenements(): Response
    {
        return $this->render('navbarRH/gestion-evenements.html.twig');
    }

    #[Route('/rh/gestion-des-evenements', name: 'rh_evenements_des')]
    public function evenementsDes(): Response
    {
        return $this->redirectToRoute('rh_evenements');
    }

    #[Route('/rh/gestion-formations', name: 'rh_formations')]
    public function formations(): Response
    {
        return $this->render('navbarRH/gestion-formations.html.twig');
    }

    #[Route('/rh/gestion-des-formations', name: 'rh_formations_des')]
    public function formationsDes(): Response
    {
        return $this->redirectToRoute('rh_formations');
    }
}
