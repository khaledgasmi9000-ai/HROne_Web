<?php

namespace App\Service;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserService
{
    public function __construct(
        private UtilisateurRepository $utilisateurRepository,
        private Security $security
    ) {
    }

    /**
     * Resolve current user ID from session or security context
     */
    public function resolveCurrentUserId(SessionInterface $session): int
    {
        $user = $this->resolveCurrentUser($session);
        return $user?->getID_UTILISATEUR() ?? 1;
    }

    /**
     * Resolve current user entity from session or security context
     */
    public function resolveCurrentUser(SessionInterface $session): ?Utilisateur
    {
        $sessionUserId = (int)$session->get('user_id', 0);
        if ($sessionUserId > 0) {
            $user = $this->utilisateurRepository->find($sessionUserId);
            if ($user) {
                return $user;
            }
        }

        $user = $this->security->getUser();
        if (!$user) {
            $session->set('user_id', 1);
            return $this->utilisateurRepository->find(1);
        }

        $rawIdentifier = trim((string)($user->getUserIdentifier() ?? ''));
        $identifier = mb_strtolower($rawIdentifier);

        if ($identifier !== '') {
            // Try to find existing user
            $existingUser = $this->utilisateurRepository->createQueryBuilder('u')
                ->where('LOWER(u.Email) = :identifier OR LOWER(u.Nom_Utilisateur) = :identifier')
                ->setParameter('identifier', $identifier)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($existingUser) {
                $resolvedId = $existingUser->getID_UTILISATEUR() ?? 0;
                if ($resolvedId > 0) {
                    $session->set('user_id', $resolvedId);
                    return $existingUser;
                }
            }

            // Create new user if not exists
            $username = $this->buildUsernameFromIdentifier($rawIdentifier);
            $candidate = $this->getUniqueUsername($username);
            $email = filter_var($rawIdentifier, FILTER_VALIDATE_EMAIL) ? mb_strtolower($rawIdentifier) : null;

            $newUser = new Utilisateur();
            $newUser->setNom_Utilisateur($candidate);
            $newUser->setMot_Passe('external_login');
            if ($email) {
                $newUser->setEmail($email);
            }

            $this->utilisateurRepository->save($newUser, true);
            $createdId = $newUser->getID_UTILISATEUR() ?? 0;

            if ($createdId > 0) {
                $session->set('user_id', $createdId);
                return $newUser;
            }
        }

        $session->set('user_id', 1);
        return $this->utilisateurRepository->find(1);
    }

    /**
     * Get unique username by checking against existing usernames
     */
    private function getUniqueUsername(string $username): string
    {
        $candidate = $username;
        $suffix = 1;

        while ($this->utilisateurRepository->createQueryBuilder('u')
            ->select('COUNT(u.ID_UTILISATEUR)')
            ->where('LOWER(u.Nom_Utilisateur) = :username')
            ->setParameter('username', mb_strtolower($candidate))
            ->getQuery()
            ->getSingleScalarResult() > 0) {
            $candidate = $username . '_' . $suffix;
            $suffix++;

            if ($suffix > 25) {
                $candidate = $username . '_' . substr(md5($username), 0, 6);
                break;
            }
        }

        return $candidate;
    }

    /**
     * Build username from email or other identifier
     */
    private function buildUsernameFromIdentifier(string $identifier): string
    {
        $value = trim($identifier);
        if ($value === '') {
            return 'user';
        }

        if (str_contains($value, '@')) {
            $value = explode('@', $value)[0];
        }

        $value = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $value) ?? '');
        $value = trim($value, '_');

        if ($value === '') {
            return 'user';
        }

        return substr($value, 0, 40);
    }
}
