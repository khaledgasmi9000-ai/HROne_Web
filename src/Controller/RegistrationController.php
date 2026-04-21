<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Entity\Ordre;
use App\Entity\Utilisateur;
use App\Form\RegistrationFormType;
use App\Form\RegistrationRhType;
use App\Repository\EntrepriseRepository;
use App\Repository\OrdreRepository;
use App\Repository\ProfilRepository;
use App\Repository\UtilisateurRepository;
use App\Service\RegistrationEmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        EntrepriseRepository $entrepriseRepository,
        ProfilRepository $profilRepository,
        OrdreRepository $ordreRepository,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $passwordHasher,
        RegistrationEmailVerifier $registrationEmailVerifier,
    ): Response {
        if ($this->getUser() instanceof Utilisateur) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $normalizedEmail = mb_strtolower(trim((string) $user->getEmail()));
            $user->setEmail($normalizedEmail !== '' ? $normalizedEmail : null);

            foreach ($registrationEmailVerifier->validateForCandidate($normalizedEmail) as $error) {
                $form->get('email')->addError(new FormError($error));
            }

            if ($normalizedEmail !== '' && $utilisateurRepository->findOneBy(['Email' => $normalizedEmail]) instanceof Utilisateur) {
                $form->get('email')->addError(new FormError('Cet email est deja utilise.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $profilRepository->find(4);
            if ($profil === null) {
                throw $this->createNotFoundException('Profil candidat introuvable (ID_Profil 4).');
            }

            $defaultEntreprise = $entrepriseRepository->find(1);
            if (!$defaultEntreprise instanceof Entreprise) {
                $defaultEntreprise = new Entreprise();
                $defaultEntreprise->setID_Entreprise(1);
                $defaultEntreprise->setNomEntreprise('HR One');
                $defaultEntreprise->setReference('HRONE-REF-001');
                $entityManager->persist($defaultEntreprise);
            }

            $user->setEntreprise($defaultEntreprise);
            $user->setProfil($profil);
            $user->setIsActive(true);
            $user->setFirstLogin(1);
            $user->setFirst_login(1);
            $user->setOrdre($this->createSignInOrdre($ordreRepository->getNextOrdreNumber()));
            $user->setMotPasse(
                $passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData())
            );

            $entityManager->persist($user->getOrdre());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte candidat cree avec succes. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/register/rh', name: 'app_register_rh', methods: ['GET', 'POST'])]
    public function registerRh(
        Request $request,
        EntityManagerInterface $entityManager,
        ProfilRepository $profilRepository,
        OrdreRepository $ordreRepository,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $passwordHasher,
        RegistrationEmailVerifier $registrationEmailVerifier,
    ): Response {
        if ($this->getUser() instanceof Utilisateur) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new Utilisateur();
        $user->setEntreprise(new Entreprise());
        $form = $this->createForm(RegistrationRhType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $normalizedEmail = mb_strtolower(trim((string) $user->getEmail()));
            $user->setEmail($normalizedEmail !== '' ? $normalizedEmail : null);

            foreach ($registrationEmailVerifier->validateForRh($normalizedEmail) as $error) {
                $form->get('Email')->addError(new FormError($error));
            }

            if ($normalizedEmail !== '' && $utilisateurRepository->findOneBy(['Email' => $normalizedEmail]) instanceof Utilisateur) {
                $form->get('Email')->addError(new FormError('Cet email est deja utilise.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $profilRepository->find(2);
            if ($profil === null) {
                throw $this->createNotFoundException('Profil RH introuvable (ID_Profil 2).');
            }

            $user->setProfil($profil);
            $user->setIsActive(true);
            $user->setFirstLogin(1);
            $user->setFirst_login(1);
            $user->setOrdre($this->createSignInOrdre($ordreRepository->getNextOrdreNumber()));
            $user->setMotPasse(
                $passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData())
            );

            if ($user->getEntreprise() instanceof Entreprise) {
                if ($user->getEntreprise()->getReference() === null || trim((string) $user->getEntreprise()->getReference()) === '') {
                    $ref = sprintf('ENT-%s', strtoupper(substr(sha1(uniqid('', true)), 0, 8)));
                    $user->getEntreprise()->setReference($ref);
                }

                $entityManager->persist($user->getEntreprise());
            }

            $entityManager->persist($user->getOrdre());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte RH cree avec succes. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_rh.html.twig', [
            'registrationRhForm' => $form->createView(),
        ]);
    }

    #[Route('/register/employee', name: 'app_register_employee', methods: ['GET', 'POST'])]
    public function registerEmployee(
        Request $request,
        EntityManagerInterface $entityManager,
        EntrepriseRepository $entrepriseRepository,
        ProfilRepository $profilRepository,
        OrdreRepository $ordreRepository,
        UtilisateurRepository $utilisateurRepository,
        UserPasswordHasherInterface $passwordHasher,
        RegistrationEmailVerifier $registrationEmailVerifier,
    ): Response {
        if ($this->getUser() instanceof Utilisateur) {
            return $this->redirectToRoute('app_dashboard');
        }

        $user = new Utilisateur();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $normalizedEmail = mb_strtolower(trim((string) $user->getEmail()));
            $user->setEmail($normalizedEmail !== '' ? $normalizedEmail : null);

            foreach ($registrationEmailVerifier->validateForCandidate($normalizedEmail) as $error) {
                $form->get('email')->addError(new FormError($error));
            }

            if ($normalizedEmail !== '' && $utilisateurRepository->findOneBy(['Email' => $normalizedEmail]) instanceof Utilisateur) {
                $form->get('email')->addError(new FormError('Cet email est deja utilise.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $profil = $profilRepository->find(3);
            if ($profil === null) {
                throw $this->createNotFoundException('Profil employe introuvable (ID_Profil 3).');
            }

            $defaultEntreprise = $entrepriseRepository->find(1);
            if (!$defaultEntreprise instanceof Entreprise) {
                $defaultEntreprise = new Entreprise();
                $defaultEntreprise->setID_Entreprise(1);
                $defaultEntreprise->setNomEntreprise('HR One');
                $defaultEntreprise->setReference('HRONE-REF-001');
                $entityManager->persist($defaultEntreprise);
            }

            $user->setEntreprise($defaultEntreprise);
            $user->setProfil($profil);
            $user->setIsActive(true);
            $user->setFirstLogin(1);
            $user->setFirst_login(1);
            $user->setOrdre($this->createSignInOrdre($ordreRepository->getNextOrdreNumber()));
            $user->setMotPasse(
                $passwordHasher->hashPassword($user, (string) $form->get('plainPassword')->getData())
            );

            $entityManager->persist($user->getOrdre());
            $entityManager->persist($user);
            $entityManager->flush();

            $this->addFlash('success', 'Compte employe cree avec succes. Vous pouvez vous connecter.');

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register_employee.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function createSignInOrdre(int $orderNumber): Ordre
    {
        $now = new \DateTimeImmutable();
        $ordre = new Ordre();
        $ordre->setNum_Ordre($orderNumber);
        $ordre->setAAAA((int) $now->format('Y'));
        $ordre->setMM((int) $now->format('m'));
        $ordre->setJJ((int) $now->format('d'));
        $ordre->setHH((int) $now->format('H'));
        $ordre->setMN((int) $now->format('i'));
        $ordre->setSS((int) $now->format('s'));

        return $ordre;
    }
}
