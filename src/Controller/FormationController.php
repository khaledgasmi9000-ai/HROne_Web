<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\Certification;
use App\Entity\ParticipationFormation;
use App\Repository\CertificationRepository;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\UtilisateurRepository;
use App\Service\CertificatePdfGenerator;
use App\Service\FormationMailer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

final class FormationController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(): Response
    {
        return $this->redirectToRoute('app_formation_index');
    }

    #[Route('/formations', name: 'app_formation_index', methods: ['GET'])]
    public function index(Request $request, FormationRepository $formationRepository): Response
    {
        $mode = $request->query->get('mode');
        $level = $request->query->get('level');
        $keyword = $request->query->get('q');
        $formations = $formationRepository->searchForCatalog($mode, $keyword, $level);
        $featured = $formationRepository->findFeaturedFormation();

        return $this->render('formation/index.html.twig', [
            'formations' => array_map([$this, 'mapFormation'], $formations),
            'featured' => $featured ? $this->mapFormation($featured) : null,
            'filters' => [
                'mode' => $mode,
                'level' => $level,
                'q' => $keyword,
            ],
            'stats' => [
                'total' => count($formations),
                'available' => count(array_filter($formations, static fn (Formation $formation) => ($formation->getPlacesRestantes() ?? 0) > 0)),
                'online' => count(array_filter($formations, static fn (Formation $formation) => $formation->getMode() === 'en_ligne')),
                'presentiel' => count(array_filter($formations, static fn (Formation $formation) => $formation->getMode() === 'presentiel')), 
            ],
        ]);
    }

    #[Route('/formations/{id}', name: 'app_formation_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        Request $request,
        FormationRepository $formationRepository,
        ParticipationFormationRepository $participationFormationRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response
    {
        $formation = $formationRepository->find($id);

        if (!$formation instanceof Formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $demoEmployees = $this->buildDemoEmployees($utilisateurRepository);
        $selectedParticipantId = $this->resolveParticipantId($request, $demoEmployees);
        $selectedEmployee = $this->findSelectedEmployee($selectedParticipantId, $demoEmployees);
        $activeParticipation = $selectedParticipantId !== null
            ? $participationFormationRepository->findActiveParticipation($formation->getIDFormation() ?? 0, $selectedParticipantId)
            : null;

        return $this->render('formation/show.html.twig', [
            'formation' => $this->mapFormation($formation),
            'demo_employees' => $demoEmployees,
            'selected_participant_id' => $selectedParticipantId,
            'selected_employee' => $selectedEmployee,
            'active_participation' => $activeParticipation,
        ]);
    }

    #[Route('/formations/{id}/inscription', name: 'app_formation_subscribe', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function subscribe(
        int $id,
        Request $request,
        FormationRepository $formationRepository,
        ParticipationFormationRepository $participationFormationRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager,
        FormationMailer $formationMailer
    ): RedirectResponse
    {
        $formation = $formationRepository->find($id);

        if (!$formation instanceof Formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $demoEmployees = $this->buildDemoEmployees($utilisateurRepository);
        $participantId = $this->resolveParticipantId($request, $demoEmployees);

        if ($participantId === null) {
            $this->addFlash('error', 'Selectionnez un employe avant de demander une inscription.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id]);
        }

        if (($formation->getPlacesRestantes() ?? 0) <= 0) {
            $this->addFlash('error', 'Cette formation est complete. Aucune inscription supplementaire n est possible.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
        }

        $registrationIdentity = [
            'nom' => trim((string) $request->request->get('nom', '')),
            'prenom' => trim((string) $request->request->get('prenom', '')),
            'email' => trim((string) $request->request->get('email', '')),
        ];

        if ($registrationIdentity['nom'] === '' || $registrationIdentity['prenom'] === '' || $registrationIdentity['email'] === '') {
            $this->addFlash('error', 'Nom, prenom et email sont obligatoires pour participer a la formation.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
        }

        if (!filter_var($registrationIdentity['email'], FILTER_VALIDATE_EMAIL)) {
            $this->addFlash('error', 'L email saisi est invalide.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
        }

        $matchedEmployee = $utilisateurRepository->findEmployeeByEmail($registrationIdentity['email']);

        if ($matchedEmployee === null) {
            $this->addFlash('error', 'L email saisi n existe pas dans la base utilisateur.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
        }

        $participantId = (int) $matchedEmployee['id'];

        if ($participationFormationRepository->hasActiveParticipation($id, $participantId)) {
            $this->addFlash('error', 'Cet employe est deja inscrit a cette formation.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
        }

        $activeParticipations = $participationFormationRepository->findActiveByParticipant($participantId);

        foreach ($activeParticipations as $existingParticipation) {
            $existingFormationId = $existingParticipation->getIDFormation();

            if ($existingFormationId === null || $existingFormationId === $id) {
                continue;
            }

            $existingFormation = $formationRepository->find($existingFormationId);

            if ($existingFormation instanceof Formation && $this->hasDateOverlap($formation, $existingFormation)) {
                $this->addFlash('error', 'Inscription refusee : cette formation chevauche une autre formation deja reservee sur la meme periode.');

                return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
            }
        }

        $participation = (new ParticipationFormation())
            ->setID_Formation($id)
            ->setID_Participant($participantId)
            ->setNum_Ordre_Participation($participationFormationRepository->getNextOrderNumber())
            ->setStatut('inscrit');

        $formation->setPlacesRestantes(max(0, ($formation->getPlacesRestantes() ?? 0) - 1));

        $entityManager->persist($participation);
        $entityManager->flush();
        $request->getSession()->set('participant_email', $registrationIdentity['email']);

        try {
            $formationMailer->sendRegistrationConfirmation(
                $registrationIdentity['email'],
                trim($registrationIdentity['prenom'] . ' ' . $registrationIdentity['nom']),
                $formation
            );
        } catch (\Throwable) {
            // Keep registration successful even if email delivery is not configured yet.
        }


        $this->addFlash('success', 'Inscription enregistree avec succes.');

        return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
    }

    #[Route('/formations/{id}/annulation', name: 'app_formation_cancel', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function cancel(
        int $id,
        Request $request,
        FormationRepository $formationRepository,
        ParticipationFormationRepository $participationFormationRepository,
        UtilisateurRepository $utilisateurRepository,
        EntityManagerInterface $entityManager
    ): RedirectResponse
    {
        $formation = $formationRepository->find($id);

        if (!$formation instanceof Formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $demoEmployees = $this->buildDemoEmployees($utilisateurRepository);
        $participantId = $this->resolveParticipantId($request, $demoEmployees);

        if ($participantId === null) {
            $this->addFlash('error', 'Employe introuvable pour cette annulation.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id]);
        }

        if (!$participationFormationRepository->hasActiveParticipation($id, $participantId)) {
            $this->addFlash('error', 'Aucune inscription active n a ete trouvee pour cet employe.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
        }

        $remainingPlaces = ($formation->getPlacesRestantes() ?? 0) + 1;
        $totalPlaces = $formation->getNombrePlaces() ?? $remainingPlaces;
        $formation->setPlacesRestantes(min($remainingPlaces, $totalPlaces));

        $participationFormationRepository->removeActiveParticipation($id, $participantId);
        $entityManager->flush();

        $this->addFlash('success', 'Inscription annulee avec succes.');

        return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
    }

      #[Route('/mes-participations', name: 'app_my_participations', methods: ['GET'])]
    public function myParticipations(
        Request $request,
        ParticipationFormationRepository $participationFormationRepository,
        FormationRepository $formationRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response
    {
        // Récupérer l'email depuis l'URL (?email=xxx)
        $email = (string) $request->getSession()->get('participant_email', '');
        $selectedParticipantId = null;
        $selectedEmployee = null;
        $rows = [];

        if ($email !== '') {
            // Chercher l'employé par son email
            $matchedEmployee = $utilisateurRepository->findEmployeeByEmail($email);

            if ($matchedEmployee !== null) {
                $selectedParticipantId = $matchedEmployee['id'];
                $selectedEmployee = $matchedEmployee;

                // Récupérer toutes ses participations
                $participations = $participationFormationRepository->findByParticipantOrdered($selectedParticipantId);

                foreach ($participations as $participation) {
                    $formation = $formationRepository->find($participation->getIDFormation());

                    if ($formation instanceof Formation) {
                        $rows[] = [
                            'participation' => $participation,
                            'formation'     => $this->mapFormation($formation),
                        ];
                    }
                }
            }
        }

        return $this->render('formation/participations.html.twig', [
            'selected_participant_id' => $selectedParticipantId,
            'selected_employee'       => $selectedEmployee,
            'email'                   => $email,
            'participations'          => $rows,
            'calendar_events'         => $this->buildParticipationCalendarEvents($rows, $selectedParticipantId),
        ]);
    }


    #[Route('/rh/formations', name: 'app_admin_formation_index', methods: ['GET'])]
    public function adminIndex(Request $request, FormationRepository $formationRepository): Response
    {
        $mode = $request->query->get('mode');
        $level = $request->query->get('level');
        $keyword = $request->query->get('q');
        $formations = $formationRepository->searchForCatalog($mode, $keyword, $level);

        return $this->render('admin/formation/index.html.twig', [
            'formations' => array_map([$this, 'mapFormation'], $formations),
            'filters' => [
                'mode' => $mode,
                'level' => $level,
                'q' => $keyword,
            ],
            'stats' => [
                'total' => count($formations),
                'available' => count(array_filter($formations, static fn (Formation $formation) => ($formation->getPlacesRestantes() ?? 0) > 0)),
                'full' => count(array_filter($formations, static fn (Formation $formation) => ($formation->getPlacesRestantes() ?? 0) <= 0)),
                'online' => count(array_filter($formations, static fn (Formation $formation) => $formation->getMode() === 'en_ligne')),
                'presentiel' => count(array_filter($formations, static fn (Formation $formation) => $formation->getMode() === 'presentiel')), 
            ],
        ]);
    }
    #[Route('/rh/formations/{id}/participants', name: 'app_admin_formation_participants', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function adminParticipants(
        int $id,
        FormationRepository $formationRepository,
        ParticipationFormationRepository $participationFormationRepository,
        UtilisateurRepository $utilisateurRepository
    ): Response
    {
        $formation = $formationRepository->find($id);

        if (!$formation instanceof Formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $participations = $participationFormationRepository->findByFormationOrdered($id);
        $participantIds = array_values(array_unique(array_map(
            static fn (ParticipationFormation $participation): int => $participation->getIDParticipant() ?? 0,
            $participations
        )));
        $participantIds = array_values(array_filter($participantIds, static fn (int $value): bool => $value > 0));
        $participantsById = $utilisateurRepository->findEmployeesByIds($participantIds);

        $rows = array_map(function (ParticipationFormation $participation) use ($participantsById): array {
            $participantId = $participation->getIDParticipant() ?? 0;
            $participant = $participantsById[$participantId] ?? [
                'id' => $participantId,
                'label' => 'Utilisateur inconnu',
                'email' => 'Email indisponible',
            ];

            return [
                'participant' => $participant,
                'participation' => $participation,
                'certificate_path' => $participation->getCertificat(),
            ];
        }, $participations);

        return $this->render('admin/formation/participants.html.twig', [
            'formation' => $this->mapFormation($formation),
            'participants' => $rows,
        ]);
    }

    #[Route('/rh/formations/{formationId}/participants/{participantId}/{order}/acheve', name: 'app_admin_formation_participant_complete', methods: ['POST'], requirements: ['formationId' => '\d+', 'participantId' => '\d+', 'order' => '\d+'])]
    public function adminCompleteParticipant(
        int $formationId,
        int $participantId,
        int $order,
        FormationRepository $formationRepository,
        ParticipationFormationRepository $participationFormationRepository,
        UtilisateurRepository $utilisateurRepository,
        CertificationRepository $certificationRepository,
        CertificatePdfGenerator $certificatePdfGenerator,
        FormationMailer $formationMailer,
        EntityManagerInterface $entityManager
    ): Response {
        $formation = $formationRepository->find($formationId);

        if (!$formation instanceof Formation) {
            throw $this->createNotFoundException('Formation introuvable.');
        }

        $participation = $participationFormationRepository->findOneBy([
            'ID_Formation' => $formationId,
            'ID_Participant' => $participantId,
            'Num_Ordre_Participation' => $order,
        ]);

        if (!$participation instanceof ParticipationFormation) {
            throw $this->createNotFoundException('Participation introuvable.');
        }

        $participant = $utilisateurRepository->findEmployeesByIds([$participantId])[$participantId] ?? null;

        if (!is_array($participant)) {
            throw $this->createNotFoundException('Participant introuvable.');
        }

        $certificateReference = sprintf('CERT-%04d-%04d-%d', $formationId, $participantId, $order);
        $pdfContent = $certificatePdfGenerator->generate($formation, $participant, $certificateReference);
        $filename = sprintf('certificat-formation-%d-participant-%d.pdf', $formationId, $participantId);
        $relativePath = $this->storeCertificateFile($filename, $pdfContent);

        $participation->setStatut('acheve');
        $participation->setCertificat($relativePath);

        $certification = $certificationRepository->findOneByFormationAndParticipant($formationId, $participantId) ?? (new Certification())
            ->setID_Certif($certificationRepository->getNextId())
            ->setID_Formation($formationId)
            ->setID_Participant($participantId);

        $certification
            ->setDescription_Certif(sprintf('Certificat genere pour %s', $participant['label']))
            ->setFichier_PDF($pdfContent);

        $entityManager->persist($certification);
        $entityManager->flush();

        if ($participant['email'] !== '') {
            try {
                $formationMailer->sendCertificateEmail(
                    $participant['email'],
                    $participant['label'],
                    $formation,
                    $pdfContent,
                    $filename
                );
            } catch (\Throwable) {
                // Keep certificate generation available even if the email cannot be sent.
            }
        }

        return $this->buildPdfResponse($pdfContent, $filename);
    }

    #[Route('/rh/formations/{formationId}/participants/{participantId}/{order}/certificat', name: 'app_admin_formation_participant_certificate', methods: ['GET'], requirements: ['formationId' => '\d+', 'participantId' => '\d+', 'order' => '\d+'])]
    public function adminParticipantCertificate(
        int $formationId,
        int $participantId,
        int $order,
        ParticipationFormationRepository $participationFormationRepository
    ): Response {
        $participation = $participationFormationRepository->findOneBy([
            'ID_Formation' => $formationId,
            'ID_Participant' => $participantId,
            'Num_Ordre_Participation' => $order,
        ]);

        if (!$participation instanceof ParticipationFormation || $participation->getCertificat() === null) {
            throw $this->createNotFoundException('Certificat introuvable.');
        }

        $absolutePath = dirname(__DIR__, 2) . '/public' . $participation->getCertificat();

        if (!is_file($absolutePath)) {
            throw $this->createNotFoundException('Fichier certificat introuvable.');
        }

        $response = new BinaryFileResponse($absolutePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($absolutePath));

        return $response;
    }

    #[Route('/rh/formations/new', name: 'app_admin_formation_new', methods: ['GET', 'POST'])]
    public function adminNew(Request $request, EntityManagerInterface $entityManager, FormationRepository $formationRepository): Response
    {
        $formation = new Formation();
        $errors = [];
        $formData = $this->buildFormData($formation, $formationRepository);

        if ($request->isMethod('POST')) {
            $formData = $this->extractFormData($request, $formationRepository);
            $errors = $this->validateFormData($formData);

            if ($errors === []) {
                $formData['image'] = $this->resolveStoredImagePath($request, $formData['image']);
                $this->hydrateFormation($formation, $formData);
                $entityManager->persist($formation);
                $entityManager->flush();

                $this->addFlash('success', 'La formation a ete creee avec succes.');

                return $this->redirectToRoute('app_admin_formation_index');
            }
        }

        return $this->render('admin/formation/new.html.twig', [
            'formation' => null,
            'form_data' => $formData,
            'errors' => $errors,
            'submit_label' => 'Creer la formation',
            'page_title' => 'Creer une formation',
        ]);
    }

    #[Route('/rh/formations/{id}/edit', name: 'app_admin_formation_edit', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function adminEdit(Formation $formation, Request $request, EntityManagerInterface $entityManager): Response
    {
        $errors = [];
        $formData = $this->buildFormData($formation, null);

        if ($request->isMethod('POST')) {
            $formData = $this->extractFormData($request, null, $formation);
            $errors = $this->validateFormData($formData);

            if ($errors === []) {
                $formData['image'] = $this->resolveStoredImagePath($request, $formData['image']);
                $this->hydrateFormation($formation, $formData);
                $entityManager->flush();

                $this->addFlash('success', 'La formation a ete modifiee avec succes.');

                return $this->redirectToRoute('app_admin_formation_index');
            }
        }

        return $this->render('admin/formation/edit.html.twig', [
            'formation' => $this->mapFormation($formation),
            'form_data' => $formData,
            'errors' => $errors,
            'submit_label' => 'Enregistrer les modifications',
            'page_title' => 'Modifier la formation',
        ]);
    }
    #[Route('/rh/formations/{id}/delete', name: 'app_admin_formation_delete', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function adminDelete(
        Formation $formation,
        EntityManagerInterface $entityManager,
        ParticipationFormationRepository $participationFormationRepository,
        CertificationRepository $certificationRepository
    ): RedirectResponse
    {
        $formationId = $formation->getIDFormation() ?? $formation->getID_Formation();

        if ($formationId !== null) {
            $certificationRepository->removeByFormationId($formationId);
            $participationFormationRepository->removeByFormationId($formationId);
        }

        $entityManager->remove($formation);
        $entityManager->flush();

        $this->addFlash('success', 'La formation a ete supprimee avec succes.');

        return $this->redirectToRoute('app_admin_formation_index');
    }


    private function mapFormation(Formation $formation): array
    {
        $description = trim((string) $formation->getDescription());
        $mode = $formation->getMode() ?: 'presentiel';
        $totalPlaces = $formation->getNombrePlaces() ?? 0;
        $remainingPlaces = $formation->getPlacesRestantes() ?? 0;
        $content = $this->extractFormationContent($description);

        return [
            'id' => $formation->getIDFormation(),
            'title' => $formation->getTitre() ?: 'Sans titre',
            'description' => $content['overview'] ?: $description,
            'full_description' => $description,
            'modules' => $content['modules'],
            'excerpt' => mb_strimwidth(($content['overview'] ?: $description) ?: 'Aucune description disponible pour cette formation.', 0, 180, '...'),
            'image' => $this->normalizeImageSource($formation->getImage()),
            'mode' => $mode,
            'mode_label' => $mode === 'en_ligne' ? 'En ligne' : 'Presentiel',
            'mode_icon' => $mode === 'en_ligne' ? 'En ligne' : 'Presentiel',
            'level' => $formation->getNiveau() ?: 'Debutant',
            'level_icon' => 'Niveau',
            'enterprise_id' => $formation->getIDEntreprise(),
            'order_number' => $formation->getNumOrdreCreation(),
            'date_start' => $this->formatStoredDate($formation->getDateDebut()),
            'date_end' => $this->formatStoredDate($formation->getDateFin()),
            'total_places' => $totalPlaces,
            'remaining_places' => $remainingPlaces,
            'is_available' => $remainingPlaces > 0,
            'status_label' => $remainingPlaces > 0 ? 'Inscriptions ouvertes' : 'Complet',
            'occupancy_label' => $totalPlaces > 0 ? sprintf('%d / %d places', $remainingPlaces, $totalPlaces) : 'Capacite non definie',
            'status_icon' => $remainingPlaces > 0 ? 'Disponible' : 'Complet',
        ];
    }


    private function hasDateOverlap(Formation $currentFormation, Formation $existingFormation): bool
    {
        $currentStart = $this->normalizeComparableDate($currentFormation->getDateDebut());
        $currentEnd = $this->normalizeComparableDate($currentFormation->getDateFin());
        $existingStart = $this->normalizeComparableDate($existingFormation->getDateDebut());
        $existingEnd = $this->normalizeComparableDate($existingFormation->getDateFin());

        if ($currentStart === null || $currentEnd === null || $existingStart === null || $existingEnd === null) {
            return false;
        }

        return $currentStart <= $existingEnd && $existingStart <= $currentEnd;
    }

    private function normalizeComparableDate(?int $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $raw = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($raw === '') {
            return null;
        }

        if (strlen($raw) >= 8) {
            return (int) substr($raw, 0, 8);
        }

        if (strlen($raw) === 10 || strlen($raw) === 9) {
            return (int) date('Ymd', (int) $raw);
        }

        return null;
    }

    private function resolveStoredImagePath(Request $request, string $currentValue): string
    {
        $uploadedFile = $request->files->get('image_file');

        if (!$uploadedFile instanceof UploadedFile || !$uploadedFile->isValid()) {
            return trim($currentValue);
        }

        $uploadDirectory = dirname(__DIR__, 2) . '/public/uploads/formations';

        if (!is_dir($uploadDirectory)) {
            mkdir($uploadDirectory, 0777, true);
        }

        $extension = $uploadedFile->guessExtension() ?: $uploadedFile->getClientOriginalExtension() ?: 'bin';
        $filename = 'formation-' . uniqid('', true) . '.' . strtolower($extension);
        $uploadedFile->move($uploadDirectory, $filename);

        return '/uploads/formations/' . $filename;
    }
    private function formatStoredDate(?int $value): string
    {
        if (!$value) {
            return 'Non definie';
        }

        $raw = (string) $value;

        if (strlen($raw) === 8) {
            $date = \DateTimeImmutable::createFromFormat('Ymd', $raw);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('d/m/Y');
            }
        }

        if (strlen($raw) === 14) {
            $date = \DateTimeImmutable::createFromFormat('YmdHis', $raw);
            if ($date instanceof \DateTimeImmutable) {
                return $date->format('d/m/Y H:i');
            }
        }

        if (strlen($raw) === 10) {
            return date('d/m/Y', (int) $raw);
        }

        if (strlen($raw) === 9) {
            return date('d/m/Y', (int) $raw);
        }

        return $raw;
    }

    /**
     * @param array<int, array{participation: ParticipationFormation, formation: array<string, mixed>}> $rows
     *
     * @return array<int, array<string, mixed>>
     */
    private function buildParticipationCalendarEvents(array $rows, ?int $participantId): array
    {
        if ($participantId === null) {
            return [];
        }

        $events = [];

        foreach ($rows as $row) {
            $formation = $row['formation'];
            $start = $this->createDateFromStoredValue($formation['date_start'] ?? null);
            $endInclusive = $this->createDateFromStoredValue($formation['date_end'] ?? null) ?? $start;

            if (!$start instanceof \DateTimeImmutable || !$endInclusive instanceof \DateTimeImmutable) {
                continue;
            }

            $events[] = [
                'title' => (string) ($formation['title'] ?? 'Formation'),
                'start' => $start->format('Y-m-d'),
                'end' => $endInclusive->modify('+1 day')->format('Y-m-d'),
                'allDay' => true,
                'url' => $this->generateUrl('app_formation_show', [
                    'id' => $formation['id'] ?? 0,
                    'employee' => $participantId,
                ]),
                'backgroundColor' => '#dbeafe',
                'borderColor' => '#60a5fa',
                'textColor' => '#0f172a',
                'classNames' => ['participation-calendar-event'],
                'extendedProps' => [
                    'mode' => (string) ($formation['mode_label'] ?? ''),
                    'level' => (string) ($formation['level'] ?? ''),
                    'status' => (string) ($row['participation']->getStatut() ?? 'inscrit'),
                ],
            ];
        }

        return $events;
    }

    private function createDateFromStoredValue(mixed $value): ?\DateTimeImmutable
    {
        $raw = preg_replace('/\D/', '', (string) $value) ?? '';

        if ($raw === '') {
            return null;
        }

        if (strlen($raw) === 8) {
            $date = \DateTimeImmutable::createFromFormat('d/m/Y', (string) $value);

            if ($date instanceof \DateTimeImmutable) {
                return $date->setTime(0, 0, 0);
            }

            $date = \DateTimeImmutable::createFromFormat('Ymd', $raw);

            if ($date instanceof \DateTimeImmutable) {
                return $date->setTime(0, 0, 0);
            }
        }

        if (strlen($raw) >= 14) {
            $date = \DateTimeImmutable::createFromFormat('YmdHis', substr($raw, 0, 14));

            if ($date instanceof \DateTimeImmutable) {
                return $date;
            }
        }

        if (strlen($raw) === 10 || strlen($raw) === 9) {
            return (new \DateTimeImmutable())->setTimestamp((int) $raw)->setTime(0, 0, 0);
        }

        return null;
    }
    /**
     * @return array<string, string>
     */
    private function extractFormData(Request $request, ?FormationRepository $formationRepository = null, ?Formation $formation = null): array
    {
        return [
            'titre' => trim((string) $request->request->get('titre', '')),
            'description' => trim((string) $request->request->get('description', '')),
            'mode' => trim((string) $request->request->get('mode', 'presentiel')),
            'niveau' => trim((string) $request->request->get('niveau', '')),
            'nombre_places' => trim((string) $request->request->get('nombre_places', '')),
            'date_debut' => trim((string) $request->request->get('date_debut', '')),
            'date_fin' => trim((string) $request->request->get('date_fin', '')),
            'id_entreprise' => (string) ($formation?->getIDEntreprise() ?? $formationRepository?->getDefaultEnterpriseId() ?? 1),
            'num_ordre_creation' => (string) ($formation?->getNumOrdreCreation() ?? $formationRepository?->getNextOrderNumber() ?? 1),
            'image' => trim((string) $request->request->get('image', '')),
            'modules' => trim((string) $request->request->get('modules', '')),
        ];
    }
    /**
     * @return array<string, string>
     */
    private function buildFormData(Formation $formation, ?FormationRepository $formationRepository): array
    {
        $content = $this->extractFormationContent((string) ($formation->getDescription() ?? ''));

        return [
            'titre' => (string) ($formation->getTitre() ?? ''),
            'description' => $content['overview'],
            'mode' => (string) ($formation->getMode() ?? 'presentiel'),
            'niveau' => (string) ($formation->getNiveau() ?? 'Debutant'),
            'nombre_places' => $formation->getNombrePlaces() !== null ? (string) $formation->getNombrePlaces() : '',
            'date_debut' => $this->formatDateForInput($formation->getDateDebut()),
            'date_fin' => $this->formatDateForInput($formation->getDateFin()),
            'id_entreprise' => $formation->getIDEntreprise() !== null ? (string) $formation->getIDEntreprise() : (string) ($formationRepository?->getDefaultEnterpriseId() ?? 1),
            'num_ordre_creation' => $formation->getNumOrdreCreation() !== null ? (string) $formation->getNumOrdreCreation() : (string) ($formationRepository?->getNextOrderNumber() ?? 1),
            'image' => (string) ($formation->getImage() ?? ''),
            'modules' => implode(PHP_EOL, $content['modules']),
        ];
    }

    /**
     * @param array<string, string> $data
     *
     * @return array<string, string>
     */
    private function validateFormData(array $data): array
    {
        $errors = [];

        if ($data['titre'] === '') {
            $errors['titre'] = 'Le titre est obligatoire.';
        }

        if ($data['mode'] === '' || !in_array($data['mode'], ['presentiel', 'en_ligne'], true)) {
            $errors['mode'] = 'Le mode doit etre presentiel ou en_ligne.';
        }

        if ($data['description'] === '') {
            $errors['description'] = 'La description est obligatoire.';
        }

        if ($data['niveau'] === '') {
            $errors['niveau'] = 'Le niveau est obligatoire.';
        }

        if (!$this->isPositiveInteger($data['nombre_places'])) {
            $errors['nombre_places'] = 'Le nombre de places doit etre un entier positif.';
        }

        if (!$this->isDateInput($data['date_debut'])) {
            $errors['date_debut'] = 'La date de debut est invalide.';
        }

        if (!$this->isDateInput($data['date_fin'])) {
            $errors['date_fin'] = 'La date de fin est invalide.';
        }

        if (!isset($errors['date_debut'], $errors['date_fin']) && $data['date_fin'] < $data['date_debut']) {
            $errors['date_fin'] = 'La date de fin doit etre posterieure ou egale a la date de debut.';
        }

        if ($data['image'] === '') {
            $errors['image'] = 'L image ou le visuel est obligatoire.';
        }

        return $errors;
    }

    /**
     * @param array<string, string> $data
     */
    private function hydrateFormation(Formation $formation, array $data): void
    {
        $totalPlaces = (int) $data['nombre_places'];
        $currentRemainingPlaces = $formation->getPlacesRestantes();
        $remainingPlaces = $currentRemainingPlaces ?? $totalPlaces;

        if ($remainingPlaces > $totalPlaces) {
            $remainingPlaces = $totalPlaces;
        }

        $formation
            ->setTitre($data['titre'])
            ->setDescription($this->buildStoredDescription($data['description'], $data['modules']))
            ->setMode($data['mode'])
            ->setNiveau($data['niveau'])
            ->setNombrePlaces($totalPlaces)
            ->setPlacesRestantes($remainingPlaces)
            ->setIDEntreprise((int) $data['id_entreprise'])
            ->setNumOrdreCreation((int) $data['num_ordre_creation'])
            ->setDateDebut((int) str_replace('-', '', $data['date_debut']))
            ->setDateFin((int) str_replace('-', '', $data['date_fin']))
            ->setImage($data['image'] !== '' ? $data['image'] : null);
    }
    private function buildStoredDescription(string $overview, string $modules): ?string
    {
        $overview = trim($overview);
        $moduleLines = array_values(array_filter(array_map(
            static fn (string $line): string => trim($line),
            preg_split('/\r\n|\r|\n/', $modules) ?: []
        ), static fn (string $line): bool => $line !== ''));

        if ($overview === '' && $moduleLines === []) {
            return null;
        }

        if ($moduleLines === []) {
            return $overview;
        }

        $payload = implode(PHP_EOL, $moduleLines);

        return $overview !== ''
            ? $overview . PHP_EOL . PHP_EOL . '[MODULES]' . PHP_EOL . $payload
            : '[MODULES]' . PHP_EOL . $payload;
    }

    private function normalizeImageSource(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (preg_match('#^(https?:)?//#i', $value) || str_starts_with($value, 'data:') || str_starts_with($value, 'blob:')) {
            return $value;
        }

        if (str_starts_with($value, '/')) {
            return $value;
        }

        $normalizedValue = str_replace('\\', '/', $value);

        if (preg_match('#^(uploads|images|assets)/#i', ltrim($normalizedValue, '/')) === 1) {
            return '/' . ltrim($normalizedValue, '/');
        }

        $filename = basename($normalizedValue);
        $uploadCandidate = dirname(__DIR__, 2) . '/public/uploads/formations/' . $filename;

        if ($filename !== '' && is_file($uploadCandidate)) {
            return '/uploads/formations/' . rawurlencode($filename);
        }

        if (preg_match('#^[A-Za-z]:[\\/]#', $value) === 1) {
            $publicMarker = '/public/';
            $position = stripos($normalizedValue, $publicMarker);

            if ($position !== false) {
                return '/' . ltrim(substr($normalizedValue, $position + strlen($publicMarker)), '/');
            }

            return null;
        }

        return '/' . ltrim($normalizedValue, '/');
    }
    private function formatDateForInput(?int $value): string
    {
        if (!$value) {
            return '';
        }

        $raw = (string) $value;

        if (strlen($raw) === 8) {
            $date = \DateTimeImmutable::createFromFormat('Ymd', $raw);

            return $date instanceof \DateTimeImmutable ? $date->format('Y-m-d') : '';
        }

        return '';
    }

    private function isPositiveInteger(string $value): bool
    {
        return $value !== '' && ctype_digit($value) && (int) $value > 0;
    }

    private function isNonNegativeInteger(string $value): bool
    {
        return $value !== '' && ctype_digit($value);
    }

    private function isDateInput(string $value): bool
    {
        if ($value === '') {
            return false;
        }

        $date = \DateTimeImmutable::createFromFormat('Y-m-d', $value);

        return $date instanceof \DateTimeImmutable && $date->format('Y-m-d') === $value;
    }

    /**
     * @return array<int, array{id: int, label: string, email: string, first_name: string, last_name: string}>
     */
    private function buildDemoEmployees(UtilisateurRepository $utilisateurRepository): array
    {
        return array_map(function (array $employee): array {
            [$firstName, $lastName] = $this->splitEmployeeName($employee['label']);

            return [
                'id' => $employee['id'],
                'label' => $employee['label'],
                'email' => $employee['email'],
                'first_name' => $firstName,
                'last_name' => $lastName,
            ];
        }, $utilisateurRepository->findDemoEmployees());
    }

    /**
     * @param array<int, array{id: int, label: string, email: string, first_name: string, last_name: string}> $demoEmployees
     */
    private function resolveParticipantId(Request $request, array $demoEmployees): ?int
    {
        $submittedValue = $request->get('employee', $request->request->get('participant_id'));
        $allowedIds = array_column($demoEmployees, 'id');

        if ($submittedValue !== null && $submittedValue !== '') {
            $candidate = (int) $submittedValue;

            if (in_array($candidate, $allowedIds, true)) {
                return $candidate;
            }
        }

        return $allowedIds[0] ?? null;
    }

    /**
     * @param array<int, array{id: int, label: string, email: string, first_name: string, last_name: string}> $demoEmployees
     *
     * @return array{id: int, label: string, email: string, first_name: string, last_name: string}|null
     */
    private function findSelectedEmployee(?int $participantId, array $demoEmployees): ?array
    {
        foreach ($demoEmployees as $employee) {
            if ($employee['id'] === $participantId) {
                return $employee;
            }
        }

        return $demoEmployees[0] ?? null;
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitEmployeeName(string $label): array
    {
        $parts = preg_split('/\s+/', trim($label)) ?: [];
        $firstName = $parts[0] ?? '';
        $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : '';

        return [$firstName, $lastName];
    }

    /**
     * @return array{overview: string, modules: string[]}
     */
    private function extractFormationContent(string $description): array
    {
        if ($description === '') {
            return [
                'overview' => '',
                'modules' => [],
            ];
        }

        $parts = preg_split('/\[MODULES\]/i', $description);
        $overview = trim((string) ($parts[0] ?? ''));
        $modules = [];

        if (isset($parts[1])) {
            $lines = preg_split('/\r\n|\r|\n/', trim($parts[1]));

            foreach ($lines as $line) {
                $line = trim($line);

                if ($line !== '') {
                    $modules[] = $line;
                }
            }
        }

        return [
            'overview' => $overview,
            'modules' => $modules,
        ];
    }

    private function storeCertificateFile(string $filename, string $pdfContent): string
    {
        $directory = dirname(__DIR__, 2) . '/public/uploads/certificates';

        if (!is_dir($directory)) {
            mkdir($directory, 0777, true);
        }

        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '-', $filename) ?: ('certificat-' . uniqid() . '.pdf');
        file_put_contents($directory . '/' . $safeFilename, $pdfContent);

        return '/uploads/certificates/' . $safeFilename;
    }

    private function buildPdfResponse(string $pdfContent, string $filename): Response
    {
        $response = new Response($pdfContent);
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', sprintf('inline; filename="%s"', $filename));

        return $response;
    }
}


















