<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ListeAttente;
use App\Entity\ParticipationEvenement;
use App\Form\ParticipationEvenementType;
use App\Repository\EvenementRepository;
use App\Service\EmailService;
use App\Service\EventQrCodeService;
use App\Service\ShadowUserService;
use Doctrine\ORM\EntityManagerInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Knp\Component\Pager\PaginatorInterface;

class FrontEvenementController extends AbstractController
{
    #[Route('/evenements', name: 'topnav_evenements', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepo, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search');
        $sort = $request->query->get('sort');

        $queryBuilder = $evenementRepo->createQueryBuilder('e')
            ->orderBy('e.ID_Evenement', 'DESC');

        if ($search) {
            $queryBuilder->andWhere('e.Titre LIKE :search OR e.Description LIKE :search OR e.Localisation LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($sort === 'date_asc') {
            $queryBuilder->orderBy('e.ID_Evenement', 'ASC');
        } elseif ($sort === 'titre') {
            $queryBuilder->orderBy('e.Titre', 'ASC');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('Topnavbar/evenements.html.twig', [
            'evenements' => $pagination,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/evenements/{id}', name: 'app_front_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $participation = new ParticipationEvenement();
        $form = $this->createForm(ParticipationEvenementType::class, $participation, [
            'action' => $this->generateUrl('app_front_evenement_inscrire', ['id' => $evenement->getIDEvenement()]),
            'activites_evenement' => $evenement->getActivites()->toArray(),
        ]);

        $nbInscrits = $em->getRepository(ParticipationEvenement::class)->count(['evenement' => $evenement]);
        $placesRestantes = $evenement->getNbMax() ? $evenement->getNbMax() - $nbInscrits : null;

        return $this->render('Topnavbar/evenement/show.html.twig', [
            'evenement' => $evenement,
            'inscriptionForm' => $form->createView(),
            'nbInscrits' => $nbInscrits,
            'placesRestantes' => $placesRestantes,
        ]);
    }

    #[Route('/evenements/{id}/inscription', name: 'app_front_evenement_inscrire', methods: ['POST'])]
    public function inscrire(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $em,
        EmailService $emailService,
        ShadowUserService $shadowUserService
    ): Response
    {
        $participation = new ParticipationEvenement();
        $form = $this->createForm(ParticipationEvenementType::class, $participation, [
            'activites_evenement' => $evenement->getActivites()->toArray(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $isAlreadyRegistered = $em->getRepository(ParticipationEvenement::class)
                ->findOneBy(['evenement' => $evenement, 'email' => $participation->getEmail()]);
            
            $isWaiting = $em->getRepository(ListeAttente::class)
                ->findOneBy(['evenement' => $evenement, 'email' => $participation->getEmail()]);
                
            if ($isAlreadyRegistered || $isWaiting) {
                $status = $isAlreadyRegistered ? "déjà inscrit(e)" : "déjà en liste d'attente";
                $this->addFlash('error', '⚠️ Cet email est ' . $status . ' pour cet événement !');
                return $this->redirectToRoute('app_front_evenement_show', ['id' => $evenement->getIDEvenement()]);
            }

            $nbInscrits = $em->getRepository(ParticipationEvenement::class)->count(['evenement' => $evenement]);
            
            if ($evenement->getNbMax() && $nbInscrits >= $evenement->getNbMax()) {
                $attente = new ListeAttente();
                $attenteRepo = $em->getRepository(ListeAttente::class);
                $nextId = $attenteRepo->createQueryBuilder('a')
                    ->select('MAX(a.ID_Attente)')
                    ->getQuery()
                    ->getSingleScalarResult();
                $attente->setIdAttente(($nextId ?? 0) + 1);
                $attente->setEvenement($evenement);
                // Utiliser la première activité de l'événement pour la liste d'attente
                if ($evenement->getActivites()->count() > 0) {
                    $attente->setIdActivite($evenement->getActivites()->first()->getIDActivite());
                }
                $attente->setNomComplet($participation->getNomComplet());
                $attente->setEmail($participation->getEmail());
                $attente->setDateDemande(new \DateTime());
                
                $em->persist($attente);
                $em->flush();

                try {
                    $emailService->sendWaitlistNotice($attente);
                } catch (\Exception $e) {
                    // Continue even if email fails
                }

                $this->addFlash('warning', '⏳ L\'événement est complet. Vous avez été ajouté(e) à la liste d\'attente !');
                return $this->redirectToRoute('app_front_evenement_show', ['id' => $evenement->getIDEvenement()]);
            }

            $participationRepo = $em->getRepository(ParticipationEvenement::class);
            $participantId = $shadowUserService->createShadowUser($participation->getEmail(), $participation->getNomComplet());
            
            $participation->setIdParticipant($participantId);
            $participation->setNumOrdreParticipation($participationRepo->getNextNumOrdre());
            $participation->setEvenement($evenement);
            
            // Si aucune activité n'est sélectionnée, utiliser la première activité de l'événement
            if (!$participation->getActivite() && $evenement->getActivites()->count() > 0) {
                $participation->setActivite($evenement->getActivites()->first());
            }
            
            $em->persist($participation);
            $em->flush();

            try {
                $emailService->sendConfirmation($participation);
                $this->addFlash('success', '🎉 Félicitations ' . $participation->getNomComplet() . ' ! Un email de confirmation vous a été envoyé.');
            } catch (\Exception $e) {
                $this->addFlash('success', '🎉 Inscription validée ! (Note: Problème technique temporaire pour l\'envoi de l\'email).');
            }

            return $this->redirectToRoute('app_front_evenement_show', ['id' => $evenement->getIDEvenement()]);
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Erreurs : ' . implode(' | ', $errors));
        }

        return $this->redirectToRoute('app_front_evenement_show', ['id' => $evenement->getIDEvenement()]);
    }

    /**
     * Affiche le résumé personnel d'une inscription (Ticket)
     */
    #[Route('/participation/{idParticipant}/{idEvenement}/{idActivite}/details', name: 'app_front_participation_details', methods: ['GET'])]
    public function participationDetails(int $idParticipant, int $idEvenement, int $idActivite, EntityManagerInterface $em): Response
    {
        $participation = $em->getRepository(ParticipationEvenement::class)->findOneBy([
            'ID_Participant' => $idParticipant,
            'evenement' => $idEvenement,
            'activite' => $idActivite
        ]);

        if (!$participation) {
            throw $this->createNotFoundException('Participation non trouvée');
        }

        return $this->render('Topnavbar/evenement/participation_details.html.twig', [
            'participation' => $participation,
        ]);
    }

    /**
     * Génère un QR code pour une participation
     */
    #[Route('/participation/{idParticipant}/{idEvenement}/{idActivite}/qr-code', name: 'app_front_participation_qr_code', methods: ['GET'])]
    public function participationQrCode(int $idParticipant, int $idEvenement, int $idActivite, EntityManagerInterface $em, EventQrCodeService $qrCodeService): Response
    {
        $participation = $em->getRepository(ParticipationEvenement::class)->findOneBy([
            'ID_Participant' => $idParticipant,
            'evenement' => $idEvenement,
            'activite' => $idActivite
        ]);

        if (!$participation) {
            throw $this->createNotFoundException('Participation non trouvée');
        }

        // Generate QR code data with participation details
        $qrData = sprintf(
            "HR One Event Ticket\nRef: #HR1-%d\nParticipant: %s\nEmail: %s\nEvent: %s\nActivity: %s",
            $participation->getIdParticipant(),
            $participation->getNomComplet(),
            $participation->getEmail(),
            $participation->getEvenement()->getTitre(),
            $participation->getActivite() ? $participation->getActivite()->getTitre() : 'N/A'
        );

        $result = $qrCodeService->buildParticipationQr($qrData);

        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => $result->getMimeType(),
        ]);
    }

    /**
     * Génère un ticket PDF via le bundle externe NucleosDompdf
     */
    #[Route('/participation/{idParticipant}/{idEvenement}/{idActivite}/pdf', name: 'app_front_participation_pdf', methods: ['GET'])]
    public function participationPdf(int $idParticipant, int $idEvenement, int $idActivite, EntityManagerInterface $em, DompdfWrapperInterface $dompdfWrapper): Response
    {
        $participation = $em->getRepository(ParticipationEvenement::class)->findOneBy([
            'ID_Participant' => $idParticipant,
            'evenement' => $idEvenement,
            'activite' => $idActivite
        ]);

        if (!$participation) {
            throw $this->createNotFoundException('Participation non trouvée');
        }

        $html = $this->renderView('Topnavbar/evenement/participation_pdf.html.twig', [
            'participation' => $participation,
        ]);

        return $dompdfWrapper->getStreamResponse($html, "ticket-hrone-" . $participation->getIdParticipant() . ".pdf");
    }
}
