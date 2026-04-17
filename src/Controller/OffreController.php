<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class OffreController extends AbstractController
{
    #[Route('/offres', name: 'app_offres_index', methods: ['GET'])]
    public function index(Connection $connection): Response
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        if ($this->isGranted('ROLE_RH') || $this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Acces reserve aux candidats et employes.');
        }

        if (!$this->isGranted('ROLE_CANDIDAT') && !$this->isGranted('ROLE_EMPLOYEE')) {
            throw $this->createAccessDeniedException('Acces reserve aux candidats et employes.');
        }

        $offers = $this->loadOffers($connection);

        return $this->render('offre/index.html.twig', [
            'offers' => $offers,
            'totalOffers' => count($offers),
        ]);
    }

    /**
     * @return array<int, array{
     *   id:int,
     *   titre:string,
     *   description:string,
     *   entreprise:string,
     *   contrat:string,
     *   work_type:string,
     *   salaire:string,
     *   experience:string
     * }>
     */
    private function loadOffers(Connection $connection): array
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
                 ORDER BY COALESCE(o.num_ordre_creation, 0) DESC, o.id_offre DESC"
            );
        } catch (\Throwable) {
            return [];
        }

        return array_map(function (array $row): array {
            $minSalary = isset($row['min_salaire']) ? (int) $row['min_salaire'] : null;
            $maxSalary = isset($row['max_salaire']) ? (int) $row['max_salaire'] : null;
            $salaryLabel = ($minSalary !== null && $maxSalary !== null && $maxSalary > 0)
                ? sprintf('%d - %d', $minSalary, $maxSalary)
                : 'Non precise';

            $years = isset($row['nbr_annee_experience']) ? (int) $row['nbr_annee_experience'] : null;
            $experienceLabel = $years !== null && $years > 0 ? sprintf('%d an(s)', $years) : 'Debutant accepte';

            return [
                'id' => (int) ($row['id_offre'] ?? 0),
                'titre' => (string) ($row['titre'] ?? 'Offre'),
                'description' => trim((string) ($row['description'] ?? 'Description indisponible')),
                'entreprise' => (string) ($row['entreprise'] ?? 'Entreprise'),
                'contrat' => (string) ($row['contrat'] ?? 'Contrat'),
                'work_type' => (string) ($row['work_type'] ?? 'Standard'),
                'salaire' => $salaryLabel,
                'experience' => $experienceLabel,
            ];
        }, $rows);
    }
}
