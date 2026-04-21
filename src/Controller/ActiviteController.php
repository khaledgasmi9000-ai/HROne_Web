<?php

namespace App\Controller;

use App\Entity\Activite;
use App\Form\ActiviteType;
use App\Repository\ActiviteRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rh/activites')]
class ActiviteController extends AbstractController
{
    #[Route('/', name: 'rh_activites', methods: ['GET'])]
    public function index(ActiviteRepository $repo): Response
    {
        $activites = $repo->findAll();

        return $this->render('navbarRH/activite/index.html.twig', [
            'activites' => $activites,
        ]);
    }

    #[Route('/new', name: 'rh_activite_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, ActiviteRepository $activiteRepo): Response
    {
        $activite = new Activite();

        $form = $this->createForm(ActiviteType::class, $activite);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $activite->setID_Activite($activiteRepo->getNextId());

            $em->persist($activite);
            $em->flush();

            $this->addFlash('success', 'Activité créée avec succès !');

            return $this->redirectToRoute('rh_activites');
        }

        return $this->render('navbarRH/activite/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'rh_activite_show', methods: ['GET'])]
    public function show(Activite $activite): Response
    {
        return $this->render('navbarRH/activite/show.html.twig', [
            'activite' => $activite,
        ]);
    }

    #[Route('/{id}/edit', name: 'rh_activite_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Activite $activite, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ActiviteType::class, $activite);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', 'Activité modifiée avec succès !');

            return $this->redirectToRoute('rh_activites');
        }

        return $this->render('navbarRH/activite/edit.html.twig', [
            'activite' => $activite,
            'form'     => $form->createView(),
        ]);
    }

    #[Route('/{id}/delete', name: 'rh_activite_delete', methods: ['POST'])]
    public function delete(Request $request, Activite $activite, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $activite->getID_Activite(), $request->request->get('_token'))) {
            $em->remove($activite);
            $em->flush();

            $this->addFlash('success', 'Activité supprimée avec succès !');
        }

        return $this->redirectToRoute('rh_activites');
    }
}
