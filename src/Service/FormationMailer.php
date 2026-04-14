<?php

namespace App\Service;

use App\Entity\Formation;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Twig\Environment;

class FormationMailer
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromAddress = 'no-reply@hr-one.local'
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
            ->from($this->fromAddress)
            ->to($recipientEmail)
            ->subject('Confirmation d inscription - ' . $title)
            ->text($this->buildRegistrationBody($participantName, $formation))
            ->htmlTemplate('emails/formation_registration.html.twig')
            ->context([
                'participant_name' => trim($participantName) !== '' ? $participantName : 'participant',
                'formation_title' => $title,
                'formation_mode' => $formation->getMode() ?: 'Non defini',
                'formation_level' => $formation->getNiveau() ?: 'Non defini',
                'formation_start' => $this->formatStoredDate($formation->getDateDebut()),
                'formation_end' => $this->formatStoredDate($formation->getDateFin()),
                'ticket_reference' => $ticketReference,
                'qr_code_url' => 'https://api.qrserver.com/v1/create-qr-code/?size=180x180&data=' . rawurlencode($qrPayload),
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
            ->from($this->fromAddress)
            ->to($recipientEmail)
            ->subject('Certificat de formation - ' . $title)
            ->text($this->buildCertificateBody($participantName, $formation))
            ->html($this->buildCertificateHtml($participantName, $formation))
            ->attach($pdfContent, $filename, 'application/pdf');

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
            $lines[] = sprintf('Date debut : %s', (string) $formation->getDateDebut());
        }

        if ($formation->getDateFin() !== null) {
            $lines[] = sprintf('Date fin : %s', (string) $formation->getDateFin());
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
            sprintf('Date emission : %s', date('d/m/Y')),
            '',
            'Equipe HR One',
        ]);
    }

    private function buildCertificateHtml(string $participantName, Formation $formation): string
    {
        $participantName = htmlspecialchars(trim($participantName) !== '' ? $participantName : 'participant', ENT_QUOTES, 'UTF-8');
        $formationTitle = htmlspecialchars($formation->getTitre() ?: 'Formation', ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Certificat de formation</title></head>
<body style="margin:0;padding:24px;background:#f4f7fb;font-family:Arial,sans-serif;color:#1f2937;">
  <div style="max-width:620px;margin:0 auto;background:#ffffff;border:1px solid #dbe4f0;border-radius:16px;overflow:hidden;">
    <div style="background:#0f172a;padding:22px 28px;color:#ffffff;">
      <div style="font-size:24px;font-weight:700;">HR One</div>
      <div style="margin-top:6px;font-size:14px;color:#cbd5e1;">Certificat de formation</div>
    </div>
    <div style="padding:28px;">
      <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Bonjour <strong>{$participantName}</strong>,</p>
      <p style="margin:0 0 18px;font-size:15px;line-height:1.7;color:#475569;">Votre certificat pour la formation <strong>{$formationTitle}</strong> est pret. Vous le trouverez en piece jointe a cet e-mail.</p>
      <p style="margin:0;font-size:15px;line-height:1.7;color:#475569;">Equipe HR One</p>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function generateTicketReference(Formation $formation, string $recipientEmail): string
    {
        $formationId = $formation->getIDFormation() ?? 0;
        $emailHash = strtoupper(substr(sha1(mb_strtolower($recipientEmail)), 0, 6));

        return sprintf('FRM-%04d-%s', $formationId, $emailHash);
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
