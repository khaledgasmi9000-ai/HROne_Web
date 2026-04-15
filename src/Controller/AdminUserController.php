<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rh/users')]
class AdminUserController extends AbstractController
{
    public function __construct(
        private readonly ChartBuilderInterface $chartBuilder,
    ) {
    }

    #[Route('', name: 'app_rh_user_index', methods: ['GET'])]
    public function index(Request $request, UtilisateurRepository $utilisateurRepository): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        [$users, $filters] = $this->resolveFilteredUsers($request, $utilisateurRepository);
        $stats = $utilisateurRepository->getUserStats();

        return $this->render('rh/user/index.html.twig', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $filters,
            'userRoleChart' => $this->buildUserRoleChart($stats)->createView(),
            'userStatusChart' => $this->buildUserStatusChart($stats)->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_rh_user_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(Utilisateur $user): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        return $this->render('rh/user/show.html.twig', [
            'userItem' => $user,
        ]);
    }

    #[Route('/{id}/toggle-status', name: 'app_rh_user_toggle', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function toggle(Utilisateur $user, Request $request, EntityManagerInterface $entityManager): RedirectResponse
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        if (!$this->isCsrfTokenValid('toggle_user_' . $user->getIDUTILISATEUR(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $user->setIsActive(!$user->isActive());
        $entityManager->flush();

        $this->addFlash(
            'success',
            $user->isActive() ? 'Le compte a ete active.' : 'Le compte a ete desactive.'
        );

        return $this->redirectToRoute('app_rh_user_show', ['id' => $user->getIDUTILISATEUR()]);
    }

    /**
     * @return array{0: Utilisateur[], 1: array{q: string, profil: mixed, status: mixed, completion: mixed}}
     */
    private function resolveFilteredUsers(Request $request, UtilisateurRepository $utilisateurRepository): array
    {
        $search = trim((string) $request->query->get('q', ''));
        $profilId = $request->query->get('profil');
        $status = $request->query->get('status');
        $completion = $request->query->get('completion');

        $profilFilter = is_numeric($profilId) ? (int) $profilId : null;
        $statusFilter = match ($status) {
            'active' => true,
            'inactive' => false,
            default => null,
        };

        $users = $utilisateurRepository->searchUsers($search, $profilFilter, $statusFilter);

        if ($completion === 'complete') {
            $users = array_values(array_filter($users, static fn (Utilisateur $user) => $user->isProfileComplete()));
        } elseif ($completion === 'incomplete') {
            $users = array_values(array_filter($users, static fn (Utilisateur $user) => !$user->isProfileComplete()));
        }

        return [$users, [
            'q' => $search,
            'profil' => $profilId,
            'status' => $status,
            'completion' => $completion,
        ]];
    }

    private function buildUserRoleChart(array $stats): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $chart->setData([
            'labels' => ['Candidats', 'RH/Admin', 'Employes'],
            'datasets' => [[
                'label' => 'Repartition des profils',
                'data' => [$stats['candidats'], $stats['rh'], $stats['employees']],
                'backgroundColor' => ['#3b82f6', '#0f172a', '#14b8a6'],
                'borderColor' => '#ffffff',
                'borderWidth' => 2,
            ]],
        ]);
        $chart->setOptions([
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ]);

        return $chart;
    }

    private function buildUserStatusChart(array $stats): Chart
    {
        $chart = $this->chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => ['Actifs', 'Inactifs'],
            'datasets' => [[
                'label' => 'Statut des comptes',
                'data' => [$stats['active'], $stats['inactive']],
                'backgroundColor' => ['#22c55e', '#ef4444'],
                'borderRadius' => 10,
            ]],
        ]);
        $chart->setOptions([
            'plugins' => [
                'legend' => [
                    'display' => false,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'precision' => 0,
                    ],
                ],
            ],
        ]);

        return $chart;
    }
}
