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
    public function conges(Request $request): Response
    {
        $allConges = [
            ['id' => 1, 'name' => 'John Doe', 'start' => '2024-01-10', 'end' => '2024-01-15', 'days' => 5],
            ['id' => 2, 'name' => 'Jane Smith', 'start' => '2024-02-01', 'end' => '2024-02-03', 'days' => 2],
            ['id' => 3, 'name' => 'Alice Brown', 'start' => '2024-03-05', 'end' => '2024-03-10', 'days' => 5],
            ['id' => 4, 'name' => 'Bob White', 'start' => '2024-04-12', 'end' => '2024-04-14', 'days' => 2],
            ['id' => 5, 'name' => 'Emma Green', 'start' => '2024-05-01', 'end' => '2024-05-07', 'days' => 6],
            ['id' => 6, 'name' => 'Tom Black', 'start' => '2024-06-10', 'end' => '2024-06-12', 'days' => 2],
            ['id' => 7, 'name' => 'Sara Blue', 'start' => '2024-07-15', 'end' => '2024-07-20', 'days' => 5],
            ['id' => 8, 'name' => 'John Doe', 'start' => '2024-01-10', 'end' => '2024-01-15', 'days' => 5],
            ['id' => 9, 'name' => 'Jane Smith', 'start' => '2024-02-01', 'end' => '2024-02-03', 'days' => 2],
            ['id' => 10, 'name' => 'Alice Brown', 'start' => '2024-03-05', 'end' => '2024-03-10', 'days' => 5],
            ['id' => 11, 'name' => 'Bob White', 'start' => '2024-04-12', 'end' => '2024-04-14', 'days' => 2],
            ['id' => 12, 'name' => 'Emma Green', 'start' => '2024-05-01', 'end' => '2024-05-07', 'days' => 6],
            ['id' => 13, 'name' => 'Tom Black', 'start' => '2024-06-10', 'end' => '2024-06-12', 'days' => 2],
            ['id' => 14, 'name' => 'Sara Blue', 'start' => '2024-07-15', 'end' => '2024-07-20', 'days' => 5],
            ['id' => 1, 'name' => 'John Doe', 'start' => '2024-01-10', 'end' => '2024-01-15', 'days' => 5],
            ['id' => 2, 'name' => 'Jane Smith', 'start' => '2024-02-01', 'end' => '2024-02-03', 'days' => 2],
            ['id' => 3, 'name' => 'Alice Brown', 'start' => '2024-03-05', 'end' => '2024-03-10', 'days' => 5],
            ['id' => 4, 'name' => 'Bob White', 'start' => '2024-04-12', 'end' => '2024-04-14', 'days' => 2],
            ['id' => 5, 'name' => 'Emma Green', 'start' => '2024-05-01', 'end' => '2024-05-07', 'days' => 6],
            ['id' => 6, 'name' => 'Tom Black', 'start' => '2024-06-10', 'end' => '2024-06-12', 'days' => 2],
            ['id' => 7, 'name' => 'Sara Blue', 'start' => '2024-07-15', 'end' => '2024-07-20', 'days' => 5],
            ['id' => 8, 'name' => 'John Doe', 'start' => '2024-01-10', 'end' => '2024-01-15', 'days' => 5],
            ['id' => 9, 'name' => 'Jane Smith', 'start' => '2024-02-01', 'end' => '2024-02-03', 'days' => 2],
            ['id' => 10, 'name' => 'Alice Brown', 'start' => '2024-03-05', 'end' => '2024-03-10', 'days' => 5],
            ['id' => 11, 'name' => 'Bob White', 'start' => '2024-04-12', 'end' => '2024-04-14', 'days' => 2],
            ['id' => 12, 'name' => 'Emma Green', 'start' => '2024-05-01', 'end' => '2024-05-07', 'days' => 6],
            ['id' => 13, 'name' => 'Tom Black', 'start' => '2024-06-10', 'end' => '2024-06-12', 'days' => 2],
            ['id' => 14, 'name' => 'Sara Blue', 'start' => '2024-07-15', 'end' => '2024-07-20', 'days' => 5],
        ];

        $rowsPerPage = 7;
        $currentPage = max(1, (int)$request->query->get('page', 1));

        $total = count($allConges);
        $totalPages = (int) ceil($total / $rowsPerPage);

        $offset = ($currentPage - 1) * $rowsPerPage;
        $conges = array_slice($allConges, $offset, $rowsPerPage);

        return $this->render('Gestion Administrative/conges.html.twig',[
            'conges' => $conges,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'rowsPerPage' => $rowsPerPage,
            'totalConges' => $total,
        ]);
    }
}