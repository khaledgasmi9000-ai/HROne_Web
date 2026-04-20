<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route('/login', name: 'app_login')]
    public function login(Request $request, AuthenticationUtils $authenticationUtils): Response
    {
        if ($this->getUser() instanceof Utilisateur) {
            return $this->redirectToRoute('app_dashboard');
        }

        $captchaFirst = random_int(1, 9);
        $captchaSecond = random_int(1, 9);
        $request->getSession()->set('login_captcha_answer', (string) ($captchaFirst + $captchaSecond));

        return $this->render('security/login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $authenticationUtils->getLastAuthenticationError(),
            'captcha_question' => sprintf('%d + %d', $captchaFirst, $captchaSecond),
        ]);
    }

    #[Route('/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('Logout is handled by the firewall.');
    }
}
