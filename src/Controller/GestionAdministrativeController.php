<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GestionAdministrativeController extends AbstractController
{
    #[Route('/Gestion_Administrative', name: 'gestion_administrative')]
    #[Route('/Gestion_Administrative/employee', name: 'employee_overview')]
    public function overview(Request $request): Response
    {
        $allEmployees = [
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Khaled', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Khaled', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Khaled', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Khaled', 'position' => 'Manager'],
            ['name' => 'John Doe', 'position' => 'Developer'],
            ['name' => 'Jane Smith', 'position' => 'Manager'],
        ];

        $rowsPerPage = 6;

        $currentPage = max(1, (int)$request->query->get('page', 1));
        $totalEmployees = count($allEmployees);
        $totalPages = (int) ceil($totalEmployees / $rowsPerPage);

        $offset = ($currentPage - 1) * $rowsPerPage;
        $employees = array_slice($allEmployees, $offset, $rowsPerPage);

        return $this->render('Gestion Administrative/overview.html.twig', [
            'employees' => $employees,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalEmployees' => $totalEmployees,
            'rowsPerPage' => $rowsPerPage
        ]);
    }

    #[Route('/Gestion_Administrative/outils', name: 'employee_outils')]
    public function outils(Request $request): Response
    {
        $allTools = [
            ['id' => 1, 'name' => 'Tool A', 'avgTime' => 120, 'users' => 5],
            ['id' => 2, 'name' => 'Tool B', 'avgTime' => 80,  'users' => 12],
            ['id' => 3, 'name' => 'Tool C', 'avgTime' => 200, 'users' => 3],
            ['id' => 4, 'name' => 'Tool D', 'avgTime' => 60,  'users' => 20],
            ['id' => 5, 'name' => 'Tool E', 'avgTime' => 150, 'users' => 8],
            ['id' => 6, 'name' => 'Tool F', 'avgTime' => 90,  'users' => 10],
            ['id' => 7, 'name' => 'Tool G', 'avgTime' => 300, 'users' => 2],
            ['id' => 8, 'name' => 'Tool H', 'avgTime' => 40,  'users' => 25],
        ];

        // Read ordering flags from query (?orderUsers=1&orderTime=1)
        $orderUsers = (int)$request->query->get('orderUsers', 0);
        $orderTime  = (int)$request->query->get('orderTime', 0);

        // Apply ordering (simple example; refine later)
        if ($orderUsers && $orderTime) {
            usort($allTools, fn($a, $b) =>
                ($b['users'] <=> $a['users']) ?: ($b['avgTime'] <=> $a['avgTime'])
            );
        } elseif ($orderUsers) {
            usort($allTools, fn($a, $b) => $b['users'] <=> $a['users']);
        } elseif ($orderTime) {
            usort($allTools, fn($a, $b) => $b['avgTime'] <=> $a['avgTime']);
        }

        // Pagination
        $rowsPerPage = 6;
        $currentPage = max(1, (int)$request->query->get('page', 1));
        $total = count($allTools);
        $totalPages = (int) ceil($total / $rowsPerPage);

        $offset = ($currentPage - 1) * $rowsPerPage;
        $tools = array_slice($allTools, $offset, $rowsPerPage);

        return $this->render('Gestion Administrative/outils.html.twig', [
            'tools' => $tools,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'rowsPerPage' => $rowsPerPage,
            'totalTools' => $total,
            'orderUsers' => $orderUsers,
            'orderTime' => $orderTime,
        ]);
    }

    #[Route('/Gestion_Administrative/conges', name: 'employee_conges')]
    public function conges(): Response
    {
        return $this->render('Gestion Administrative/conges.html.twig',[
            'totalPages' => 5,
        ]);
    }
}