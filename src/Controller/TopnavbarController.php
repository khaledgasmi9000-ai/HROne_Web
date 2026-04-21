<?php

namespace App\Controller;

use App\Repository\CondidatRepository;
use App\Repository\OffreRepository;
use App\Repository\EvenementRepository;
use App\Service\CandidateAiScoringService;
use App\Service\CvTextExtractorService;
use App\Service\ExternalJobBoardService;
use App\Service\OfferQrCodeService;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TopnavbarController extends AbstractController
{
    #[Route('/top/offres', name: 'topnav_offres')]
    public function offres(Request $request, OffreRepository $offreRepository, ExternalJobBoardService $externalJobBoardService): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $location = trim((string) $request->query->get('location', ''));

        $offers = $this->normalizeUtf8Recursive($offreRepository->fetchOffersForCandidate($search, $location));

        $externalOffers = $externalJobBoardService->fetchExternalOffers();
        if ($search !== '' || $location !== '') {
            $searchLower = mb_strtolower($search);
            $locationLower = mb_strtolower($location);

            $externalOffers = array_values(array_filter($externalOffers, static function (array $offer) use ($searchLower, $locationLower): bool {
                $title = mb_strtolower((string) ($offer['title'] ?? ''));
                $description = mb_strtolower((string) ($offer['description'] ?? ''));
                $workType = mb_strtolower((string) ($offer['workType'] ?? ''));
                $offerLocation = mb_strtolower((string) ($offer['location'] ?? ''));
                $experience = mb_strtolower((string) ($offer['experience'] ?? ''));

                $matchesSearch = $searchLower === ''
                    || str_contains($title, $searchLower)
                    || str_contains($description, $searchLower)
                    || str_contains($workType, $searchLower)
                    || str_contains($experience, $searchLower);

                $matchesLocation = $locationLower === '' || str_contains($offerLocation, $locationLower);

                return $matchesSearch && $matchesLocation;
            }));
        }

        $externalOffers = $this->normalizeUtf8Recursive($externalOffers);

        return $this->render('OffresEmplois/OffresEmplois.html.twig', [
            'offers' => is_array($offers) ? $offers : [],
            'externalOffers' => is_array($externalOffers) ? $externalOffers : [],
            'filters' => [
                'search' => $search,
                'location' => $location,
            ],
        ]);
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeUtf8Recursive(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizeUtf8Recursive($item);
            }

            return $value;
        }

        if (!is_string($value)) {
            return $value;
        }

        if (mb_check_encoding($value, 'UTF-8')) {
            return $value;
        }

        $iconvNormalized = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
        if (is_string($iconvNormalized) && $iconvNormalized !== '') {
            return $iconvNormalized;
        }

        $mbNormalized = @mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        if (is_string($mbNormalized) && $mbNormalized !== '') {
            return $mbNormalized;
        }

        return '';
    }

    #[Route('/top/offres/{id}/qr-code', name: 'topnav_offre_qr_code', methods: ['GET'])]
    public function offerQrCode(int $id, OffreRepository $offreRepository, OfferQrCodeService $offerQrCodeService): Response
    {
        $offer = $offreRepository->fetchOfferForManagement($id);
        if ($offer === null) {
            throw $this->createNotFoundException('Offre introuvable.');
        }

        $title = trim((string) ($offer['title'] ?? ''));
        $location = trim((string) ($offer['location'] ?? ''));
        $contract = trim((string) ($offer['contract'] ?? ''));
        $workType = trim((string) ($offer['workType'] ?? ''));
        $experience = trim((string) ($offer['experience'] ?? ''));
        $minSalary = $offer['minSalary'] ?? null;
        $maxSalary = $offer['maxSalary'] ?? null;
        $description = trim((string) ($offer['description'] ?? ''));

        $qrText = implode("\n", [
            'HR-ONE | FICHE OFFRE',
            '----------------------',
            'Title: ' . ($title !== '' ? $title : '-'),
            'Location: ' . ($location !== '' ? $location : '-'),
            'Contract: ' . ($contract !== '' ? $contract : '-'),
            'Work Type: ' . ($workType !== '' ? $workType : '-'),
            'Experience: ' . ($experience !== '' ? $experience : '-'),
            'Min Salary: ' . ($minSalary !== null ? (string) $minSalary . ' DTN' : '-'),
            'Max Salary: ' . ($maxSalary !== null ? (string) $maxSalary . ' DTN' : '-'),
            'Description: ' . ($description !== '' ? $description : '-'),
        ]);

        $result = $offerQrCodeService->buildOfferDetailsQr($qrText);

        return new Response($result->getString(), Response::HTTP_OK, [
            'Content-Type' => $result->getMimeType(),
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
        ]);
    }

    #[Route('/top/offres/{id}/qr-details', name: 'topnav_offre_qr_details', methods: ['GET'])]
    public function offerQrDetails(int $id, OffreRepository $offreRepository): Response
    {
        $offer = $offreRepository->fetchOfferForManagement($id);
        if ($offer === null) {
            throw $this->createNotFoundException('Offre introuvable.');
        }

        return $this->render('OffresEmplois/offer-qr-details.html.twig', [
            'offer' => $offer,
        ]);
    }

    #[Route('/top/formations', name: 'topnav_formations')]
    public function formations(): Response
    {
        return $this->redirectToRoute('app_formation_index');
    }

    #[Route('/top/evenements', name: 'topnav_evenements')]
    public function evenements(Request $request, EvenementRepository $evenementRepo, PaginatorInterface $paginator): Response
    {
        $search = $request->query->get('search', '');
        $sort = $request->query->get('sort', '');

        $queryBuilder = $evenementRepo->createQueryBuilder('e')
            ->orderBy('e.ID_Evenement', 'DESC');

        if ($search) {
            $queryBuilder->andWhere('e.Titre LIKE :search OR e.Description LIKE :search OR e.Localisation LIKE :search')
                ->setParameter('search', '%' . $search . '%');
        }

        if ($sort === 'date_asc') {
            $queryBuilder->orderBy('e.ID_Evenement', 'ASC');
        } elseif ($sort === 'titre') {
            $queryBuilder->orderBy('e.Titre', 'ASC');
        }

        $pagination = $paginator->paginate(
            $queryBuilder->getQuery(),
            $request->query->getInt('page', 1),
            6
        );

        return $this->render('Topnavbar/evenements.html.twig', [
            'evenements' => $pagination,
            'search' => $search,
            'sort' => $sort,
        ]);
    }

    #[Route('/top/participations', name: 'topnav_participations')]
    public function participations(): Response
    {
        return $this->redirectToRoute('app_my_participations');
    }

    #[Route('/top/demande-conge', name: 'topnav_demande_conge')]
    public function demandeConge(): Response
    {
        return $this->render('Topnavbar/demande-conge.html.twig');
    }

    #[Route('/top/communaute', name: 'topnav_communaute')]
    public function communaute(): Response
    {
        return $this->redirectToRoute('community_index');
    }

    #[Route('/top/mes-candidatures', name: 'topnav_mes_candidatures')]
    public function mesCandidatures(Request $request, CondidatRepository $condidatRepository): Response
    {
        $search = trim((string) $request->query->get('q', ''));
        $dashboard = $condidatRepository->getCandidateDashboard($search);

        return $this->render('Topnavbar/mes-candidatures.html.twig', [
            'candidatures' => $dashboard['items'],
            'candidaturesTotal' => $dashboard['total'],
            'candidaturesInProgress' => $dashboard['inProgress'],
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    #[Route('/top/candidatures/api', name: 'topnav_candidatures_api_list', methods: ['GET'])]
    public function listCandidaturesApi(Request $request, CondidatRepository $condidatRepository): JsonResponse
    {
        $search = trim((string) $request->query->get('q', ''));
        $dashboard = $condidatRepository->getCandidateDashboard($search);

        return $this->json([
            'candidatures' => $dashboard['items'] ?? [],
            'total' => $dashboard['total'] ?? 0,
            'inProgress' => $dashboard['inProgress'] ?? 0,
        ]);
    }

    #[Route('/top/candidatures/api', name: 'topnav_candidatures_api_create', methods: ['POST'])]
    public function createCandidatureApi(
        Request $request,
        CondidatRepository $condidatRepository,
        OffreRepository $offreRepository,
        CvTextExtractorService $cvTextExtractorService,
        CandidateAiScoringService $candidateAiScoringService
    ): JsonResponse
    {
        try {
            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                return $this->json(['message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $aiStatus = null;
            $payload['cvExtractedText'] = '';
            if (trim((string) ($payload['cvFileContentBase64'] ?? '')) !== '') {
                try {
                    $payload['cvExtractedText'] = $cvTextExtractorService->extractFromBase64(
                        (string) ($payload['cvFileName'] ?? ''),
                        (string) ($payload['cvMimeType'] ?? ''),
                        (string) ($payload['cvFileContentBase64'] ?? '')
                    );
                } catch (\Throwable $cvException) {
                    $aiStatus = [
                        'status' => 'failed',
                        'message' => trim($cvException->getMessage()) !== ''
                            ? 'Extraction CV impossible: ' . $cvException->getMessage()
                            : 'Extraction CV impossible.',
                    ];
                }
            }

            $validated = $condidatRepository->validateCandidaturePayload($payload);
            if ($validated['errors'] !== []) {
                return $this->json([
                    'message' => 'Validation des donnees echouee.',
                    'errors' => $validated['errors'],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $candidature = $condidatRepository->createCandidature($validated['data']);
            if (!is_array($aiStatus)) {
                $aiStatus = [
                    'status' => 'skipped',
                    'message' => 'Analyse IA non lancee.',
                ];
            }

            if ($aiStatus['status'] !== 'failed' && isset($candidature['id']) && isset($candidature['offerId'])) {
                if (!$candidateAiScoringService->isConfigured()) {
                    $aiStatus = [
                        'status' => 'skipped',
                        'message' => 'Analyse IA desactivee: definir GROQ_API_KEY dans .env.local.',
                    ];
                } else {
                try {
                    $offer = $offreRepository->fetchOfferForManagement((int) $candidature['offerId']) ?? [];
                    $offerForAi = [
                        ...$offer,
                        'skills' => $offer['skillNames'] ?? ($offer['skills'] ?? []),
                        'languages' => $offer['languageNames'] ?? ($offer['languages'] ?? []),
                        'background' => $offer['backgroundNames'] ?? ($offer['background'] ?? []),
                    ];
                    $assessment = $candidateAiScoringService->scoreCandidate($candidature, $offerForAi);
                    $updatedRows = $condidatRepository->saveAiAssessmentForCandidature(
                        (int) $candidature['id'],
                        $assessment['score'],
                        $assessment['recommendation'],
                        $assessment['summary']
                    );
                    if ($updatedRows < 1) {
                        throw new \RuntimeException('Analyse IA terminee mais non enregistree en base.');
                    }
                    $candidature = $condidatRepository->fetchCandidatureById((int) $candidature['id']) ?? $candidature;
                    $aiStatus = [
                        'status' => 'success',
                        'message' => 'Analyse IA terminee.',
                    ];
                } catch (\Throwable $aiException) {
                    $aiStatus = [
                        'status' => 'failed',
                        'message' => trim($aiException->getMessage()) !== ''
                            ? $aiException->getMessage()
                            : 'Echec analyse IA.',
                    ];
                }
                }
            }

            return $this->json([
                'candidature' => $candidature,
                'aiStatus' => $aiStatus,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $exception) {
            $status = str_contains($exception->getMessage(), 'deja')
                ? Response::HTTP_CONFLICT
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return $this->json([
                'message' => "Erreur serveur lors de la creation de la candidature.",
                'details' => $exception->getMessage(),
            ], $status);
        }
    }

    #[Route('/top/candidatures/api/{id}', name: 'topnav_candidatures_api_get', methods: ['GET'])]
    public function getCandidatureApi(int $id, CondidatRepository $condidatRepository): JsonResponse
    {
        $candidature = $condidatRepository->fetchCandidatureById($id);
        if ($candidature === null) {
            return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['candidature' => $candidature]);
    }

    #[Route('/top/candidatures/api/{id}', name: 'topnav_candidatures_api_update', methods: ['PUT'])]
    public function updateCandidatureApi(
        int $id,
        Request $request,
        CondidatRepository $condidatRepository,
        OffreRepository $offreRepository,
        CvTextExtractorService $cvTextExtractorService,
        CandidateAiScoringService $candidateAiScoringService
    ): JsonResponse
    {
        try {
            $existing = $condidatRepository->fetchCandidatureById($id);
            if ($existing === null) {
                return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
            }

            $payload = json_decode($request->getContent(), true);
            if (!is_array($payload)) {
                return $this->json(['message' => 'Payload invalide.'], Response::HTTP_BAD_REQUEST);
            }

            $aiStatus = null;
            $payload['cvExtractedText'] = '';
            if (trim((string) ($payload['cvFileContentBase64'] ?? '')) !== '') {
                try {
                    $payload['cvExtractedText'] = $cvTextExtractorService->extractFromBase64(
                        (string) ($payload['cvFileName'] ?? ''),
                        (string) ($payload['cvMimeType'] ?? ''),
                        (string) ($payload['cvFileContentBase64'] ?? '')
                    );
                } catch (\Throwable $cvException) {
                    $payload['cvExtractedText'] = (string) ($existing['cvExtractedText'] ?? '');
                    $aiStatus = [
                        'status' => 'failed',
                        'message' => trim($cvException->getMessage()) !== ''
                            ? 'Extraction CV impossible: ' . $cvException->getMessage()
                            : 'Extraction CV impossible.',
                    ];
                }
            } elseif (isset($existing['cvExtractedText'])) {
                $payload['cvExtractedText'] = (string) $existing['cvExtractedText'];
            }

            $validated = $condidatRepository->validateCandidaturePayload($payload);
            if ($validated['errors'] !== []) {
                return $this->json([
                    'message' => 'Validation des donnees echouee.',
                    'errors' => $validated['errors'],
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $candidature = $condidatRepository->updateCandidature($id, $validated['data']);
            if ($candidature === null) {
                return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
            }

            if (!is_array($aiStatus)) {
                $aiStatus = [
                    'status' => 'skipped',
                    'message' => 'Analyse IA non lancee.',
                ];
            }

            if ($aiStatus['status'] !== 'failed') {
                if (!$candidateAiScoringService->isConfigured()) {
                    $aiStatus = [
                        'status' => 'skipped',
                        'message' => 'Analyse IA desactivee: definir GROQ_API_KEY dans .env.local.',
                    ];
                } else {
                try {
                    $offer = $offreRepository->fetchOfferForManagement((int) ($candidature['offerId'] ?? 0)) ?? [];
                    $offerForAi = [
                        ...$offer,
                        'skills' => $offer['skillNames'] ?? ($offer['skills'] ?? []),
                        'languages' => $offer['languageNames'] ?? ($offer['languages'] ?? []),
                        'background' => $offer['backgroundNames'] ?? ($offer['background'] ?? []),
                    ];
                    $assessment = $candidateAiScoringService->scoreCandidate($candidature, $offerForAi);
                    $updatedRows = $condidatRepository->saveAiAssessmentForCandidature(
                        (int) $candidature['id'],
                        $assessment['score'],
                        $assessment['recommendation'],
                        $assessment['summary']
                    );
                    if ($updatedRows < 1) {
                        throw new \RuntimeException('Analyse IA terminee mais non enregistree en base.');
                    }
                    $candidature = $condidatRepository->fetchCandidatureById((int) $candidature['id']) ?? $candidature;
                    $aiStatus = [
                        'status' => 'success',
                        'message' => 'Analyse IA terminee.',
                    ];
                } catch (\Throwable $aiException) {
                    $aiStatus = [
                        'status' => 'failed',
                        'message' => trim($aiException->getMessage()) !== ''
                            ? $aiException->getMessage()
                            : 'Echec analyse IA.',
                    ];
                }
                }
            }

            return $this->json([
                'candidature' => $candidature,
                'aiStatus' => $aiStatus,
            ]);
        } catch (\Throwable $exception) {
            $status = str_contains($exception->getMessage(), 'deja')
                ? Response::HTTP_CONFLICT
                : Response::HTTP_INTERNAL_SERVER_ERROR;

            return $this->json([
                'message' => "Erreur serveur lors de la modification de la candidature.",
                'details' => $exception->getMessage(),
            ], $status);
        }
    }

    #[Route('/top/candidatures/api/{id}', name: 'topnav_candidatures_api_delete', methods: ['DELETE'])]
    public function deleteCandidatureApi(int $id, CondidatRepository $condidatRepository): JsonResponse
    {
        try {
            $deleted = $condidatRepository->deleteCandidature($id);
            if (!$deleted) {
                return $this->json(['message' => 'Candidature introuvable.'], Response::HTTP_NOT_FOUND);
            }

            return $this->json(['deleted' => true]);
        } catch (\Throwable $exception) {
            return $this->json([
                'message' => 'Erreur serveur lors de la suppression de la candidature.',
                'details' => $exception->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
