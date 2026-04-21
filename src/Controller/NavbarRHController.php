<?php

namespace App\Controller;

use DateTimeImmutable;
use Throwable;
use App\Repository\CondidatureRepository;
use App\Repository\OffreRepository;
use App\Service\CandidateNotificationService;
use App\Service\ContractPdfService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\TypeBackgroundEtudeRepository;
use App\Repository\TypeCompetenceRepository;
use App\Repository\TypeContratRepository;
use App\Repository\TypeLangueRepository;
use App\Repository\TypeNiveauEtudeRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NavbarRHController extends AbstractController
{
    #[Route('/rh', name: 'rh_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_user_module');
    }

    #[Route('/rh/users', name: 'rh_users')]
    public function users(): Response
    {
        return $this->redirectToRoute('app_user_module');
    }

    #[Route('/rh/historique-actions-master', name: 'rh_history')]
    public function history(): Response
    {
        return $this->redirectToRoute('app_rh_history');
    }

    #[Route('/rh/historique-des-actions', name: 'rh_history_des')]
    public function historyDes(): Response
    {
        return $this->redirectToRoute('app_rh_history');
    }

    #[Route('/rh/activity-watch', name: 'rh_activity_watch')]
    public function activityWatch(): Response
    {
        return $this->render('navbarRH/activity-watch.html.twig');
    }

    #[Route('/rh/gestion-conges', name: 'rh_conges')]
    public function conges(): Response
    {
        return $this->render('navbarRH/gestion-conges.html.twig');
    }

    #[Route('/rh/gestion-administrative', name: 'rh_admin')]
    public function administrative(): Response
    {
        return $this->render('navbarRH/gestion-administrative.html.twig');
    }

    #[Route('/rh/gestion-outils', name: 'rh_outils')]
    public function outils(): Response
    {
        return $this->render('navbarRH/gestion-outils.html.twig');
    }

    #[Route('/rh/gestion-des-outils', name: 'rh_outils_des')]
    public function outilsDes(): Response
    {
        return $this->redirectToRoute('rh_outils');
    }

    #[Route('/rh/gestion-entretiens', name: 'rh_entretiens')]
    public function entretiens(CondidatureRepository $condidatureRepository): Response
    {
        return $this->render('GestionEntretiensRH/gestion-entretiens.html.twig', [
            'offers' => $condidatureRepository->fetchOfferOptionsForRh(),
            'candidatures' => $condidatureRepository->fetchForRhManagement(),
        ]);
    }

    #[Route('/rh/gestion-entretiens/api/candidatures/{id}', name: 'rh_entretiens_api_candidature_details', methods: ['GET'])]
    public function candidatureDetailsApi(int $id, CondidatureRepository $condidatureRepository): JsonResponse
    {
        $candidate = $condidatureRepository->fetchRhCandidateByCandidatureId($id);
        if ($candidate === null) {
            return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['candidate' => $candidate]);
    }

    #[Route('/rh/gestion-entretiens/api/candidatures/{id}/status', name: 'rh_entretiens_api_candidature_status', methods: ['PUT'])]
    public function candidatureStatusApi(
        int $id,
        Request $request,
        CondidatureRepository $condidatureRepository,
        CandidateNotificationService $candidateNotificationService
    ): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                return $this->json(['message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $status = (string) ($payload['status'] ?? '');
            if (trim($status) === '') {
                return $this->json(['message' => 'Le statut est obligatoire.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $candidate = $condidatureRepository->updateStatusForRh($id, $status);
            if ($candidate === null) {
                return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $normalizedStatus = strtoupper(trim($status));
            $notification = [
                'sent' => false,
                'message' => null,
            ];

            try {
                if (in_array($normalizedStatus, ['ACCEPTED', 'ACCEPTEE', 'ACCEPTE'], true)) {
                    $candidateNotificationService->sendAcceptedStatusEmail($candidate);
                    $notification['sent'] = true;
                    $notification['message'] = 'Candidature acceptee. Email de confirmation envoye au candidat.';
                } elseif (in_array($normalizedStatus, ['REJECTED', 'REJETEE', 'REJETE'], true)) {
                    $candidateNotificationService->sendRejectionEmail($candidate);
                    $notification['sent'] = true;
                    $notification['message'] = 'Email de rejet envoye.';
                }
            } catch (Throwable $mailException) {
                $notification['message'] = 'Statut mis a jour, mais l email na pas pu etre envoye.';
                $notification['details'] = $mailException->getMessage();
            }

            return $this->json([
                'candidate' => $candidate,
                'notification' => $notification,
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'message' => 'Erreur serveur lors de la mise a jour du statut.',
                'details' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rh/gestion-entretiens/api/candidatures/{id}/entretien', name: 'rh_entretiens_api_schedule_interview', methods: ['POST'])]
    public function scheduleInterviewApi(
        int $id,
        Request $request,
        CondidatureRepository $condidatureRepository,
        CandidateNotificationService $candidateNotificationService
    ): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                return $this->json(['message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $scheduledAtInput = (string) ($payload['scheduledAt'] ?? '');
            $location = (string) ($payload['location'] ?? '');
            $evaluation = (string) ($payload['evaluation'] ?? '');

            if (trim($scheduledAtInput) === '') {
                return $this->json(['message' => 'La date et l heure de lentretien sont obligatoires.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if (trim($evaluation) === '') {
                return $this->json(['message' => 'Levaluation RH est obligatoire lors de la planification.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $scheduledAt = new DateTimeImmutable($scheduledAtInput);
            if ($scheduledAt <= new DateTimeImmutable('now')) {
                return $this->json([
                    'message' => 'La date de lentretien doit etre posterieure a la date et lheure actuelles.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $scheduled = $condidatureRepository->scheduleInterviewForRh($id, $scheduledAt, $location, $evaluation);

            if (!$scheduled) {
                return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $candidate = $condidatureRepository->fetchRhCandidateByCandidatureId($id);

            $notification = [
                'sent' => false,
                'message' => null,
            ];

            if ($candidate !== null) {
                try {
                    $candidateNotificationService->sendInterviewEmail($candidate);
                    $notification['sent'] = true;
                    $notification['message'] = 'Email de convocation dentretien envoye.';
                } catch (Throwable $mailException) {
                    $notification['message'] = 'Entretien planifie, mais lemail de convocation na pas pu etre envoye.';
                    $notification['details'] = $mailException->getMessage();
                }
            }

            return $this->json([
                'scheduled' => true,
                'candidate' => $candidate,
                'notification' => $notification,
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'message' => 'Erreur serveur lors de la planification de lentretien.',
                'details' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rh/gestion-entretiens/api/candidatures/{id}/decision-finale', name: 'rh_entretiens_api_final_decision', methods: ['POST'])]
    public function finalDecisionAfterInterviewApi(
        int $id,
        Request $request,
        CondidatureRepository $condidatureRepository,
        CandidateNotificationService $candidateNotificationService,
        ContractPdfService $contractPdfService
    ): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                return $this->json(['message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $decision = strtoupper(trim((string) ($payload['decision'] ?? '')));
            if (!in_array($decision, ['ACCEPTED', 'REJECTED'], true)) {
                return $this->json(['message' => 'La decision finale doit etre ACCEPTED ou REJECTED.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $current = $condidatureRepository->fetchRhCandidateByCandidatureId($id);
            if ($current === null) {
                return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $interviewDateTimeIso = trim((string) ($current['interviewDateTimeIso'] ?? ''));
            if ($interviewDateTimeIso === '') {
                return $this->json(['message' => 'Aucun entretien planifie pour cette candidature.'], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $interviewDateTime = new DateTimeImmutable($interviewDateTimeIso);
            if ($interviewDateTime > new DateTimeImmutable('now')) {
                return $this->json([
                    'message' => 'La decision finale est disponible uniquement apres la date et heure de lentretien.',
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $candidate = $condidatureRepository->updateStatusForRh($id, $decision);
            if ($candidate === null) {
                return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $notification = [
                'sent' => false,
                'message' => null,
            ];

            try {
                if ($decision === 'ACCEPTED') {
                    $contractPath = $contractPdfService->generateContractPdf($candidate);
                    $candidateNotificationService->sendAcceptanceEmail($candidate, $contractPath, '');
                    $notification['sent'] = true;
                    $notification['message'] = 'Decision finale enregistree. Email avec contrat envoye.';
                } else {
                    $candidateNotificationService->sendRejectionEmail($candidate);
                    $notification['sent'] = true;
                    $notification['message'] = 'Decision finale enregistree. Email de rejet envoye.';
                }
            } catch (Throwable $mailException) {
                $notification['message'] = 'Decision finale enregistree, mais lemail na pas pu etre envoye.';
                $notification['details'] = $mailException->getMessage();
            }

            return $this->json([
                'candidate' => $candidate,
                'notification' => $notification,
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'message' => 'Erreur serveur lors de la decision finale.',
                'details' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rh/gestion-des-entretiens', name: 'rh_entretiens_des')]
    public function entretiensDes(): Response
    {
        return $this->redirectToRoute('rh_entretiens');
    }

    #[Route('/rh/gestion-offres', name: 'rh_offres')]
    #[Route('/rh/gestion-offres/', name: 'rh_offres_slash')]
    public function offres(
        OffreRepository $offreRepository,
        TypeContratRepository $typeContratRepository,
        TypeNiveauEtudeRepository $typeNiveauEtudeRepository,
        TypeCompetenceRepository $typeCompetenceRepository,
        TypeLangueRepository $typeLangueRepository,
        TypeBackgroundEtudeRepository $typeBackgroundEtudeRepository
    ): Response
    {
        $offres = $offreRepository->fetchOffersForManagement();

        return $this->render('GestionDesOffresRH/gestion-offres.html.twig', [
            'offres' => $offres,
            'typeContrats' => $typeContratRepository->findBy([], ['Description_Contrat' => 'ASC']),
            'typeNiveauxEtude' => $typeNiveauEtudeRepository->findBy([], ['Description_Type_Etude' => 'ASC']),
            'typeCompetences' => $typeCompetenceRepository->findBy([], ['Description_Competence' => 'ASC']),
            'typeLangues' => $typeLangueRepository->findBy([], ['Description_Langue' => 'ASC']),
            'typeBackgroundEtudes' => $typeBackgroundEtudeRepository->findBy([], ['Description_Type_Background_Etude' => 'ASC']),
        ]);
    }

    #[Route('/rh/gestion-offres/api', name: 'rh_offres_api_create', methods: ['POST'])]
    public function createOffreApi(Request $request, OffreRepository $offreRepository): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                return $this->json(['message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $validated = $offreRepository->validateOfferPayload($payload);
            if ($validated['errors'] !== []) {
                return $this->json([
                    'message' => 'Validation des donnees echouee.',
                    'errors' => $validated['errors'],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $offer = $offreRepository->createOfferFromData($validated['data']);

            return $this->json(['offer' => $offer], Response::HTTP_CREATED);
        } catch (Throwable $exception) {
            $status = str_contains($exception->getMessage(), 'aucune entreprise disponible')
                ? Response::HTTP_CONFLICT
                : Response::HTTP_INTERNAL_SERVER_ERROR;
            return $this->json([
                'message' => "Erreur serveur lors de l'ajout de l'offre.",
                'details' => $exception->getMessage(),
            ], $status);
        }
    }

    #[Route('/rh/gestion-offres/api/{id}', name: 'rh_offres_api_get', methods: ['GET'])]
    public function getOffreApi(int $id, OffreRepository $offreRepository): JsonResponse
    {
        $offer = $offreRepository->fetchOfferForManagement($id);
        if ($offer === null) {
            return $this->json(['message' => 'Offre introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['offer' => $offer]);
    }

    #[Route('/rh/gestion-offres/api/{id}', name: 'rh_offres_api_update', methods: ['PUT'])]
    public function updateOffreApi(int $id, Request $request, OffreRepository $offreRepository): JsonResponse
    {
        try {
            $existing = $offreRepository->fetchOfferForManagement($id);
            if ($existing === null) {
                return $this->json(['message' => 'Offre introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                return $this->json(['message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $validated = $offreRepository->validateOfferPayload($payload);
            if ($validated['errors'] !== []) {
                return $this->json([
                    'message' => 'Validation des donnees echouee.',
                    'errors' => $validated['errors'],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $offer = $offreRepository->updateOfferFromData($id, $validated['data']);
            if ($offer === null) {
                return $this->json(['message' => 'Offre introuvable.'], Response::HTTP_NOT_FOUND);
            }

            return $this->json(['offer' => $offer]);
        } catch (Throwable $exception) {
            $status = str_contains($exception->getMessage(), 'Ordre de creation introuvable')
                ? Response::HTTP_CONFLICT
                : Response::HTTP_INTERNAL_SERVER_ERROR;
            return $this->json([
                'message' => "Erreur serveur lors de la modification de l'offre.",
                'details' => $exception->getMessage(),
            ], $status);
        }
    }

    #[Route('/rh/gestion-offres/api/{id}', name: 'rh_offres_api_delete', methods: ['DELETE'])]
    public function deleteOffreApi(int $id, OffreRepository $offreRepository): JsonResponse
    {
        try {
            $deleted = $offreRepository->deleteOfferWithDependencies($id);
            if (!$deleted) {
                return $this->json(['message' => 'Offre introuvable.'], Response::HTTP_NOT_FOUND);
            }

            return $this->json(['deleted' => true]);
        } catch (Throwable $exception) {
            return $this->json([
                'message' => "Erreur serveur lors de la suppression de l'offre.",
                'details' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/rh/gestion-des-offres', name: 'rh_offres_des')]
    public function offresDes(): Response
    {
        return $this->redirectToRoute('rh_offres');
    }

    #[Route('/rh/gestion-evenements', name: 'rh_evenements')]
    public function evenements(): Response
    {
        return $this->redirectToRoute('app_rh_evenement_index');
    }

    #[Route('/rh/gestion-des-evenements', name: 'rh_evenements_des')]
    public function evenementsDes(): Response
    {
        return $this->redirectToRoute('app_rh_evenement_index');
    }

    #[Route('/rh/gestion-formations', name: 'rh_formations')]
    public function formations(): Response
    {
        return $this->redirectToRoute('app_admin_formation_index');
    }

    #[Route('/rh/gestion-des-formations', name: 'rh_formations_des')]
    public function formationsDes(): Response
    {
        return $this->redirectToRoute('rh_formations');
    }
}
