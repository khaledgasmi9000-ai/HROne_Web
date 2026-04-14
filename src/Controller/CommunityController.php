<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Post;
use App\Entity\PostVote;
use App\Entity\Utilisateur;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\PostRepository;
use App\Repository\PostVoteRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CommunityController extends AbstractController
{
    #[Route('/communaute', name: 'community_index', methods: ['GET'])]
    public function search(
        PostRepository $posts,
        CommentRepository $comments,
        SessionInterface $session,
        Request $request,
        PaginatorInterface $paginator,
        Connection $conn
    ): Response {
        $tagFilter = trim((string)$request->query->get('tag'));
        $search = trim((string)$request->query->get('q'));
        $commentSort = (string)$request->query->get('comment_sort', 'asc');
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = min(30, max(3, (int)$request->query->get('per_page', 5)));
        $botQuestion = trim((string)$request->query->get('bot_q'));

        $allPosts = $posts->findBy([], ['created_at' => 'DESC']);
        $filteredPosts = array_values(array_filter($allPosts, function (Post $post) use ($tagFilter, $search): bool {
            if (!$post->isActive()) {
                return false;
            }
            if ($tagFilter !== '' && stripos((string)$post->getTag(), $tagFilter) === false) {
                return false;
            }
            if ($search !== '' && stripos((string)$post->getTitle(), $search) === false) {
                return false;
            }

            return true;
        }));

        $pagination = $paginator->paginate($filteredPosts, $page, $perPage);
        /** @var Post[] $postList */
        $postList = $pagination->getItems();
        $totalPosts = $pagination->getTotalItemCount();
        $totalPages = $pagination->getPageCount();

        $pagePostIds = array_values(array_map(static fn(Post $post): int => (int)$post->getId(), $postList));
        $filteredPostIds = array_values(array_map(static fn(Post $post): int => (int)$post->getId(), $filteredPosts));

        $commentsByPost = [];
        foreach ($pagePostIds as $postId) {
            $commentsByPost[$postId] = [];
        }

        $allPageComments = [];
        if (!empty($pagePostIds)) {
            $allPageComments = $comments->findBy(['post_id' => $pagePostIds], ['created_at' => 'ASC']);
            foreach ($allPageComments as $comment) {
                $commentsByPost[(int)$comment->getPostId()][] = $comment;
            }
        }

        $commentIds = array_values(array_map(static fn(Comment $comment): int => (int)$comment->getId(), $allPageComments));
        $voteCounts = [];
        foreach ($commentIds as $commentId) {
            $voteCounts[$commentId] = ['up' => 0, 'down' => 0];
        }
        if (!empty($commentIds)) {
            $rows = $conn->executeQuery(
                'SELECT comment_id, vote_type, COUNT(*) AS cnt FROM comment_votes WHERE comment_id IN (?) GROUP BY comment_id, vote_type',
                [$commentIds],
                [ArrayParameterType::INTEGER]
            )->fetchAllAssociative();
            foreach ($rows as $row) {
                $cid = (int)$row['comment_id'];
                $type = (string)$row['vote_type'];
                $count = (int)$row['cnt'];
                if (!isset($voteCounts[$cid])) {
                    $voteCounts[$cid] = ['up' => 0, 'down' => 0];
                }
                if ($type === 'up' || $type === 'down') {
                    $voteCounts[$cid][$type] = $count;
                }
            }
        }

        if ($commentSort === 'recent') {
            foreach ($commentsByPost as &$list) {
                usort($list, static fn(Comment $a, Comment $b): int => $b->getCreatedAt() <=> $a->getCreatedAt());
            }
            unset($list);
        } elseif ($commentSort === 'flagged') {
            foreach ($commentsByPost as &$list) {
                usort($list, static function (Comment $a, Comment $b) use ($voteCounts): int {
                    $downA = (int)($voteCounts[(int)$a->getId()]['down'] ?? 0);
                    $downB = (int)($voteCounts[(int)$b->getId()]['down'] ?? 0);
                    if ($downB === $downA) {
                        return $b->getCreatedAt() <=> $a->getCreatedAt();
                    }

                    return $downB <=> $downA;
                });
            }
            unset($list);
        }

        $commentCounts = [];
        foreach ($filteredPostIds as $postId) {
            $commentCounts[$postId] = 0;
        }
        if (!empty($filteredPostIds)) {
            $commentRows = $conn->executeQuery(
                'SELECT post_id, COUNT(*) AS cnt FROM comments WHERE post_id IN (?) GROUP BY post_id',
                [$filteredPostIds],
                [ArrayParameterType::INTEGER]
            )->fetchAllAssociative();
            foreach ($commentRows as $row) {
                $commentCounts[(int)$row['post_id']] = (int)$row['cnt'];
            }
        }

        $allPostVoteCounts = [];
        foreach ($filteredPostIds as $postId) {
            $allPostVoteCounts[$postId] = ['up' => 0, 'down' => 0];
        }
        if (!empty($filteredPostIds)) {
            $postVoteRows = $conn->executeQuery(
                'SELECT post_id, vote_type, COUNT(*) AS cnt FROM post_votes WHERE post_id IN (?) GROUP BY post_id, vote_type',
                [$filteredPostIds],
                [ArrayParameterType::INTEGER]
            )->fetchAllAssociative();
            foreach ($postVoteRows as $row) {
                $pid = (int)$row['post_id'];
                $type = (string)$row['vote_type'];
                $count = (int)$row['cnt'];
                if (!isset($allPostVoteCounts[$pid])) {
                    $allPostVoteCounts[$pid] = ['up' => 0, 'down' => 0];
                }
                if ($type === 'up' || $type === 'down') {
                    $allPostVoteCounts[$pid][$type] = $count;
                }
            }
        }

        $postVoteCounts = [];
        foreach ($pagePostIds as $postId) {
            $postVoteCounts[$postId] = $allPostVoteCounts[$postId] ?? ['up' => 0, 'down' => 0];
        }

        $tags = array_values(array_unique(array_filter(array_map(
            static fn(Post $post): ?string => $post->getTag(),
            $allPosts
        ))));

        $totalLikes = array_sum(array_map(static fn(array $counts): int => (int)$counts['up'], $allPostVoteCounts));
        $totalDislikes = array_sum(array_map(static fn(array $counts): int => (int)$counts['down'], $allPostVoteCounts));

        [$viewsCounts, $shareCounts, $saveCounts, $avgReadSeconds, $engagementRates] =
            $this->computeEngagementMetrics($filteredPosts, $commentCounts, $allPostVoteCounts);

        $weather = $this->getCachedWeather($session);
        $botAnswer = $botQuestion !== '' ? $this->askChatbot($botQuestion) : null;

        return $this->render('community/index.html.twig', [
            'posts' => $postList,
            'commentsByPost' => $commentsByPost,
            'voteCounts' => $voteCounts,
            'postVoteCounts' => $postVoteCounts,
            'commentCounts' => $commentCounts,
            'pinned' => $session->get('pinned_comments', []),
            'favorites' => $session->get('favorite_posts', []),
            'tags' => $tags,
            'currentTag' => $tagFilter,
            'currentSearch' => $search,
            'commentSort' => $commentSort,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'totalLikes' => $totalLikes,
            'totalDislikes' => $totalDislikes,
            'viewsCounts' => $viewsCounts,
            'shareCounts' => $shareCounts,
            'saveCounts' => $saveCounts,
            'avgReadSeconds' => $avgReadSeconds,
            'engagementRates' => $engagementRates,
            'pagination' => $pagination,
            'weather' => $weather,
            'botQuestion' => $botQuestion,
            'botAnswer' => $botAnswer,
            'chatMessages' => $session->get('community_chat_messages', []),
        ]);
    }

    #[Route('/communaute/chat/send', name: 'community_chat_send', methods: ['POST'])]
    public function sendLocalChat(Request $request, SessionInterface $session): RedirectResponse
    {
        $message = trim((string)$request->request->get('message'));
        if ($message === '') {
            $this->addFlash('error', 'Message vide.');
            return $this->redirectToRoute('community_index');
        }

        $messages = $session->get('community_chat_messages', []);
        $messages[] = [
            'user' => 'Ons',
            'text' => $message,
            'at' => (new \DateTime())->format('H:i'),
        ];

        if (count($messages) > 100) {
            $messages = array_slice($messages, -100);
        }

        $session->set('community_chat_messages', $messages);
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/chat/clear', name: 'community_chat_clear', methods: ['POST'])]
    public function clearLocalChat(SessionInterface $session): RedirectResponse
    {
        $session->set('community_chat_messages', []);
        $this->addFlash('success', 'Chat interne vide.');

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post', name: 'community_post_create', methods: ['POST'])]
    public function createPost(Request $request, UtilisateurRepository $users, EntityManagerInterface $em): RedirectResponse
    {
        $userId = (int)$request->request->get('user_id', 1);
        $user = $users->find($userId);
        if (!$user) {
            $user = new Utilisateur();
            $user->setNomUtilisateur('auto_user');
            $user->setMotPasse('temp');
            $em->persist($user);
            $em->flush();
            $userId = (int)$user->getIDUTILISATEUR();
        }

        [$data, $error] = Post::hydrateAndValidate($request);
        if ($error) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('community_index');
        }

        $post = new Post();
        $post->setTitle($data['title']);
        $post->setDescription($data['description']);
        $post->setUserId($userId);
        $post->setIsActive(false);
        $post->setCreatedAt(new \DateTime());
        $post->setTag($data['tag']);

        $uploadedImage = $request->files->get('image_file');
        if ($uploadedImage instanceof UploadedFile) {
            try {
                $post->setImageUrl($this->storeUploadedImage($uploadedImage));
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Upload image impossible: ' . $e->getMessage());
                return $this->redirectToRoute('community_index');
            }
        } elseif ($data['image_url'] !== '') {
            $post->setImageUrl($data['image_url']);
        }

        $em->persist($post);
        $em->flush();

        $this->addFlash('success', 'Post soumis. En attente de validation admin.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post/{id}/update', name: 'community_post_update', methods: ['POST'])]
    public function updatePost(int $id, Request $request, PostRepository $posts, EntityManagerInterface $em): RedirectResponse
    {
        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('community_index');
        }

        [$data, $error] = Post::hydrateAndValidate($request);
        if ($error) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute('community_index');
        }

        $post->setTitle($data['title']);
        $post->setDescription($data['description']);
        $post->setTag($data['tag']);

        $uploadedImage = $request->files->get('image_file');
        if ($uploadedImage instanceof UploadedFile) {
            try {
                $post->setImageUrl($this->storeUploadedImage($uploadedImage));
            } catch (\Throwable $e) {
                $this->addFlash('error', 'Upload image impossible: ' . $e->getMessage());
                return $this->redirectToRoute('community_index');
            }
        } elseif ($data['image_url'] !== '') {
            $post->setImageUrl($data['image_url']);
        }

        $em->flush();
        $this->addFlash('success', 'Post mis a jour.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post/{id}/delete', name: 'community_post_delete', methods: ['POST'])]
    public function deletePost(
        int $id,
        PostRepository $posts,
        EntityManagerInterface $em,
        Connection $conn,
        Request $request
    ): RedirectResponse {
        $post = $posts->find($id);
        if ($post) {
            $conn->executeStatement('UPDATE comments SET parent_comment_id = NULL WHERE post_id = :pid', ['pid' => $id]);
            $conn->executeStatement('DELETE FROM post_votes WHERE post_id = :pid', ['pid' => $id]);
            $conn->executeStatement('DELETE FROM comment_votes WHERE comment_id IN (SELECT id FROM comments WHERE post_id = :pid)', ['pid' => $id]);
            $conn->executeStatement('DELETE FROM comments WHERE post_id = :pid', ['pid' => $id]);
            $em->remove($post);
            $em->flush();
            $this->addFlash('success', 'Post supprime.');
        } else {
            $this->addFlash('error', 'Post introuvable.');
        }

        $redirect = (string)$request->request->get('redirect_route');
        if ($redirect !== '' && $this->routeExists($redirect)) {
            return $this->redirectToRoute($redirect);
        }

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post/{id}/favorite', name: 'community_post_fav', methods: ['POST'])]
    public function favoritePost(int $id, SessionInterface $session): RedirectResponse
    {
        $favorites = $session->get('favorite_posts', []);
        if (!in_array($id, $favorites, true)) {
            $favorites[] = $id;
            $this->addFlash('success', 'Post ajoute aux favoris.');
        } else {
            $favorites = array_values(array_diff($favorites, [$id]));
            $this->addFlash('success', 'Post retire des favoris.');
        }
        $session->set('favorite_posts', $favorites);

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post/{id}/vote/{type}', name: 'community_post_vote', methods: ['POST'])]
    public function votePost(
        int $id,
        string $type,
        PostRepository $posts,
        PostVoteRepository $postVotes,
        EntityManagerInterface $em,
        SessionInterface $session
    ): RedirectResponse {
        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $voteType = $type === 'down' ? 'down' : 'up';
        $userId = (int)$session->get('user_id', 1);
        $existing = $postVotes->findOneBy(['post_id' => $id, 'user_id' => $userId]);
        if ($existing) {
            $existing->setVoteType($voteType);
        } else {
            $vote = new PostVote();
            $vote->setPostId($id);
            $vote->setUserId($userId);
            $vote->setVoteType($voteType);
            $vote->setCreatedAt(new \DateTime());
            $em->persist($vote);
        }

        $em->flush();
        $this->addFlash('success', $voteType === 'up' ? 'Like ajoute.' : 'Dislike ajoute.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/comment', name: 'community_comment_create', methods: ['POST'])]
    public function createComment(Request $request, UtilisateurRepository $users, EntityManagerInterface $em): RedirectResponse
    {
        $postId = (int)$request->request->get('post_id');
        $content = trim((string)$request->request->get('content'));
        $userId = (int)$request->request->get('user_id', 1);
        $parentId = (int)$request->request->get('parent_comment_id', 0);

        $user = $users->find($userId);
        if (!$user) {
            $user = new Utilisateur();
            $user->setNomUtilisateur('auto_user');
            $user->setMotPasse('temp');
            $em->persist($user);
            $em->flush();
            $userId = (int)$user->getIDUTILISATEUR();
        }

        if ($postId <= 0 || $content === '') {
            $this->addFlash('error', 'Commentaire invalide.');
            return $this->redirectToRoute('community_index');
        }

        $comment = new Comment();
        $comment->setPostId($postId);
        $comment->setUserId($userId);
        $comment->setContent($content);
        $comment->setParentCommentId($parentId > 0 ? $parentId : null);
        $comment->setIsActive(true);
        $comment->setCreatedAt(new \DateTime());

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire ajoute.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/comment/{id}/update', name: 'community_comment_update', methods: ['POST'])]
    public function updateComment(int $id, Request $request, CommentRepository $comments, EntityManagerInterface $em): RedirectResponse
    {
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $content = trim((string)$request->request->get('content'));
        if ($content === '') {
            $this->addFlash('error', 'Le contenu est obligatoire.');
            return $this->redirectToRoute('community_index');
        }

        $comment->setContent($content);
        $em->flush();
        $this->addFlash('success', 'Commentaire mis a jour.');

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/comment/{id}/delete', name: 'community_comment_delete', methods: ['POST'])]
    public function deleteComment(int $id, CommentRepository $comments, EntityManagerInterface $em, Connection $conn): RedirectResponse
    {
        $comment = $comments->find($id);
        if ($comment) {
            $conn->executeStatement('UPDATE comments SET parent_comment_id = NULL WHERE parent_comment_id = :cid', ['cid' => $id]);
            $conn->executeStatement('DELETE FROM comment_votes WHERE comment_id = :cid', ['cid' => $id]);
            $em->remove($comment);
            $em->flush();
            $this->addFlash('success', 'Commentaire supprime.');
        } else {
            $this->addFlash('error', 'Commentaire introuvable.');
        }

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/comment/{id}/vote/{type}', name: 'community_comment_vote', methods: ['POST'])]
    public function voteComment(
        int $id,
        string $type,
        CommentRepository $comments,
        CommentVoteRepository $votes,
        EntityManagerInterface $em,
        SessionInterface $session
    ): RedirectResponse {
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $voteType = $type === 'down' ? 'down' : 'up';
        $userId = (int)$session->get('user_id', 1);
        $existing = $votes->findOneBy(['comment_id' => $id, 'user_id' => $userId]);
        if ($existing) {
            $existing->setVoteType($voteType);
        } else {
            $vote = new CommentVote();
            $vote->setCommentId($id);
            $vote->setUserId($userId);
            $vote->setVoteType($voteType);
            $vote->setCreatedAt(new \DateTime());
            $em->persist($vote);
        }

        $em->flush();
        $this->addFlash('success', $voteType === 'up' ? 'Like ajoute.' : 'Dislike ajoute.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/comment/{id}/toggle', name: 'community_comment_toggle', methods: ['POST'])]
    public function toggleComment(int $id, CommentRepository $comments, EntityManagerInterface $em): RedirectResponse
    {
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $comment->setIsActive(!$comment->isActive());
        $em->flush();
        $this->addFlash('success', $comment->isActive() ? 'Commentaire affiche.' : 'Commentaire masque.');

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/comment/{id}/pin', name: 'community_comment_pin', methods: ['POST'])]
    public function pinComment(int $id, SessionInterface $session): RedirectResponse
    {
        $pinned = $session->get('pinned_comments', []);
        if (!in_array($id, $pinned, true)) {
            $pinned[] = $id;
            $this->addFlash('success', 'Commentaire epingle.');
        } else {
            $pinned = array_values(array_diff($pinned, [$id]));
            $this->addFlash('success', 'Commentaire desepingle.');
        }
        $session->set('pinned_comments', $pinned);

        return $this->redirectToRoute('community_index');
    }

    #[Route('/api/chatbot', name: 'community_chatbot', methods: ['POST'])]
    public function chatbot(Request $request): JsonResponse
    {
        $payload = json_decode($request->getContent(), true) ?? [];
        $question = trim((string)($payload['question'] ?? ''));
        if ($question === '') {
            return new JsonResponse(['answer' => 'Ecris une question.'], 400);
        }

        return new JsonResponse(['answer' => $this->askChatbot($question)]);
    }

    #[Route('/api/weather', name: 'community_weather_api', methods: ['GET'])]
    public function weatherApi(): JsonResponse
    {
        return new JsonResponse($this->fetchWeather());
    }

    #[Route('/admin/community', name: 'community_admin_dashboard', methods: ['GET'])]
    public function adminDashboard(PostRepository $posts, CommentRepository $comments, Request $request): Response
    {
        $q = trim((string)$request->query->get('q'));
        $tagFilter = trim((string)$request->query->get('tag'));
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = min(50, max(5, (int)$request->query->get('per_page', 10)));

        $allPosts = $posts->findBy([], ['created_at' => 'DESC']);
        $filtered = array_values(array_filter($allPosts, function (Post $post) use ($q, $tagFilter): bool {
            if ($q !== '' && stripos((string)$post->getTitle(), $q) === false && stripos((string)$post->getDescription(), $q) === false) {
                return false;
            }
            if ($tagFilter !== '' && stripos((string)$post->getTag(), $tagFilter) === false) {
                return false;
            }

            return true;
        }));

        $totalPosts = count($filtered);
        $totalPages = max(1, (int)ceil($totalPosts / $perPage));
        $page = min($page, $totalPages);
        $offset = ($page - 1) * $perPage;
        $postList = array_slice($filtered, $offset, $perPage);

        $commentCounts = [];
        foreach ($filtered as $post) {
            $commentCounts[(int)$post->getId()] = $comments->count(['post_id' => $post->getId()]);
        }

        $tagCounts = [];
        foreach ($filtered as $post) {
            $tag = $post->getTag();
            if ($tag) {
                $tagCounts[$tag] = ($tagCounts[$tag] ?? 0) + 1;
            }
        }
        arsort($tagCounts);

        $tags = array_values(array_unique(array_filter(array_map(
            static fn(Post $post): ?string => $post->getTag(),
            $allPosts
        ))));

        $activeCount = 0;
        $inactiveCount = 0;
        $postsPerMonth = [];
        $commentsPerMonth = [];
        $topAuthors = [];
        foreach ($filtered as $post) {
            $post->isActive() ? $activeCount++ : $inactiveCount++;
            $month = $post->getCreatedAt()?->format('Y-m') ?? 'n-a';
            $postsPerMonth[$month] = ($postsPerMonth[$month] ?? 0) + 1;
            $uid = $post->getUserId();
            if ($uid) {
                $topAuthors[$uid] = ($topAuthors[$uid] ?? 0) + 1;
            }
        }
        ksort($postsPerMonth);

        $allComments = $comments->findBy([], ['created_at' => 'ASC']);
        foreach ($allComments as $comment) {
            $month = $comment->getCreatedAt()?->format('Y-m') ?? 'n-a';
            $commentsPerMonth[$month] = ($commentsPerMonth[$month] ?? 0) + 1;
        }
        ksort($commentsPerMonth);

        $topCommented = $filtered;
        usort($topCommented, static function (Post $a, Post $b) use ($commentCounts): int {
            $countA = $commentCounts[(int)$a->getId()] ?? 0;
            $countB = $commentCounts[(int)$b->getId()] ?? 0;
            return $countB <=> $countA;
        });
        $topCommented = array_slice($topCommented, 0, 5);
        arsort($topAuthors);

        return $this->render('admin/community.html.twig', [
            'posts' => $postList,
            'commentCounts' => $commentCounts,
            'tagCounts' => $tagCounts,
            'tags' => $tags,
            'currentTag' => $tagFilter,
            'currentSearch' => $q,
            'activeCount' => $activeCount,
            'inactiveCount' => $inactiveCount,
            'postsPerMonth' => $postsPerMonth,
            'commentsPerMonth' => $commentsPerMonth,
            'topCommented' => $topCommented,
            'topAuthors' => $topAuthors,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
        ]);
    }

    #[Route('/admin/community/post/{id}/toggle', name: 'community_admin_post_toggle', methods: ['POST'])]
    public function adminTogglePost(int $id, PostRepository $posts, EntityManagerInterface $em): RedirectResponse
    {
        $post = $posts->find($id);
        if ($post) {
            $post->setIsActive(!$post->isActive());
            $em->flush();
            $this->addFlash('success', $post->isActive() ? 'Post approuve.' : 'Post masque.');
        } else {
            $this->addFlash('error', 'Post introuvable.');
        }

        return $this->redirectToRoute('community_admin_dashboard');
    }

    private function computeEngagementMetrics(array $posts, array $commentCounts, array $postVoteCounts): array
    {
        $viewsCounts = [];
        $shareCounts = [];
        $saveCounts = [];
        $avgReadSeconds = [];
        $engagementRates = [];

        foreach ($posts as $post) {
            $pid = (int)$post->getId();
            $ups = (int)($postVoteCounts[$pid]['up'] ?? 0);
            $downs = (int)($postVoteCounts[$pid]['down'] ?? 0);
            $commentCount = (int)($commentCounts[$pid] ?? 0);
            $baseViews = max(40, ($ups + $downs + ($commentCount * 2)) * 7);
            $viewsCounts[$pid] = $baseViews;
            $shareCounts[$pid] = (int)round($baseViews * 0.08);
            $saveCounts[$pid] = (int)round($baseViews * 0.05);
            $avgReadSeconds[$pid] = 45 + (int)round(strlen((string)$post->getDescription()) / 8);
            $engagementRates[$pid] = round((($ups + $downs + ($commentCount * 2) + $saveCounts[$pid] + $shareCounts[$pid]) / max(1, $baseViews)) * 100, 1);
        }

        return [$viewsCounts, $shareCounts, $saveCounts, $avgReadSeconds, $engagementRates];
    }

    private function storeUploadedImage(UploadedFile $file): string
    {
        $targetDir = (string)$this->getParameter('kernel.project_dir') . '/public/uploads/post_images';
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new \RuntimeException('Impossible de creer le dossier upload.');
        }

        $extension = $file->guessExtension() ?: 'bin';
        $fileName = bin2hex(random_bytes(16)) . '.' . $extension;

        try {
            $file->move($targetDir, $fileName);
        } catch (FileException) {
            throw new \RuntimeException('Deplacement du fichier echoue.');
        }

        return $fileName;
    }

    private function routeExists(string $name): bool
    {
        try {
            $this->generateUrl($name);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    private function getCachedWeather(SessionInterface $session): array
    {
        $cache = $session->get('community_weather_cache');
        $ttlSeconds = 300;
        if (is_array($cache) && isset($cache['fetched_at'], $cache['data'])) {
            $age = time() - (int)$cache['fetched_at'];
            if ($age >= 0 && $age <= $ttlSeconds && is_array($cache['data'])) {
                return $cache['data'];
            }
        }

        $fresh = $this->fetchWeather();
        $session->set('community_weather_cache', [
            'fetched_at' => time(),
            'data' => $fresh,
        ]);

        return $fresh;
    }

    private function fetchWeather(): array
    {
        try {
            $url = 'https://api.open-meteo.com/v1/forecast?latitude=36.8065&longitude=10.1815&current_weather=true&timezone=Africa%2FTunis';
            $data = $this->requestJson('GET', $url, null, [], 6);
            $current = is_array($data) ? ($data['current_weather'] ?? null) : null;
            if (!is_array($current)) {
                return [
                    'error' => 'Meteo indisponible',
                    'temperature' => null,
                    'windspeed' => null,
                    'weathercode' => null,
                ];
            }

            return [
                'error' => null,
                'temperature' => $current['temperature'] ?? null,
                'windspeed' => $current['windspeed'] ?? null,
                'weathercode' => $current['weathercode'] ?? null,
            ];
        } catch (\Throwable) {
            return [
                'error' => 'Meteo indisponible',
                'temperature' => null,
                'windspeed' => null,
                'weathercode' => null,
            ];
        }
    }

    private function askChatbot(string $question): string
    {
        $question = trim($question);
        if ($question === '') {
            return 'Ecris une question.';
        }

        $token = $_ENV['HUGGINGFACE_TOKEN'] ?? null;
        if ($token) {
            try {
                $data = $this->requestJson(
                    'POST',
                    'https://api-inference.huggingface.co/models/mistralai/Mistral-7B-Instruct-v0.2',
                    [
                        'inputs' => "Instruction: Reponds brievement en francais.\nQuestion: " . $question,
                        'parameters' => [
                            'max_new_tokens' => 120,
                            'temperature' => 0.7,
                        ],
                    ],
                    ['Authorization: Bearer ' . $token],
                    18
                );
                if (is_array($data)) {
                    if (isset($data[0]['generated_text']) && is_string($data[0]['generated_text'])) {
                        return trim($data[0]['generated_text']);
                    }
                    if (isset($data['generated_text']) && is_string($data['generated_text'])) {
                        return trim($data['generated_text']);
                    }
                }
            } catch (\Throwable) {
            }
        }

        $q = mb_strtolower($question);
        if (str_contains($q, 'bonjour') || str_contains($q, 'salut')) {
            return 'Bonjour, je suis la pour t aider.';
        }
        if (str_contains($q, 'meteo') || str_contains($q, 'weather')) {
            return 'La meteo vient de l API Symfony /api/weather et apparait dans la carte meteo.';
        }
        if (str_contains($q, 'publier') || str_contains($q, 'post')) {
            return 'Remplis le formulaire de publication puis clique sur Publier.';
        }

        return 'Pose une question precise: meteo, publier, commentaires, votes.';
    }

    private function requestJson(string $method, string $url, ?array $payload = null, array $headers = [], int $timeout = 8): ?array
    {
        $method = strtoupper($method);
        $baseHeaders = ['Accept: application/json'];
        $options = [
            'http' => [
                'method' => $method,
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ];

        if ($payload !== null) {
            $json = json_encode($payload);
            if ($json === false) {
                return null;
            }
            $baseHeaders[] = 'Content-Type: application/json';
            $options['http']['content'] = $json;
        }

        $options['http']['header'] = implode("\r\n", array_merge($baseHeaders, $headers)) . "\r\n";

        $context = stream_context_create($options);
        $raw = @file_get_contents($url, false, $context);
        if ($raw === false || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : null;
    }
}
