<?php

namespace App\Service;

use App\Entity\Condidat;
use App\Entity\Ordre;
use App\Entity\Utilisateur;
use App\Repository\EntrepriseRepository;
use App\Repository\OrdreRepository;
use App\Repository\ProfilRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AuthService
{
    public function __construct(
        private UtilisateurRepository $repo,
        private ProfilRepository $profilRepository,
        private OrdreRepository $ordreRepository,
        private EntrepriseRepository $entrepriseRepository,
        private RequestStack $requestStack,
        private EntityManagerInterface $em
    ) {}

    private function session()
    {
        return $this->requestStack->getSession();
    }

    // -------------------------------------------------------------------------
    // LOGIN
    // -------------------------------------------------------------------------

    public function login(string $email, string $password): ?Utilisateur
    {
        $email = $this->normalizeEmail($email);
        $user = $this->repo->findOneBy(['Email' => $email]);

        if (!$user || !password_verify($password, $user->getMot_Passe())) {
            return null;
        }

        $this->session()->set('user_id', $user->getID_UTILISATEUR());
        $role = $this->normalizeRole($user->getProfil()?->getNom_Profil());
        $this->session()->set('role', $role);

        return $user;
    }

    // -------------------------------------------------------------------------
    // REGISTER CANDIDATE
    // -------------------------------------------------------------------------

    public function registerCandidate(Utilisateur $user): ?Utilisateur
    {
        $user->setEmail($this->normalizeEmail($user->getEmail()));
        $this->assertUniqueUtilisateur($user);

        $user->setMot_Passe(password_hash($user->getMot_Passe(), PASSWORD_BCRYPT));
        $user->setProfil($this->requireProfil('Candidat'));
        $user->setEntreprise($this->requireEntreprise(1));
        $user->setOrdre($this->getDefaultOrdre());
        $user->setFirstLogin($user->getFirstLogin() ?? 0);

        $this->em->persist($user);

        $candidate = new Condidat();
        $candidate->setUtilisateur($user);
        $candidate->setCV('');
        $this->em->persist($candidate);

        $this->em->flush();

        return $user;
    }

    // -------------------------------------------------------------------------
    // REGISTER RH
    // -------------------------------------------------------------------------

    public function registerRH(Utilisateur $user): ?Utilisateur
    {
        $user->setEmail($this->normalizeEmail($user->getEmail()));
        $this->assertUniqueUtilisateur($user);
        $this->assertUniqueEntreprise($user->getEntreprise());

        $user->setMot_Passe(password_hash($user->getMot_Passe(), PASSWORD_BCRYPT));
        $user->setProfil($this->requireProfil('RH'));
        $user->setOrdre($this->getDefaultOrdre());
        $user->setFirstLogin($user->getFirstLogin() ?? 0);

        $entreprise = $user->getEntreprise();
        if ($entreprise) {
            $this->em->persist($entreprise);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    // -------------------------------------------------------------------------
    // LOGOUT
    // -------------------------------------------------------------------------

    public function logout(): void
    {
        $this->session()->invalidate();
    }

    // -------------------------------------------------------------------------
    // SESSION HELPERS
    // -------------------------------------------------------------------------

    public function getCurrentUser(): ?Utilisateur
    {
        $id = $this->session()->get('user_id');
        if (!$id) return null;

        return $this->repo->find($id);
    }

    public function isLoggedIn(): bool
    {
        return $this->session()->has('user_id');
    }

    public function isCandidate(): bool
    {
        return $this->session()->get('role') === 'candidate';
    }

    public function isRH(): bool
    {
        return $this->session()->get('role') === 'rh';
    }

    public function isAdmin(): bool
    {
        return in_array($this->session()->get('role'), ['admin', 'employee'], true);
    }

    public function hasRole(string $role): bool
    {
        return $this->session()->get('role') === $role;
    }

    private function normalizeRole(?string $role): string
    {
        return match (mb_strtolower(trim((string) $role))) {
            'rh' => 'rh',
            'admin' => 'admin',
            'employee' => 'employee',
            default => 'candidate',
        };
    }

    private function requireProfil(string $name)
    {
        $profil = $this->profilRepository->findOneBy(['Nom_Profil' => $name]);
        if (!$profil) {
            throw new \RuntimeException(sprintf('Profil "%s" introuvable.', $name));
        }

        return $profil;
    }

    private function requireEntreprise(int $id)
    {
        $entreprise = $this->entrepriseRepository->find($id);
        if (!$entreprise) {
            throw new \RuntimeException(sprintf('Entreprise #%d introuvable.', $id));
        }

        return $entreprise;
    }

    private function getDefaultOrdre(): Ordre
    {
        $ordre = $this->ordreRepository->find(0);
        if (!$ordre) {
            throw new \RuntimeException('Ordre par defaut introuvable.');
        }

        return $ordre;
    }

    private function normalizeEmail(?string $email): ?string
    {
        if ($email === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($email));

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeOptionalValue(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim($value);

        return $normalized === '' ? null : $normalized;
    }

    private function assertUniqueUtilisateur(Utilisateur $user): void
    {
        $email = $this->normalizeEmail($user->getEmail());
        if ($email !== null && $this->repo->findOneBy(['Email' => $email])) {
            throw new \DomainException('Cet email est deja utilise.');
        }

        $cin = $this->normalizeOptionalValue($user->getCIN());
        $user->setCIN($cin);
        if ($cin !== null && $this->repo->findOneBy(['CIN' => $cin])) {
            throw new \DomainException('Ce CIN est deja utilise.');
        }
    }

    private function assertUniqueEntreprise(?\App\Entity\Entreprise $entreprise): void
    {
        if ($entreprise === null) {
            return;
        }

        $nom = $this->normalizeOptionalValue($entreprise->getNom_Entreprise());
        if ($nom !== null) {
            $entreprise->setNom_Entreprise($nom);
        }

        if ($nom !== null && $this->entrepriseRepository->findOneBy(['Nom_Entreprise' => $nom])) {
            throw new \DomainException('Ce nom d\'entreprise est deja utilise.');
        }

        $reference = $this->normalizeOptionalValue($entreprise->getReference());
        $entreprise->setReference($reference);
        if ($reference !== null && $this->entrepriseRepository->findOneBy(['Reference' => $reference])) {
            throw new \DomainException('Cette reference est deja utilisee.');
        }
    }
}
