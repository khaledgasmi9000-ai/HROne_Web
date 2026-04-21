<?php

namespace App\Controller;

use App\Repository\EmployeeRepository;
use App\Repository\WorkSessionRepository;
use App\Repository\OutilsDeTravailRepository;
use App\Repository\WorkSessionDetailRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class StatisticsController extends AbstractController{
    
    #[Route('/Gestion_Administrative/statistique', name: 'stats_overview')]
    public function statsOverview(
        ChartBuilderInterface $chartBuilder,
        WorkSessionRepository $workSessionRepo,
        WorkSessionDetailRepository $detailRepo,
        OutilsDeTravailRepository $toolRepo,
        EmployeeRepository $employeeRepo): Response
    {
        // KPI Generation
        $stats = $this->buildKpis($workSessionRepo, $toolRepo);

        $toolUsageData = $detailRepo->getGlobalToolUsage();
        $productivityData = $workSessionRepo->getProductivityBreakdown();
        $totalCost = $toolRepo->getTotalCost();

        $stats = $this->buildKpis($workSessionRepo, $toolRepo);

        // Insights generation
        $stats['insights'] = $this->buildInsights(
            $toolUsageData,
            $productivityData,
            $totalCost
        );

        // charts
        [$toolUsageChart, $toolUsageEmpty] = $this->buildToolUsageChart($chartBuilder, $detailRepo);
        [$costUsageChart, $costUsageEmpty] = $this->buildCostUsageChart($chartBuilder, $detailRepo, $toolRepo);
        [$productivityChart, $productivityEmpty] = $this->buildProductivityChart($chartBuilder, $workSessionRepo);
        [$departmentChart, $departmentEmpty] = $this->buildDepartmentChart($chartBuilder, $employeeRepo);

        return $this->render('Gestion Administrative/statistics_overview.html.twig', [
            'stats' => $stats,

            'toolUsageChart' => $toolUsageChart,
            'toolUsageEmpty' => $toolUsageEmpty,

            'costUsageChart' => $costUsageChart,
            'costUsageEmpty' => $costUsageEmpty,

            'productivityChart' => $productivityChart,
            'productivityEmpty' => $productivityEmpty,

            'departmentChart' => $departmentChart,
            'departmentEmpty' => $departmentEmpty,
        ]);
    }

    private function buildKpis(WorkSessionRepository $workSessionRepo, OutilsDeTravailRepository $toolRepo): array 
    {

        $timeStats = $workSessionRepo->getTimeStats();

        $totalActiveTime = $timeStats['totalActive'];
        $totalSessionTime = $timeStats['totalSession'];

        $productivityRate = 0;
        if ($totalSessionTime > 0) {
            $productivityRate = ($totalActiveTime / $totalSessionTime) * 100;
        }

        $totalCost = $toolRepo->getTotalCost();

        $costPerHour = 0;
        if ($totalActiveTime > 0) {
            $costPerHour = $totalCost / $totalActiveTime;
        }

        return [
            'totalActiveTime' => round($totalActiveTime, 2),
            'productivityRate' => round($productivityRate, 2),
            'totalCost' => round($totalCost, 2),
            'costPerHour' => round($costPerHour, 2),
            'insights' => []
        ];
    }

    private function buildToolUsageChart(ChartBuilderInterface $chartBuilder, WorkSessionDetailRepository $detailRepo): array
    {
        $rows = $detailRepo->getGlobalToolUsage();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = preg_replace('/\.exe$/i', '', $row['app']);
            $values[] = round($row['totalDuration'] / 60, 2);
        }

        $isEmpty = empty($values);

        if ($isEmpty) {
            $labels = ['Aucune donnée'];
            $values = [0];
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Temps (heures)',
                    'data' => $values,
                ],
            ],
        ]);

        return [$chart, $isEmpty];
    }

    private function buildCostUsageChart(
        ChartBuilderInterface $chartBuilder,
        WorkSessionDetailRepository $detailRepo,
        OutilsDeTravailRepository $toolRepo
    ): array {

        $usageData = $detailRepo->getGlobalToolUsage();
        $tools = $toolRepo->findAllTools();

        $toolMap = [];
        foreach ($tools as $tool) {
            $key = strtolower(trim($tool->getIdentifiantUniverselle()));
            $toolMap[$key] = $tool->getMonthlyCost();
        }

        $labels = [];
        $usage = [];
        $costs = [];

        foreach ($usageData as $row) {
            $app = strtolower(trim($row['app']));

            if (!isset($toolMap[$app])) continue;

            $labels[] = preg_replace('/\.exe$/i', '', $app);
            $usage[] = round($row['totalDuration'] / 60, 2);
            $costs[] = $toolMap[$app];
        }

        $isEmpty = empty($usage);

        if ($isEmpty) {
            $labels = ['Aucune donnée'];
            $usage = [0];
            $costs = [0];
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Utilisation (h)',
                    'data' => $usage,
                ],
                [
                    'label' => 'Coût (DT)',
                    'data' => $costs,
                ],
            ],
        ]);

        return [$chart, $isEmpty];
    }

    private function buildProductivityChart(
        ChartBuilderInterface $chartBuilder,
        WorkSessionRepository $workSessionRepo
    ): array {

        $data = $workSessionRepo->getProductivityBreakdown();

        $active = $data['active'] ?? 0;
        $afk = $data['afk'] ?? 0;
        $unknown = $data['unknown'] ?? 0;

        $total = $active + $afk + $unknown;

        $isEmpty = ($total === 0);

        if ($isEmpty) {
            $active = 1;
            $afk = 0;
            $unknown = 0;

            $isEmpty = false; // IMPORTANT FIX
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_PIE);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);

        $chart->setData([
            'labels' => ['Actif', 'AFK', 'Inconnu'],
            'datasets' => [
                [
                    'data' => [
                        round($active / 60, 2),
                        round($afk / 60, 2),
                        round($unknown / 60, 2),
                    ],
                ],
            ],
        ]);

        return [$chart, $isEmpty];
    }

    private function buildDepartmentChart(
        ChartBuilderInterface $chartBuilder,
        EmployeeRepository $employeeRepo
    ): array {

        $rows = $employeeRepo->getDepartmentProductivity();

        $labels = [];
        $values = [];

        foreach ($rows as $row) {
            $labels[] = $row['departement'] ?? 'N/A';
            $values[] = round($row['avgActive'] / 60, 2);
        }

        $isEmpty = empty($values);

        if ($isEmpty) {
            $labels = ['Aucune donnée'];
            $values = [0];
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);

        $chart->setOptions([
            'responsive' => true,
            'maintainAspectRatio' => false,
        ]);

        $chart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Temps actif moyen (h)',
                    'data' => $values,
                ],
            ],
        ]);

        return [$chart, $isEmpty];
    }

    private function buildInsights(array $toolUsageData, array $productivityData, float $totalCost): array
    {
        $insights = [];

        // ================= TOOL DOMINANCE =================
        if (!empty($toolUsageData)) {

            $totalDuration = array_sum(array_column($toolUsageData, 'totalDuration'));

            $topTool = $toolUsageData[0];
            $topPercent = $totalDuration > 0
                ? ($topTool['totalDuration'] / $totalDuration) * 100
                : 0;

            if ($topPercent > 40) {
                $insights[] = sprintf(
                    "%s domine l'utilisation (%.1f%% du temps total)",
                    ucfirst(preg_replace('/\.exe$/i', '', $topTool['app'])),
                    $topPercent
                );
            }
        }

        // ================= PRODUCTIVITY =================
        $active = $productivityData['active'] ?? 0;
        $afk = $productivityData['afk'] ?? 0;
        $total = $active + $afk + ($productivityData['unknown'] ?? 0);

        if ($total > 0) {
            $afkPercent = ($afk / $total) * 100;

            if ($afkPercent > 25) {
                $insights[] = sprintf(
                    "Temps inactif élevé (%.1f%%) → potentiel d'amélioration",
                    $afkPercent
                );
            }
        }

        // ================= COST SIGNAL =================
        if ($totalCost > 1000) {
            $insights[] = "Coût global des outils élevé → optimisation recommandée";
        }

        // ================= TOOL DISPERSION =================
        if (count($toolUsageData) > 10) {
            $insights[] = "Utilisation répartie sur de nombreux outils → possible dispersion";
        }

        return $insights;
    }
}