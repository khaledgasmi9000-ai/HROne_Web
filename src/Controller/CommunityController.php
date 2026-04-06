<?php

namespace App\Controller;

use App\Service\Community\CommunityStore;
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
    public function dashboard(Request $request, CommunityStore $store): Response
    {
        $uid = $this->readSessionCommunityUserId($request);
        $displayName = $this->resolveProfileDisplayName($uid, $store);

        return $this->render('community/dashboard.html.twig', [
            'initial_user_id' => $uid,
            'profile_user_id' => $uid,
            'profile_display_name' => $displayName,
            'profile_avatar_url' => $this->buildAvatarDataUri($displayName, $uid),
        ]);
    }

    #[Route('/communaute/tableau-de-bord/rapport', name: 'app_communaute_dashboard_pdf', methods: ['GET'])]
    public function exportDashboardPdf(
        Request $request,
        CommunityMetrics $metrics,
        CommunityStore $store,
        Environment $twig,
    ): Response {
        $uid = $this->readSessionCommunityUserId($request);
        if ($uid === null) {
            return $this->redirectToRoute('app_communaute_dashboard');
        }

        $myPosts = [];
        foreach ($store->findUserPostsFiltered($uid, null, null) as $post) {
            $myPosts[] = [
                'id' => $post['id'],
                'title' => $post['title'],
                'tag' => $post['tag'],
                'active' => $post['is_active'],
                'created' => $this->formatSqlDate($post['created_at']),
            ];
        }

        $myComments = [];
        foreach ($store->findUserCommentsFiltered($uid, null) as $comment) {
            $excerpt = (string) $comment['content'];
            if (mb_strlen($excerpt) > 120) {
                $excerpt = mb_substr($excerpt, 0, 117).'...';
            }

            $myComments[] = [
                'id' => $comment['id'],
                'post_id' => $comment['post_id'],
                'excerpt' => $excerpt,
                'created' => $this->formatSqlDate($comment['created_at']),
            ];
        }

        $html = $twig->render('community/dashboard_pdf.html.twig', [
            'generated_at' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            'user_label' => $this->resolveProfileDisplayName($uid, $store).' (#'.$uid.')',
            'global' => $metrics->buildGlobalStats(),
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

        $value = $request->getSession()->get(self::SESSION_USER_KEY);
        if (\is_int($value) && $value > 0) {
            return $value;
        }
        if (\is_string($value) && ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function resolveProfileDisplayName(?int $uid, CommunityStore $store): string
    {
        if ($uid === null || $uid < 1) {
            return '';
        }

        $names = $store->getDisplayNamesByIds([$uid]);
        $name = trim((string) ($names[$uid] ?? ''));
        if ($name !== '') {
            return $name;
        }

        return (string) $this->getParameter('app.community_profile_fallback_name');
    }

    private function buildAvatarDataUri(string $name, ?int $seed = null): string
    {
        $palette = [
            ['#1d4ed8', '#60a5fa'],
            ['#0f766e', '#2dd4bf'],
            ['#7c3aed', '#a78bfa'],
            ['#be123c', '#fb7185'],
            ['#b45309', '#f59e0b'],
            ['#0f172a', '#475569'],
        ];
        $index = abs(($seed ?? crc32($name)) % \count($palette));
        [$start, $end] = $palette[$index];
        $safeInitials = htmlspecialchars($this->buildInitials($name), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="$start"/>
      <stop offset="100%" stop-color="$end"/>
    </linearGradient>
  </defs>
  <rect width="160" height="160" rx="80" fill="url(#g)"/>
  <text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle" font-family="Segoe UI, Arial, sans-serif" font-size="56" font-weight="700" fill="#ffffff">$safeInitials</text>
</svg>
SVG;

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
    }

    private function buildInitials(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '?';
        }

        $parts = preg_split('/\s+/u', $name) ?: [];
        $letters = [];
        foreach ($parts as $part) {
            $part = trim((string) $part);
            if ($part === '') {
                continue;
            }
            $letters[] = mb_strtoupper(mb_substr($part, 0, 1));
            if (\count($letters) >= 2) {
                break;
            }
        }

        return $letters !== [] ? implode('', $letters) : mb_strtoupper(mb_substr($name, 0, 1));
    }

    private function formatSqlDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format('Y-m-d H:i');
        } catch (\Exception) {
            return $value;
        }
    }
}
