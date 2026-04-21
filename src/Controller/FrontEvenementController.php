<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\ListeAttente;
use App\Entity\ParticipationEvenement;
use App\Form\ParticipationEvenementType;
use App\Repository\EvenementRepository;
use App\Repository\ListeAttenteRepository;
use App\Repository\ParticipationEvenementRepository;
use App\Service\EmailService;
use App\Service\ShadowUserService;
use Doctrine\ORM\EntityManagerInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/front/evenement')]
class FrontEvenementController extends AbstractController
{
    /**
     * Affiche la liste des événements côté utilisateur (Front-End)
     * -------------------------------------------------------------------------
     * RÔLE : C'est la vitrine de votre site. 
     * Elle gère la recherche, le tri et la pagination (affichage par blocs de 4).
     * -------------------------------------------------------------------------
     */
    #[Route('/', name: 'app_front_evenement_index', methods: ['GET'])]
    public function index(Request $request, EvenementRepository $evenementRepo, PaginatorInterface $paginator): Response
    {
        // 1. Récupération des paramètres de recherche et de tri depuis l'URL (?search=...&sort=...)
        $search = $request->query->get('search');
        $sort = $request->query->get('sort');

        // 2. Appel au Repository pour construire la requête SQL personnalisée
        $query = $evenementRepo->findBySearchAndSort($search, $sort);

        // 3. Utilisation du Bundle KnpPaginator pour ne pas charger 1000 événements d'un coup
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            4 // Limite fixée à 4 événements par page
        );

        return $this->render('front/evenement/index.html.twig', [
            'evenements' => $pagination,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    /**
     * Affiche le détail d'un événement + formulaire d'inscription
     */
    #[Route('/{id}', name: 'app_front_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement, EntityManagerInterface $em): Response
    {
        $participation = new ParticipationEvenement();
        $form = $this->createForm(ParticipationEvenementType::class, $participation, [
            'action' => $this->generateUrl('app_front_evenement_inscrire', ['id' => $evenement->getIDEvenement()]),
            'activites_evenement' => $evenement->getActivites()->toArray(),
        ]);

        $nbInscrits = $em->getRepository(ParticipationEvenement::class)->count(['evenement' => $evenement]);
        $placesRestantes = $evenement->getNbMax() ? $evenement->getNbMax() - $nbInscrits : null;

        return $this->render('front/evenement/show.html.twig', [
            'evenement' => $evenement,
            'inscriptionForm' => $form->createView(),
            'nbInscrits' => $nbInscrits,
            'placesRestantes' => $placesRestantes,
        ]);
    }

    /**
     * Traite le formulaire d'inscription (POST)
     */
    #[Route('/{id}/inscription', name: 'app_front_evenement_inscrire', methods: ['POST'])]
    public function inscrire(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $em,
        EmailService $emailService,
        ShadowUserService $shadowUserService
    ): Response {
        $participation = new ParticipationEvenement();
        $form = $this->createForm(ParticipationEvenementType::class, $participation, [
            'activites_evenement' => $evenement->getActivites()->toArray(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Anti-doublon basé sur email (Participation ET Liste d'attente)
            $isAlreadyRegistered = $em->getRepository(ParticipationEvenement::class)
                ->findOneBy(['evenement' => $evenement, 'email' => $participation->getEmail()]);

            $isWaiting = $em->getRepository(ListeAttente::class)
                ->findOneBy(['evenement' => $evenement, 'email' => $participation->getEmail()]);

            if ($isAlreadyRegistered || $isWaiting) {
                $status = $isAlreadyRegistered ? "déjà inscrit(e)" : "déjà en liste d'attente";
                $this->addFlash('error', '⚠️ Cet email est ' . $status . ' pour cet événement !');
                return $this->redirectToRoute('app_front_evenement_show', ['id' => $evenement->getIDEvenement()]);
            }

            // VÉRIFICATION 2 : Si l'événement est complet (Gestion de la liste d'attente)
            $nbInscrits = $em->getRepository(ParticipationEvenement::class)->count(['evenement' => $evenement]);

            if ($evenement->getNbMax() && $nbInscrits >= $evenement->getNbMax()) {
                // LOGIQUE AVANCÉE : On bascule l'inscription vers la Liste d'Attente
                $attente = new ListeAttente();
                $attente->setIDAttente($em->getRepository(ListeAttente::class)->getNextId());
                $attente->setEvenement($evenement);
                foreach ($participation->getActivites() as $act) {
                    $attente->addActivite($act);
                }
                $attente->setNomComplet($participation->getNomComplet());
                $attente->setEmail($participation->getEmail());
                $attente->setDateDemande(new \DateTime());

                $em->persist($attente);
                $em->flush();

                // Notification Email automatique (Grâce à votre EmailService)
                try {
                    $emailService->sendWaitlistNotice($attente);
                } catch (\Exception $e) {
                    $this->addFlash('warning', 'Note: L\'email de notification n\'a pas pu être envoyé, mais vous êtes bien sur la liste.');
                }

                $this->addFlash('warning', '⏳ L\'événement est complet. Vous avez été ajouté(e) à la liste d\'attente !');
                return $this->redirectToRoute('app_front_evenement_show', ['id' => $evenement->getIDEvenement()]);
            }

            // Sauvegarde dans Participation
            $participationRepo = $em->getRepository(ParticipationEvenement::class);
            $participantId = $shadowUserService->createShadowUser($participation->getEmail(), $participation->getNomComplet());

            $participation->setIdParticipant($participantId);
            $participation->setNumOrdreParticipation($participationRepo->getNextNumOrdre());
            $participation->setEvenement($evenement);
            $em->persist($participation);
            $em->flush();

            // Notification Email (Sécurisée)
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
    #[Route('/participation/{id}/details', name: 'app_front_participation_details', methods: ['GET'])]
    public function participationDetails(ParticipationEvenement $participation): Response
    {
        return $this->render('front/evenement/participation_details.html.twig', [
            'participation' => $participation,
        ]);
    }
    /**
     * Génère un ticket PDF via le bundle externe NucleosDompdf
     */
    #[Route('/participation/{id}/pdf', name: 'app_front_participation_pdf', methods: ['GET'])]
    public function participationPdf(ParticipationEvenement $participation, DompdfWrapperInterface $dompdfWrapper): Response
    {
        $html = $this->renderView('front/evenement/participation_pdf.html.twig', [
            'participation' => $participation,
        ]);

        return $dompdfWrapper->getStreamResponse($html, "ticket-hrone-" . $participation->getIDParticipant() . ".pdf");
    }

}
