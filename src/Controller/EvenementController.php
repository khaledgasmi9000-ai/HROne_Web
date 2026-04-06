<?php

// Déclaration du namespace : ce fichier appartient au dossier Controller
namespace App\Controller;

// On importe l'entité Evenement
use App\Entity\Evenement;
use App\Entity\Ordre;
// On importe le formulaire EvenementType qu'on a créé
use App\Form\EvenementType;
// On importe le Repository pour récupérer les données de la table evenement
use App\Repository\EvenementRepository;
// EntityManagerInterface permet de faire persist (sauvegarder) et flush (envoyer en BDD)
use Doctrine\ORM\EntityManagerInterface;
// AbstractController contient des méthodes utiles : render, redirectToRoute, addFlash...
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Request contient toutes les données envoyées par le navigateur (formulaire POST, GET...)
use Symfony\Component\HttpFoundation\Request;
// Response est l'objet retourné par chaque méthode du controller (la page HTML)
use Symfony\Component\HttpFoundation\Response;
// Route permet de définir l'URL qui appelle chaque méthode
use Symfony\Component\Routing\Annotation\Route;

// Toutes les routes de ce controller commencent par /evenement
#[Route('/evenement')]
class EvenementController extends AbstractController
{
    // =========================================================
    // LISTE : affiche tous les événements
    // URL : GET /evenement/
    // =========================================================
    #[Route('/', name: 'evenement_index', methods: ['GET'])]
    public function index(EvenementRepository $repo): Response
    {
        // On récupère tous les événements depuis la base de données
        $evenements = $repo->findAll();

        // On envoie la liste au template Twig pour l'afficher
        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
        ]);
    }

    // =========================================================
    // CRÉER : affiche le formulaire et sauvegarde le nouvel événement
    // URL : GET /evenement/new  → affiche le formulaire vide
    //       POST /evenement/new → traite et sauvegarde
    // =========================================================
    #[Route('/new', name: 'evenement_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request, 
        EntityManagerInterface $em,
        EvenementRepository $evenementRepo,
        \App\Repository\OrdreRepository $ordreRepo
    ): Response
    {
        // On crée un objet Evenement vide
        $evenement = new Evenement();

        // On crée le formulaire lié à cet objet
        $form = $this->createForm(EvenementType::class, $evenement);

        // On remplit le formulaire avec les données envoyées
        $form->handleRequest($request);

        // Étape 1 : On vérifie si le formulaire a été envoyé et s'il est valide
        if ($form->isSubmitted() && $form->isValid()) {
            
            // Étape 2 : Récupération des dates saisies
            $dateDebutData = $form->get('dateDebut')->getData();
            $dateFinData   = $form->get('dateFin')->getData();
            $now           = new \DateTime();

            // Étape 3 : Création manuelle des objets "Ordre" (Dates)
            // -- Date de Début --
            $ordreDebut = new \App\Entity\Ordre();
            $ordreDebut->setNum_Ordre($ordreRepo->getNextId()); 
            $ordreDebut->setAAAA((int)$dateDebutData->format('Y'));
            $ordreDebut->setMM((int)$dateDebutData->format('m'));
            $ordreDebut->setJJ((int)$dateDebutData->format('d'));
            $ordreDebut->setHH((int)$dateDebutData->format('H'));
            $ordreDebut->setMN((int)$dateDebutData->format('i'));
            $ordreDebut->setSS(0);
            $em->persist($ordreDebut);

            // -- Date de Fin --
            $ordreFin = new \App\Entity\Ordre();
            $ordreFin->setNum_Ordre($ordreRepo->getNextId() + 1);
            $ordreFin->setAAAA((int)$dateFinData->format('Y'));
            $ordreFin->setMM((int)$dateFinData->format('m'));
            $ordreFin->setJJ((int)$dateFinData->format('d'));
            $ordreFin->setHH((int)$dateFinData->format('H'));
            $ordreFin->setMN((int)$dateFinData->format('i'));
            $ordreFin->setSS(0);
            $em->persist($ordreFin);

            // -- Date de Création --
            $ordreCrea = new \App\Entity\Ordre();
            $ordreCrea->setNum_Ordre($ordreRepo->getNextId() + 2);
            $ordreCrea->setAAAA((int)$now->format('Y'));
            $ordreCrea->setMM((int)$now->format('m'));
            $ordreCrea->setJJ((int)$now->format('d'));
            $ordreCrea->setHH((int)$now->format('H'));
            $ordreCrea->setMN((int)$now->format('i'));
            $ordreCrea->setSS(0);
            $em->persist($ordreCrea);

            // Étape 4 : On lie les dates à l'Événement
            $evenement->setOrdreDebut($ordreDebut);
            $evenement->setOrdreFin($ordreFin);
            $evenement->setOrdreCreation($ordreCrea);
            $evenement->setID_Evenement($evenementRepo->getNextId());

            // Étape 5 : Liaison avec les Activités (Table detail_evenement)
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

            // Étape 6 : Enregistrement final (COMMIT)
            $em->persist($evenement);
            $em->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès !');
            return $this->redirectToRoute('evenement_index');
        }

        // Diagnostic : si le formulaire est refusé, on affiche les erreurs
        if ($form->isSubmitted() && !$form->isValid()) {
            $errors = [];
            foreach ($form->getErrors(true) as $error) {
                $errors[] = $error->getMessage();
            }
            $this->addFlash('error', 'Erreurs : ' . implode(' | ', $errors));
        }

        return $this->render('evenement/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // =========================================================
    // AFFICHER : affiche le détail d'un événement
    // URL : GET /evenement/{id}
    // Symfony trouve automatiquement l'événement par son id
    // =========================================================
    #[Route('/{id}', name: 'evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        // On envoie l'événement trouvé au template Twig
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    // =========================================================
    // MODIFIER : affiche le formulaire pré-rempli et sauvegarde
    // URL : GET  /evenement/{id}/edit → affiche formulaire rempli
    //       POST /evenement/{id}/edit → enregistre les modifications
    // =========================================================
    #[Route('/{id}/edit', name: 'evenement_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        // On crée le formulaire avec les données de l'événement existant
        $form = $this->createForm(EvenementType::class, $evenement);

        // ==========================================
        // Récupération de l'Ordre existant pour les dates
        // ==========================================
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

        // On remplit le formulaire avec les nouvelles données POST
        $form->handleRequest($request);

        // Si le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {

            // ==========================================
            // Modification de l'Ordre pour les dates
            // ==========================================
            $dateDebutData = $form->get('dateDebut')->getData();
            $dateFinData = $form->get('dateFin')->getData();

            if ($dateDebutData) {
                // Si l'ordre existe on le modifie, sinon on le crée
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

            // Pas besoin de persist() car l'objet existe déjà en BDD
            // flush() exécute la requête SQL UPDATE
            $em->flush();

            $this->addFlash('success', 'Événement modifié avec succès !');

            // On redirige vers la liste
            return $this->redirectToRoute('evenement_index');
        }

        // Sinon on affiche le formulaire pré-rempli
        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form'      => $form->createView(),
        ]);
    }

    // =========================================================
    // SUPPRIMER : supprime un événement
    // URL : POST /evenement/{id}/delete
    // On utilise POST (pas DELETE) car HTML ne supporte que GET/POST
    // =========================================================
    #[Route('/{id}/delete', name: 'evenement_delete', methods: ['POST'])]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $em): Response
    {
        // On vérifie le token CSRF pour sécuriser contre les attaques
        if ($this->isCsrfTokenValid('delete' . $evenement->getID_Evenement(), $request->request->get('_token'))) {

            // On supprime l'objet de la base de données
            $em->remove($evenement);

            // On exécute la requête SQL DELETE
            $em->flush();

            $this->addFlash('success', 'Événement supprimé avec succès !');
        }

        // On redirige toujours vers la liste
        return $this->redirectToRoute('evenement_index');
    }
}
