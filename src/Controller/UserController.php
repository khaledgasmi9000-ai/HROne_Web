<?php

namespace App\Controller;

use App\Entity\Profil;
use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserController extends AbstractController
{
    #[Route('/users', name: 'app_user_module', methods: ['GET'])]
    public function index(Request $request, UtilisateurRepository $utilisateurs): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $search = trim((string) $request->query->get('q', ''));
        $profil = $request->query->get('profil');
        $status = $request->query->get('status');
        $completion = $request->query->get('completion');

        $profilId = is_numeric($profil) ? (int) $profil : null;
        $statusFilter = match ($status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $users = $utilisateurs->searchUsers($search, $profilId, $statusFilter);

        if ($completion === 'complete') {
            $users = array_values(array_filter($users, static fn (Utilisateur $user): bool => $user->isProfileComplete()));
        } elseif ($completion === 'incomplete') {
            $users = array_values(array_filter($users, static fn (Utilisateur $user): bool => !$user->isProfileComplete()));
        }

        $stats = $utilisateurs->getUserStats();
        $dashboardStats = $this->buildDashboardStats($utilisateurs->searchUsers(), $stats);

        $currentRoles = $this->getUser() ? $this->getUser()->getRoles() : [];
        sort($currentRoles);

        return $this->render('user/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'filters' => [
                'q' => $search,
                'profil' => (string) $profil,
                'status' => (string) $status,
                'completion' => (string) $completion,
            ],
            'currentIdentifier' => (string) ($this->getUser()?->getUserIdentifier() ?? 'inconnu'),
            'currentRoles' => $currentRoles,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
            'currentEntityUserId' => $this->getUser() instanceof Utilisateur ? (int) $this->getUser()->getIDUTILISATEUR() : null,
            'dashboardStats' => $dashboardStats,
        ]);
    }

    #[Route('/users/{id}', name: 'app_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Utilisateur $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        return $this->render('user/show.html.twig', [
            'userItem' => $user,
            'isAdmin' => $this->isGranted('ROLE_ADMIN'),
            'currentEntityUserId' => $this->getUser() instanceof Utilisateur ? (int) $this->getUser()->getIDUTILISATEUR() : null,
        ]);
    }

    #[Route('/users/{id}/toggle-status', name: 'app_user_toggle_status', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleStatus(
        Utilisateur $user,
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        $this->denyAccessUnlessGranted('ROLE_RH');

        if (!$this->isCsrfTokenValid('toggle_user_status_' . $user->getIDUTILISATEUR(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('app_user_show', ['id' => $user->getIDUTILISATEUR()]);
        }

        $currentEntityUser = $this->getUser() instanceof Utilisateur ? $this->getUser() : null;
        if ($currentEntityUser instanceof Utilisateur && $currentEntityUser->getIDUTILISATEUR() === $user->getIDUTILISATEUR()) {
            $this->addFlash('error', 'Vous ne pouvez pas desactiver votre propre compte connecte.');

            return $this->redirectToRoute('app_user_show', ['id' => $user->getIDUTILISATEUR()]);
        }

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash('success', $user->isActive() ? 'Compte active.' : 'Compte desactive.');

        return $this->redirectToRoute('app_user_show', ['id' => $user->getIDUTILISATEUR()]);
    }

    #[Route('/users/{id}/toggle-admin', name: 'app_user_toggle_admin', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggleAdmin(
        int $id,
        Request $request,
        UtilisateurRepository $utilisateurs,
        EntityManagerInterface $em
    ): RedirectResponse {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Action reservee a l administrateur.');
            return $this->redirectToRoute('app_user_show', ['id' => $id]);
        }
        if (!$this->isCsrfTokenValid('toggle_admin_user_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_user_show', ['id' => $id]);
        }

        $user = $utilisateurs->find($id);
        if (!$user instanceof Utilisateur) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_user_module');
        }

        $currentEntityUser = $this->getUser() instanceof Utilisateur ? $this->getUser() : null;
        if ($currentEntityUser instanceof Utilisateur && $currentEntityUser->getIDUTILISATEUR() === $user->getIDUTILISATEUR()) {
            $this->addFlash('error', 'Vous ne pouvez pas changer votre propre role administrateur.');
            return $this->redirectToRoute('app_user_show', ['id' => $id]);
        }

        $adminProfil = $em->getRepository(\App\Entity\Profil::class)->find(1);
        $standardProfil = $em->getRepository(\App\Entity\Profil::class)->find(3);
        $isAdmin = in_array('ROLE_ADMIN', $user->getRoles(), true);

        if ($isAdmin && $standardProfil instanceof Profil) {
            $user->setProfil($standardProfil);
        } elseif (!$isAdmin && $adminProfil instanceof Profil) {
            $user->setProfil($adminProfil);
        } else {
            $this->addFlash('error', 'Profils Admin/User introuvables (ID_Profil 1 et 3).');
            return $this->redirectToRoute('app_user_module');
        }

        $em->flush();

        $this->addFlash('success', $isAdmin ? 'Role admin retire.' : 'Role admin attribue.');
        return $this->redirectToRoute('app_user_show', ['id' => $id]);
    }

    #[Route('/users/{id}/delete', name: 'app_user_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function delete(
        int $id,
        Request $request,
        UtilisateurRepository $utilisateurs,
        EntityManagerInterface $em
    ): RedirectResponse {
        if (!$this->isGranted('ROLE_ADMIN')) {
            $this->addFlash('error', 'Action reservee a l administrateur.');
            return $this->redirectToRoute('app_user_show', ['id' => $id]);
        }
        if (!$this->isCsrfTokenValid('delete_user_' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('app_user_show', ['id' => $id]);
        }

        $user = $utilisateurs->find($id);
        if (!$user instanceof Utilisateur) {
            $this->addFlash('error', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_user_module');
        }

        $currentEntityUser = $this->getUser() instanceof Utilisateur ? $this->getUser() : null;
        if ($currentEntityUser instanceof Utilisateur && $currentEntityUser->getIDUTILISATEUR() === $user->getIDUTILISATEUR()) {
            $this->addFlash('error', 'Vous ne pouvez pas supprimer votre propre compte connecte.');
            return $this->redirectToRoute('app_user_show', ['id' => $id]);
        }

        $em->remove($user);
        $em->flush();

        $this->addFlash('success', 'Utilisateur supprime.');
        return $this->redirectToRoute('app_user_module');
    }

    /**
     * @param Utilisateur[] $users
     * @param array{total:int,active:int,inactive:int,candidats:int,rh:int,employees:int} $stats
     *
     * @return array{
     *     averageCompletion:int,
     *     complete:int,
     *     incomplete:int,
     *     activeRate:int,
     *     roleDistribution:array{admin:int,rh:int,employees:int,candidats:int,other:int}
     * }
     */
    private function buildDashboardStats(array $users, array $stats): array
    {
        $roleDistribution = [
            'admin' => 0,
            'rh' => 0,
            'employees' => 0,
            'candidats' => 0,
            'other' => 0,
        ];

        $completionSum = 0;
        $completeCount = 0;
        $incompleteCount = 0;

        foreach ($users as $user) {
            $score = $user->getProfileCompletionScore();
            $completionSum += $score;

            if ($user->isProfileComplete()) {
                $completeCount++;
            } else {
                $incompleteCount++;
            }

            $profilId = (int) ($user->getProfil()?->getIDProfil() ?? 0);
            match ($profilId) {
                1 => $roleDistribution['admin']++,
                2 => $roleDistribution['rh']++,
                3 => $roleDistribution['employees']++,
                4 => $roleDistribution['candidats']++,
                default => $roleDistribution['other']++,
            };
        }

        $total = max(0, (int) ($stats['total'] ?? count($users)));
        $averageCompletion = $total > 0 ? (int) round($completionSum / $total) : 0;
        $active = max(0, (int) ($stats['active'] ?? 0));
        $activeRate = $total > 0 ? (int) round(($active / $total) * 100) : 0;

        return [
            'averageCompletion' => $averageCompletion,
            'complete' => $completeCount,
            'incomplete' => $incompleteCount,
            'activeRate' => $activeRate,
            'roleDistribution' => $roleDistribution,
        ];
    }
}
