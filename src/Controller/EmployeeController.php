<?php

namespace App\Controller;

use App\Repository\EmployeeRepository;
use App\Repository\OutilsDeTravailRepository;
use App\Repository\UtilisateurRepository;
use App\Repository\DepartementRepository;


use App\Entity\Utilisateur;

use App\Repository\OrdreRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class EmployeeController extends AbstractController{

#pragma region Employee Controller
    // ========== Employee Controller ==========  //

    // Export Functions 
    
    private function formatEmployee($emp): array
    {
        $user = $emp->getUtilisateur();

        if (!$user) {
            return [];
        }

        $genderRaw = strtolower($user->getGender() ?? '');

        $gender = match ($genderRaw) {
            'h', 'm' => 'Homme',
            'f'      => 'Femme',
            default  => ''
        };

        return [
            'Nom' => $user->getNomUtilisateur() ?? '',
            'Email' => $user->getEmail() ?? '',
            'Téléphone' => $user->getNumTel() ?? '',
            'CIN' => $user->getCIN() ?? '',
            'Date Naissance' => $user->getDateNaissance()?->format('Y-m-d') ?? '',
            'Genre' => $gender,
            
            'Solde Congé' => $emp->getSoldeConge() ?? 0,
            'Salaire' => $emp->getSALAIRE() ?? 0,
            'Heures' => $emp->getNbrHeureDeTravail() ?? 0,
            'Departement' => $emp->getDepartement() ? $emp->getDepartement()->getNom() : 'N/A'
        ];
    }

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
            'Heures',
            'Departement'
        ]);

        // ✅ Data rows
        foreach ($employees as $emp) {
            fputcsv($handle, $this->formatEmployee($emp));
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
            'Salaire', 'Heures','Departement'
        ];

        $sheet->fromArray($headers, null, 'A1');

        // Data
        $row = 2;

        foreach ($employees as $emp) {

            $sheet->fromArray(array_values($this->formatEmployee($emp)), null, 'A' . $row);

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
        $formatted = array_map(fn($emp) => $this->formatEmployee($emp), $employees);
        // Render Twig view as HTML
        $html = $this->renderView('Gestion Administrative/components/employees_pdf.html.twig', [
            'employees' => $formatted,
            'public_path' => str_replace('\\', '/', $this->getParameter('kernel.project_dir') . '/public')
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

    // Data validation function
    private function validateEmployeeData(array $data,UtilisateurRepository $userRepo, bool $isEdit = false): array
    {
        $errors = [];

        if (empty($data['name'])) {
            $errors['name'] = 'Nom requis';
        }

        if(!$isEdit){
            if (!empty($data['email'])) {
                if ($userRepo->emailExistsForOther($data['email'], $data['id'] ?? null)) {
                    $errors['email'] = 'Email déjà utilisé';
                }
            }else{
                $errors['email'] = 'Email obligatoire';
            }

            if (!empty($data['cin'])) {
                if ($userRepo->cinExistsForOther($data['cin'], $data['id'] ?? null)) {
                    $errors['cin'] = 'CIN déjà utilisé';
                }
            }else{
                $errors['cin'] = 'CIN obligatoire';
            }
        }
        

        if (!$isEdit && empty($data['phone'])) {
            $errors['phone'] = 'Téléphone requis';
        }

        if (!empty($data['birth'])) {
            try {
                $date = new \DateTime($data['birth']);
                if ($date >= new \DateTime()) {
                    $errors['birth'] = 'Date doit être passée';
                }
            } catch (\Exception $e) {
                $errors['birth'] = 'Date invalide';
            }
        } else {
            $errors['birth'] = 'Date requise';
        }

        if (!isset($data['salaire']) || $data['salaire'] < 0) {
            $errors['salaire'] = 'Salaire invalide';
        }

        if (!isset($data['solde']) || $data['solde'] < 0) {
            $errors['solde'] = 'Solde invalide';
        }

        if (!isset($data['heures']) || $data['heures'] < 0) {
            $errors['heures'] = 'Heures invalides';
        }

        if (!isset($data['gender']) || empty($data['gender'])){
            $errors['gender'] = 'Sexe obligatoire';
        }
        
        if (empty($data['departement'])) {
            $errors['departement'] = 'Département obligatoire';
        }

        return $errors;
    }
    
    // Routes
    #[Route('/Gestion_Administrative', name: 'gestion_administrative')]
    public function indexGestionAdministrative(): Response
    {
        return $this->redirectToRoute('employee_overview');
    }

    #[Route('/Gestion_Administrative/employee', name: 'employee_overview')]
    public function overview(Request $request, EmployeeRepository $employeeRepository, DepartementRepository $depRepo): Response
    {
        
        $allEmployees = array_map(function($emp) use ($employeeRepository) {
            return [
                'id' => $emp->getIDEmploye(),
                'name' => $emp->getUtilisateur()->getNomUtilisateur(),
                'salaire' => $emp->getSalaire(),
                'heures' => $emp->getNbrHeureDeTravail(),
                'email' => $emp->getUtilisateur()->getEmail(),
                'soldeConge' => $emp->getSoldeConge(),
                'soldeRestant' => $this->CalculateSodeCongeRestant($employeeRepository ,$emp->getSoldeConge(), $emp->getIDEmploye()),
                'departement' => $emp->getDepartement() ? $emp->getDepartement()->getNom() : 'N/A'
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
        
        $departements = $depRepo->findAllDepartements();
        return $this->render('Gestion Administrative/overview.html.twig', [
            'employees' => $employees,
            'currentPage' => $currentPage,
            'totalPages' => $totalPages,
            'totalEmployees' => $totalEmployees,
            'rowsPerPage' => $rowsPerPage,
            'departements' => $departements
        ]);
    }

    #[Route('/Gestion_Administrative/employee/delete/{id}', name: 'employee_delete',methods: ['POST'])]
    public function deleteEmployee(int $id, EmployeeRepository $employeeRepository): Response
    {
       try {
            $deleted = $employeeRepository->deleteEmployee($id);

            if (!$deleted) {
                return $this->json([
                    'success' => false,
                    'error' => 'Employé introuvable'
                ], 404);
            }

            return $this->json([
                'success' => true,
                'message' => 'Employé supprimé avec succès'
            ], 200);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => 'Erreur suppression: ' . $e->getMessage()
            ], 500);
        }
    }
    
    #[Route('/Gestion_Administrative/employee/get/{id}', name: 'employee_get', methods: ['GET'])]
    public function getEmployee(int $id, EmployeeRepository $repo): Response
    {
        $employee = $repo->findEmployeeById($id);
        $user = $employee->getUtilisateur();

        return $this->json([
            'id' => $employee->getIDEmploye(),
            'name' => $user->getNomUtilisateur(),
            'email' => $user->getEmail(),
            'phone' => $user->getNumTel(),
            'cin' => $user->getCIN(),
            'birth' => $user->getDateNaissance()?->format('Y-m-d'),
            'gender' => $user->getGender(),
            'salaire' => $employee->getSalaire(),
            'heures' => $employee->getNbrHeureDeTravail(),
            'solde' => $employee->getSoldeConge(),
            'departement' => $employee->getDepartement() ? $employee->getDepartement()->getIDDepartement() : null
        ]);
    }

    #[Route('/Gestion_Administrative/employee/update/{id}', name:'employee_update', methods: ['POST'])]
    public function updateEmployee(
        int $id,
        Request $request,
        EmployeeRepository $repo,
        UtilisateurRepository $userRepo
    ): Response {

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['error' => 'Invalid JSON'], 400);
        }

        $employee = $repo->find($id);
        if (!$employee) {
            return $this->json(['error' => 'Employee not found'], 404);
        }

        $errors = $this->validateEmployeeData($data,$userRepo, true);

        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        // ===== UPDATE EMPLOYEE =====
        $repo->updateEmployee($id, $data);

        // ===== UPDATE USER =====
        $user = $employee->getUtilisateur();
        $userRepo->updateUser($user, $data);

        return $this->json(['success' => true]);
    }

    #[Route('/Gestion_Administrative/employee/create', name: 'employee_create', methods: ['POST'])]
    public function createEmployee(Request $request,
        EmployeeRepository $employeeRepo,
        UtilisateurRepository $userRepo,
        OrdreRepository $ordreRepository,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): Response {
        
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json(['success' => false, 'error' => 'Invalid JSON'], 400);
        }

        $errors = $this->validateEmployeeData($data,$userRepo, false);

        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        // ===== CREATE USER =====
        $user = new Utilisateur();
        $user->setNomUtilisateur($data['name']);
        $user->setEmail($data['email']);
        $user->setCIN($data['cin']);
        $user->setNum_Tel($data['phone']);
        $user->setGender($data['gender']);
        $user->setDateNaissance(new \DateTime($data['birth']));
        $user->setMotPasse($passwordHasher->hashPassword($user, 'Changeme'));

        $user->setOrdre($ordreRepository->getOrCreateOrdre(1));

        $entreprise = $em->getRepository(\App\Entity\Entreprise::class)->find(1);
        $profil = $em->getRepository(\App\Entity\Profil::class)->find(3);

        if (!$entreprise) {
            return $this->json(['error' => 'Entreprise not found'], 500);
        }

        if (!$profil) {
            return $this->json(['error' => 'Profil not found'], 500);
        }

        $user->setEntreprise($entreprise);
        $user->setProfil($profil);

        $em->persist($user);

        // ===== CREATE EMPLOYEE =====
        $employee = $employeeRepo->createEmployee($data, $user);

        return $this->json([
            'success' => true,
            'id' => $employee->getIDEmploye()
        ]);
    }
    
    #[Route('/Gestion_Administrative/employee/export', name: 'employee_export', methods: ['GET'])]
    public function exportEmployees(Request $request, EmployeeRepository $repo, Pdf $pdf): Response
    {
        $format = $request->query->get('format', 'csv');

        $employees = $repo->findAllEmployees();

        if (empty($employees)) {
            return new Response('Aucune donnée à exporter', 204);
        }

        return match ($format) {
            'csv'   => $this->exportEmployeeCsv($employees),
            'excel' => $this->exportEmployeeExcel($employees),
            'pdf'   => $this->exportEmployeePdf($employees, $pdf),
            default => $this->json(['message' => 'Format non supporté'], 400),
        };
    }
    
    #[Route('/Gestion_Administrative/employee/{id}/tools', name: 'employee_tools_get', methods: ['GET'])]
    public function getEmployeeTools(int $id,EmployeeRepository $employeeRepo,OutilsDeTravailRepository $toolRepo): Response {

        $allTools = $toolRepo->findAllTools();

        $assignedTools = $employeeRepo->getEmployeeTools($id);

        // Convert assigned to simple ID array
        $assignedIds = array_map(fn($t) => $t['ID_Outil'], $assignedTools);

        $tools = array_map(function($tool) use ($assignedIds) {
            return [
                'id' => $tool->getIDOutil(),
                'name' => $tool->getNomOutil(),
                'checked' => in_array($tool->getIDOutil(), $assignedIds)
            ];
        }, $allTools);

        return $this->json([
            'tools' => $tools
        ]);
    }


    #[Route('/Gestion_Administrative/employee/{id}/tools', name: 'employee_tools_save', methods: ['POST'])]
    public function saveEmployeeTools(int $id,Request $request,EmployeeRepository $employeeRepo): Response {

        $data = json_decode($request->getContent(), true);

        $tools = $data['tools'] ?? [];

        $employeeRepo->updateEmployeeTools($id, $tools);

        return $this->json(['success' => true]);
    }

    #[Route('/Gestion_Administrative/employee/fiche/{id}', name: 'employee_fiche')]
    public function ficheEmployee(int $id, EmployeeRepository $repo): Response
    {
        // =========================
        // DEBUG 0 — Route hit check
        // =========================
            //dump("ROUTE HIT", $id);

        $employee = $repo->findEmployeeById($id);

        if (!$employee) {
            //dump("Employee NOT found");
            throw $this->createNotFoundException('Employé non trouvé');
        }

        //dump("Employee FOUND", $employee->getIDEmploye());

        // =========================
        // 1. Managed tools map
        // =========================
        $managedToolsMap = [];

        foreach ($employee->getOutilsDeTravails() as $outil) {
            $key = strtolower(trim($outil->getIdentifiantUniverselle()));
            $managedToolsMap[$key] = $outil->getNomOutil() ?? $key;
        }

        //dump("Managed tools", $managedToolsMap);

        // =========================
        // 2. Init variables
        // =========================
        $tools = [];
        $productifTime = 0;
        $nonProductifTime = 0;
        $idleTime = 0;

        // =========================
        // 3. Loop sessions
        // =========================
        foreach ($employee->getWorkSessions() as $session) {

            $idleTime += $session->getAfkTime() ?? 0;

            foreach ($session->getDetails() as $detail) {

                $appRaw = strtolower(trim($detail->getApp()));
                $duration = $detail->getDuration();

                if (!$appRaw) continue;

                $isManaged = isset($managedToolsMap[$appRaw]);

                $app = $isManaged
                    ? $managedToolsMap[$appRaw]
                    : preg_replace('/\.exe$/i', '', $appRaw);

                if (!isset($tools[$app])) {
                    $tools[$app] = 0;
                }

                $tools[$app] += $duration;

                if ($isManaged) {
                    $productifTime += $duration;
                } else {
                    $nonProductifTime += $duration;
                }
            }
        }

        //dump("Tools aggregated", $tools);

        // =========================
        // 4. Global time
        // =========================
        $totalTimeSeconds = array_sum($tools);
        $totalTime = round($totalTimeSeconds / 60, 2);

        //dump("Total time (h)", $totalTime);

        // =========================
        // 5. Build toolsData
        // =========================
        $toolsData = [];

        $monthlyHours = $employee->getNbrHeureDeTravail() ?: 160;
        $salary = $employee->getSALAIRE();

        foreach ($tools as $app => $time) {

            $hours = round($time / 60, 2);

            $percent = $totalTime > 0
                ? round(($hours / $totalTime) * 100, 2)
                : 0;

            $cost = ($salary && $hours > 0)
                ? round(($hours / $monthlyHours) * $salary, 2)
                : 0;

            $toolsData[] = [
                'name' => $app,
                'temps' => $hours,
                'percent' => $percent,
                'cout' => $cost
            ];
        }

        usort($toolsData, fn($a, $b) => $b['temps'] <=> $a['temps']);

        //dump("Tools data sorted", $toolsData);

        // =========================
        // 6. Split tools
        // =========================
        $managedTopTools = [];
        $unmanagedTopTools = [];

        foreach ($toolsData as $tool) {

            $isManaged = in_array($tool['name'], array_values($managedToolsMap));

            if ($isManaged) {
                $managedTopTools[] = $tool;
            } else {
                $unmanagedTopTools[] = $tool;
            }
        }

        $managedTopTools = array_slice($managedTopTools, 0, 3);

        //dump("Managed top tools", $managedTopTools);

        // =========================
        // 7. Stats
        // =========================
        $toolsCount = count($toolsData);

        $totalCost = ($salary && $totalTime > 0)
            ? round(($totalTime / $monthlyHours) * $salary, 2)
            : 0;

        $breakdown = [
            'productif' => round($productifTime / 60, 2),
            'nonProductif' => round($nonProductifTime / 60, 2),
            'idle' => round($idleTime / 60, 2)
        ];

        //dump("Breakdown", $breakdown);

        // =========================
        // 8. Format tools for AI
        // =========================
        $topToolsFormatted = array_map(
            fn($t) => "{$t['name']} ({$t['temps']}h)",
            $managedTopTools
        );

        $topToolsStr = implode(', ', $topToolsFormatted);

        //dump("Top tools string", $topToolsStr);

        // =========================
        // 9. Build prompt
        // =========================
        $prompt = trim(sprintf(
        "Tu rédiges un court résumé pour une fiche employé affichée dans une application de gestion.

        Contraintes STRICTES :
        - 2 à 3 phrases maximum
        - Ton neutre et professionnel
        - AUCUNE première personne (interdit : je, nous)
        - AUCUNE mise en contexte (interdit : \"en tant que\", \"je suis\", etc.)
        - Pas d’introduction, aller directement à l’analyse
        - Style direct, factuel
        - Phrase 1: niveau de productivité  
        - Phrase 2: observation principale  
        - Phrase 3: recommandation

        Données :
        - Temps total : %s h
        - Nombre d’outils : %d
        - Coût total : %s TND
        - Temps productif : %s h
        - Temps non productif : %s h
        - Temps idle : %s h
        - Outils principaux : %s

        Objectif :
        - Évaluer le niveau de productivité
        - Mentionner un point d’amélioration si pertinent",
            $totalTime,
            $toolsCount,
            $totalCost,
            $breakdown['productif'],
            $breakdown['nonProductif'],
            $breakdown['idle'],
            $topToolsStr ?: 'Aucun'
        ));

        //dump("Prompt", $prompt);

        // =========================
        // 10. Call Groq
        // =========================
        try {
            $client = HttpClient::create();

            $response = $client->request('POST', 'https://api.groq.com/openai/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $_ENV['GROQ_API_KEY'],
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => 'llama-3.3-70b-versatile',
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => $prompt
                        ]
                    ],
                    'max_tokens' => 100
                ],
            ]);

            $raw = $response->getContent(false);
            //dump("Raw API response", $raw);

            $data = json_decode($raw, true);

            $summary = $data['choices'][0]['message']['content'] ?? 'Résumé indisponible';

        } catch (\Exception $e) {
            dump("API ERROR", $e->getMessage());
            $summary = 'Erreur génération résumé';
        }

        //dump("Final summary", $summary);

        // =========================
        // 11. Render
        // =========================
        return $this->render('Gestion Administrative/fiche_employee.html.twig', [
            'employee' => $employee,
            'stats' => [
                'totalTime' => $totalTime,
                'toolsCount' => $toolsCount,
                'totalCost' => $totalCost
            ],
            'tools' => $toolsData,
            'managedTopTools' => $managedTopTools,
            'unmanagedTopTools' => $unmanagedTopTools,
            'breakdown' => $breakdown,
            'summary' => $summary
        ]);
    }

    // ========== End Employee Controller ==========  //

#pragma endregion


}