<?php

namespace App\Controller;

use App\Entity\Employee;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EmployeeRepository;
use App\Repository\OutilsDeTravailRepository;

class GestionAdministrativeController extends AbstractController
{

    // ========== Employee Controller ==========  //
    private function CalculateSodeCongeRestant(
        EmployeeRepository $employeeRepository,
        int $soldeConge,
        int $employeeId
    ): int {
        $usedConge = 0; // This should be calculated based on actual conge data for the employee
        $usedConge = $employeeRepository->getNumberofUsedConge($employeeId);
        return $soldeConge - $usedConge; // temporary placeholder
    }

    #[Route('/Gestion_Administrative', name: 'gestion_administrative')]
    public function index(): Response
    {
        return $this->redirectToRoute('employee_overview');
    }

    #[Route('/Gestion_Administrative/employee', name: 'employee_overview')]
    public function overview(Request $request, EmployeeRepository $employeeRepository): Response
    {
        
        $allEmployees = array_map(function($emp) use ($employeeRepository) {
            return [
                'id' => $emp['ID_Employe'],
                'name' => $emp['Nom_Utilisateur'],
                'salaire' => (int)$emp['SALAIRE'],
                'heures' => (int)$emp['Nbr_Heure_De_Travail'],
                'email' => $emp['Email'],
                'soldeConge' => (int)$emp['Solde_Conge'],
                'soldeRestant' => $this->CalculateSodeCongeRestant($employeeRepository ,$emp['Solde_Conge'], $emp['ID_Employe'])
            ];
        }, $employeeRepository->findAllEmployees());
        
        // ✅ Get filters from request
        $search = $request->query->get('search') ?? '';
        $hours  = $request->query->get('hours') ?? 0;
        $salary = $request->query->get('salary') ?? 0;

        // ✅ FILTER FIRST
        $filtered = array_filter($allEmployees, function ($employee) use ($search, $hours, $salary) {

            // 🔍 Search (name)
            if ($search && stripos($employee['name'], $search) === false) {
                return false;
            }

            // ⏱ Hours filter
            if ($hours && $employee['heures'] < (int)$hours) {
                return false;
            }

            // 💰 Salary filter
            if ($salary && $employee['salaire'] < (int)$salary) {
                return false;
            }

            return true;
        });

        // Re-index array after filtering
        $filtered = array_values($filtered);

        // ✅ PAGINATION AFTER FILTERING
        $rowsPerPage = 6;
        $currentPage = max(1, (int)$request->query->get('page', 1));

        $totalEmployees = count($filtered);
        $totalPages = (int) ceil($totalEmployees / $rowsPerPage);

        $offset = ($currentPage - 1) * $rowsPerPage;
        $employees = array_slice($filtered, $offset, $rowsPerPage);
        
        return $this->render('Gestion Administrative/overview.html.twig', [
            'employees' => $employees,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalEmployees' => $totalEmployees,
            'rowsPerPage' => $rowsPerPage
        ]);
    }

    #[Route('/Gestion_Administrative/employee/delete/{id}', name: 'employee_delete')]
    public function deleteEmployee(int $id, EmployeeRepository $employeeRepository): Response
    {
        echo "Attempting to delete employee with ID: $id\n"; // Debug statement
        $employeeRepository->deleteEmployee($id);

        return $this->redirectToRoute('gestion_administrative');
    }
    // ========== End Employee Controller ==========  //

    // ========== Outil Controller ==========  //

    private function CalculateAvgUseTime(OutilsDeTravailRepository $outilsRepository, int $outilId): int
    {
        // This function should calculate the average time based on actual usage data for the given tool ID
        // Placeholder implementation:
        return rand(30, 300); // Random value between 30 and 300 minutes
    }

    private function CalculateNbrofUserPerTool(OutilsDeTravailRepository $outilsRepository,int $outilId): int
    {
        // This function should calculate the number of users based on actual usage data for the given tool ID
        // Placeholder implementation:
        return rand(1, 100); // Random value between 1 and 100
    }

    #[Route('/Gestion_Administrative/outils/delete/{id}', name: 'tool_delete')]
    public function deleteTool(int $id, OutilsDeTravailRepository $outilsRepository): Response
    {
        echo "Attempting to delete tool with ID: $id\n"; // Debug statement
        $outilsRepository->deleteTool($id);

        return $this->redirectToRoute('employee_outils');
    }

    #[Route('/Gestion_Administrative/outils', name: 'employee_outils')]
    public function outils(Request $request ,OutilsDeTravailRepository $outilsRepository): Response
    {

        $allTools = array_map(function($tool) use($outilsRepository) {
            return [
                'id' => $tool['ID_Outil'],
                'name' => $tool['Nom_Outil'],
                'avgTime' => $this->CalculateAvgUseTime($outilsRepository, $tool['ID_Outil']), // This function should calculate the average time based on actual usage data
                'users' => $this->CalculateNbrofUserPerTool($outilsRepository, $tool['ID_Outil']), // This function should calculate the number of users based on actual usage data
            ];
        }, $outilsRepository->findAllTools());
        
        // 🔍 Search
        $search = $request->query->get('search');

        // 🎛 Ordering
        $orderUsers = (int)$request->query->get('orderUsers', 0);
        $orderTime  = (int)$request->query->get('orderTime', 0);

        // ✅ FILTER FIRST
        $filtered = array_filter($allTools, function ($tool) use ($search) {

            if ($search && stripos($tool['name'], $search) === false) {
                return false;
            }

            return true;
        });

        // Reindex
        $filtered = array_values($filtered);

        // ✅ ORDER AFTER FILTER
        if ($orderUsers && $orderTime) {
            usort($filtered, fn($a, $b) =>
                ($b['users'] <=> $a['users']) ?: ($b['avgTime'] <=> $a['avgTime'])
            );
        } elseif ($orderUsers) {
            usort($filtered, fn($a, $b) => $b['users'] <=> $a['users']);
        } elseif ($orderTime) {
            usort($filtered, fn($a, $b) => $b['avgTime'] <=> $a['avgTime']);
        }

        // ✅ PAGINATION LAST
        $rowsPerPage = 6;
        $currentPage = max(1, (int)$request->query->get('page', 1));

        $totalTools = count($filtered);
        $totalPages = (int) ceil($totalTools / $rowsPerPage);

        $offset = ($currentPage - 1) * $rowsPerPage;
        $tools = array_slice($filtered, $offset, $rowsPerPage);

        return $this->render('Gestion Administrative/outils.html.twig', [
            'tools' => $tools,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'rowsPerPage' => $rowsPerPage,
            'totalTools' => $totalTools,
            'orderUsers' => $orderUsers,
            'orderTime' => $orderTime,
        ]);
    }

    // ========== End Outil Controller ==========  //

    // ========== Conge Controller ==========  //
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

    // ========== End Conge Controller ==========  //
}

// $allEmployees = [
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2500, 'heures' => 160],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3200, 'heures' => 180],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 3000, 'heures' => 170],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2200, 'heures' => 150],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3500, 'heures' => 190],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 2800, 'heures' => 160],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 3000, 'heures' => 170],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2200, 'heures' => 150],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3500, 'heures' => 190],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 2800, 'heures' => 160],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2500, 'heures' => 160],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3200, 'heures' => 180],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 3000, 'heures' => 170],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2200, 'heures' => 150],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3500, 'heures' => 190],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 2800, 'heures' => 160],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 3000, 'heures' => 170],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2200, 'heures' => 150],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3500, 'heures' => 190],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 2800, 'heures' => 160],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2500, 'heures' => 160],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3200, 'heures' => 180],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 3000, 'heures' => 170],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2200, 'heures' => 150],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3500, 'heures' => 190],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 2800, 'heures' => 160],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 3000, 'heures' => 170],
        //     ['name' => 'John Doe', 'position' => 'Developer', 'salaire' => 2200, 'heures' => 150],
        //     ['name' => 'Jane Smith', 'position' => 'Manager', 'salaire' => 3500, 'heures' => 190],
        //     ['name' => 'Khaled', 'position' => 'Manager', 'salaire' => 2800, 'heures' => 160],
        // ];

        // $allTools = [
        //     ['id' => 1, 'name' => 'Tool A', 'avgTime' => 120, 'users' => 5],
        //     ['id' => 2, 'name' => 'Tool B', 'avgTime' => 80,  'users' => 12],
        //     ['id' => 3, 'name' => 'Tool C', 'avgTime' => 200, 'users' => 3],
        //     ['id' => 4, 'name' => 'Tool D', 'avgTime' => 60,  'users' => 20],
        //     ['id' => 5, 'name' => 'Tool E', 'avgTime' => 150, 'users' => 8],
        //     ['id' => 6, 'name' => 'Tool F', 'avgTime' => 90,  'users' => 10],
        //     ['id' => 7, 'name' => 'Tool G', 'avgTime' => 300, 'users' => 2],
        //     ['id' => 8, 'name' => 'Tool H', 'avgTime' => 40,  'users' => 25],
        // ];