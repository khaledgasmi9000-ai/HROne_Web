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

            // The API returns an array of results for each query block
            return $response->toArray()[0] ?? [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function formatTime(\DateTime $date): string
    {
        // Ensure UTC and format for AW
        return (clone $date)->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');
    }

    /**
     * This method now returns window events INTERSECTED with non-AFK time.
     * This solves the overlap problem entirely.
     */
    public function getActiveWindowEvents(\DateTime $start, \DateTime $end): array
    {
        $body = [
            "timeperiods" => [
                $this->formatTime($start) . "/" . $this->formatTime($end)
            ],
            "query" => [
                // 1. Get Buckets
                "win_buckets = find_bucket('aw-watcher-window_');",
                "afk_buckets = find_bucket('aw-watcher-afk_');",
                
                // 2. Get Events
                "win_events = query_bucket(win_buckets);",
                "afk_events = query_bucket(afk_buckets);",
                
                // 3. Filter AFK (only keep 'not-afk')
                "not_afk = filter_keyvals(afk_events, 'status', ['not-afk']);",
                
                // 4. Intersect: Only keep window events where user was NOT afk
                "active_events = filter_period_intersect(win_events, not_afk);",
                
                // 5. Merge by APP only (resolves your Duplicate Entry SQL error)
                "merged = merge_events_by_keys(active_events, ['app']);",
                
                // 6. Sort by duration
                "RETURN = sort_by_duration(merged);"
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

        $formatted = ['afk' => 0, 'not-afk' => 0];
        foreach ($result as $item) {
            $status = $item['data']['status'] ?? 'unknown';
            if (isset($formatted[$status])) {
                $formatted[$status] = $item['duration'];
            }
        }

        return $formatted;
    }

    /**
     * Cleans and maps the AWQL return to your Entity structure
     */
    public function cleanEvents(array $events): array
    {
        $cleaned = [];
        foreach ($events as $event) {
            // AWQL merge puts the key in data['app']
            $app = $event['data']['app'] ?? ($event['data']['title'] ?? null);
            $duration = $event['duration'] ?? 0;

            if (!$app || $duration < 1) continue;

            $appKey = strtolower(trim($app)); // Normalize the name

            if (!isset($cleaned[$appKey])) {
                $cleaned[$appKey] = ['app' => $app, 'duration' => 0];
            }
            $cleaned[$appKey]['duration'] += $duration;
        }
        return array_values($cleaned);
    }
}