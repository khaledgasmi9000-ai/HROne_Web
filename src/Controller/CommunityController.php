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
        $page = max(1, (int)$request->query->get('page', 1));
        $perPage = min(30, max(3, (int)$request->query->get('per_page', 5)));

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
        $currentUserId = $this->resolveCurrentUserId($session, $conn);
        $favoritePostIds = $this->loadFavoritePostIds($conn, $currentUserId);
        $chatMessages = $this->loadInternalChatMessages($conn);

        $weather = $this->getCachedWeather($session);

        return $this->render('community/index.html.twig', [
            'posts' => $postList,
            'commentsByPost' => $commentsByPost,
            'voteCounts' => $voteCounts,
            'postVoteCounts' => $postVoteCounts,
            'commentCounts' => $commentCounts,
            'pinned' => $session->get('pinned_comments', []),
            'favorites' => $favoritePostIds,
            'currentUserId' => $currentUserId,
            'tags' => $tags,
            'currentTag' => $tagFilter,
            'currentSearch' => $search,
            'page' => $page,
            'perPage' => $perPage,
            'totalPages' => $totalPages,
            'totalPosts' => $totalPosts,
            'totalLikes' => $totalLikes,
            'totalDislikes' => $totalDislikes,
            'pagination' => $pagination,
            'weather' => $weather,
            'chatMessages' => $chatMessages,
        ]);
    }

    #[Route('/communaute/chat/send', name: 'community_chat_send', methods: ['POST'])]
    public function sendInternalChatMessage(
        Request $request,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse {
        $content = trim((string)$request->request->get('content'));
        if ($content === '') {
            $this->addFlash('error', 'Message vide. Merci d ecrire quelque chose.');
            return $this->redirectToRoute('community_index');
        }
        if (mb_strlen($content) > 1200) {
            $this->addFlash('error', 'Message trop long (max 1200 caracteres).');
            return $this->redirectToRoute('community_index');
        }

        $this->ensureInternalChatTable($conn);
        $userId = $this->resolveCurrentUserId($session, $conn);
        $conn->insert('community_chat_messages', [
            'user_id' => $userId,
            'content' => $content,
            'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            'is_active' => 1,
        ]);

        $this->addFlash('success', 'Message envoye dans le chat interne.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/chat/{id}/delete', name: 'community_chat_delete', methods: ['POST'])]
    public function deleteInternalChatMessage(
        int $id,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse {
        $this->ensureInternalChatTable($conn);

        $row = $conn->fetchAssociative(
            'SELECT id, user_id FROM community_chat_messages WHERE id = :id LIMIT 1',
            ['id' => $id]
        );
        if (!is_array($row)) {
            $this->addFlash('error', 'Message chat introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $currentUserId = $this->resolveCurrentUserId($session, $conn);
        $ownerId = (int)($row['user_id'] ?? 0);
        if (!$this->canManageContent($ownerId, $currentUserId)) {
            $this->addFlash('error', 'Action refusee: vous ne pouvez supprimer que vos messages.');
            return $this->redirectToRoute('community_index');
        }

        $conn->executeStatement(
            'DELETE FROM community_chat_messages WHERE id = :id',
            ['id' => $id]
        );

        $this->addFlash('success', 'Message chat supprime.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post', name: 'community_post_create', methods: ['POST'])]
    public function createPost(
        Request $request,
        UtilisateurRepository $users,
        EntityManagerInterface $em,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse
    {
        $userId = $this->resolveCurrentUserId($session, $conn);
        $user = $users->find($userId);
        if (!$user) {
            $identifier = (string)($this->getUser()?->getUserIdentifier() ?? '');
            $user = new Utilisateur();
            $user->setNomUtilisateur($identifier !== '' ? $identifier : 'auto_user');
            $user->setMotPasse('temp');
            if ($identifier !== '' && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user->setEmail($identifier);
            }
            $em->persist($user);
            $em->flush();
            $userId = (int)$user->getIDUTILISATEUR();
            $session->set('user_id', $userId);
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
        }

        $em->persist($post);
        $em->flush();

        $this->addFlash('success', 'Post soumis. En attente de validation admin.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post/{id}/update', name: 'community_post_update', methods: ['POST'])]
    public function updatePost(
        int $id,
        Request $request,
        PostRepository $posts,
        EntityManagerInterface $em,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse
    {
        $redirect = (string)$request->request->get('redirect_route');
        $targetRoute = ($redirect !== '' && $this->routeExists($redirect)) ? $redirect : 'community_index';
        $currentUserId = $this->resolveCurrentUserId($session, $conn);

        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute($targetRoute);
        }
        if (!$this->canManageContent((int)$post->getUserId(), $currentUserId)) {
            $this->addFlash('error', 'Action refusee: vous ne pouvez modifier que vos publications.');
            return $this->redirectToRoute($targetRoute);
        }

        [$data, $error] = Post::hydrateAndValidate($request);
        if ($error) {
            $this->addFlash('error', $error);
            return $this->redirectToRoute($targetRoute);
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
                return $this->redirectToRoute($targetRoute);
            }
        }

        $em->flush();
        $this->addFlash('success', 'Post mis a jour.');
        return $this->redirectToRoute($targetRoute);
    }

    #[Route('/communaute/post/{id}/delete', name: 'community_post_delete', methods: ['POST'])]
    public function deletePost(
        int $id,
        PostRepository $posts,
        EntityManagerInterface $em,
        Connection $conn,
        Request $request,
        SessionInterface $session
    ): RedirectResponse {
        $currentUserId = $this->resolveCurrentUserId($session, $conn);
        $post = $posts->find($id);
        if ($post) {
            if (!$this->canManageContent((int)$post->getUserId(), $currentUserId)) {
                $this->addFlash('error', 'Action refusee: vous ne pouvez supprimer que vos publications.');
                return $this->redirectToRoute('community_index');
            }
            $this->ensurePostFavoritesTable($conn);
            $conn->executeStatement('UPDATE comments SET parent_comment_id = NULL WHERE post_id = :pid', ['pid' => $id]);
            $conn->executeStatement('DELETE FROM post_votes WHERE post_id = :pid', ['pid' => $id]);
            $conn->executeStatement('DELETE FROM comment_votes WHERE comment_id IN (SELECT id FROM comments WHERE post_id = :pid)', ['pid' => $id]);
            $conn->executeStatement('DELETE FROM post_favorites WHERE post_id = :pid', ['pid' => $id]);
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
    public function favoritePost(int $id, SessionInterface $session, PostRepository $posts, Connection $conn): RedirectResponse
    {
        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $this->ensurePostFavoritesTable($conn);
        $userId = $this->resolveCurrentUserId($session, $conn);
        $exists = (bool)$conn->fetchOne(
            'SELECT 1 FROM post_favorites WHERE post_id = :pid AND user_id = :uid LIMIT 1',
            ['pid' => $id, 'uid' => $userId]
        );

        if (!$exists) {
            $conn->insert('post_favorites', [
                'post_id' => $id,
                'user_id' => $userId,
                'created_at' => (new \DateTime())->format('Y-m-d H:i:s'),
            ]);
            $this->addFlash('success', 'Post ajoute aux favoris.');
        } else {
            $conn->executeStatement(
                'DELETE FROM post_favorites WHERE post_id = :pid AND user_id = :uid',
                ['pid' => $id, 'uid' => $userId]
            );
            $this->addFlash('success', 'Post retire des favoris.');
        }

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post/{id}/vote/{type}', name: 'community_post_vote', methods: ['POST'])]
    public function votePost(
        int $id,
        string $type,
        PostRepository $posts,
        PostVoteRepository $postVotes,
        EntityManagerInterface $em,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse {
        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $voteType = $type === 'down' ? 'down' : 'up';
        $userId = $this->resolveCurrentUserId($session, $conn);
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
    public function createComment(
        Request $request,
        UtilisateurRepository $users,
        EntityManagerInterface $em,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse
    {
        $postId = (int)$request->request->get('post_id');
        $content = trim((string)$request->request->get('content'));
        $userId = $this->resolveCurrentUserId($session, $conn);
        $parentId = (int)$request->request->get('parent_comment_id', 0);

        $user = $users->find($userId);
        if (!$user) {
            $identifier = (string)($this->getUser()?->getUserIdentifier() ?? '');
            $user = new Utilisateur();
            $user->setNomUtilisateur($identifier !== '' ? $identifier : 'auto_user');
            $user->setMotPasse('temp');
            if ($identifier !== '' && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user->setEmail($identifier);
            }
            $em->persist($user);
            $em->flush();
            $userId = (int)$user->getIDUTILISATEUR();
            $session->set('user_id', $userId);
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
    public function updateComment(
        int $id,
        Request $request,
        CommentRepository $comments,
        EntityManagerInterface $em,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse
    {
        $currentUserId = $this->resolveCurrentUserId($session, $conn);
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }
        if (!$this->canManageContent((int)$comment->getUserId(), $currentUserId)) {
            $this->addFlash('error', 'Action refusee: vous ne pouvez modifier que vos commentaires.');
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
    public function deleteComment(
        int $id,
        CommentRepository $comments,
        EntityManagerInterface $em,
        Connection $conn,
        SessionInterface $session
    ): RedirectResponse
    {
        $currentUserId = $this->resolveCurrentUserId($session, $conn);
        $comment = $comments->find($id);
        if ($comment) {
            if (!$this->canManageContent((int)$comment->getUserId(), $currentUserId)) {
                $this->addFlash('error', 'Action refusee: vous ne pouvez supprimer que vos commentaires.');
                return $this->redirectToRoute('community_index');
            }
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
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse {
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $voteType = $type === 'down' ? 'down' : 'up';
        $userId = $this->resolveCurrentUserId($session, $conn);
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
    public function toggleComment(
        int $id,
        CommentRepository $comments,
        EntityManagerInterface $em,
        SessionInterface $session,
        Connection $conn
    ): RedirectResponse
    {
        $currentUserId = $this->resolveCurrentUserId($session, $conn);
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }
        if (!$this->canManageContent((int)$comment->getUserId(), $currentUserId)) {
            $this->addFlash('error', 'Action refusee: vous ne pouvez modifier que vos commentaires.');
            return $this->redirectToRoute('community_index');
        }

        $comment->setIsActive(!$comment->isActive());
        $em->flush();
        $this->addFlash('success', $comment->isActive() ? 'Commentaire affiche.' : 'Commentaire masque.');

        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/comment/{id}/pin', name: 'community_comment_pin', methods: ['POST'])]
    public function pinComment(
        int $id,
        SessionInterface $session,
        CommentRepository $comments,
        Connection $conn
    ): RedirectResponse
    {
        $currentUserId = $this->resolveCurrentUserId($session, $conn);
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }
        if (!$this->canManageContent((int)$comment->getUserId(), $currentUserId)) {
            $this->addFlash('error', 'Action refusee: vous ne pouvez modifier que vos commentaires.');
            return $this->redirectToRoute('community_index');
        }

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

    #[Route('/api/weather', name: 'community_weather_api', methods: ['GET'])]
    public function weatherApi(): JsonResponse
    {
        return new JsonResponse($this->fetchWeather());
    }

    #[Route('/admin/community', name: 'community_admin_dashboard', methods: ['GET'])]
    public function adminDashboard(PostRepository $posts, CommentRepository $comments, Request $request): Response
    {
        $this->denyAccessUnlessGranted('ROLE_RH');

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
        $this->denyAccessUnlessGranted('ROLE_RH');

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

    private function resolveCurrentUserId(SessionInterface $session, Connection $conn): int
    {
        $sessionUserId = (int)$session->get('user_id', 0);
        if ($sessionUserId > 0) {
            return $sessionUserId;
        }

        $rawIdentifier = trim((string)($this->getUser()?->getUserIdentifier() ?? ''));
        $identifier = mb_strtolower($rawIdentifier);
        if ($identifier !== '') {
            $matchedId = $conn->fetchOne(
                'SELECT ID_UTILISATEUR FROM utilisateur WHERE LOWER(Email) = :identifier OR LOWER(Nom_Utilisateur) = :identifier LIMIT 1',
                ['identifier' => $identifier]
            );
            $resolvedId = (int)$matchedId;
            if ($resolvedId > 0) {
                $session->set('user_id', $resolvedId);
                return $resolvedId;
            }

            $username = $this->buildUsernameFromIdentifier($rawIdentifier);
            $candidate = $username;
            $suffix = 1;
            while ((int)$conn->fetchOne(
                'SELECT COUNT(*) FROM utilisateur WHERE LOWER(Nom_Utilisateur) = :username',
                ['username' => mb_strtolower($candidate)]
            ) > 0) {
                $candidate = $username . '_' . $suffix;
                $suffix++;
                if ($suffix > 25) {
                    $candidate = $username . '_' . substr(md5($identifier), 0, 6);
                    break;
                }
            }

            $email = filter_var($rawIdentifier, FILTER_VALIDATE_EMAIL) ? mb_strtolower($rawIdentifier) : null;
            $conn->insert('utilisateur', [
                'Nom_Utilisateur' => $candidate,
                'Mot_Passe' => 'external_login',
                'Email' => $email,
            ]);

            $createdId = (int)$conn->lastInsertId();
            if ($createdId > 0) {
                $session->set('user_id', $createdId);
                return $createdId;
            }
        }

        $session->set('user_id', 1);
        return 1;
    }

    private function buildUsernameFromIdentifier(string $identifier): string
    {
        $value = trim($identifier);
        if ($value === '') {
            return 'user';
        }

        if (str_contains($value, '@')) {
            $value = explode('@', $value)[0];
        }

        $value = strtolower(preg_replace('/[^a-zA-Z0-9_]+/', '_', $value) ?? '');
        $value = trim($value, '_');

        if ($value === '') {
            return 'user';
        }

        return substr($value, 0, 40);
    }

    private function canManageContent(int $ownerId, int $currentUserId): bool
    {
        return $this->isGranted('ROLE_ADMIN') || ($ownerId > 0 && $ownerId === $currentUserId);
    }

    private function loadFavoritePostIds(Connection $conn, int $userId): array
    {
        $this->ensurePostFavoritesTable($conn);
        $rows = $conn->fetchFirstColumn(
            'SELECT post_id FROM post_favorites WHERE user_id = :uid',
            ['uid' => $userId]
        );

        return array_values(array_map(static fn($value): int => (int)$value, $rows));
    }

    private function ensurePostFavoritesTable(Connection $conn): void
    {
        $conn->executeStatement(
            "CREATE TABLE IF NOT EXISTS post_favorites (
                post_id INT NOT NULL,
                user_id INT NOT NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (post_id, user_id),
                KEY idx_post_favorites_user (user_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    private function loadInternalChatMessages(Connection $conn): array
    {
        $this->ensureInternalChatTable($conn);

        $rows = $conn->fetchAllAssociative(
            "SELECT
                m.id,
                m.user_id AS userId,
                m.content,
                m.created_at AS createdAt,
                COALESCE(NULLIF(u.Nom_Utilisateur, ''), CONCAT('Utilisateur #', m.user_id)) AS username
             FROM community_chat_messages m
             LEFT JOIN utilisateur u ON u.ID_UTILISATEUR = m.user_id
             WHERE m.is_active = 1
             ORDER BY m.created_at DESC, m.id DESC
             LIMIT 40"
        );

        return array_reverse($rows);
    }

    private function ensureInternalChatTable(Connection $conn): void
    {
        $conn->executeStatement(
            "CREATE TABLE IF NOT EXISTS community_chat_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                user_id INT NOT NULL,
                content TEXT NOT NULL,
                created_at DATETIME NOT NULL,
                is_active TINYINT(1) NOT NULL DEFAULT 1,
                KEY idx_ccm_user (user_id),
                KEY idx_ccm_created (created_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );
    }

    private function getCachedWeather(SessionInterface $session): array
    {
        $cache = $session->get('community_weather_cache');
        if (is_array($cache) && isset($cache['data']) && is_array($cache['data'])) {
            return $cache['data'];
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
