<?php

namespace App\Service;

use App\Entity\Formation;

/**
 * Local fallback mailer.
 *
 * The original module used Symfony Mailer, but this project does not currently
 * ship with that package. We keep the same public API and persist email content
 * to local logs so the formation workflow still works end-to-end.
 */
class FormationMailer
{
    public function sendRegistrationConfirmation(string $recipientEmail, string $participantName, Formation $formation): void
    {
        $payload = [
            'type' => 'registration_confirmation',
            'to' => $recipientEmail,
            'participant' => trim($participantName) !== '' ? $participantName : 'participant',
            'formation_title' => $formation->getTitre() ?: 'Formation',
            'formation_mode' => $formation->getMode() ?: 'Non defini',
            'formation_level' => $formation->getNiveau() ?: 'Non defini',
            'formation_start' => $this->formatStoredDate($formation->getDateDebut()),
            'formation_end' => $this->formatStoredDate($formation->getDateFin()),
        ];

        $this->writeLog($payload);
    }

    public function sendCertificateEmail(
        string $recipientEmail,
        string $participantName,
        Formation $formation,
        string $pdfContent,
        string $filename
    ): void {
        $payload = [
            'type' => 'certificate_ready',
            'to' => $recipientEmail,
            'participant' => trim($participantName) !== '' ? $participantName : 'participant',
            'formation_title' => $formation->getTitre() ?: 'Formation',
            'issued_at' => date('Y-m-d H:i:s'),
            'attachment_filename' => $filename,
            'attachment_size' => strlen($pdfContent),
        ];

        $this->writeLog($payload);
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function writeLog(array $payload): void
    {
        $logDir = dirname(__DIR__, 2) . '/var/log/formation-mails';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $line = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($line)) {
            return;
        }

        $file = $logDir . '/mail-' . date('Ymd') . '.log';
        @file_put_contents($file, '[' . date('H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND);
    }

    private function formatStoredDate(?int $value): string
    {
        if ($value === null) {
            return 'Non definie';
        }

        $raw = (string) $value;
        if (strlen($raw) === 8) {
            $date = \DateTimeImmutable::createFromFormat('Ymd', $raw);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('d/m/Y');
            }
        }

        return $raw;
    }
}
