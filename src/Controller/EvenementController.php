<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Ordre;
use App\Entity\ParticipationEvenement;
use App\Entity\ListeAttente;
use App\Entity\Activite;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use App\Service\EmailService;
use App\Service\ShadowUserService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rh/evenements')]
class EvenementController extends AbstractController
{
    #[Route('/', name: 'app_rh_evenement_index', methods: ['GET'])]
    public function index(EvenementRepository $repo): Response
    {
        $evenements = $repo->findAll();

        return $this->render('navbarRH/gestion-evenements.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    #[Route('/new', name: 'app_rh_evenement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $em,
        EvenementRepository $evenementRepo,
        \App\Repository\OrdreRepository $ordreRepo
    ): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            
            $dateDebutData = $form->get('dateDebut')->getData();
            $dateFinData   = $form->get('dateFin')->getData();
            $now           = new \DateTime();

            $ordreDebut = new \App\Entity\Ordre();
            $ordreDebut->setNum_Ordre($ordreRepo->getNextId()); 
            $ordreDebut->setAAAA((int)$dateDebutData->format('Y'));
            $ordreDebut->setMM((int)$dateDebutData->format('m'));
            $ordreDebut->setJJ((int)$dateDebutData->format('d'));
            $ordreDebut->setHH((int)$dateDebutData->format('H'));
            $ordreDebut->setMN((int)$dateDebutData->format('i'));
            $ordreDebut->setSS(0);
            $em->persist($ordreDebut);

            $ordreFin = new \App\Entity\Ordre();
            $ordreFin->setNum_Ordre($ordreRepo->getNextId() + 1);
            $ordreFin->setAAAA((int)$dateFinData->format('Y'));
            $ordreFin->setMM((int)$dateFinData->format('m'));
            $ordreFin->setJJ((int)$dateFinData->format('d'));
            $ordreFin->setHH((int)$dateFinData->format('H'));
            $ordreFin->setMN((int)$dateFinData->format('i'));
            $ordreFin->setSS(0);
            $em->persist($ordreFin);

            $ordreCrea = new \App\Entity\Ordre();
            $ordreCrea->setNum_Ordre($ordreRepo->getNextId() + 2);
            $ordreCrea->setAAAA((int)$now->format('Y'));
            $ordreCrea->setMM((int)$now->format('m'));
            $ordreCrea->setJJ((int)$now->format('d'));
            $ordreCrea->setHH((int)$now->format('H'));
            $ordreCrea->setMN((int)$now->format('i'));
            $ordreCrea->setSS(0);
            $em->persist($ordreCrea);

            $evenement->setOrdreDebut($ordreDebut);
            $evenement->setOrdreFin($ordreFin);
            $evenement->setOrdreCreation($ordreCrea);
            $evenement->setID_Evenement($evenementRepo->getNextId());

            $selectedActivites = $form->get('activites')->getData();
            foreach ($selectedActivites as $activite) {
                $detail = new \App\Entity\DetailEvenement();
                $detail->setEvenement($evenement);
                $detail->setActivite($activite);
                $detail->setOrdreDebut($ordreDebut);
                $detail->setOrdreFin($ordreFin);
                $em->persist($detail);
                $evenement->addDetail($detail);
            }

            $em->persist($evenement);
            $em->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès !');
            return $this->redirectToRoute('app_rh_evenement_index');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Erreurs : ' . implode(' | ', $errors));
        }

        return $this->render('navbarRH/evenement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_rh_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('navbarRH/evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/inscriptions', name: 'app_rh_evenement_inscriptions', methods: ['GET'])]
    public function inscriptions(
        Evenement $evenement, 
        EntityManagerInterface $em,
        \App\Service\WaitlistPromotionService $promotionService
    ): Response
    {
        // Automatically promote people from waiting list to fill empty spots
        $promoted = $promotionService->promoteFromWaitlist($evenement);
        
        if ($promoted > 0) {
            $this->addFlash('success', sprintf(
                '✅ %d personne(s) de la liste d\'attente ont été automatiquement inscrites !',
                $promoted
            ));
        }

        $acceptes = $em->getRepository(\App\Entity\ParticipationEvenement::class)->findBy(['evenement' => $evenement]);
        $enAttente = $em->getRepository(\App\Entity\ListeAttente::class)->findBy(['evenement' => $evenement]);

        return $this->render('navbarRH/evenement/inscriptions.html.twig', [
            'evenement' => $evenement,
            'acceptes' => $acceptes,
            'en_attente' => $enAttente,
        ]);
    }
    
    #[Route('/{id}/edit', name: 'app_rh_evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);

        if ($oDebut = $evenement->getOrdreDebut()) {
            $dateStr = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
                $oDebut->getAAAA(), $oDebut->getMM(), $oDebut->getJJ(), 
                $oDebut->getHH(), $oDebut->getMN(), $oDebut->getSS());
            $form->get('dateDebut')->setData(new \DateTime($dateStr));
        }
        if ($oFin = $evenement->getOrdreFin()) {
            $dateStr = sprintf('%04d-%02d-%02d %02d:%02d:%02d', 
                $oFin->getAAAA(), $oFin->getMM(), $oFin->getJJ(), 
                $oFin->getHH(), $oFin->getMN(), $oFin->getSS());
            $form->get('dateFin')->setData(new \DateTime($dateStr));
        }

        $form->get('activites')->setData($evenement->getActivites());
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $dateDebutData = $form->get('dateDebut')->getData();
            $dateFinData = $form->get('dateFin')->getData();

            if ($dateDebutData) {
                $ordreDebut = $evenement->getOrdreDebut() ?? new Ordre();
                $ordreDebut->setAAAA((int)$dateDebutData->format('Y'));
                $ordreDebut->setMM((int)$dateDebutData->format('m'));
                $ordreDebut->setJJ((int)$dateDebutData->format('d'));
                $ordreDebut->setHH((int)$dateDebutData->format('H'));
                $ordreDebut->setMN((int)$dateDebutData->format('i'));
                $ordreDebut->setSS((int)$dateDebutData->format('s'));
                $em->persist($ordreDebut);
                $evenement->setOrdreDebut($ordreDebut);
            }

            if ($dateFinData) {
                $ordreFin = $evenement->getOrdreFin() ?? new Ordre();
                $ordreFin->setAAAA((int)$dateFinData->format('Y'));
                $ordreFin->setMM((int)$dateFinData->format('m'));
                $ordreFin->setJJ((int)$dateFinData->format('d'));
                $ordreFin->setHH((int)$dateFinData->format('H'));
                $ordreFin->setMN((int)$dateFinData->format('i'));
                $ordreFin->setSS((int)$dateFinData->format('s'));
                $em->persist($ordreFin);
                $evenement->setOrdreFin($ordreFin);
            }

            foreach ($evenement->getDetails() as $oldDetail) {
                $em->remove($oldDetail);
            }
            $em->flush();

            $selectedActivites = $form->get('activites')->getData();
            foreach ($selectedActivites as $activite) {
                $detail = new \App\Entity\DetailEvenement();
                $detail->setEvenement($evenement);
                $detail->setActivite($activite);
                $detail->setOrdreDebut($evenement->getOrdreDebut());
                $detail->setOrdreFin($evenement->getOrdreFin());
                $em->persist($detail);
                $evenement->addDetail($detail);
            }

            $em->flush();

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('app_rh_evenement_index');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Erreurs de validation : ' . implode(' | ', $errors));
        }

        return $this->render('navbarRH/evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form'      => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'app_rh_evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $evenement->getID_Evenement(), $request->request->get('_token'))) {
            $em->remove($evenement);
            $em->flush();

            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        return $this->redirectToRoute('app_rh_evenement_index');
    }

    #[Route('/participation/{idParticipant}/{idEvenement}/{idActivite}/delete', name: 'app_rh_evenement_participation_delete', methods: ['POST'])]
    public function deleteParticipation(
        int $idParticipant,
        int $idEvenement,
        int $idActivite,
        EntityManagerInterface $em,
        \App\Service\WaitlistPromotionService $promotionService
    ): Response
    {
        $participation = $em->getRepository(ParticipationEvenement::class)->findOneBy([
            'ID_Participant' => $idParticipant,
            'evenement' => $idEvenement,
            'activite' => $idActivite
        ]);

        if (!$participation) {
            throw $this->createNotFoundException('Participation non trouvée');
        }

        $evenement = $participation->getEvenement();
        $em->remove($participation);
        $em->flush();

        // Automatically promote from waiting list
        $promoted = $promotionService->promoteFromWaitlist($evenement);

        if ($promoted > 0) {
            $this->addFlash('success', sprintf(
                'Participant supprimé. %d personne(s) de la liste d\'attente ont été automatiquement inscrites !',
                $promoted
            ));
        } else {
            $this->addFlash('success', 'Participant supprimé avec succès.');
        }

        return $this->redirectToRoute('app_rh_evenement_inscriptions', ['id' => $evenement->getIDEvenement()]);
    }

    #[Route('/attente/{id}/delete', name: 'app_rh_evenement_attente_delete', methods: ['POST'])]
    public function deleteWaitlist(ListeAttente $attente, EntityManagerInterface $em): Response
    {
        $evenement = $attente->getEvenement();
        $em->remove($attente);
        $em->flush();

        $this->addFlash('success', 'Participant retiré de la liste d\'attente.');

        return $this->redirectToRoute('app_rh_evenement_inscriptions', ['id' => $evenement->getIDEvenement()]);
    }
}
