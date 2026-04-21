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

class CongesController extends AbstractController
{


    
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

    #[Route('/Gestion_Administrative/conges/demande', name: 'demande_conge')]
    public function demandeConge(
        Request $request,
        EmployeeRepository $employeeRepository
    ): Response
    {
        // dump($request->getSession()->all());
        // die();
        $userId = $request->getSession()->get('user_id');
        $employeeId = $employeeRepository->findEmployeeByUserId($userId)?->getIDEmploye();
        // $employeeId = 22;
        if (!$employeeId) {
            return $this->redirectToRoute('login');
        }

        $soldeInitial = $employeeRepository->getSoldeConge($employeeId);
        $soldeUtiliser = $employeeRepository->getNumberofUsedConge($employeeId);

        return $this->render('Employee FrontEnd/demande_conge.html.twig', [
            'soldeRestant' => $soldeInitial - $soldeUtiliser
        ]);
    }

    #[Route('/Gestion_Administrative/conges/create', name: 'conge_create', methods: ['POST'])]
    public function createConge(
        Request $request,
        DemandeCongeRepository $repo,
        EntityManagerInterface $em,
        EmployeeRepository $employeeRepository
    ): Response {

        $data = json_decode($request->getContent(), true);

        if (!$data) {
            return $this->json([
                'success' => false,
                'error' => 'Invalid JSON'
            ], 400);
        }

        // GET EMPLOYEE FROM SESSION
        $userId = $request->getSession()->get('user_id');
        $employeeId = $employeeRepository->findEmployeeByUserId($userId)?->getIDEmploye();
        if (!$employeeId) {
            return $this->json([
                'success' => false,
                'error' => 'Non authentifié'
            ], 401);
        }

        $errors = [];

        // REQUIRED FIELDS (ONLY USER INPUT)
        if (empty($data['start'])) {
            $errors['start'] = 'Date début requise';
        }

        if (empty($data['end'])) {
            $errors['end'] = 'Date fin requise';
        }

        if (empty($data['nbrJours']) || $data['nbrJours'] <= 0) {
            $errors['days'] = 'Nombre de jours invalide';
        }

        // DATE VALIDATION
        try {
            $start = new \DateTime($data['start']);
            $end   = new \DateTime($data['end']);

            if ($end < $start) {
                $errors['dates'] = 'Date fin doit être après début';
            }

        } catch (\Exception $e) {
            $errors['dates'] = 'Format de date invalide';
        }

        $employee = $em->getRepository(\App\Entity\Employee::class)
            ->find($employeeId);

        if (!$employee) {
            $errors['employee'] = 'Employé introuvable';
        }

        if ($employee) {
            $soldeInitial = $employeeRepository->getSoldeConge($employeeId);
            $soldeUtiliser = $employeeRepository->getNumberofUsedConge($employeeId);

            $soldeRestant = $soldeInitial - $soldeUtiliser;

            if ($data['nbrJours'] > $soldeRestant) {
                $errors['solde'] = 'Solde insuffisant';
            }
        }

        // STOP IF ERRORS
        if (!empty($errors)) {
            return $this->json([
                'success' => false,
                'errors' => $errors
            ], 400);
        }

        try {
            $repo->createConge(
                $employeeId, // ✅ FROM SESSION
                $data['start'],
                $data['end'],
                $data['nbrJours']
            );

            return $this->json([
                'success' => true,
                'message' => 'Demande envoyée'
            ]);

        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
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

