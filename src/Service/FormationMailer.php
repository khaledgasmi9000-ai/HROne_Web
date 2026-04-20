<?php

namespace App\Service;

use App\Entity\Formation;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class FormationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress = 'no-reply@hr-one.local',
        private readonly string $fromName = 'HR One Formation'
    ) {
    }

    public function sendRegistrationConfirmation(string $recipientEmail, string $participantName, Formation $formation): void
    {
        $title = $formation->getTitre() ?: 'Formation';
        $ticketReference = $this->generateTicketReference($formation, $recipientEmail);
        $qrPayload = sprintf(
            "Ticket: %s\nParticipant: %s\nFormation: %s\nMode: %s\nNiveau: %s\nDate debut: %s\nDate fin: %s",
            $ticketReference,
            trim($participantName) !== '' ? $participantName : 'participant',
            $title,
            $formation->getMode() ?: 'Non defini',
            $formation->getNiveau() ?: 'Non defini',
            $this->formatStoredDate($formation->getDateDebut()),
            $this->formatStoredDate($formation->getDateFin())
        );

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($recipientEmail)
            ->subject('Confirmation d inscription - ' . $title)
            ->text($this->buildRegistrationBody($participantName, $formation))
            ->htmlTemplate('emails/formation_registration.html.twig');

        $logoCid = $this->attachLogo($email);

        $email->context([
            'participant_name' => trim($participantName) !== '' ? $participantName : 'participant',
            'formation_title' => $title,
            'formation_mode' => $formation->getMode() ?: 'Non defini',
            'formation_level' => $formation->getNiveau() ?: 'Non defini',
            'formation_start' => $this->formatStoredDate($formation->getDateDebut()),
            'formation_end' => $this->formatStoredDate($formation->getDateFin()),
            'ticket_reference' => $ticketReference,
            'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($qrPayload),
            'logo_cid' => $logoCid,
        ]);

        $this->mailer->send($email);
    }

    public function sendCertificateEmail(
        string $recipientEmail,
        string $participantName,
        Formation $formation,
        string $pdfContent,
        string $filename
    ): void {
        $title = $formation->getTitre() ?: 'Formation';
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromAddress, $this->fromName))
            ->to($recipientEmail)
            ->subject('Certificat de formation - ' . $title)
            ->text($this->buildCertificateBody($participantName, $formation))
            ->htmlTemplate('emails/formation_certificate.html.twig');

        $logoCid = $this->attachLogo($email);

        $email->context([
            'participant_name' => trim($participantName) !== '' ? $participantName : 'participant',
            'formation_title' => $title,
            'formation_mode' => $formation->getMode() ?: 'Non defini',
            'formation_level' => $formation->getNiveau() ?: 'Non defini',
            'formation_start' => $this->formatStoredDate($formation->getDateDebut()),
            'formation_end' => $this->formatStoredDate($formation->getDateFin()),
            'issued_at' => $this->formatToday(),
            'logo_cid' => $logoCid,
        ]);

        $email->attach($pdfContent, $filename, 'application/pdf');

        $this->mailer->send($email);
    }

    private function buildRegistrationBody(string $participantName, Formation $formation): string
    {
        $lines = [
            sprintf('Bonjour %s,', trim($participantName) !== '' ? $participantName : 'participant'),
            '',
            'Votre inscription a bien ete enregistree.',
            sprintf('Formation : %s', $formation->getTitre() ?: 'Formation'),
            sprintf('Mode : %s', $formation->getMode() ?: 'Non defini'),
            sprintf('Niveau : %s', $formation->getNiveau() ?: 'Non defini'),
        ];

        if ($formation->getDateDebut() !== null) {
            $lines[] = sprintf('Date debut : %s', $this->formatStoredDate($formation->getDateDebut()));
        }

        if ($formation->getDateFin() !== null) {
            $lines[] = sprintf('Date fin : %s', $this->formatStoredDate($formation->getDateFin()));
        }

        $lines[] = '';
        $lines[] = 'Merci,';
        $lines[] = 'Equipe HR One';

        return implode(PHP_EOL, $lines);
    }

    private function buildCertificateBody(string $participantName, Formation $formation): string
    {
        return implode(PHP_EOL, [
            sprintf('Bonjour %s,', trim($participantName) !== '' ? $participantName : 'participant'),
            '',
            'Votre certificat de formation est disponible en piece jointe.',
            sprintf('Formation : %s', $formation->getTitre() ?: 'Formation'),
            sprintf('Date emission : %s', $this->formatToday()),
            '',
            'Equipe HR One',
        ]);
    }

    private function attachLogo(TemplatedEmail $email): string
    {
        $logoPath = dirname(__DIR__, 2) . '/public/images/logo-rh.png';

        if (!is_file($logoPath)) {
            return '';
        }

        $contentId = 'logo-rh';
        $email->embedFromPath($logoPath, $contentId);

        return 'cid:' . $contentId;
    }

    private function generateTicketReference(Formation $formation, string $recipientEmail): string
    {
        $formationId = $formation->getIDFormation() ?? 0;
        $emailHash = strtoupper(substr(sha1(mb_strtolower($recipientEmail)), 0, 6));

        return sprintf('FRM-%04d-%s', $formationId, $emailHash);
    }

    private function formatToday(): string
    {
        return (new \DateTimeImmutable())->format('d/m/Y');
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
