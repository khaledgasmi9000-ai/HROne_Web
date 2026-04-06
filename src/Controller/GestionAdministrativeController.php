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

use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class GestionAdministrativeController extends AbstractController
{

#pragma region Employee Controller
    // ========== Employee Controller ==========  //

    // Export Functions 
    private function exportEmployeeCsv(array $employees): Response
    {
        $handle = fopen('php://temp', 'r+');

        // ✅ Header row
        fputcsv($handle, [
            'Nom',
            'Email',
            'Téléphone',
            'CIN',
            'Date Naissance',
            'Genre',
            'Solde Congé',
            'Salaire',
            'Heures'
        ]);

        // ✅ Data rows
        foreach ($employees as $emp) {
            $gender = match ($emp['Gender'] ?? '') {
                    'H','M','h','m' => 'Homme',
                    'F', 'f' => 'Femme',
                    default => ''
                };

            fputcsv($handle, [
                $emp['Nom_Utilisateur'] ?? '',
                $emp['Email'] ?? '',
                $emp['Num_Tel'] ?? '',
                $emp['CIN'] ?? '',
                $emp['Date_Naissance'] ?? '',
                $gender,
                $emp['Solde_Conge'] ?? '',
                $emp['SALAIRE'] ?? '',
                $emp['Nbr_Heure_De_Travail'] ?? ''
            ]);
        }

        rewind($handle);
        $csvContent = stream_get_contents($handle);
        fclose($handle);

        return new Response($csvContent, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="employees.csv"',
        ]);
    }

    private function exportEmployeeExcel(array $employees): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Header
        $headers = [
            'Nom', 'Email', 'Téléphone', 'CIN',
            'Date Naissance', 'Genre', 'Solde Congé',
            'Salaire', 'Heures'
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Data
        $row = 2;

        foreach ($employees as $emp) {

            $gender = match ($emp['Gender'] ?? '') {
                    'H','M','h','m' => 'Homme',
                    'F', 'f' => 'Femme',
                    default => ''
                };

            $sheet->fromArray([
                $emp['Nom_Utilisateur'] ?? '',
                $emp['Email'] ?? '',
                $emp['Num_Tel'] ?? '',
                $emp['CIN'] ?? '',
                $emp['Date_Naissance'] ?? '',
                $gender,
                $emp['Solde_Conge'] ?? '',
                $emp['SALAIRE'] ?? '',
                $emp['Nbr_Heure_De_Travail'] ?? ''
            ], null, 'A' . $row);

            $row++;
        }

        // Output
        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $excelContent = ob_get_clean();

        return new Response($excelContent, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="employees.xlsx"',
        ]);
    }
    
    private function exportEmployeePdf(array $employees, Pdf $pdf): Response
    {
        // Render Twig view as HTML
        $html = $this->renderView('Gestion Administrative/components/employees_pdf.html.twig', [
            'employees' => $employees
        ]);

        $output = $pdf->getOutputFromHtml($html);

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="employees.pdf"',
        ]);
    }

    //Helper function to calculate remaining conge
    private function CalculateSodeCongeRestant(
        EmployeeRepository $employeeRepository,
        int $soldeConge,
        int $employeeId
    ): int {
        $usedConge = 0; // This should be calculated based on actual conge data for the employee
        $usedConge = $employeeRepository->getNumberofUsedConge($employeeId);
        return $soldeConge - $usedConge; // temporary placeholder
    }

    // Routes
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

    #[Route('/Gestion_Administrative/employee/check', name: 'employee_check', methods: ['POST'])]
    public function checkEmployee(Request $request, EmployeeRepository $repo): Response
    {
        $data = json_decode($request->getContent(), true);

        $id = $data['id'] ?? null;

        $emailExists = $repo->emailExistsForOther($data['email'], $id);
        $cinExists   = $repo->cinExistsForOther($data['cin'], $id);

        return $this->json([
            'emailExists' => $emailExists,
            'cinExists' => $cinExists
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
        $data = json_decode($request->getContent(), true);

        $repo->createEmployee($data);

        return $this->json(['success' => true]);
    }
    
    #[Route('/Gestion_Administrative/employee/export', name: 'employee_export', methods: ['GET'])]
    public function exportEmployees(Request $request, EmployeeRepository $repo, Pdf $pdf): Response
    {
        $format = $request->query->get('format', 'csv');

        $employees = $repo->findAllEmployees();

        return match ($format) {
            'csv'   => $this->exportEmployeeCsv($employees),
            'excel' => $this->exportEmployeeExcel($employees),
            'pdf'   => $this->exportEmployeePdf($employees, $pdf),
            default => $this->json(['message' => 'Format non supporté'], 400),
        };
    }
    
    // ========== End Employee Controller ==========  //

#pragma endregion

#pragma region Outil Controller

    // ========== Outil Controller ==========  //
    // Export Functions
    private function exportToolsCsv(array $tools): Response
    {
        $handle = fopen('php://temp', 'r+');

        // Header
        fputcsv($handle, [
            'Nom',
            'Executable',
            'Hash'
        ]);

        foreach ($tools as $tool) {
            fputcsv($handle, [
                $tool['Nom_Outil'] ?? '',
                $tool['Identifiant_Universelle'] ?? '',
                $tool['Hash_App'] ?? ''
            ]);
        }

        rewind($handle);
        $csv = "\xEF\xBB\xBF" . stream_get_contents($handle);
        fclose($handle);

        return new Response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="tools.csv"',
        ]);
    }

    private function exportToolsExcel(array $tools): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->fromArray(['Nom', 'Executable', 'Hash'], null, 'A1');

        $row = 2;

        foreach ($tools as $tool) {
            $sheet->fromArray([
                $tool['Nom_Outil'] ?? '',
                $tool['Identifiant_Universelle'] ?? '',
                $tool['Hash_App'] ?? ''
            ], null, 'A' . $row);

            $row++;
        }

        $writer = new Xlsx($spreadsheet);

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return new Response($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="tools.xlsx"',
        ]);
    }

    private function exportToolsPdf(array $tools, Pdf $pdf): Response
    {
        $html = $this->renderView('Gestion Administrative/components/tools_pdf.html.twig', [
            'tools' => $tools
        ]);

        $output = $pdf->getOutputFromHtml($html);

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="tools.pdf"',
        ]);
    }

    // Helper Function to calculate average usage time
    private function CalculateAvgUseTime(OutilsDeTravailRepository $outilsRepository, int $outilId): int
    {
        // This function should calculate the average time based on actual usage data for the given tool ID
        // Placeholder implementation:
        return rand(30, 300); // Random value between 30 and 300 minutes
    }

    //Helper Function to calculate number of users per tool
    private function CalculateNbrofUserPerTool(OutilsDeTravailRepository $outilsRepository,int $outilId): int
    {
        // This function should calculate the number of users based on actual usage data for the given tool ID
        // Placeholder implementation:
        return rand(1, 100); // Random value between 1 and 100
    }

    // Routes
    #[Route('/Gestion_Administrative/outils/delete/{id}', name: 'tool_delete')]
    public function deleteTool(int $id, OutilsDeTravailRepository $outilsRepository): Response
    {
        echo "Attempting to delete tool with ID: $id\n"; // Debug statement
        $outilsRepository->deleteTool($id);

        return $this->redirectToRoute('employee_outils');
    }

    #[Route('/Gestion_Administrative/outils/ActivityWatch', name: 'activity_watch')]
    public function activityWatch(): Response
    {
        return $this->render('Employee FrontEnd/activity_watch.html.twig');
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

    #[Route('/Gestion_Administrative/outils/export', name: 'tool_export', methods: ['GET'])]
    public function exportTools(Request $request, OutilsDeTravailRepository $repo, Pdf $pdf): Response
    {
        $format = $request->query->get('format', 'csv');

        $tools = $repo->findAllTools();

        return match ($format) {
            'csv'   => $this->exportToolsCsv($tools),
            'excel' => $this->exportToolsExcel($tools),
            'pdf'   => $this->exportToolsPdf($tools, $pdf),
            default => $this->json(['message' => 'Format non supporté'], 400),
        };
    }

    #[Route('/Gestion_Administrative/outils/import', name: 'tool_import', methods: ['POST'])]
    public function importTools(Request $request, OutilsDeTravailRepository $repo): Response
    {
        $file = $request->files->get('file');

        if (!$file) {
            return $this->json(['message' => 'Fichier manquant'], 400);
        }

        $handle = fopen($file->getPathname(), 'r');

        if (!$handle) {
            return $this->json(['message' => 'Erreur lecture fichier'], 400);
        }

        $rowIndex = 0;
        $success = 0;
        $total = 0;

        while (($row = fgetcsv($handle)) !== false) {

            $rowIndex++;

            // Skip header
            if ($rowIndex === 1) continue;

            $total++;

            [$name, $exe, $hash] = $row;

            // 🔴 SAME VALIDATION AS MODAL
            if (!$name || strlen($name) < 3) continue;
            if (!$exe || !str_ends_with(strtolower($exe), '.exe')) continue;
            if (!$hash || !preg_match('/^[a-fA-F0-9]{16,}$/', $hash)) continue;

            try {
                $repo->createTool([
                    'name' => $name,
                    'exe'  => $exe,
                    'hash' => $hash,
                ]);

                $success++;

            } catch (\Exception $e) {
                // skip duplicates or DB errors
                continue;
            }
        }

        fclose($handle);

        return $this->json([
            'successCount' => $success,
            'total' => $total
        ]);
    }
    // ========== End Outil Controller ==========  //

#pragma endregion
    
#pragma region Conge Controller
    // ========== Conge Controller ==========  //

    // Routes
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

    #[Route('/Gestion_Administrative/conges/demande/{id}', name: 'demande_conge')]
    public function demandeConge(int $id,EmployeeRepository $employeeRepository): Response
    {
        $soldeRestant = $this->CalculateSodeCongeRestant($employeeRepository,$employeeRepository->getSoldeConge($id), $id);

        return $this->render('Employee FrontEnd/demande_conge.html.twig', [
            'soldeRestant' => $soldeRestant
        ]);
    }

    #[Route('/Gestion_Administrative/conges/create', name: 'conge_create', methods: ['POST'])]
    public function createConge(Request $request, DemandeCongeRepository $repo): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data || !isset($data['start'], $data['end'])) {
            return $this->json(['error' => 'Invalid data'], 400);
        }

        try {
            $repo->createConge($data["id_Employee"], $data['start'], $data['end'], $data['nbrJours']);

            return $this->json(['success' => true]);

        } catch (\Exception $e) {
            return $this->json([
                'error' => $e->getMessage()
            ], 500);
        }
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

