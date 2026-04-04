<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmployeeController extends AbstractController
{
    #[Route('/employee', name: 'employee_overview')]
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

        return $this->render('employee/overview.html.twig', [
            'employees' => $employees,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalEmployees' => $totalEmployees,
            'rowsPerPage' => $rowsPerPage
        ]);
    }

    #[Route('/employee/outils', name: 'employee_outils')]
    public function outils(): Response
    {
        return $this->render('employee/outils.html.twig',[
            'totalPages' => 5,
        ]);
    }

    #[Route('/employee/conges', name: 'employee_conges')]
    public function conges(): Response
    {
        return $this->render('employee/conges.html.twig',[
            'totalPages' => 5,
        ]);
    }
}