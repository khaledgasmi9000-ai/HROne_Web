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

use App\Repository\WorkSessionDetailRepository;
use App\Repository\WorkSessionRepository;

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

    #[Route('/api/session/start', methods: ['POST'])]
    public function startSession(
        Request $request,
        EntityManagerInterface $em
    ): JsonResponse {

        $session = $request->getSession();

        $employeeId = $session->get('ID_Employee') ?? 22;

        $start = new \DateTime();
        
        $workSession = new WorkSession();
        $workSession->setEmployeeId($employeeId);
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

        if (!$workSession) {
            return $this->json(['error' => 'Session not found'], 404);
        }

        if ($workSession->getStatus() === 'terminated') {
            return $this->json(['error' => 'Session already ended'], 400);
        }
        
        // 1. Create DateTime objects
        $start = new \DateTime($startStr);
        $end = new \DateTime();

        // 2. CONVERT TO UTC (This is the critical fix)
        $utcTimeZone = new \DateTimeZone('UTC');
        $start->setTimezone($utcTimeZone);
        $end->setTimezone($utcTimeZone);
        
        /*
        ========================
        FETCH ACTIVITYWATCH DATA
        ========================
        */

        $events = $awService->getWindowEvents($start, $end);
        $afk = $awService->getAfkData($start, $end);

        $cleanEvents = $awService->cleanEvents($events);

        $sessionDuration = $end->getTimestamp() - $start->getTimestamp();

        $activeTime = $afk['not-afk'] ?? 0;
        $afkTime = $afk['afk'] ?? 0;

        /*
        ========================
        NORMALIZE EVENTS
        ========================
        */

        $totalEventTime = array_sum(array_column($cleanEvents, 'duration'));

        if ($totalEventTime > 0 && $totalEventTime > $sessionDuration) {
            $factor = $sessionDuration / $totalEventTime;

            foreach ($cleanEvents as &$event) {
                $event['duration'] *= $factor;
            }
        }

        foreach ($cleanEvents as &$event) {
            $event['duration'] = round($event['duration'], 2);
        }

        $coveredTime = $activeTime + $afkTime;
        $unknownTime = round(max(0, $sessionDuration - $coveredTime), 2);

        /*
        ========================
        UPDATE SESSION
        ========================
        */

        $workSession->setEndTime($end);
        $workSession->setStatus('terminated');
        $workSession->setSessionDuration($sessionDuration);
        $workSession->setActiveTime($activeTime);
        $workSession->setAfkTime($afkTime);
        $workSession->setUnknownTime($unknownTime);

        /*
        ========================
        INSERT DETAILS
        ========================
        */

        foreach ($cleanEvents as $event) {

            $detail = new WorkSessionDetail();
            $detail->setApp($event['app']);
            $detail->setDuration($event['duration']);

            // Optional percentage
            $percentage = $sessionDuration > 0
                ? round(($event['duration'] / $sessionDuration) * 100, 2)
                : null;

            $detail->setPercentage($percentage);

            // IMPORTANT: use helper
            $workSession->addDetail($detail);
        }

        /*
        ========================
        SAVE EVERYTHING
        ========================
        */

        $em->flush();

        /*
        ========================
        CLEAN WEB SESSION
        ========================
        */

        $session->remove('ActivityWatchSessionId');
        $session->remove('ActivityWatchSessionStartTime');

        return $this->json([
            'status' => 'terminated',
            'sessionId' => $workSession->getId()
        ]);
    }

    private function buildSummary(
        Request $request,
        ActivityWatchService $awService
    ): JsonResponse {

        $session = $request->getSession();

        $start = $session->get('ActivityWatchSessionStartTime');
        $end = $session->get('ActivityWatchSessionEndTime');
        $employeeId = $session->get('ID_Employee') ?? 22; // fallback

        // $start = "2026-04-17T11:59:30";
        // $end = "2026-04-17T12:00:12";
        if (!$start || !$end) {
            return $this->json(['error' => 'Session not completed'], 400);
        }

        
        $start = new \DateTime($start);
        $end = new \DateTime($end);

        // Fetch data
        $events = $awService->getWindowEvents($start, $end);
        $afk = $awService->getAfkData($start, $end);

        // Clean
        $cleanEvents = $awService->cleanEvents($events);

        // Duration
        $sessionDuration = $end->getTimestamp() - $start->getTimestamp();

        // AFK / Active
        $activeTime = $afk['not-afk'] ?? 0;
        $afkTime = $afk['afk'] ?? 0;

        // Normalize overlap
        $totalEventTime = array_sum(array_column($cleanEvents, 'duration'));

        if ($totalEventTime > 0 && $totalEventTime > $sessionDuration) {
            $factor = $sessionDuration / $totalEventTime;

            foreach ($cleanEvents as &$event) {
                $event['duration'] *= $factor;
            }
        }

        // Round
        foreach ($cleanEvents as &$event) {
            $event['duration'] = round($event['duration'], 2);
        }

        $coveredTime = $activeTime + $afkTime;
        $unknownTime = round(max(0, $sessionDuration - $coveredTime), 2);

        return $this->json([
            'employeeId' => $employeeId,
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
            'sessionDuration' => $sessionDuration,
            'activeTime' => round($activeTime, 2),
            'afkTime' => round($afkTime, 2),
            'unknownTime' => $unknownTime,
            'events' => $cleanEvents
        ]);
    }

    #[Route('/api/session/summary', methods: ['GET'])]
    public function getSessionSummary(
        Request $request,
        ActivityWatchService $awService
    ): JsonResponse {
        return $this->buildSummary($request, $awService);
    }
}