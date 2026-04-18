<?php

namespace App\Controller;

use App\Entity\Ordre;
use App\Repository\DemandeCongeRepository;
use App\Repository\EmployeeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends AbstractController{
    #[Route('/Gestion_Administrative/statistique', name: 'stats_overview')]
    public function statsOverview(
        ChartBuilderInterface $chartBuilder
    ): Response {

        // ================= MOCK KPIs =================
        $stats = [
            'totalActiveTime' => 420,
            'productivityRate' => 78.5,
            'totalCost' => 1200,
            'costPerHour' => 2.85,
            'insights' => [
                'Slack représente 35% du temps total',
                '3 outils représentent 70% du coût',
                'Le département IT est le plus productif'
            ]
        ];

        // ================= TOOL USAGE =================
        $toolUsageChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $toolUsageChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false, // 🔥 IMPORTANT
        ]);
        $toolUsageChart->setData([
            'labels' => ['Slack', 'Jira', 'VS Code', 'Teams'],
            'datasets' => [
                [
                    'label' => 'Temps (heures)',
                    'data' => [120, 90, 150, 60],
                ],
            ],
        ]);

        // ================= COST VS USAGE =================
        $costUsageChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $costUsageChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false, // 🔥 IMPORTANT
        ]);
        $costUsageChart->setData([
            'labels' => ['Slack', 'Jira', 'VS Code', 'Teams'],
            'datasets' => [
                [
                    'label' => 'Coût (€)',
                    'data' => [300, 400, 200, 300],
                ],
                [
                    'label' => 'Utilisation (h)',
                    'data' => [120, 90, 150, 60],
                ],
            ],
        ]);

        // ================= PRODUCTIVITY =================
        $productivityChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $productivityChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false, // 🔥 IMPORTANT
        ]);

        $productivityChart->setData([
            'labels' => ['Actif', 'AFK', 'Inconnu'],
            'datasets' => [
                [
                    'data' => [300, 80, 40],
                ],
            ],
        ]);

        // ================= DEPARTMENT =================
        $departmentChart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $departmentChart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false, // 🔥 IMPORTANT
        ]);

        $departmentChart->setData([
            'labels' => ['IT', 'RH', 'Finance'],
            'datasets' => [
                [
                    'label' => 'Temps actif moyen',
                    'data' => [160, 120, 140],
                ],
            ],
        ]);

        

        return $this->render('Gestion Administrative/statistics_overview.html.twig', [
            'stats' => $stats,
            'toolUsageChart' => $toolUsageChart,
            'costUsageChart' => $costUsageChart,
            'productivityChart' => $productivityChart,
            'departmentChart' => $departmentChart,
        ]);
    }

}