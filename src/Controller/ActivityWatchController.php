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
    public function startSession(): JsonResponse
    {
        return $this->json([
            'sessionId' => rand(1000, 9999), // temporary
        ]);
    }

    #[Route('/api/session/end/{id}', methods: ['POST'])]
    public function endSession(int $id): JsonResponse
    {
        return $this->json([
            'status' => 'ended',
            'sessionId' => $id
        ]);
    }

    #[Route('/api/session/summary', methods: ['GET'])]
    public function getSessionSummary(
        Request $request,
        ActivityWatchService $awService
    ): JsonResponse {

        // $session = $request->getSession();

        // $start = $session->get('ActivityWatchSessionStartTime');
        // $end = $session->get('ActivityWatchSessionEndTime');
        // $employeeId = $session->get('ID_Employee');

        $start = '2026-04-16T19:00:00';
        $end = '2026-04-16T20:00:00';
        $employeeId = 22;

        $start = new \DateTime($start);
        $end = new \DateTime($end);

        if (!$start || !$end) {
            return $this->json(['error' => 'No active session'], 400);
        }

        // Fetch data
        $events = $awService->getWindowEvents($start, $end);
        $afk = $awService->getAfkData($start, $end);

        // Clean events
        $cleanEvents = $awService->cleanEvents($events);

        // Session duration
        $sessionDuration = $end->getTimestamp() - $start->getTimestamp();

        // AFK / Active
        $activeTime = $afk['not-afk'] ?? 0;
        $afkTime = $afk['afk'] ?? 0;

        // Fix overlap → normalize durations
        $totalEventTime = array_sum(array_column($cleanEvents, 'duration'));

        if ($totalEventTime > 0 && $totalEventTime > $sessionDuration) {

            $factor = $sessionDuration / $totalEventTime;

            foreach ($cleanEvents as &$event) {
                $event['duration'] *= $factor;
            }
        }

        // Final rounding
        foreach ($cleanEvents as &$event) {
            $event['duration'] = round($event['duration'], 2);
        }

        // Unknown time
        $coveredTime = $activeTime + $afkTime;
        $unknownTime = round(max(0, $sessionDuration - $coveredTime), 2);

        return $this->json([
            'employeeId' => $employeeId,
            'start' => $start->format('Y-m-d\TH:i:s'),
            'end' => $end->format('Y-m-d\TH:i:s'),
            'sessionDuration' => $sessionDuration,
            'activeTime' => $activeTime,
            'afkTime' => $afkTime,
            'unknownTime' => $unknownTime,
            'events' => $cleanEvents
        ]);
    }
}