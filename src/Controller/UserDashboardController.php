<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserDashboardController extends AbstractController
{
    #[Route('/dashboard', name: 'app_dashboard', methods: ['GET'])]
    public function index(Connection $connection): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        /** @var Utilisateur|null $user */
        $user = $this->getUser();
        $roles = $user?->getRoles() ?? [];

        if (in_array('ROLE_ADMIN', $roles, true) || in_array('ROLE_RH', $roles, true)) {
            return $this->redirectToRoute('app_user_module');
        }

        if (in_array('ROLE_CANDIDAT', $roles, true)) {
            return $this->redirectToRoute('app_offres_index');
        }

        if (in_array('ROLE_EMPLOYEE', $roles, true)) {
            return $this->redirectToRoute('community_index');
        }

        $stats = [
            'formations_total' => $this->safeCount($connection, 'formation'),
            'formations_open' => $this->safeCount($connection, 'formation', 'COALESCE(places_restantes, 0) > 0'),
            'participations_total' => $this->safeCount($connection, 'participation_formation'),
            'community_posts' => $this->safeCount($connection, 'posts', 'COALESCE(is_active, 1) = 1'),
            'community_comments' => $this->safeCount($connection, 'comments'),
            'community_favorites' => $this->safeCount($connection, 'post_favorites'),
            'users_total' => $this->safeCount($connection, 'utilisateur'),
            'users_active' => $this->safeCount($connection, 'utilisateur', 'COALESCE(is_active, 1) = 1'),
        ];

        return $this->render('user/dashboard.html.twig', [
            'stats' => $stats,
            'recent_formations' => $this->fetchRecentFormations($connection),
            'recent_posts' => $this->fetchRecentPosts($connection),
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

    private function safeCount(Connection $connection, string $table, string $where = '1=1'): int
    {
        try {
            $sql = sprintf('SELECT COUNT(*) FROM `%s` WHERE %s', $table, $where);

            return (int) $connection->fetchOne($sql);
        } catch (\Throwable) {
            return 0;
        }
    }

    /**
     * @return array<int, array{
     *   id:int,
     *   titre:string,
     *   mode:string,
     *   date_debut:string,
     *   date_fin:string
     * }>
     */
    private function fetchRecentFormations(Connection $connection): array
    {
        try {
            $rows = $connection->fetchAllAssociative(
                'SELECT id_formation, titre, mode, date_debut, date_fin
                 FROM formation
                 ORDER BY COALESCE(date_debut, 0) DESC, id_formation DESC
                 LIMIT 3'
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map(function (array $row): array {
            return [
                'id' => (int) ($row['id_formation'] ?? 0),
                'titre' => (string) ($row['titre'] ?? 'Formation'),
                'mode' => (string) ($row['mode'] ?? 'presentiel'),
                'date_debut' => $this->formatStoredDate($row['date_debut'] ?? null),
                'date_fin' => $this->formatStoredDate($row['date_fin'] ?? null),
            ];
        }, $rows);
    }

    /**
     * @return array<int, array{
     *   id:int,
     *   title:string,
     *   username:string,
     *   created_at:string
     * }>
     */
    private function fetchRecentPosts(Connection $connection): array
    {
        try {
            $rows = $connection->fetchAllAssociative(
                "SELECT
                    p.id,
                    p.title,
                    p.created_at,
                    COALESCE(NULLIF(u.Nom_Utilisateur, ''), CONCAT('Utilisateur #', p.user_id)) AS username
                 FROM posts p
                 LEFT JOIN utilisateur u ON u.ID_UTILISATEUR = p.user_id
                 WHERE COALESCE(p.is_active, 1) = 1
                 ORDER BY p.created_at DESC, p.id DESC
                 LIMIT 4"
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map(function (array $row): array {
            $createdAt = (string) ($row['created_at'] ?? '');
            $dateLabel = $createdAt !== '' ? substr($createdAt, 0, 16) : 'Date indisponible';

            return [
                'id' => (int) ($row['id'] ?? 0),
                'title' => (string) ($row['title'] ?? 'Publication'),
                'username' => (string) ($row['username'] ?? 'Utilisateur'),
                'created_at' => $dateLabel,
            ];
        }, $rows);
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
