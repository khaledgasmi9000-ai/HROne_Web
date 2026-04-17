<?php

namespace App\Services;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class ActivityWatchService
{
    private HttpClientInterface $client;

    public function __construct(HttpClientInterface $client)
    {
        $this->client = $client;
    }

    private function query(array $body): array
    {
        try {
            $response = $this->client->request('POST', 'http://127.0.0.1:5600/api/0/query/', [
                'json' => $body,
                'timeout' => 5
            ]);

            return $response->toArray()[0] ?? [];

        } catch (\Throwable $e) {
            return []; // fail silently for now
        }
    }

    private function formatTime(\DateTime $date): string
    {
        return $date->format('Y-m-d\TH:i:s\Z');
    }

    public function getWindowEvents(\DateTime $start, \DateTime $end): array
    {
        $body = [
            "timeperiods" => [
                $this->formatTime($start) . "/" . $this->formatTime($end)
            ],
            "query" => [
                "bucket = find_bucket('aw-watcher-window_');",
                "events = query_bucket(bucket);",
                "events = merge_events_by_keys(events, ['app', 'title']);",
                "RETURN = events;"
            ]
        ];

        return $this->query($body);
    }

    public function getAfkData(\DateTime $start, \DateTime $end): array
    {
        $body = [
            "timeperiods" => [
                $this->formatTime($start) . "/" . $this->formatTime($end)
            ],
            "query" => [
                "bucket = find_bucket('aw-watcher-afk_');",
                "events = query_bucket(bucket);",
                "events = merge_events_by_keys(events, ['status']);",
                "RETURN = events;"
            ]
        ];

        $result = $this->query($body);

        $formatted = [];
        foreach ($result as $item) {
            $status = $item['data']['status'] ?? 'unknown';
            $formatted[$status] = $item['duration'];
        }

        return $formatted;
    }

    public function cleanEvents(array $events): array
    {
        $cleaned = [];

        foreach ($events as $event) {

            $duration = $event['duration'] ?? 0;
            if ($duration < 1) continue;

            $app = $event['data']['app'] ?? '';
            $title = $event['data']['title'] ?? '';

            if (!$app) continue;

            $key = $app;

            if (!isset($cleaned[$key])) {
                $cleaned[$key] = [
                    'app' => $app,
                    'duration' => 0
                ];
            }

            $cleaned[$key]['duration'] += $duration;
        }

        // Convert to indexed array BEFORE sorting
        $cleaned = array_values($cleaned);

        // Round values
        foreach ($cleaned as &$event) {
            $event['duration'] = round($event['duration'], 2);
        }

        // Sort DESC
        usort($cleaned, fn($a, $b) => $b['duration'] <=> $a['duration']);

        return $cleaned;
    }
}