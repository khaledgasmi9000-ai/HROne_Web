<?php

namespace App\Controller;

use App\Repository\OutilsDeTravailRepository;
use App\Repository\CategorieRepository;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

use Knp\Snappy\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ToolController extends AbstractController{

#pragma region Outil Controller

    // ========== Outil Controller ==========  //
    // Export Functions

    private function formatTool($tool): array
    {
        return [
            'Nom' => $tool->getNomOutil() ?? '',
            'Executable' => $tool->getIdentifiantUniverselle() ?? '',
            'Hash' => $tool->getHashApp() ?? '',
            'Cout Mensuel' => $tool->getMonthlyCost() ?? 0,
            'categorie' => $tool->getCategorie()?->getNom() ?? 'Non catégorisé',
        ];
    }

    private function exportToolsCsv(array $tools): Response
    {
        $handle = fopen('php://temp', 'r+');

        if (empty($tools)) {
            return new Response('Aucune donnée', 204);
        }

        $first = $this->formatTool($tools[0]);
        fputcsv($handle, array_keys($first));

        foreach ($tools as $tool) {
            fputcsv($handle, array_values($this->formatTool($tool)));
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

        if (empty($tools)) {
            return new Response('Aucune donnée', 204);
        }

        $headers = array_keys($this->formatTool($tools[0]));
        $sheet->fromArray($headers, null, 'A1');

        $row = 2;

        foreach ($tools as $tool) {
            $sheet->fromArray(
                array_values($this->formatTool($tool)),
                null,
                'A' . $row
            );
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
        $formatted = array_map(fn($t) => $this->formatTool($t), $tools);

        $html = $this->renderView('Gestion Administrative/components/tools_pdf.html.twig', [
            'tools' => $formatted,
            'public_path' => str_replace('\\', '/', $this->getParameter('kernel.project_dir') . '/public')
        ]);

        $output = $pdf->getOutputFromHtml($html);

        return new Response($output, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="tools.pdf"',
        ]);
    }

    // Data Validation Function 
    private function validateToolData(array $data): array
    {
        $errors = [];

        // NAME
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est requis';
        } elseif (strlen($data['name']) < 3) {
            $errors['name'] = 'Le nom doit contenir au moins 3 caractères';
        }

        // EXE
        if (empty($data['exe'])) {
            $errors['exe'] = "L'identifiant est requis";
        } elseif (!str_ends_with(strtolower($data['exe']), '.exe')) {
            $errors['exe'] = "L'identifiant doit être un fichier .exe";
        }

        // HASH
        if (empty($data['hash'])) {
            $errors['hash'] = 'Le hash est requis';
        } elseif (!preg_match('/^[a-fA-F0-9]{16,}$/', $data['hash'])) {
            $errors['hash'] = 'Hash invalide (hex requis)';
        }

        if (empty($data['categorie'])) {
            $errors['categorie'] = 'Catégorie obligatoire';
        }

        return $errors;
    }

    // Routes
    #[Route('/Gestion_Administrative/outils/delete/{id}', name: 'tool_delete')]
    public function deleteTool(int $id, OutilsDeTravailRepository $outilsRepository): Response
    {
        echo "Attempting to delete tool with ID: $id\n"; // Debug statement
        $outilsRepository->deleteTool($id);

        return $this->redirectToRoute('employee_outils');
    }

    #[Route('/Gestion_Administrative/outils', name: 'employee_outils')]
    public function outils(Request $request ,OutilsDeTravailRepository $outilsRepository, CategorieRepository $catRepo): Response
    {
        $categories = $catRepo->findAllCategories();
        $allTools = array_map(function($tool)  {
            $employeesData = [];

            foreach ($tool->getEmployees() as $employee) {

                $time = 0;

                foreach ($employee->getWorkSessions() as $session) {
                    foreach ($session->getDetails() as $detail) {

                        if (strtolower(trim($detail->getApp())) === strtolower(trim($tool->getIdentifiantUniverselle()))) {
                            $time += $detail->getDuration();
                        }

                    }
                }

                $employeesData[] = $time / 60;
            }

            $count = count($employeesData);

            $avgTime = $count > 0
                ? round(array_sum($employeesData) / $count, 2)
                : 0;
                
            return [
                'id' => $tool->getIDOutil(),
                'name' => $tool->getNomOutil(),
                'monthly_cost' => $tool->getMonthlyCost(),
                'categorie' => $tool->getCategorie()?->getNom(),
                'avgTime' => $avgTime,
                'users' => $tool->getEmployees() ? count($tool->getEmployees()) : 0,
                'categorie' => $tool->getCategorie()?->getNom() ?? 'Non catégorisé',
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
            'categories' => $categories,
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
            'id'   => $tool->getIDOutil(),
            'name' => $tool->getNomOutil(),
            'exe'  => $tool->getIdentifiantUniverselle(),
            'hash' => $tool->getHashApp(),
            'monthly_cost' => $tool->getMonthlyCost(),
            'categorie' => $tool->getCategorie()?->getIDCategorie(),
        ]);
    }

    #[Route('/Gestion_Administrative/outils/create', name: 'tool_create', methods: ['POST'])]
    public function createTool(Request $request, OutilsDeTravailRepository $toolRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid JSON'
            ], 400);
        }

        $errors = $this->validateToolData($data);

        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        $toolRepository->createTool([
            'name' => $data['name'],
            'exe'  => $data['exe'],
            'hash' => $data['hash'],
            'monthly_cost' => $data['monthly_cost'] ?? 0,
            'categorie' => $data['categorie'],
        ]);

        return $this->json([
            'success' => true
        ]);
    }

    #[Route('/Gestion_Administrative/outils/update/{id}', name: 'tool_update', methods: ['POST'])]
    public function updateTool(int $id, Request $request, OutilsDeTravailRepository $toolRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        $errors = $this->validateToolData($data);

        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        $toolRepository->updateTool($id, [
            'name' => $data['name'],
            'exe'  => $data['exe'],
            'hash' => $data['hash'],
            'monthly_cost' => $data['monthly_cost'] ?? 0,
            'categorie' => $data['categorie'],
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
        $errors = [];

        while (($row = fgetcsv($handle)) !== false) {

            $rowIndex++;

            // Skip header
            if ($rowIndex === 1) continue;

            // Skip empty lines
            if (count($row) < 3) {
                $errors[] = "Ligne $rowIndex: colonnes insuffisantes";
                continue;
            }

            $total++;

            // Safe extraction + trim
            $name = trim($row[0] ?? '');
            $exe  = trim($row[1] ?? '');
            $hash = trim($row[2] ?? '');

            // Validation
            if (!$name || strlen($name) < 3) {
                $errors[] = "Ligne $rowIndex: nom invalide";
                continue;
            }

            if (!$exe || !str_ends_with(strtolower($exe), '.exe')) {
                $errors[] = "Ligne $rowIndex: executable invalide";
                continue;
            }

            if (!$hash || !preg_match('/^[a-fA-F0-9]{16,}$/', $hash)) {
                $errors[] = "Ligne $rowIndex: hash invalide";
                continue;
            }

            try {
                // Optional: prevent duplicates BEFORE DB
                if ($repo->findOneBy(['Hash_App' => $hash])) {
                    $errors[] = "Ligne $rowIndex: outil déjà existant (hash)";
                    continue;
                }

                $repo->createTool([
                    'name' => $name,
                    'exe'  => $exe,
                    'hash' => $hash,
                ]);

                $success++;

            } catch (\Exception $e) {
                $errors[] = "Ligne $rowIndex: erreur DB";
                continue;
            }
        }

        fclose($handle);

        return $this->json([
            'successCount' => $success,
            'total' => $total,
            'errors' => $errors
        ]);
    }

    #[Route('/Gestion_Administrative/outils/fiche/{id}', name: 'tool_fiche')]
    public function ficheTool(int $id, OutilsDeTravailRepository $repo): Response
    {
        $tool = $repo->findToolById($id);

        if (!$tool) {
            throw $this->createNotFoundException('Outil non trouvé');
        }

        // =========================
        // 1. Build employees data
        // =========================
        $employeesData = [];

        foreach ($tool->getEmployees() as $employee) {

            $time = 0;

            foreach ($employee->getWorkSessions() as $session) {
                foreach ($session->getDetails() as $detail) {

                    if (strtolower(trim($detail->getApp())) === strtolower(trim($tool->getIdentifiantUniverselle()))) {
                        $time += $detail->getDuration();
                    }
                }
            }

            $monthlyHours = $employee->getNbrHeureDeTravail() ?: 160;

            $cost = $employee->getSALAIRE() && $time > 0
                ? round(($time / 60) / $monthlyHours * $employee->getSALAIRE(), 2)
                : 0;

            $employeesData[] = [
                'id' => $employee->getIDEmploye(),
                'nom' => $employee->getUtilisateur()?->getNomUtilisateur() ?? 'N/A',
                'departement' => $employee->getDepartement()?->getNom() ?? 'Non défini',
                'temps' => round($time / 60, 2),
                'salaire' => $employee->getSALAIRE(),
                'cout' => $cost
            ];
        }

        // =========================
        // 2. Sorting + top users
        // =========================
        usort($employeesData, fn($a, $b) => $b['temps'] <=> $a['temps']);
        $topUsers = array_slice($employeesData, 0, 3);

        // =========================
        // 3. Global stats
        // =========================
        $totalTime = array_sum(array_column($employeesData, 'temps'));
        $users = count($employeesData);
        $avgTime = $users > 0 ? round($totalTime / $users, 2) : 0;
        $costTotal = array_sum(array_column($employeesData, 'cout'));

        // =========================
        // 4. Department distribution
        // =========================
        $departementStats = [];

        foreach ($employeesData as $emp) {
            $dept = $emp['departement'];

            if (!isset($departementStats[$dept])) {
                $departementStats[$dept] = 0;
            }

            $departementStats[$dept] += $emp['temps'];
        }

        $departementDistribution = [];

        foreach ($departementStats as $dept => $time) {

            $percent = $totalTime > 0
                ? round(($time / $totalTime) * 100, 2)
                : 0;

            $departementDistribution[] = [
                'name' => $dept,
                'percent' => $percent
            ];
        }

        // =========================
        // 5. Format top users for AI
        // =========================
        $topUsersFormatted = array_map(
            fn($u) => "{$u['nom']} ({$u['temps']}h)",
            $topUsers
        );

        $topUsersStr = implode(', ', $topUsersFormatted);

        // =========================
        // 6. Build AI prompt
        // =========================
        $prompt = trim(sprintf(
            "Tu rédiges un court résumé pour une fiche outil affichée dans une application de gestion.

            Contraintes STRICTES :
            - 2 à 3 phrases maximum
            - Ton neutre et professionnel
            - AUCUNE première personne
            - AUCUNE introduction ou mise en contexte
            - Style direct et factuel

            Données :
            - Temps total : %s h
            - Temps moyen par utilisateur : %s h
            - Nombre d’utilisateurs : %d
            - Coût total : %s TND
            - Top utilisateurs : %s

            Objectif :
            - Évaluer l’intensité d’utilisation de l’outil
            - Identifier une sur-utilisation ou sous-utilisation éventuelle
            - Mentionner une piste d’optimisation si pertinente",
                    $totalTime,
                    $avgTime,
                    $users,
                    $costTotal,
                    $topUsersStr ?: 'Aucun'
                ));

        // =========================
        // 7. Call Groq API
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

            $data = $response->toArray();

            $summary = $data['choices'][0]['message']['content'] ?? 'Résumé indisponible';

            // Clean formatting
            $summary = preg_replace('/\*\*/', '', $summary);
            $summary = trim($summary);

        } catch (\Exception $e) {
            $summary = 'Résumé non disponible.';
        }

        // =========================
        // 8. Render
        // =========================
        return $this->render('Gestion Administrative/fiche_outil.html.twig', [
            'tool' => $tool,
            'stats' => [
                'avgTime' => $avgTime,
                'users' => $users,
                'costTotal' => $costTotal
            ],
            'employees' => $employeesData,
            'topUsers' => $topUsers,
            'departements' => $departementDistribution,
            'summary' => $summary
        ]);
    }

    // ========== End Outil Controller ==========  //

#pragma endregion

}
