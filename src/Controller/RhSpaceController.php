<?php

namespace App\Controller;

use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/rh')]
class RhSpaceController extends AbstractController
{
    #[Route('', name: 'app_rh_home')]
    public function dashboard(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        return $this->redirectToRoute('app_rh_user_index');
    }

    #[Route('/historique-actions', name: 'app_rh_history')]
    public function history(Request $request, Connection $connection): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $search = trim((string) $request->query->get('q', ''));

        $sql = <<<SQL
SELECT
    au.ID_UTILISATEUR,
    u.Nom_Utilisateur,
    u.Email,
    ta.Description_Action,
    au.Commentaire,
    o.Num_Ordre,
    o.AAAA,
    o.MM,
    o.JJ,
    o.HH,
    o.MN,
    o.SS
FROM action_utilisateur au
LEFT JOIN utilisateur u ON u.ID_UTILISATEUR = au.ID_UTILISATEUR
LEFT JOIN type_action ta ON ta.Code_Type_Action = au.Code_Type_Action
LEFT JOIN ordre o ON o.Num_Ordre = au.Num_Ordre
WHERE (:term = '' OR LOWER(COALESCE(u.Nom_Utilisateur, '')) LIKE :likeTerm OR LOWER(COALESCE(u.Email, '')) LIKE :likeTerm OR LOWER(COALESCE(ta.Description_Action, '')) LIKE :likeTerm OR LOWER(COALESCE(au.Commentaire, '')) LIKE :likeTerm)
ORDER BY o.AAAA DESC, o.MM DESC, o.JJ DESC, o.HH DESC, o.MN DESC, o.SS DESC, au.Num_Ordre DESC
LIMIT 100
SQL;

        $rows = $connection->fetchAllAssociative($sql, [
            'term' => mb_strtolower($search),
            'likeTerm' => '%' . mb_strtolower($search) . '%',
        ]);

        return $this->render('rh/history.html.twig', [
            'rows' => $rows,
            'search' => $search,
        ]);
    }

    #[Route('/{slug}', name: 'app_rh_module_placeholder', requirements: ['slug' => 'activity-watch|gestion-conges|gestion-administrative|gestion-outils|gestion-entretiens|gestion-offres|gestion-evenements|gestion-formations'])]
    public function placeholder(string $slug): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

        $titles = [
            'activity-watch' => 'Activity Watch',
            'gestion-conges' => 'Gestion des conges',
            'gestion-administrative' => 'Gestion administrative',
            'gestion-outils' => 'Gestion des outils',
            'gestion-entretiens' => 'Gestion des entretiens',
            'gestion-offres' => "Gestion des offres d'emploi",
            'gestion-evenements' => 'Gestion des evenements',
            'gestion-formations' => 'Gestion des formations',
        ];

        return $this->render('rh/placeholder.html.twig', [
            'title' => $titles[$slug] ?? $slug,
        ]);
    }
}
