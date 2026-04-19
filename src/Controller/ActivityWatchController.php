<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;
use Doctrine\ORM\EntityManagerInterface;

use App\Services\ActivityWatchService;

use App\Entity\WorkSession;
use App\Entity\WorkSessionDetail;

use App\Repository\EmployeeRepository;

class ActivityWatchController extends AbstractController
{
    #[Route('/Employee_Dashboard/ActivityWatch', name: 'activity_watch')]
    public function activityWatch()
    {
        return $this->render('Employee FrontEnd/activity_watch.html.twig');
    }
    
    

    #[Route('/api/activitywatch/status', methods: ['GET'])]
    public function checkActivityWatch(): JsonResponse
    {
        $client = HttpClient::create();

        try {
            $response = $client->request('GET', 'http://127.0.0.1:5600/api/0/buckets', [
                'timeout' => 2,
            ]);

            if (empty($response->getHeaders())){
                throw new \Exception();
            }

            if ($response->getStatusCode() !== 200) {
                throw new \Exception();
            }

            return $this->json(['status' => 'running']);

        } catch (\Exception $e) {
            return $this->json([
                'status' => 'offline',
                'error' => $e->getMessage() // TEMP: for debugging
            ]);
        }
    }

    #[Route('/api/session/status', methods: ['GET'])]
    public function getSessionStatus(Request $request): JsonResponse
    {
        $session = $request->getSession();

        $sessionId = $session->get('ActivityWatchSessionId');
        $start = $session->get('ActivityWatchSessionStartTime');

        if ($sessionId && $start) {
            return $this->json([
                'active' => true,
                'sessionId' => $sessionId,
                'start' => $start
            ]);
        }

        return $this->json([
            'active' => false
        ]);
    }

    #[Route('/api/session/start', methods: ['POST'])]
    public function startSession(
        Request $request,
        EntityManagerInterface $em,
        EmployeeRepository $employeeRepo
    ): JsonResponse {

        $session = $request->getSession();

        $employeeId = $session->get('ID_Employee') ?? 22;

        if (!$employeeId) {
            return $this->json([
                'error' => 'No employee in session'
            ], 400);
        }

        $employee = $employeeRepo->find($employeeId);

        if (!$employee) {
            return $this->json([
                'error' => 'Employee not found'
            ], 404);
        }

        $start = new \DateTime();
        
        $workSession = new WorkSession();
        $workSession->setEmployee($employee);
        $workSession->setStartTime($start);
        $workSession->setStatus('running');

        $em->persist($workSession);
        $em->flush();

        // Store in web session
        $startFormated = $start->format('Y-m-d\TH:i:s');
        $session->set('ActivityWatchSessionStartTime', $startFormated);
        $session->set('ActivityWatchSessionId', $workSession->getId());

        return $this->json([
            'status' => 'started',
            'sessionId' => $workSession->getId()
        ]);
    }

    #[Route('/api/session/end', methods: ['POST'])]
    public function endSession(
        Request $request,
        EntityManagerInterface $em,
        ActivityWatchService $awService
    ): JsonResponse {
        $session = $request->getSession();
        $sessionId = $session->get('ActivityWatchSessionId');
        $startStr = $session->get('ActivityWatchSessionStartTime');

        if (!$sessionId || !$startStr) {
            return $this->json(['error' => 'No active session'], 400);
        }

        $workSession = $em->getRepository(WorkSession::class)->find($sessionId);
        if (!$workSession || $workSession->getStatus() === 'terminated') {
            return $this->json(['error' => 'Session not found or already ended'], 404);
        }

        // 1. Setup Time Objects
        $utcTimeZone = new \DateTimeZone('UTC');
        $localStart = new \DateTime($startStr); // Original local time
        $localEnd = new \DateTime();           // Current local time
        
        // Clone for the API to avoid messing up the database timestamps
        $utcStart = (clone $localStart)->setTimezone($utcTimeZone);
        $utcEnd = (clone $localEnd)->setTimezone($utcTimeZone);

        /*
        ========================
        FETCH ACTIVITYWATCH DATA
        ========================
        */
        $events = $awService->getActiveWindowEvents($utcStart, $utcEnd);
        $afk = $awService->getAfkData($utcStart, $utcEnd);
        $cleanEvents = $awService->cleanEvents($events); 

        // Calculate total duration in seconds
        $sessionDuration = $localEnd->getTimestamp() - $localStart->getTimestamp();

        $activeTime = $afk['not-afk'] ?? 0;
        $afkTime = $afk['afk'] ?? 0;

        /*
        ========================
        NORMALIZE EVENTS
        ========================
        */

        $coveredTime = $activeTime + $afkTime;
        $unknownTime = round(max(0, $sessionDuration - $coveredTime), 2);
        $afkTime = $unknownTime;
        $unknownTime = 0;
        /*
        ========================
        UPDATE SESSION ENTITY
        ========================
        */
        $workSession->setEndTime($localEnd); // Stays as local time
        $workSession->setStatus('terminated');
        $workSession->setSessionDuration($sessionDuration);
        $workSession->setActiveTime(round($activeTime, 2));
        $workSession->setAfkTime(round($afkTime, 2));
        $workSession->setUnknownTime($unknownTime);

        /*
        ========================
        INSERT DETAILS
        ========================
        */
        foreach ($cleanEvents as $event) {
            $detail = new WorkSessionDetail();
            // Ensure the service returned the 'app' key
            $detail->setApp($event['app'] ?? 'Unknown App');
            $detail->setDuration($event['duration']);

            $percentage = $sessionDuration > 0
                ? round(($event['duration'] / $sessionDuration) * 100, 2)
                : 0;

            $detail->setPercentage($percentage);
            $workSession->addDetail($detail);
        }

        $em->flush();

        // Clean up web session
        $session->remove('ActivityWatchSessionId');
        $session->remove('ActivityWatchSessionStartTime');

        return $this->json([
            'status' => 'terminated',
            'sessionId' => $workSession->getId(),
            'active_time' => $activeTime,
            'unknown_time' => $unknownTime
        ]);
    }

}