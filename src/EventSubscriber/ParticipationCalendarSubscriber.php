<?php

namespace App\EventSubscriber;

use App\Entity\Formation;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\UtilisateurRepository;
use CalendarBundle\CalendarEvents;
use CalendarBundle\Entity\Event;
use CalendarBundle\Event\CalendarEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ParticipationCalendarSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly UtilisateurRepository $utilisateurRepository,
        private readonly ParticipationFormationRepository $participationFormationRepository,
        private readonly FormationRepository $formationRepository,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CalendarEvents::SET_DATA => 'onCalendarSetData',
        ];
    }

    public function onCalendarSetData(CalendarEvent $calendar): void
    {
        $participantId = $this->resolveCurrentParticipantId();

        if ($participantId === null) {
            return;
        }

        $rangeStart = \DateTimeImmutable::createFromInterface($calendar->getStart())->setTime(0, 0, 0);
        $rangeEnd = \DateTimeImmutable::createFromInterface($calendar->getEnd())->setTime(23, 59, 59);
        $participations = $this->participationFormationRepository->findActiveByParticipant($participantId);

        foreach ($participations as $participation) {
            $formationId = $participation->getIDFormation();

            if ($formationId === null) {
                continue;
            }

            $formation = $this->formationRepository->find($formationId);

            if (!$formation instanceof Formation) {
                continue;
            }

            $start = $this->createDateFromStoredValue($formation->getDateDebut());
            $endInclusive = $this->createDateFromStoredValue($formation->getDateFin()) ?? $start;

            if ($start === null || $endInclusive === null) {
                continue;
            }

            if ($start > $rangeEnd || $endInclusive < $rangeStart) {
                continue;
            }

            $calendar->addEvent($this->buildFormationEvent($formation, $participantId, $start, $endInclusive));
        }
    }

    private function buildFormationEvent(
        Formation $formation,
        int $participantId,
        \DateTimeImmutable $start,
        \DateTimeImmutable $endInclusive
    ): Event {
        $formationId = $formation->getIDFormation() ?? 0;
        $modeLabel = $formation->getMode() === 'en_ligne' ? 'En ligne' : 'Presentiel';
        $levelLabel = $formation->getNiveau() ?: 'Debutant';
        $endExclusive = $endInclusive->modify('+1 day');

        return new Event(
            $formation->getTitre() ?: 'Formation',
            $start,
            $endExclusive,
            null,
            [
                'allDay' => true,
                'url' => $this->urlGenerator->generate('app_formation_show', [
                    'id' => $formationId,
                    'employee' => $participantId,
                ]),
                'backgroundColor' => '#dbeafe',
                'borderColor' => '#60a5fa',
                'textColor' => '#0f172a',
                'classNames' => ['participation-calendar-event'],
                'extendedProps' => [
                    'mode' => $modeLabel,
                    'level' => $levelLabel,
                    'status' => 'inscrit',
                ],
            ]
        );
    }

    private function resolveCurrentParticipantId(): ?int
    {
        $session = $this->requestStack->getMainRequest()?->getSession();

        if ($session === null) {
            return null;
        }

        $email = trim((string) $session->get('participant_email', ''));

        if ($email === '') {
            return null;
        }

        $employee = $this->utilisateurRepository->findEmployeeByEmail($email);

        if (!is_array($employee) || !isset($employee['id'])) {
            return null;
        }

        return (int) $employee['id'];
    }

    private function createDateFromStoredValue(?int $value): ?\DateTimeImmutable
    {
        if ($value === null) {
            return null;
        }

        $raw = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($raw === '') {
            return null;
        }

        if (strlen($raw) >= 14) {
            $date = \DateTimeImmutable::createFromFormat('YmdHis', substr($raw, 0, 14));

            if ($date instanceof \DateTimeImmutable) {
                return $date;
            }
        }

        if (strlen($raw) >= 8) {
            $date = \DateTimeImmutable::createFromFormat('Ymd', substr($raw, 0, 8));

            if ($date instanceof \DateTimeImmutable) {
                return $date->setTime(0, 0, 0);
            }
        }

        if (strlen($raw) === 10 || strlen($raw) === 9) {
            return (new \DateTimeImmutable())->setTimestamp((int) $raw)->setTime(0, 0, 0);
        }

        return null;
    }
}
