<?php

namespace App\Service;

use App\Entity\Evenement;
use App\Entity\ListeAttente;
use App\Entity\ParticipationEvenement;
use App\Entity\Activite;
use Doctrine\ORM\EntityManagerInterface;

class WaitlistPromotionService
{
    public function __construct(
        private EntityManagerInterface $em,
        private EmailService $emailService,
        private ShadowUserService $shadowUserService
    ) {
    }

    /**
     * Automatically promote people from waiting list to fill empty spots
     * Returns the number of people promoted
     */
    public function promoteFromWaitlist(Evenement $evenement): int
    {
        $promoted = 0;

        // Check if event has a maximum capacity
        if (!$evenement->getNbMax()) {
            return 0; // No limit, no need to promote
        }

        // Count current participants
        $nbInscrits = $this->em->getRepository(ParticipationEvenement::class)
            ->count(['evenement' => $evenement]);

        // Calculate available spots
        $availableSpots = $evenement->getNbMax() - $nbInscrits;

        if ($availableSpots <= 0) {
            return 0; // Event is full
        }

        // Get waiting list ordered by date
        $waitingList = $this->em->getRepository(ListeAttente::class)->findBy(
            ['evenement' => $evenement],
            ['dateDemande' => 'ASC'],
            $availableSpots // Limit to available spots
        );

        foreach ($waitingList as $attente) {
            try {
                // Create new participation
                $participation = new ParticipationEvenement();
                
                // Create shadow user
                $participantId = $this->shadowUserService->createShadowUser(
                    $attente->getEmail(), 
                    $attente->getNomComplet()
                );
                
                $participation->setIdParticipant($participantId);
                
                // Get next order number
                $peRepo = $this->em->getRepository(ParticipationEvenement::class);
                $participation->setNumOrdreParticipation($peRepo->getNextNumOrdre());
                
                // Set event and participant details
                $participation->setEvenement($evenement);
                $participation->setNomComplet($attente->getNomComplet());
                $participation->setEmail($attente->getEmail());
                
                // Set activity from waiting list
                $activite = $this->em->getRepository(Activite::class)->find($attente->getIdActivite());
                if ($activite) {
                    $participation->setActivite($activite);
                } else {
                    // If no activity found, use first activity of event
                    if ($evenement->getActivites()->count() > 0) {
                        $participation->setActivite($evenement->getActivites()->first());
                    }
                }

                // Persist participation and remove from waiting list
                $this->em->persist($participation);
                $this->em->remove($attente);
                $this->em->flush();

                // Send promotion email
                try {
                    $this->emailService->sendPromotionNotice($participation);
                } catch (\Exception $e) {
                    // Continue even if email fails
                }

                $promoted++;

            } catch (\Exception $e) {
                // Log error but continue with next person
                continue;
            }
        }

        return $promoted;
    }
}
