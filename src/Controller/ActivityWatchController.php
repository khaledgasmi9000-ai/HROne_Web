<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpClient\HttpClient;

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
}