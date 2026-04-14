<?php

namespace App\Controller;

use App\Entity\Ordre;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use App\Repository\EmployeeRepository;
use App\Repository\DemandeCongeRepository;




class GestionAdministrativeController extends AbstractController
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

    #[Route('/Gestion_Administrative/conges/demande/{id}', name: 'demande_conge')]
    public function demandeConge(int $id,EmployeeRepository $employeeRepository): Response
    {
        //$soldeRestant = $this->CalculateSodeCongeRestant($employeeRepository,$employeeRepository->getSoldeConge($id), $id);

        return $this->render('Employee FrontEnd/demande_conge.html.twig', [
            'soldeRestant' => 1
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

