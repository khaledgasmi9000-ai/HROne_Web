<?php

namespace App\Controller;

use App\Repository\CommentRepository;
use App\Repository\PostRepository;
use App\Repository\UtilisateurRepository;
use App\Service\CommunityMetrics;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class CommunityController extends AbstractController
{
    /** @see CommunityApiController::SESSION_USER_KEY */
    private const SESSION_USER_KEY = 'community_uid';

    #[Route('/communaute', name: 'app_communaute', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('community/index.html.twig', [
            'initial_user_id' => $this->readSessionCommunityUserId($request),
        ]);
    }

    #[Route('/communaute/tableau-de-bord', name: 'app_communaute_dashboard', methods: ['GET'])]
    public function dashboard(Request $request, UtilisateurRepository $utilisateurs): Response
    {
        $uid = $this->readSessionCommunityUserId($request);

        return $this->render('community/dashboard.html.twig', [
            'initial_user_id' => $uid,
            'profile_user_id' => $uid,
            'profile_display_name' => $this->resolveProfileDisplayName($uid, $utilisateurs),
        ]);
    }

    /**
     * Sans suffixe .pdf : sinon Apache/nginx tente un fichier statique et renvoie 404 avant index.php.
     */
    #[Route('/communaute/tableau-de-bord/rapport', name: 'app_communaute_dashboard_pdf', methods: ['GET'])]
    public function exportDashboardPdf(
        Request $request,
        CommunityMetrics $metrics,
        PostRepository $posts,
        CommentRepository $comments,
        Environment $twig,
        UtilisateurRepository $utilisateurs,
    ): Response {
        $uid = $this->readSessionCommunityUserId($request);
        if ($uid === null) {
            return $this->redirectToRoute('app_communaute_dashboard');
        }

        $global = $metrics->buildGlobalStats();
        $postEntities = $posts->findByUserIdOrdered($uid);
        $myPosts = [];
        foreach ($postEntities as $p) {
            $myPosts[] = [
                'id' => $p->getId(),
                'title' => $p->getTitle(),
                'tag' => $p->getTag(),
                'active' => $p->isActive(),
                'created' => $p->getCreatedAt()?->format('Y-m-d H:i'),
            ];
        }
        $commentEntities = $comments->findByUserIdOrdered($uid);
        $myComments = [];
        foreach ($commentEntities as $c) {
            $txt = (string) $c->getContent();
            if (mb_strlen($txt) > 120) {
                $txt = mb_substr($txt, 0, 117).'…';
            }
            $myComments[] = [
                'id' => $c->getId(),
                'post_id' => $c->getPostId(),
                'excerpt' => $txt,
                'created' => $c->getCreatedAt()?->format('Y-m-d H:i'),
            ];
        }

        $html = $twig->render('community/dashboard_pdf.html.twig', [
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'user_label' => $this->resolveProfileDisplayName($uid, $utilisateurs).' (#'.$uid.')',
            'global' => $global,
            'my_posts' => $myPosts,
            'my_comments' => $myComments,
        ]);

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return new Response((string) $dompdf->output(), Response::HTTP_OK, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="hrone-communaute-rapport.pdf"',
        ]);
    }

    private function readSessionCommunityUserId(Request $request): ?int
    {
        if (!$request->hasSession()) {
            return null;
        }
        $v = $request->getSession()->get(self::SESSION_USER_KEY);
        if (\is_int($v) && $v > 0) {
            return $v;
        }
        if (\is_string($v) && ctype_digit($v)) {
            $n = (int) $v;

            return $n > 0 ? $n : null;
        }

        return null;
    }

    private function resolveProfileDisplayName(?int $uid, UtilisateurRepository $utilisateurs): string
    {
        if ($uid === null || $uid < 1) {
            return '';
        }
        $names = $utilisateurs->getDisplayNamesByIds([$uid]);
        $n = trim((string) ($names[$uid] ?? ''));
        if ($n !== '') {
            return $n;
        }

        return (string) $this->getParameter('app.community_profile_fallback_name');
    }
}
