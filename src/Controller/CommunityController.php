<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Post;
use App\Entity\PostVote;
use App\Entity\Utilisateur;
use App\Entity\CommunityChatMessage;
use App\Entity\PostFavorite;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\PostRepository;
use App\Repository\PostVoteRepository;
use App\Repository\PostFavoriteRepository;
use App\Repository\CommunityChatMessageRepository;
use App\Repository\UtilisateurRepository;
use App\Service\UserService;
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
use Stichoza\GoogleTranslate\GoogleTranslate;

class CommunityController extends AbstractController
{
    #[Route('/communaute', name: 'community_index', methods: ['GET'])]
    public function search(
        PostRepository $posts,
        CommentRepository $comments,
        PostVoteRepository $postVoteRepository,
        CommentVoteRepository $commentVoteRepository,
        PostFavoriteRepository $favRepository,
        CommunityChatMessageRepository $chatRepository,
        SessionInterface $session,
        UserService $userService,
        Request $request,
        PaginatorInterface $paginator
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
        $totalPages = max(1, (int)ceil($totalPosts / $perPage));

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
                $commentsByPost[(int)$comment->getPost_id()][] = $comment;
            }
        }

        // Use QueryBuilder for vote counts instead of raw SQL
        $commentIds = array_values(array_map(static fn(Comment $comment): int => (int)$comment->getId(), $allPageComments));
        $voteCounts = [];
        if (!empty($commentIds)) {
            $voteCounts = $commentVoteRepository->getVoteCountsByCommentIds($commentIds);
            // Initialize missing comment IDs
            foreach ($commentIds as $commentId) {
                if (!isset($voteCounts[$commentId])) {
                    $voteCounts[$commentId] = ['up' => 0, 'down' => 0];
                }
            }
        } else {
            foreach ($commentIds as $commentId) {
                $voteCounts[$commentId] = ['up' => 0, 'down' => 0];
            }
        }

        // Use QueryBuilder for comment counts instead of raw SQL
        $commentCounts = $comments->getCountsByPostIds($filteredPostIds);
        foreach ($filteredPostIds as $postId) {
            if (!isset($commentCounts[$postId])) {
                $commentCounts[$postId] = 0;
            }
        }

        // Use QueryBuilder for post vote counts instead of raw SQL
        $allPostVoteCounts = $postVoteRepository->getVoteCountsByPostIds($filteredPostIds);
        foreach ($filteredPostIds as $postId) {
            if (!isset($allPostVoteCounts[$postId])) {
                $allPostVoteCounts[$postId] = ['up' => 0, 'down' => 0];
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
        $currentUserId = $userService->resolveCurrentUserId($session);
        $favoritePostIds = $favRepository->findByUserId($currentUserId);
        $chatMessages = array_reverse($chatRepository->findRecentActive(40));

        $weather = $this->getCachedWeather($session);

        return $this->render('community/index.html.twig', [
            'posts' => $postList,
            'commentsByPost' => $commentsByPost,
            'voteCounts' => $voteCounts,
            'postVoteCounts' => $postVoteCounts,
            'commentCounts' => $commentCounts,
            'pinned' => $session->get('pinned_comments', []),
            'favorites' => array_map(static fn($fav): int => (int)$fav->getPostId(), $favoritePostIds),
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
            'chatMessages' => array_map(static function ($msg) {
                return [
                    'id' => $msg->getId(),
                    'userId' => $msg->getUserId(),
                    'content' => $msg->getContent(),
                    'createdAt' => $msg->getCreatedAt(),
                    'username' => 'User ' . ($msg->getUserId() ?? 'Unknown'),
                ];
            }, $chatMessages),
        ]);
    }

    #[Route('/communaute/chat/send', name: 'community_chat_send', methods: ['POST'])]
    public function sendInternalChatMessage(
        Request $request,
        SessionInterface $session,
        UserService $userService,
        CommunityChatMessageRepository $chatRepository,
        EntityManagerInterface $em
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

        $userId = $userService->resolveCurrentUserId($session);
        $message = new CommunityChatMessage();
        $message->setUserId($userId);
        $message->setContent($content);
        $message->setCreatedAt(new \DateTime());
        $message->setIsActive(true);

        $em->persist($message);
        $em->flush();

        $this->addFlash('success', 'Message envoye dans le chat interne.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/chat/{id}/delete', name: 'community_chat_delete', methods: ['POST'])]
    public function deleteInternalChatMessage(
        int $id,
        SessionInterface $session,
        UserService $userService,
        CommunityChatMessageRepository $chatRepository,
        EntityManagerInterface $em
    ): RedirectResponse {
        $message = $chatRepository->findOneById($id);
        if (!$message) {
            $this->addFlash('error', 'Message chat introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $currentUserId = $userService->resolveCurrentUserId($session);
        $ownerId = $message->getUserId() ?? 0;
        if (!$this->canManageContent($ownerId, $currentUserId)) {
            $this->addFlash('error', 'Action refusee: vous ne pouvez supprimer que vos messages.');
            return $this->redirectToRoute('community_index');
        }

        $em->remove($message);
        $em->flush();

        $this->addFlash('success', 'Message chat supprime.');
        return $this->redirectToRoute('community_index');
    }

    #[Route('/communaute/post', name: 'community_post_create', methods: ['POST'])]
    public function createPost(
        Request $request,
        UtilisateurRepository $users,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserService $userService
    ): RedirectResponse
    {
        $userId = $userService->resolveCurrentUserId($session);
        $user = $users->find($userId);
        if (!$user) {
            $identifier = (string)($this->getUser()?->getUserIdentifier() ?? '');
            $user = new Utilisateur();
            $user->setNom_Utilisateur($identifier !== '' ? $identifier : 'auto_user');
            $user->setMot_Passe('temp');
            if ($identifier !== '' && filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
                $user->setEmail($identifier);
            }
            $em->persist($user);
            $em->flush();
            $userId = (int)$user->getID_UTILISATEUR();
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
        UserService $userService
    ): RedirectResponse
    {
        $redirect = (string)$request->request->get('redirect_route');
        $targetRoute = ($redirect !== '' && $this->routeExists($redirect)) ? $redirect : 'community_index';
        $currentUserId = $userService->resolveCurrentUserId($session);

        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute($targetRoute);
        }
        if (!$this->canManageContent((int)$post->getUser_id(), $currentUserId)) {
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
        Request $request,
        SessionInterface $session,
        UserService $userService
    ): RedirectResponse {
        $currentUserId = $userService->resolveCurrentUserId($session);
        $post = $posts->find($id);
        if ($post) {
            if (!$this->canManageContent((int)$post->getUser_id(), $currentUserId)) {
                $this->addFlash('error', 'Action refusee: vous ne pouvez supprimer que vos publications.');
                return $this->redirectToRoute('community_index');
            }
            // ORM cascade delete will handle all related entities (comments, votes, favorites)
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
    public function favoritePost(
        int $id,
        SessionInterface $session,
        PostRepository $posts,
        PostFavoriteRepository $favRepository,
        EntityManagerInterface $em,
        UserService $userService
    ): RedirectResponse
    {
        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $userId = $userService->resolveCurrentUserId($session);
        $isFavorited = $favRepository->isFavorited($id, $userId);

        if (!$isFavorited) {
            $user = $em->getReference(Utilisateur::class, $userId);
            $favorite = new PostFavorite();
            $favorite->setPost($post);
            $favorite->setUser($user);
            $favorite->setCreatedAt(new \DateTime());
            $em->persist($favorite);
            $em->flush();
            $this->addFlash('success', 'Post ajoute aux favoris.');
        } else {
            $this->addFlash('success', 'Post retire des favoris.');
            $em->createQueryBuilder()
                ->delete(PostFavorite::class, 'pf')
                ->where('pf.post = :post')
                ->andWhere('pf.user = :user')
                ->setParameter('post', $post)
                ->setParameter('user', $em->getReference(Utilisateur::class, $userId))
                ->getQuery()
                ->execute();
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
        UserService $userService
    ): RedirectResponse {
        $post = $posts->find($id);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $voteType = $type === 'down' ? 'down' : 'up';
        $userId = $userService->resolveCurrentUserId($session);
        $existing = $postVotes->findByUserAndPost($userId, $id);
        if ($existing) {
            $existing->setVoteType($voteType);
        } else {
            $user = $em->getReference(Utilisateur::class, $userId);
            $vote = new PostVote();
            $vote->setPost($post);
            $vote->setUser($user);
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
        PostRepository $posts,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserService $userService
    ): RedirectResponse
    {
        $postId = (int)$request->request->get('post_id');
        $content = trim((string)$request->request->get('content'));
        $parentId = (int)$request->request->get('parent_comment_id', 0);

        $post = $posts->find($postId);
        if (!$post) {
            $this->addFlash('error', 'Post introuvable.');
            return $this->redirectToRoute('community_index');
        }

        if ($content === '') {
            $this->addFlash('error', 'Commentaire invalide.');
            return $this->redirectToRoute('community_index');
        }

        $userId = $userService->resolveCurrentUserId($session);
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
        UserService $userService
    ): RedirectResponse
    {
        $currentUserId = $userService->resolveCurrentUserId($session);
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }
        if (!$this->canManageContent((int)$comment->getUser_id(), $currentUserId)) {
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
        SessionInterface $session,
        UserService $userService
    ): RedirectResponse
    {
        $currentUserId = $userService->resolveCurrentUserId($session);
        $comment = $comments->find($id);
        if ($comment) {
            if (!$this->canManageContent((int)$comment->getUser_id(), $currentUserId)) {
                $this->addFlash('error', 'Action refusee: vous ne pouvez supprimer que vos commentaires.');
                return $this->redirectToRoute('community_index');
            }
            // ORM cascade delete will handle orphaned child comments and votes
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
        UserService $userService
    ): RedirectResponse {
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }

        $voteType = $type === 'down' ? 'down' : 'up';
        $userId = $userService->resolveCurrentUserId($session);
        $existing = $votes->findByUserAndComment($userId, $id);
        if ($existing) {
            $existing->setVoteType($voteType);
        } else {
            $user = $em->getReference(Utilisateur::class, $userId);
            $vote = new CommentVote();
            $vote->setComment($comment);
            $vote->setUser($user);
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
        UserService $userService
    ): RedirectResponse
    {
        $currentUserId = $userService->resolveCurrentUserId($session);
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }
        if (!$this->canManageContent((int)$comment->getUser_id(), $currentUserId)) {
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
        UserService $userService
    ): RedirectResponse
    {
        $currentUserId = $userService->resolveCurrentUserId($session);
        $comment = $comments->find($id);
        if (!$comment) {
            $this->addFlash('error', 'Commentaire introuvable.');
            return $this->redirectToRoute('community_index');
        }
        if (!$this->canManageContent((int)$comment->getUser_id(), $currentUserId)) {
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

    #[Route('/api/communaute/hrbot', name: 'api_community_hrbot', methods: ['POST'])]
    public function askHrBot(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = trim((string)($data['question'] ?? ''));

        if ($question === '') {
            return new JsonResponse(['success' => false, 'error' => 'Question vide.']);
        }

        $systemContext = "Tu es HR-Bot, l'assistant virtuel intelligent de l'application HR One Web (modules: Communaute, RH, Conges, Recrutement, Formations). Reviens très brièvement à la ligne si besoin, sois concis et utilise la langue française pour répondre intelligemment.";
        $promptUrl = "https://text.pollinations.ai/prompt/" . urlencode($question) . "?system=" . urlencode($systemContext);
        
        try {
            $client = new \GuzzleHttp\Client([
                'verify' => false,
                'proxy' => ''
            ]);
            $response = $client->get($promptUrl, ['timeout' => 60]);
            $aiResponse = $response->getBody()->getContents();

            if ($aiResponse) {
                // Return clean HTML string without XSS that breaks the JS injected DOM
                $cleanHtml = nl2br(htmlspecialchars(trim($aiResponse)));
                return new JsonResponse([
                    'success' => true,
                    'reply' => $cleanHtml
                ]);
            }
        } catch (\Exception $e) {
            return new JsonResponse(['success' => false, 'error' => 'Serveur IA indisponible: ' . $e->getMessage()], 500);
        }

        return new JsonResponse(['success' => false, 'error' => 'Serveur IA indisponible.'], 500);
    }

    #[Route('/api/communaute/comment/{id}/translate', name: 'api_community_comment_translate', methods: ['POST'])]
    public function translateComment(
        int $id,
        Request $request,
        CommentRepository $comments
    ): JsonResponse {
        $comment = $comments->find($id);
        if (!$comment) {
            return new JsonResponse(['error' => 'Commentaire introuvable'], 404);
        }

        $data = json_decode($request->getContent(), true);
        $targetLang = $data['target'] ?? 'fr';

        try {
            $tr = new GoogleTranslate();
            $tr->setTarget($targetLang);
            // Disable default unexpected local proxy/ssl issues in XAMPP on Windows
            $tr->setOptions([
                'proxy' => '',
                'verify' => false
            ]);
            $translatedText = $tr->translate($comment->getContent() ?? '');

            return new JsonResponse([
                'success' => true,
                'translatedText' => $translatedText
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'error' => 'Erreur de traduction : ' . $e->getMessage()
            ], 500);
        }
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

    private function canManageContent(int $ownerId, int $currentUserId): bool
    {
        return $this->isGranted('ROLE_ADMIN') || ($ownerId > 0 && $ownerId === $currentUserId);
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
