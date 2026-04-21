<?php

namespace App\Service;

use App\Entity\ParticipationEvenement;
use App\Entity\ListeAttente;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailService
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Mail de confirmation d'inscription.
     */
    public function sendConfirmation(ParticipationEvenement $participation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('mohamedkooli588@gmail.com', 'HROne RH'))
            ->to($participation->getEmail())
            ->subject('✅ Inscription Confirmée : ' . $participation->getEvenement()->getTitre())
            ->htmlTemplate('emails/confirmed.html.twig')
            ->context([
                'participation' => $participation,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Mail de mise en liste d'attente.
     */
    public function sendWaitlistNotice(ListeAttente $waitlist): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('mohamedkooli588@gmail.com', 'HROne RH'))
            ->to($waitlist->getEmail())
            ->subject('⏳ Liste d\'Attente : ' . $waitlist->getEvenement()->getTitre())
            ->htmlTemplate('emails/waitlist.html.twig')
            ->context([
                'waitlist' => $waitlist,
            ]);

        $this->mailer->send($email);
    }

    /**
     * Mail de promotion (Liste d'attente -> Confirmé).
     */
    public function sendPromotionNotice(ParticipationEvenement $participation): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('mohamedkooli588@gmail.com', 'HROne RH'))
            ->to($participation->getEmail())
            ->subject('🎉 Bonne Nouvelle : Votre place est confirmée !')
            ->htmlTemplate('emails/promotion.html.twig')
            ->context([
                'participation' => $participation,
            ]);

        $this->mailer->send($email);
    }
}
