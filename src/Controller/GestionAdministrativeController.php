<?php

namespace App\Controller;

use App\Entity\Employee;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Repository\EmployeeRepository;
use App\Repository\OutilsDeTravailRepository;
use App\Repository\DemandeCongeRepository;
use App\Entity\Ordre;

class GestionAdministrativeController extends AbstractController
{

#pragma region Employee Controller
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
    
    #[Route('/Gestion_Administrative/employee/get/{id}', name: 'employee_get', methods: ['GET'])]
    public function getEmployee(int $id, EmployeeRepository $repo): Response
    {
        $employee = $repo->findEmployeeById($id);
        $data = $this->json($employee);

        return $data;
    }

    #[Route('/Gestion_Administrative/employee/update/{id}', name: 'employee_update', methods: ['POST'])]
    public function updateEmployee(int $id, Request $request, EmployeeRepository $repo): Response
    {
        $data = json_decode($request->getContent(), true);

        $repo->updateEmployee($id, $data);

        return $this->json(['success' => true]);
    }

    #[Route('/Gestion_Administrative/employee/create', name: 'employee_create', methods: ['POST'])]
    public function createEmployee(Request $request, EmployeeRepository $repo): Response
    {
        echo "Attempting to create a new employee\n"; // Debug statement
        $data = json_decode($request->getContent(), true);

        $repo->createEmployee($data);

        return $this->json(['success' => true]);
    }
    
    // ========== End Employee Controller ==========  //

#pragma endregion

#pragma region Outil Controller

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

    #[Route('/Gestion_Administrative/outils/get/{id}', name: 'tool_get', methods: ['GET'])]
    public function getTool(int $id, OutilsDeTravailRepository $toolRepository): Response
    {
        $tool = $toolRepository->findToolById($id);

        if (!$tool) {
            return $this->json([
                'error' => 'Tool not found'
            ], 404);
        }

        return $this->json([
            'id'   => $tool['ID_Outil'],
            'name' => $tool['Nom_Outil'],
            'exe'  => $tool['Identifiant_Universelle'],
            'hash' => $tool['Hash_App'],
        ]);
    }

    #[Route('/Gestion_Administrative/outils/create', name: 'tool_create', methods: ['POST'])]
    public function createTool(Request $request, OutilsDeTravailRepository $toolRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid data'], 400);
        }

        $toolRepository->createTool([
            'name' => $data['name'] ?? '',
            'exe'  => $data['exe'] ?? '',
            'hash' => $data['hash'] ?? '',
        ]);

        return $this->json([
            'success' => true
        ]);
    }

    #[Route('/Gestion_Administrative/outils/update/{id}', name: 'tool_update', methods: ['POST'])]
    public function updateTool(int $id, Request $request, OutilsDeTravailRepository $toolRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid data'], 400);
        }

        $toolRepository->updateTool($id, [
            'name' => $data['name'] ?? '',
            'exe'  => $data['exe'] ?? '',
            'hash' => $data['hash'] ?? '',
        ]);

        return $this->json([
            'success' => true
        ]);
    }

    // ========== End Outil Controller ==========  //

#pragma endregion
    
#pragma region Conge Controller
    // ========== Conge Controller ==========  //

    #[Route('/Gestion_Administrative/conges/reject/{id}', name: 'conge_reject')]
    public function rejectConge(int $id, DemandeCongeRepository $repo): Response
    {
        $repo->updateCongeStatus($id, -1); // rejected
        return $this->redirectToRoute('employee_conges');
    }

    #[Route('/Gestion_Administrative/conges/accept/{id}', name: 'conge_accept')]
    public function acceptConge(int $id, DemandeCongeRepository $repo): Response
    {
        $repo->updateCongeStatus($id, 1); // accepted 

        return $this->redirectToRoute('employee_conges');
    }

    #[Route('/Gestion_Administrative/conges', name: 'employee_conges')]
    public function conges(Request $request,DemandeCongeRepository $congeRepository): Response
    {   
        $allConges = array_map(fn($c) => [
            'id' => $c['ID_Demende'],
            'name' => $c['Nom_Utilisateur'],
            'start' => Ordre::numOrdreToDate((int)$c['Num_Ordre_Debut_Conge'])->format('Y-m-d'),
            'end' => Ordre::numOrdreToDate((int)$c['Num_Ordre_Fin_Conge'])->format('Y-m-d'),
            'days' => (int)$c['Nbr_Jour_Demande'],
        ], $congeRepository->findAllConges());
        
        

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
#pragma endregion
   
}

