<?php

namespace App\Service;

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
                "win_buckets = find_bucket('aw-watcher-window_');",
                // 2. Get events from both
                "win_events = query_bucket(win_buckets);",
                
                // 3. Union them into one stream
                "all_events = win_events;",
                
                // 4. Get AFK data to filter out idle time
                "afk_buckets = find_bucket('aw-watcher-afk_');",
                "afk_events = query_bucket(afk_buckets);",
                "not_afk = filter_keyvals(afk_events, 'status', ['not-afk']);",
                
                // 5. Intersect: Keep only window time where user was NOT AFK
                "active_events = filter_period_intersect(all_events, not_afk);",
                
                // 6. THE FIX: Merge ONLY by 'app'. 
                // This groups all Brave tabs into one 'Brave' entry.
                "merged = merge_events_by_keys(active_events, ['app']);",
                
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