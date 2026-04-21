<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\CommentRepository;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\PostFavoriteRepository;
use App\Repository\PostRepository;
use App\Repository\UtilisateurRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(
        PostRepository $postRepository,
        CommentRepository $commentRepository,
        UtilisateurRepository $utilisateurRepository,
        FormationRepository $formationRepository,
        ParticipationFormationRepository $participationFormationRepository,
        PostFavoriteRepository $postFavoriteRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        $roles = $user?->getRoles() ?? [];

        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_RH', $roles, true)) {
            return $this->redirectToRoute('app_user_module');
        }

        if (in_array('ROLE_CANDIDAT', $roles, true)) {
            return $this->redirectToRoute('topnav_offres');
        }

        if (in_array('ROLE_EMPLOYEE', $roles, true)) {
            return $this->redirectToRoute('topnav_communaute');
        }

        try {
            // Count using repository methods instead of raw SQL
            $stats = [
                'formations_total' => $this->safeCount(fn() => $formationRepository->countAll()),
                'formations_open' => $this->safeCount(fn() => $formationRepository->countOpen()),
                'participations_total' => $this->safeCount(fn() => $participationFormationRepository->count([])),
                'community_posts' => $postRepository->countActive(),
                'community_comments' => $commentRepository->countAll(),
                'community_favorites' => $postFavoriteRepository->countByUserId($user->getID_UTILISATEUR()),
                'users_total' => $utilisateurRepository->count([]),
                'users_active' => $this->safeCount(fn() => $utilisateurRepository->countActive()),
            ];
        } catch (\Throwable $e) {
            // Fallback if repositories don't have all methods
            $stats = [
                'formations_total' => 0,
                'formations_open' => 0,
                'participations_total' => 0,
                'community_posts' => 0,
                'community_comments' => 0,
                'community_favorites' => 0,
                'users_total' => 0,
                'users_active' => 0,
            ];
        }

        return $this->render('user/dashboard.html.twig', [
            'stats' => $stats,
            'recent_formations' => $this->getRecentFormations($formationRepository),
            'recent_posts' => $this->getRecentPosts($postRepository),
            'currentUser' => $user,
            'can_manage_users' => $this->isGranted('ROLE_RH'),
            'has_admin_formation' => $this->routeExists('app_admin_formation_index'),
        ]);
    }

    private function routeExists(string $routeName): bool
    {
        try {
            $this->generateUrl($routeName);

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Safe count wrapper to prevent errors
     */
    private function safeCount(callable $countFunction): int
    {
        try {
            return $countFunction();
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * Get recent formations using repository
     *
     * @return array<int, array{
     *   id:int,
     *   titre:string,
     *   mode:string,
     *   date_debut:string,
     *   date_fin:string
     * }>
     */
    private function getRecentFormations(FormationRepository $repository): array
    {
        try {
            $formations = $repository->findRecentFormations(3);
            
            return array_map(function ($formation): array {
                // Assuming Formation entity has these methods
                $dateDebut = $formation->getDateDebut ? $this->formatStoredDate($formation->getDateDebut()) : 'Non definie';
                $dateFin = $formation->getDateFin ? $this->formatStoredDate($formation->getDateFin()) : 'Non definie';
                
                return [
                    'id' => $formation->getIdFormation ?? 0,
                    'titre' => $formation->getTitre ?? 'Formation',
                    'mode' => $formation->getMode ?? 'presentiel',
                    'date_debut' => $dateDebut,
                    'date_fin' => $dateFin,
                ];
            }, $formations);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get recent posts using repository
     *
     * @return array<int, array{
     *   id:int,
     *   title:string,
     *   username:string,
     *   created_at:string
     * }>
     */
    private function getRecentPosts(PostRepository $repository): array
    {
        try {
            $posts = $repository->findRecentActive(4);

            return array_map(function ($post): array {
                $createdAt = $post->getCreated_at();
                $dateLabel = $createdAt ? $createdAt->format('Y-m-d H:i') : 'Date indisponible';

                return [
                    'id' => $post->getId() ?? 0,
                    'title' => $post->getTitle() ?? 'Publication',
                    'username' => $this->getUsernameForPost($post),
                    'created_at' => $dateLabel,
                ];
            }, $posts);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Get username for a post
     */
    private function getUsernameForPost($post): string
    {
        try {
            $userId = $post->getUser_id();
            if ($userId === null) {
                return 'Utilisateur';
            }
            // In a real app, you might want to fetch the user or store the username
            // For now, return a placeholder
            return sprintf('Utilisateur #%d', $userId);
        } catch (\Throwable) {
            return 'Utilisateur';
        }
    }

    private function formatStoredDate(mixed $value): string
    {
        if ($value === null || $value === '') {
            return 'Non definie';
        }

        $raw = preg_replace('/\D/', '', (string) $value) ?? '';
        if ($raw === '') {
            return 'Non definie';
        }

        if (strlen($raw) >= 8) {
            $short = substr($raw, 0, 8);
            $date = \DateTimeImmutable::createFromFormat('Ymd', $short);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('d/m/Y');
            }
        }

        return (string) $value;
    }
}
