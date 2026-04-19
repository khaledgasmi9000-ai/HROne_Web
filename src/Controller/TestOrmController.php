<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Profil;
use App\Entity\Entreprise;
use App\Entity\WorkSession;
use App\Entity\WorkSessionDetail;
use App\Repository\EmployeeRepository;
use App\Repository\OutilsDeTravailRepository;
use App\Repository\DemandeCongeRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

//#[Route('/testOrm')]
class TestOrmController extends AbstractController
{
    // =========================
    // EMPLOYEE TEST
    // =========================

    #[Route('/employee/run', methods: ['GET'])]
    public function testEmployee(
        EmployeeRepository $employeeRepo,
        EntityManagerInterface $em
    ): JsonResponse {
        
        // 1. Create Utilisateur
        $entreprise = $em->getRepository(Entreprise::class)->find(1);
        $profil = $em->getRepository(Profil::class)->find(3);

        // dump($entreprise);
        // dump($profil);
        echo "Profil ref: " . ($profil ? $profil->getID_Profil() : 'null') . "\n";
        echo "Entreprise ref: " . ($entreprise ? $entreprise->getID_Entreprise() : 'null') . "\n";
        $user = new Utilisateur();
        $user->setNomUtilisateur('Test User');
        $user->setProfil($profil);
        $user->setEntreprise($entreprise);
        echo "User Profil: " . ($user->getProfil() ? $user->getProfil()->getID_Profil() : 'null') . "\n";
        echo "User Entreprise: " . ($user->getEntreprise() ? $user->getEntreprise()->getID_Entreprise() : 'null') . "\n";
        $em->persist($user);
        $em->flush();

        // 2. Create Employee
        $employee = $employeeRepo->createEmployee([
            'solde' => 10,
            'salaire' => 2000,
            'heures' => 40
        ], $user);

        // 3. Update Employee
        $employeeRepo->updateEmployee($employee->getIDEmploye(), [
            'solde' => 20,
            'salaire' => 2500,
            'heures' => 35
        ]);

        // 4. Delete Employee
        $employeeRepo->deleteEmployee($employee->getIDEmploye());

        return new JsonResponse(['status' => 'employee test completed']);
    }

    #[Route('/employee/list', methods: ['GET'])]
    public function listEmployees(EmployeeRepository $employeeRepo): JsonResponse
    {
        $employees = $employeeRepo->findAllEmployees();

        $data = array_map(function ($e) {
            return [
                'id' => $e->getIDEmploye(),
                'name' => $e->getUtilisateur()->getNomUtilisateur(),
                'mdp' => $e->getUtilisateur()->getMotPasse(),
                'solde' => $e->getSoldeConge()
            ];
        }, $employees);

        return new JsonResponse($data);
    }

    // =========================
    // OUTILS TEST
    // =========================

    #[Route('/outil/run', methods: ['GET'])]
    public function testOutil(OutilsDeTravailRepository $outilRepo): JsonResponse
    {
        // 1. Create
        $outil = $outilRepo->createTool([
            'name' => 'Test Tool',
            'exe' => 'test.exe',
            'hash' => '123456'
        ]);

        // 2. Update
        $outilRepo->updateTool($outil->getIDOutil(), [
            'name' => 'Updated Tool',
            'exe' => 'updated.exe',
            'hash' => 'abcdef'
        ]);

        // 3. Delete
        $outilRepo->deleteTool($outil->getIDOutil());

        return new JsonResponse(['status' => 'outil test completed']);
    }

    #[Route('/outil/list', methods: ['GET'])]
    public function listOutils(OutilsDeTravailRepository $outilRepo): JsonResponse
    {
        $outils = $outilRepo->findAllTools();

        $data = array_map(function ($o) {
            return [
                'id' => $o->getIDOutil(),
                'name' => $o->getNomOutil()
            ];
        }, $outils);

        return new JsonResponse($data);
    }

    // =========================
    // DEMANDE CONGE TEST
    // =========================

    #[Route('/conge/run', methods: ['GET'])]
    public function testConge(
        DemandeCongeRepository $congeRepo,
        EmployeeRepository $employeeRepo,
        EntityManagerInterface $em
    ): JsonResponse {

        // Create a dummy user + employee first
        $user = new Utilisateur();
        $user->setNomUtilisateur('Conge User');
        $em->persist($user);
        $em->flush();

        $employee = $employeeRepo->createEmployee([
            'solde' => 15,
            'salaire' => 1800,
            'heures' => 40
        ], $user);

        // 1. Create 2 demandes
        $conge1 = $congeRepo->createConge(
            $employee->getIDEmploye(),
            '2026-01-01 00:00:00',
            '2026-02-01 00:00:01',
            5
        );

        $conge2 = $congeRepo->createConge(
            $employee->getIDEmploye(),
            '2026-04-01 00:00:03',
            '2026-03-01 00:00:01',
            3
        );

        // 2. Update status
        $congeRepo->updateCongeStatus($conge1->getID_Demende(), 1);
        $congeRepo->updateCongeStatus($conge2->getID_Demende(), -1);

        return new JsonResponse(['status' => 'conge test completed']);
    }

    #[Route('/conge/list', methods: ['GET'])]
    public function listConges(DemandeCongeRepository $congeRepo): JsonResponse
    {
        $conges = $congeRepo->findAllConges();

        $data = array_map(function ($c) {

            $dateDebut = \App\Entity\Ordre::numOrdreToDate($c['Num_Ordre_Debut_Conge']);
            $dateFin   = \App\Entity\Ordre::numOrdreToDate($c['Num_Ordre_Fin_Conge']);

            return [
                ...$c, // keep existing fields

                'DateDebut' => $dateDebut->format('Y-m-d H:i:s'),
                'DateFin'   => $dateFin->format('Y-m-d H:i:s'),
            ];

        }, $conges);

        return new JsonResponse($data);
    }


    #[Route('/api/test/worksession/crud', methods: ['GET'])]
    public function testWorkSessionCrud(EntityManagerInterface $em): JsonResponse
    {
        try {
            /*
            ========================
            1. CREATE
            ========================
            */

            $session = new WorkSession();
            //$session->setEmployeeId(22);
            $session->setStartTime(new \DateTime());
            $session->setEndTime(new \DateTime('+1 hour'));
            $session->setStatus('active');

            $detail1 = new WorkSessionDetail();
            $detail1->setApp('brave.exe');
            $detail1->setDuration(120);

            $detail2 = new WorkSessionDetail();
            $detail2->setApp('Code.exe');
            $detail2->setDuration(60);

            // Link both sides
            $detail1->setWorkSession($session);
            $detail2->setWorkSession($session);

            $session->getDetails()->add($detail1);
            $session->getDetails()->add($detail2);

            $em->persist($session);
            $em->persist($detail1);
            $em->persist($detail2);

            $em->flush();

            $createdId = $session->getId();

            /*
            ========================
            2. UPDATE
            ========================
            */

            $session->setStatus('terminated');
            $session->setSessionDuration(3600);

            $detail1->setDuration(200);
            $detail2->setDuration(100);

            $em->flush();

            /*
            ========================
            3. DELETE
            ========================
            */

            // Because of FK + cascade, removing session removes details
            $em->remove($session);
            $em->flush();

            return new JsonResponse([
                'status' => 'success',
                'message' => 'CRUD test completed',
                'sessionId' => $createdId
            ]);

        } catch (\Throwable $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }



}