<?php

namespace App\Controller;

use App\Form\LoginType;
use App\Form\RegisterCandidateType;
use App\Form\RegisterRHType;
use App\Service\AuthService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AuthController extends AbstractController
{
    public function __construct(private AuthService $authService) {}

    #[Route('/login', name: 'login', methods: ['GET', 'POST'])]
    public function login(Request $request): Response
    {
        if ($this->authService->isLoggedIn()) {
            return $this->redirectToDashboard();
        }

        $form = $this->createForm(LoginType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $this->authService->login($data['email'], $data['password']);

            if (!$user) {
                $message = 'Email ou mot de passe incorrect.';
                $form->get('email')->addError(new FormError($message));
                $form->get('password')->addError(new FormError($message));
                $form->addError(new FormError($message));
            } else {
                return $this->redirectToDashboard();
            }
        }

        return $this->render('auth/login.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register/candidate', name: 'signup_candidat', methods: ['GET', 'POST'])]
    public function registerCandidate(Request $request): Response
    {
        if ($this->authService->isLoggedIn()) {
            return $this->redirectToDashboard();
        }

        $form = $this->createForm(RegisterCandidateType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->authService->registerCandidate($form->getData());
                $this->addFlash('success', 'Compte candidat cree. Connectez-vous.');
                return $this->redirectToRoute('login');
            } catch (\DomainException $e) {
                $this->attachDomainErrorToCandidateForm($form, $e->getMessage());
            }
        }

        return $this->render('auth/signup-candidat.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/register/rh', name: 'signup_rh', methods: ['GET', 'POST'])]
    public function registerRH(Request $request): Response
    {
        if ($this->authService->isLoggedIn()) {
            return $this->redirectToDashboard();
        }

        $form = $this->createForm(RegisterRHType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $this->authService->registerRH($form->getData());
                $this->addFlash('success', 'Compte RH cree. Connectez-vous.');
                return $this->redirectToRoute('login');
            } catch (\DomainException $e) {
                $this->attachDomainErrorToRhForm($form, $e->getMessage());
            }
        }

        return $this->render('auth/signup-rh.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/logout', name: 'logout')]
    public function logout(): Response
    {
        $this->authService->logout();
        return $this->redirectToRoute('login');
    }

    #[Route('/dashboard/candidate', name: 'dashboard_candidate')]
    public function dashboardCandidate(): Response
    {
        if (!$this->authService->isCandidate()) {
            return $this->redirectToRoute('login');
        }

        return $this->render('candidate/dashboard.html.twig', [
            'user' => $this->authService->getCurrentUser(),
        ]);
    }

    #[Route('/dashboard/rh', name: 'dashboard_rh')]
    public function dashboardRH(): Response
    {
        if (!$this->authService->isRH()) {
            return $this->redirectToRoute('login');
        }

        return $this->render('rh/dashboard.html.twig', [
            'user' => $this->authService->getCurrentUser(),
        ]);
    }

    #[Route('/dashboard/admin', name: 'dashboard_admin')]
    public function dashboardAdmin(): Response
    {
        if (!$this->authService->isAdmin()) {
            return $this->redirectToRoute('login');
        }

        return $this->redirectToRoute('gestion_administrative');
    }

    private function redirectToDashboard(): Response
    {
        if ($this->authService->isRH()) {
            return $this->redirectToRoute('dashboard_rh');
        }

        if ($this->authService->isAdmin()) {
            return $this->redirectToRoute('dashboard_admin');
        }

        return $this->redirectToRoute('dashboard_candidate');
    }

    private function attachDomainErrorToCandidateForm(FormInterface $form, string $message): void
    {
        $normalizedMessage = mb_strtolower($message);

        if (str_contains($normalizedMessage, 'email')) {
            $form->get('email')->addError(new FormError($message));
            return;
        }

        if (str_contains($normalizedMessage, 'cin')) {
            $form->get('cin')->addError(new FormError($message));
            return;
        }

        $form->addError(new FormError($message));
    }

    private function attachDomainErrorToRhForm(FormInterface $form, string $message): void
    {
        $normalizedMessage = mb_strtolower($message);

        if (str_contains($normalizedMessage, 'email')) {
            $form->get('Email')->addError(new FormError($message));
            return;
        }

        if (str_contains($normalizedMessage, 'cin')) {
            $form->get('CIN')->addError(new FormError($message));
            return;
        }

        if (str_contains($normalizedMessage, 'nom d\'entreprise')) {
            $form->get('entreprise')->get('Nom_Entreprise')->addError(new FormError($message));
            return;
        }

        if (str_contains($normalizedMessage, 'reference')) {
            $form->get('entreprise')->get('Reference')->addError(new FormError($message));
            return;
        }

        $form->addError(new FormError($message));
    }
}
