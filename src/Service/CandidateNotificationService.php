<?php

namespace App\Service;

use InvalidArgumentException;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class CandidateNotificationService
{
    private const DEFAULT_FROM_EMAIL = 'mohamedkooli588@gmail.com';
    private const DEFAULT_FROM_NAME = 'HR-One';

    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly string $fromEmail = self::DEFAULT_FROM_EMAIL,
        private readonly string $fromName = self::DEFAULT_FROM_NAME,
    ) {
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function sendAcceptanceEmail(array $candidate, string $pdfPath, string $qrPath): void
    {
        $to = $this->extractCandidateEmail($candidate);
        $name = $this->extractCandidateName($candidate);
        $offerTitle = $this->extractOfferTitle($candidate);
        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($to, $name))
            ->subject(sprintf('Confirmation finale - %s', $offerTitle))
            ->text(sprintf(
                "Bonjour %s,\n\nSuite a votre entretien, nous avons le plaisir de vous confirmer que votre candidature pour le poste '%s' est retenue.\nVotre contrat est joint a cet email.\n\nCordialement,\nEquipe RH",
                $name,
                $offerTitle
            ));

        if (is_file($pdfPath)) {
            $email->attachFromPath($pdfPath, 'contrat.pdf', 'application/pdf');
        }

        if (is_file($qrPath)) {
            $email->attachFromPath($qrPath, 'contrat-qr.png', 'image/png');
        }

        if (is_file($this->resolveTemplatePath('candidate-accepted.html.twig'))) {
            $email
                ->htmlTemplate('email/candidate-accepted.html.twig')
                ->context([
                    'candidate' => $candidate,
                    'candidateName' => $name,
                    'offerTitle' => $offerTitle,
                ]);
        }

        $this->mailer->send($email);
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function sendAcceptedStatusEmail(array $candidate): void
    {
        $to = $this->extractCandidateEmail($candidate);
        $name = $this->extractCandidateName($candidate);
        $offerTitle = $this->extractOfferTitle($candidate);

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($to, $name))
            ->subject(sprintf('Candidature retenue - %s', $offerTitle))
            ->text(sprintf(
                "Bonjour %s,\n\nVotre candidature pour le poste '%s' a ete retenue pour la suite du processus.\nNotre equipe RH vous contactera avec les prochaines etapes.\n\nCordialement,\nEquipe RH",
                $name,
                $offerTitle
            ));

        if (is_file($this->resolveTemplatePath('candidate-accepted-status.html.twig'))) {
            $email
                ->htmlTemplate('email/candidate-accepted-status.html.twig')
                ->context([
                    'candidate' => $candidate,
                    'candidateName' => $name,
                    'offerTitle' => $offerTitle,
                ]);
        }

        $this->mailer->send($email);
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function sendRejectionEmail(array $candidate): void
    {
        $to = $this->extractCandidateEmail($candidate);
        $name = $this->extractCandidateName($candidate);
        $offerTitle = $this->extractOfferTitle($candidate);

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($to, $name))
            ->subject(sprintf('Mise a jour de votre candidature - %s', $offerTitle))
            ->text(sprintf(
                "Bonjour %s,\n\nNous vous remercions pour votre interet concernant le poste '%s'.\nApres etude de votre profil, votre candidature n'a pas ete retenue pour cette etape.\n\nCordialement,\nEquipe RH",
                $name,
                $offerTitle
            ));

        if (is_file($this->resolveTemplatePath('candidate-rejected.html.twig'))) {
            $email
                ->htmlTemplate('email/candidate-rejected.html.twig')
                ->context([
                    'candidate' => $candidate,
                    'candidateName' => $name,
                    'offerTitle' => $offerTitle,
                ]);
        }

        $this->mailer->send($email);
    }

    /**
     * @param array<string, mixed> $candidate
     */
    public function sendInterviewEmail(array $candidate): void
    {
        $to = $this->extractCandidateEmail($candidate);
        $name = $this->extractCandidateName($candidate);
        $offerTitle = $this->extractOfferTitle($candidate);

        $date = trim((string) ($candidate['interviewDate'] ?? ''));
        $time = trim((string) ($candidate['interviewTime'] ?? ''));
        $location = trim((string) ($candidate['interviewLocation'] ?? 'A definir'));

        $email = (new TemplatedEmail())
            ->from(new Address($this->fromEmail, $this->fromName))
            ->to(new Address($to, $name))
            ->subject(sprintf('Entretien planifie - %s', $offerTitle))
            ->text(sprintf(
                "Bonjour %s,\n\nVotre entretien pour le poste '%s' est planifie le %s a %s.\nLieu: %s\n\nCordialement,\nEquipe RH",
                $name,
                $offerTitle,
                $date !== '' ? $date : 'date a confirmer',
                $time !== '' ? $time : 'heure a confirmer',
                $location !== '' ? $location : 'A definir'
            ));

        if (is_file($this->resolveTemplatePath('candidate-interview.html.twig'))) {
            $email
                ->htmlTemplate('email/candidate-interview.html.twig')
                ->context([
                    'candidate' => $candidate,
                    'candidateName' => $name,
                    'offerTitle' => $offerTitle,
                    'interviewDate' => $date,
                    'interviewTime' => $time,
                    'interviewLocation' => $location,
                ]);
        }

        $this->mailer->send($email);
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function extractCandidateEmail(array $candidate): string
    {
        $email = trim((string) ($candidate['email'] ?? ''));
        if ($email === '') {
            throw new InvalidArgumentException('Email candidat introuvable.');
        }

        return $email;
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function extractCandidateName(array $candidate): string
    {
        $name = trim((string) ($candidate['name'] ?? ''));

        return $name !== '' ? $name : 'Candidat';
    }

    /**
     * @param array<string, mixed> $candidate
     */
    private function extractOfferTitle(array $candidate): string
    {
        $offerTitle = trim((string) ($candidate['offerTitle'] ?? 'Poste'));

        return $offerTitle !== '' ? $offerTitle : 'Poste';
    }

    private function resolveTemplatePath(string $templateFile): string
    {
        return dirname(__DIR__, 2) . '/templates/email/' . $templateFile;
    }
}
