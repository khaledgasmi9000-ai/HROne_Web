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
}
