<?php

namespace App\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class LoginFormAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';

    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly RequestStack $requestStack,
    ) {
    }

    public function authenticate(Request $request): Passport
    {
        $identifier = trim((string) $request->request->get('email', ''));
        $password = (string) $request->request->get('password', '');
        $captchaAnswer = trim((string) $request->request->get('captcha_answer', ''));
        $csrfToken = (string) $request->request->get('_csrf_token');

        $request->getSession()->set('_security.last_username', $identifier);
        $this->assertCaptchaIsValid($captchaAnswer);

        return new Passport(
            new UserBadge($identifier, function (string $value): Utilisateur {
                $normalized = mb_strtolower(trim($value));

                $user = $this->utilisateurRepository->findOneBy(['Email' => $normalized]);
                if (!$user instanceof Utilisateur) {
                    $user = $this->utilisateurRepository->findOneBy(['Nom_Utilisateur' => $value]);
                }

                if (!$user instanceof Utilisateur) {
                    throw new CustomUserMessageAuthenticationException('Utilisateur introuvable.');
                }

                if (!$user->isActive()) {
                    throw new CustomUserMessageAuthenticationException('Ce compte est desactive.');
                }

                return $user;
            }),
            new PasswordCredentials($password),
            [
                new CsrfTokenBadge('authenticate', $csrfToken),
                new RememberMeBadge(),
            ]
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        /** @var Utilisateur $user */
        $user = $token->getUser();
        $roles = $user->getRoles();

        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_RH', $roles, true)) {
            if ($this->routeExists('app_user_module')) {
                return new RedirectResponse($this->urlGenerator->generate('app_user_module'));
            }
        }

        if (in_array('ROLE_CANDIDAT', $roles, true) && !in_array('ROLE_EMPLOYEE', $roles, true)) {
            if ($this->routeExists('app_offres_index')) {
                return new RedirectResponse($this->urlGenerator->generate('app_offres_index'));
            }
        }

        if (in_array('ROLE_EMPLOYEE', $roles, true)) {
            if ($this->routeExists('community_index')) {
                return new RedirectResponse($this->urlGenerator->generate('community_index'));
            }
        }

        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }

        if ($this->routeExists('app_dashboard')) {
            return new RedirectResponse($this->urlGenerator->generate('app_dashboard'));
        }

        if ($this->routeExists('app_formation_index')) {
            return new RedirectResponse($this->urlGenerator->generate('app_formation_index'));
        }

        if ($this->routeExists('community_index')) {
            return new RedirectResponse($this->urlGenerator->generate('community_index'));
        }

        return new RedirectResponse($this->urlGenerator->generate(self::LOGIN_ROUTE));
    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }

    private function routeExists(string $routeName): bool
    {
        try {
            $this->urlGenerator->generate($routeName);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function assertCaptchaIsValid(string $captchaAnswer): void
    {
        $request = $this->requestStack->getCurrentRequest();
        $session = $request?->getSession();

        if ($session === null) {
            throw new CustomUserMessageAuthenticationException('Captcha indisponible, merci de recharger la page.');
        }

        $expectedAnswer = trim((string) $session->get('login_captcha_answer', ''));
        $session->remove('login_captcha_answer');

        if ($expectedAnswer === '' || $captchaAnswer === '') {
            throw new CustomUserMessageAuthenticationException('Captcha obligatoire.');
        }

        if (!hash_equals($expectedAnswer, $captchaAnswer)) {
            throw new CustomUserMessageAuthenticationException('Captcha invalide.');
        }
    }
}
