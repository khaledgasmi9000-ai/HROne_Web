<?php

// Déclaration du namespace : ce fichier appartient au dossier Controller
namespace App\Controller;

// On importe l'entité Activite
use App\Entity\Activite;
// On importe le formulaire ActiviteType qu'on a créé
use App\Form\ActiviteType;
// On importe le Repository pour récupérer les données de la table activite
use App\Repository\ActiviteRepository;
// EntityManagerInterface permet de persist (préparer) et flush (envoyer en BDD)
use Doctrine\ORM\EntityManagerInterface;
// AbstractController contient les méthodes utiles : render, redirectToRoute, addFlash...
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
// Request contient les données envoyées par le navigateur (POST, GET...)
use Symfony\Component\HttpFoundation\Request;
// Response est l'objet retourné par chaque méthode (la page HTML)
use Symfony\Component\HttpFoundation\Response;
// Route permet de définir l'URL qui déclenche chaque méthode
use Symfony\Component\Routing\Annotation\Route;

// Toutes les routes de ce controller commencent par /activite
#[Route('/activite')]
class ActiviteController extends AbstractController
{
    // =========================================================
    // LISTE : affiche toutes les activités
    // URL : GET /activite/
    // =========================================================
    #[Route('/', name: 'activite_index', methods: ['GET'])]
    public function index(ActiviteRepository $repo): Response
    {
        // On récupère toutes les activités depuis la base de données
        $activites = $repo->findAll();

        // On envoie la liste au template Twig pour l'afficher
        return $this->render('activite/index.html.twig', [
            'activites' => $activites,
        ]);
    }

    // =========================================================
    // CRÉER : affiche le formulaire et sauvegarde la nouvelle activité
    // URL : GET  /activite/new → affiche le formulaire vide
    //       POST /activite/new → traite et sauvegarde
    // =========================================================
    #[Route('/new', name: 'activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ActiviteRepository $activiteRepo): Response
    {
        // On crée un objet Activite vide
        $activite = new Activite();

        // On crée le formulaire lié à cet objet
        $form = $this->createForm(ActiviteType::class, $activite);

        // On remplit le formulaire avec les données POST envoyées
        $form->handleRequest($request);

        // Si le formulaire est soumis ET valide (contraintes respectées)
        if ($form->isSubmitted() && $form->isValid()) {

            // Attribution manuelle de l'ID Activite
            $activite->setID_Activite($activiteRepo->getNextId());

            // On prépare l'objet pour l'insertion en base de données
            $em->persist($activite);

            // On exécute la requête SQL INSERT
            $em->flush();

            // Message de confirmation
            $this->addFlash('success', 'Activité créée avec succès !');

            // On redirige vers la liste des activités
            return $this->redirectToRoute('activite_index');
        }

        // Si formulaire non soumis ou invalide → on affiche le formulaire
        return $this->render('activite/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    // =========================================================
    // AFFICHER : affiche le détail d'une activité
    // URL : GET /activite/{id}
    // Symfony trouve automatiquement l'activité par son id
    // =========================================================
    #[Route('/{id}', name: 'activite_show', methods: ['GET'])]
    public function show(Activite $activite): Response
    {
        // On envoie l'activité trouvée au template Twig
        return $this->render('activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    // =========================================================
    // MODIFIER : affiche le formulaire pré-rempli et sauvegarde
    // URL : GET  /activite/{id}/edit → affiche formulaire rempli
    //       POST /activite/{id}/edit → enregistre les modifications
    // =========================================================
    #[Route('/{id}/edit', name: 'activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activite $activite, EntityManagerInterface $em): Response
    {
        // On crée le formulaire avec les données de l'activité existante
        $form = $this->createForm(ActiviteType::class, $activite);

        // On remplit le formulaire avec les nouvelles données POST
        $form->handleRequest($request);

        // Si le formulaire est soumis ET valide
        if ($form->isSubmitted() && $form->isValid()) {

            // Pas besoin de persist() car l'objet existe déjà en BDD
            // flush() exécute la requête SQL UPDATE
            $em->flush();

            $this->addFlash('success', 'Activité modifiée avec succès !');

            // On redirige vers la liste
            return $this->redirectToRoute('activite_index');
        }

        // Sinon on affiche le formulaire pré-rempli
        return $this->render('activite/edit.html.twig', [
            'activite' => $activite,
            'form'     => $form->createView(),
        ]);
    }

    // =========================================================
    // SUPPRIMER : supprime une activité
    // URL : POST /activite/{id}/delete
    // =========================================================
    #[Route('/{id}/delete', name: 'activite_delete', methods: ['POST'])]
    public function delete(Request $request, Activite $activite, EntityManagerInterface $em): Response
    {
        // On vérifie le token CSRF pour sécuriser contre les attaques
        if ($this->isCsrfTokenValid('delete' . $activite->getID_Activite(), $request->request->get('_token'))) {

            // On supprime l'objet de la base de données
            $em->remove($activite);

            // On exécute la requête SQL DELETE
            $em->flush();

            $this->addFlash('success', 'Activité supprimée avec succès !');
        }

        // On redirige toujours vers la liste
        return $this->redirectToRoute('activite_index');
    }
}
