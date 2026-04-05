<?php

namespace App\Controller;

use App\Entity\Formation;
use App\Entity\ParticipationFormation;
use App\Repository\FormationRepository;
use App\Repository\ParticipationFormationRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
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
        $keyword = $request->query->get('q');
        $formations = $formationRepository->searchForCatalog($mode, $keyword);
        $featured = $formationRepository->findFeaturedFormation();

        return $this->render('formation/index.html.twig', [
            'formations' => array_map([$this, 'mapFormation'], $formations),
            'featured' => $featured ? $this->mapFormation($featured) : null,
            'filters' => [
                'mode' => $mode,
                'q' => $keyword,
            ],
            'stats' => [
                'total' => count($formations),
                'available' => count(array_filter($formations, static fn (Formation $formation) => ($formation->getPlacesRestantes() ?? 0) > 0)),
                'online' => count(array_filter($formations, static fn (Formation $formation) => $formation->getMode() === 'en_ligne')),
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

        if ($participationFormationRepository->hasActiveParticipation($id, $participantId)) {
            $this->addFlash('error', 'Cet employe est deja inscrit a cette formation.');

            return $this->redirectToRoute('app_formation_show', ['id' => $id, 'employee' => $participantId]);
        }

        $participation = (new ParticipationFormation())
            ->setID_Formation($id)
            ->setID_Participant($participantId)
            ->setNum_Ordre_Participation($participationFormationRepository->getNextOrderNumber())
            ->setStatut('inscrit');

        $formation->setPlacesRestantes(max(0, ($formation->getPlacesRestantes() ?? 0) - 1));

        $entityManager->persist($participation);
        $entityManager->flush();

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
        $demoEmployees = $this->buildDemoEmployees($utilisateurRepository);
        $selectedParticipantId = $this->resolveParticipantId($request, $demoEmployees);
        $participations = $selectedParticipantId !== null
            ? $participationFormationRepository->findByParticipantOrdered($selectedParticipantId)
            : [];

        $rows = [];

        foreach ($participations as $participation) {
            $formation = $formationRepository->find($participation->getIDFormation());

            if ($formation instanceof Formation) {
                $rows[] = [
                    'participation' => $participation,
                    'formation' => $this->mapFormation($formation),
                ];
            }
        }

        return $this->render('formation/participations.html.twig', [
            'demo_employees' => $demoEmployees,
            'selected_participant_id' => $selectedParticipantId,
            'participations' => $rows,
        ]);
    }

    #[Route('/rh/formations', name: 'app_admin_formation_index', methods: ['GET'])]
    public function adminIndex(Request $request, FormationRepository $formationRepository): Response
    {
        $mode = $request->query->get('mode');
        $keyword = $request->query->get('q');
        $formations = $formationRepository->searchForCatalog($mode, $keyword);

        return $this->render('admin/formation/index.html.twig', [
            'formations' => array_map([$this, 'mapFormation'], $formations),
            'filters' => [
                'mode' => $mode,
                'q' => $keyword,
            ],
            'stats' => [
                'total' => count($formations),
                'available' => count(array_filter($formations, static fn (Formation $formation) => ($formation->getPlacesRestantes() ?? 0) > 0)),
                'full' => count(array_filter($formations, static fn (Formation $formation) => ($formation->getPlacesRestantes() ?? 0) <= 0)),
                'online' => count(array_filter($formations, static fn (Formation $formation) => $formation->getMode() === 'en_ligne')),
            ],
        ]);
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
    public function adminDelete(Formation $formation, EntityManagerInterface $entityManager): RedirectResponse
    {
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
            'image' => $formation->getImage(),
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
        ];
    }

    /**
     * @return array<string, string>
     */
    private function buildFormData(Formation $formation, ?FormationRepository $formationRepository): array
    {
        return [
            'titre' => (string) ($formation->getTitre() ?? ''),
            'description' => (string) ($formation->getDescription() ?? ''),
            'mode' => (string) ($formation->getMode() ?? 'presentiel'),
            'niveau' => (string) ($formation->getNiveau() ?? 'Debutant'),
            'nombre_places' => $formation->getNombrePlaces() !== null ? (string) $formation->getNombrePlaces() : '',
            'date_debut' => $this->formatDateForInput($formation->getDateDebut()),
            'date_fin' => $this->formatDateForInput($formation->getDateFin()),
            'id_entreprise' => $formation->getIDEntreprise() !== null ? (string) $formation->getIDEntreprise() : (string) ($formationRepository?->getDefaultEnterpriseId() ?? 1),
            'num_ordre_creation' => $formation->getNumOrdreCreation() !== null ? (string) $formation->getNumOrdreCreation() : (string) ($formationRepository?->getNextOrderNumber() ?? 1),
            'image' => (string) ($formation->getImage() ?? ''),
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
            ->setDescription($data['description'] !== '' ? $data['description'] : null)
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
}
