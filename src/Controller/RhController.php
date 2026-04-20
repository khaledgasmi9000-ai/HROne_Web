<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RhController extends AbstractController
{
    #[Route('/rh/historique-actions', name: 'app_rh_history', methods: ['GET'])]
    public function history(Request $request, Connection $connection): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $search = mb_strtolower(trim((string) $request->query->get('q', '')));
        $rows = $this->loadHistory($connection, $search);

        return $this->render('rh/history.html.twig', [
            'rows' => $rows,
            'search' => $request->query->get('q', ''),
        ]);
    }

    #[Route('/rh/offres', name: 'app_rh_offres', methods: ['GET'])]
    public function offers(Request $request, Connection $connection): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $search = mb_strtolower(trim((string) $request->query->get('q', '')));
        $rows = $this->loadOffers($connection, $search);

        return $this->render('rh/offers.html.twig', [
            'rows' => $rows,
            'search' => $request->query->get('q', ''),
        ]);
    }

    #[Route('/rh/entretiens', name: 'app_rh_entretiens', methods: ['GET'])]
    public function interviews(Request $request, Connection $connection): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $search = mb_strtolower(trim((string) $request->query->get('q', '')));
        $rows = $this->loadInterviews($connection, $search);

        return $this->render('rh/interviews.html.twig', [
            'rows' => $rows,
            'search' => $request->query->get('q', ''),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadHistory(Connection $connection, string $search): array
    {
        try {
            $rows = $connection->fetchAllAssociative(
                "SELECT
                    au.id_utilisateur,
                    u.Nom_Utilisateur,
                    u.Email,
                    ta.description_action AS Description_Action,
                    au.num_ordre AS Num_Ordre,
                    o.AAAA,
                    o.MM,
                    o.JJ,
                    o.HH,
                    o.MN,
                    o.SS
                 FROM action_utilisateur au
                 LEFT JOIN utilisateur u ON u.ID_UTILISATEUR = au.id_utilisateur
                 LEFT JOIN type_action ta ON ta.code_type_action = au.code_type_action
                 LEFT JOIN ordre o ON o.Num_Ordre = au.num_ordre
                 ORDER BY o.AAAA DESC, o.MM DESC, o.JJ DESC, o.HH DESC, o.MN DESC, o.SS DESC, au.num_ordre DESC
                 LIMIT 200"
            );
        } catch (\Throwable) {
            return [];
        }

        if ($search === '') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($search): bool {
            $haystack = mb_strtolower(implode(' ', [
                (string) ($row['Nom_Utilisateur'] ?? ''),
                (string) ($row['Email'] ?? ''),
                (string) ($row['Description_Action'] ?? ''),
                (string) ($row['Num_Ordre'] ?? ''),
            ]));

            return str_contains($haystack, $search);
        }));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadOffers(Connection $connection, string $search): array
    {
        try {
            $rows = $connection->fetchAllAssociative(
                "SELECT
                    o.id_offre,
                    o.titre,
                    o.description,
                    o.work_type,
                    o.min_salaire,
                    o.max_salaire,
                    o.nbr_annee_experience,
                    COALESCE(e.Nom_Entreprise, 'Entreprise') AS entreprise,
                    COALESCE(tc.description_contrat, 'Contrat') AS contrat
                 FROM offre o
                 LEFT JOIN entreprise e ON e.ID_Entreprise = o.id_entreprise
                 LEFT JOIN type_contrat tc ON tc.code_type_contrat = o.code_type_contrat
                 ORDER BY COALESCE(o.num_ordre_creation, 0) DESC, o.id_offre DESC
                 LIMIT 200"
            );
        } catch (\Throwable) {
            return [];
        }

        if ($search === '') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($search): bool {
            $haystack = mb_strtolower(implode(' ', [
                (string) ($row['titre'] ?? ''),
                (string) ($row['description'] ?? ''),
                (string) ($row['entreprise'] ?? ''),
                (string) ($row['contrat'] ?? ''),
                (string) ($row['work_type'] ?? ''),
            ]));

            return str_contains($haystack, $search);
        }));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadInterviews(Connection $connection, string $search): array
    {
        try {
            $rows = $connection->fetchAllAssociative(
                "SELECT
                    e.id_condidat,
                    e.id_rh,
                    e.num_ordre_entretien,
                    e.localisation,
                    e.status_entretien,
                    e.evaluation,
                    COALESCE(uc.Nom_Utilisateur, 'Candidat inconnu') AS candidat_nom,
                    COALESCE(ur.Nom_Utilisateur, 'RH inconnu') AS rh_nom
                 FROM entretien e
                 LEFT JOIN condidat c ON c.id_condidat = e.id_condidat
                 LEFT JOIN utilisateur uc ON uc.ID_UTILISATEUR = c.id_utilisateur
                 LEFT JOIN utilisateur ur ON ur.ID_UTILISATEUR = e.id_rh
                 ORDER BY e.num_ordre_entretien DESC
                 LIMIT 200"
            );
        } catch (\Throwable) {
            return [];
        }

        if ($search === '') {
            return $rows;
        }

        return array_values(array_filter($rows, static function (array $row) use ($search): bool {
            $haystack = mb_strtolower(implode(' ', [
                (string) ($row['candidat_nom'] ?? ''),
                (string) ($row['rh_nom'] ?? ''),
                (string) ($row['localisation'] ?? ''),
                (string) ($row['status_entretien'] ?? ''),
                (string) ($row['num_ordre_entretien'] ?? ''),
            ]));

            return str_contains($haystack, $search);
        }));
    }
}
