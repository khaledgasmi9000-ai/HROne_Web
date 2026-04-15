<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Ordre;
use App\Form\RegistrationFormType;
use App\Form\RegistrationRhType;
use App\Repository\EntrepriseRepository;
use App\Repository\OrdreRepository;
use App\Repository\ProfilRepository;
use App\Entity\Entreprise;
use App\Service\RegistrationEmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\FormError;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        EntrepriseRepository $entrepriseRepository,
        ProfilRepository $profilRepository,
        OrdreRepository $ordreRepository,
        UserPasswordHasherInterface $passwordHasher,
        RegistrationEmailVerifier $registrationEmailVerifier,
    ): Response {
        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($registrationEmailVerifier->validateForCandidate($user->getEmail()) as $error) {
                $form->get('email')->addError(new FormError($error));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $profilRepository->find(1);

            if ($profil === null) {
                throw $this->createNotFoundException('Le profil candidat est introuvable.');
            }

            $publicEntreprise = $entrepriseRepository->find(1);

            if ($publicEntreprise === null) {
                $publicEntreprise = new Entreprise();
                $publicEntreprise->setID_Entreprise(1);
                $publicEntreprise->setNomEntreprise('PUBLIC');
                $publicEntreprise->setReference('PUBLIC');
                $entityManager->persist($publicEntreprise);
            }

            $user->setEntreprise($publicEntreprise);
            $user->setProfil($profil);
            $user->setIsActive(true);
            $user->setFirstLogin(1);
            $user->setOrdre($this->createSignInOrdre($ordreRepository->getNextOrdreNumber()));
            $user->setMotPasse($passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));

            $entityManager->persist($user->getOrdre());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte candidat a ete cree avec succes.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/register/rh', name: 'app_register_rh')]
    public function registerRh(
        Request $request,
        EntityManagerInterface $entityManager,
        ProfilRepository $profilRepository,
        OrdreRepository $ordreRepository,
        UserPasswordHasherInterface $passwordHasher,
        RegistrationEmailVerifier $registrationEmailVerifier,
    ): Response {
        $user = new Utilisateur();
        $user->setEntreprise(new Entreprise());
        $form = $this->createForm(RegistrationRhType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            foreach ($registrationEmailVerifier->validateForRh($user->getEmail()) as $error) {
                $form->get('Email')->addError(new FormError($error));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $profilRepository->find(2);

            if ($profil === null) {
                throw $this->createNotFoundException('Le profil RH est introuvable.');
            }

            $user->setProfil($profil);
            $user->setIsActive(true);
            $user->setFirstLogin(1);
            $user->setOrdre($this->createSignInOrdre($ordreRepository->getNextOrdreNumber()));
            $user->setMotPasse($passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData()));

            if ($user->getEntreprise() !== null) {
                $entityManager->persist($user->getEntreprise());
            }

            $entityManager->persist($user->getOrdre());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Votre compte RH a ete cree avec succes.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_rh.html.twig', [
            'registrationRhForm' => $form->createView(),
        ]);
    }

    private function createSignInOrdre(int $ordreNumber): Ordre
    {
        $now = new \DateTimeImmutable();
        $ordre = new Ordre();
        $ordre->setNum_Ordre($ordreNumber);
        $ordre->setAAAA((int) $now->format('Y'));
        $ordre->setMM((int) $now->format('m'));
        $ordre->setJJ((int) $now->format('d'));
        $ordre->setHH((int) $now->format('H'));
        $ordre->setMN((int) $now->format('i'));
        $ordre->setSS((int) $now->format('s'));

        return $ordre;
    }
}
