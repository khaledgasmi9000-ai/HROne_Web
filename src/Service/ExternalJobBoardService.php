<?php

namespace App\Service;

class ExternalJobBoardService
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function fetchExternalOffers(int $limit = 12): array
    {
        $url = sprintf('https://www.arbeitnow.com/api/job-board-api?limit=%d', max(1, $limit));

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'timeout' => 8,
                'header' => [
                    'Accept: application/json',
                    'User-Agent: HROne/1.0',
                ],
            ],
        ]);

        $raw = @file_get_contents($url, false, $context);
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $payload = json_decode($raw, true, 512, JSON_INVALID_UTF8_IGNORE);
        if (!is_array($payload)) {
            return [];
        }

        $rows = $payload['data'] ?? [];
        if (!is_array($rows)) {
            return [];
        }

        $offers = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $title = $this->cleanText((string) ($row['title'] ?? ''));
            $urlValue = $this->cleanText((string) ($row['url'] ?? ''));
            if ($title === '' || $urlValue === '') {
                continue;
            }

            $descriptionHtml = $this->cleanUtf8((string) ($row['description'] ?? ''));
            $descriptionText = $this->cleanText(strip_tags($descriptionHtml));
            if (strlen($descriptionText) > 280) {
                $descriptionText = substr($descriptionText, 0, 277) . '...';
            }

            $location = $this->cleanText((string) ($row['location'] ?? 'Non precise'));
            $jobTypes = is_array($row['job_types'] ?? null) ? $row['job_types'] : [];
            $contract = $jobTypes !== []
                ? implode(' / ', array_map(fn (mixed $value): string => $this->cleanText((string) $value), $jobTypes))
                : 'Externe';

            $isRemote = (bool) ($row['remote'] ?? false);
            $tags = is_array($row['tags'] ?? null) ? $row['tags'] : [];
            $skills = implode(', ', array_slice(array_map(fn (mixed $value): string => $this->cleanText((string) $value), $tags), 0, 4));

            $slug = $this->cleanText((string) ($row['slug'] ?? md5($title . $urlValue)));

            $offers[] = [
                'id' => 'ext-' . $slug,
                'title' => $title,
                'location' => $location !== '' ? $location : 'Non precise',
                'contract' => $contract,
                'workType' => $isRemote ? 'Remote' : 'On-site',
                'experience' => $skills !== '' ? $skills : 'Non precisee',
                'description' => $descriptionText !== '' ? $descriptionText : 'Description non disponible.',
                'minSalary' => null,
                'maxSalary' => null,
                'expirationDate' => '',
                'status' => 'Externe',
                'statusClass' => 'status-external',
                'externalUrl' => $urlValue,
                'source' => 'Arbeitnow',
                'isExternal' => true,
            ];
        }

        return $offers;
    }

    private function cleanText(string $value): string
    {
        $value = $this->cleanUtf8($value);
        $value = html_entity_decode($value, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    private function cleanUtf8(string $value): string
    {
        // Remove invalid byte sequences that can break Twig escaping.
        $normalized = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if (is_string($normalized) && $normalized !== '') {
            return $normalized;
        }

        return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
    }
}
