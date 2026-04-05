<?php

namespace App\Controller;

use App\Dto\Community\CreatePostInput;
use App\Entity\Comment;
use App\Entity\CommentVote;
use App\Entity\Post;
use App\Entity\PostVote;
use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\PostRepository;
use App\Repository\PostVoteRepository;
use App\Repository\UtilisateurRepository;
use App\Service\Community\CommunityPostFeedService;
use App\Service\CommunityAssistant;
use App\Service\CommunityMetrics;
use App\Service\TunisWeatherService;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
#[OA\Tag(name: 'Communauté')]
class CommunityApiController extends AbstractController
{
    private const SESSION_USER_KEY = 'community_uid';

    private const MAX_TITLE_LEN = 255;
    private const MAX_DESC_LEN = 10000;
    private const MAX_TAG_LEN = 80;
    private const MAX_URL_LEN = 2048;
    private const MAX_COMMENT_LEN = 8000;
    private const MAX_SEARCH_LEN = 120;

    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly PostRepository $posts,
        private readonly CommentRepository $comments,
        private readonly UtilisateurRepository $utilisateurs,
        private readonly PostVoteRepository $postVotes,
        private readonly CommentVoteRepository $commentVotes,
        private readonly CommunityMetrics $communityMetrics,
        private readonly TunisWeatherService $tunisWeather,
        private readonly CommunityAssistant $communityAssistant,
        private readonly ValidatorInterface $validator,
        private readonly CommunityPostFeedService $communityPostFeed,
    ) {
    }

    // ——— Session (lien avec utilisateur.ID_UTILISATEUR) ———

    #[Route('/community/session', name: 'api_community_session_set', methods: ['POST'])]
    public function setCommunitySession(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        if ($userId < 1 || !$this->utilisateurs->existsId($userId)) {
            return $this->json(['message' => 'user_id invalide ou inconnu dans utilisateur.'], Response::HTTP_BAD_REQUEST);
        }

        $request->getSession()->set(self::SESSION_USER_KEY, $userId);

        return $this->json($this->serializeMe($userId));
    }

    #[Route('/community/session', name: 'api_community_session_clear', methods: ['DELETE'])]
    public function clearCommunitySession(Request $request): JsonResponse
    {
        $request->getSession()->remove(self::SESSION_USER_KEY);

        return $this->json(['ok' => true]);
    }

    #[Route('/community/me', name: 'api_community_me', methods: ['GET'])]
    public function me(Request $request): JsonResponse
    {
        $uid = $this->getCommunityUserId($request);
        if ($uid === null || !$this->utilisateurs->existsId($uid)) {
            return $this->json(['user' => null]);
        }

        return $this->json(['user' => $this->serializeMe($uid)]);
    }

    #[Route('/community/weather', name: 'api_community_weather', methods: ['GET'])]
    public function weather(): JsonResponse
    {
        $w = $this->tunisWeather->fetchCurrent();
        if ($w === null) {
            return $this->json(['message' => 'Météo indisponible pour le moment.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['weather' => $w]);
    }

    #[Route('/community/assistant', name: 'api_community_assistant', methods: ['POST'])]
    public function assistant(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }
        $message = isset($data['message']) ? trim((string) $data['message']) : '';
        if ($message === '') {
            return $this->json(['message' => 'Champ requis: message.'], Response::HTTP_BAD_REQUEST);
        }

        $viewer = $this->getCommunityUserId($request);
        $out = $this->communityAssistant->answer($message, $viewer, $request->getLocale());

        return $this->json($out);
    }

    // ——— Tableau de bord (stats globales + listes « mes » pour CRUD) ———

    #[Route('/community/dashboard/stats', name: 'api_community_dashboard_stats', methods: ['GET'])]
    public function dashboardStats(Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $myPosts = $this->posts->findByUserIdOrdered($actor);
        $myComments = $this->comments->findByUserIdOrdered($actor);

        return $this->json([
            'global' => $this->communityMetrics->buildGlobalStats(),
            'mine' => [
                'posts_count' => \count($myPosts),
                'comments_count' => \count($myComments),
            ],
            'session_user' => $this->serializeMe($actor),
        ]);
    }

    #[Route('/community/dashboard/my-posts', name: 'api_community_dashboard_my_posts', methods: ['GET'])]
    public function dashboardMyPosts(Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $tag = $this->sanitizeTagQuery($request->query->get('tag'));
        $q = $this->sanitizeSearchQuery($request->query->get('q'));
        $list = $this->posts->findByUserIdOrderedFiltered($actor, $tag, $q);

        return $this->json(['posts' => $this->serializePosts($list, $actor)]);
    }

    #[Route('/community/dashboard/my-comments', name: 'api_community_dashboard_my_comments', methods: ['GET'])]
    public function dashboardMyComments(Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $q = $this->sanitizeSearchQuery($request->query->get('q'));
        $list = $this->comments->findByUserIdOrderedFiltered($actor, $q);

        return $this->json(['comments' => $this->serializeComments($list, $actor)]);
    }

    // ——— Posts ———

    #[OA\Get(
        summary: 'Fil des posts actifs (pagination, filtres tag / auteur / titre)',
        parameters: [
            new OA\Parameter(name: 'page', in: 'query', schema: new OA\Schema(type: 'integer', default: 1)),
            new OA\Parameter(name: 'limit', in: 'query', schema: new OA\Schema(type: 'integer', default: 15, maximum: 50)),
            new OA\Parameter(name: 'tag', in: 'query', required: false, schema: new OA\Schema(type: 'string')),
            new OA\Parameter(name: 'user_id', in: 'query', required: false, schema: new OA\Schema(type: 'integer')),
            new OA\Parameter(name: 'q', in: 'query', required: false, description: 'Mots dans le titre', schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: '`posts` + `meta` (page, limit, total, pages)'),
        ]
    )]
    #[Route('/posts', name: 'api_posts_list', methods: ['GET'])]
    public function listPosts(Request $request): JsonResponse
    {
        $tag = $this->sanitizeTagQuery($request->query->get('tag'));
        $userFilter = $request->query->get('user_id');
        $userFilter = $userFilter !== null && $userFilter !== '' ? (int) $userFilter : null;
        if ($userFilter !== null && $userFilter < 1) {
            $userFilter = null;
        }
        $titleQ = $this->sanitizeSearchQuery($request->query->get('q'));
        $page = (int) $request->query->get('page', 1);
        $limit = (int) $request->query->get('limit', CommunityPostFeedService::DEFAULT_LIMIT);

        $feed = $this->communityPostFeed->getPublicFeedPage($page, $limit, $tag, $userFilter, $titleQ);
        $viewer = $this->getCommunityUserId($request);

        return $this->json([
            'posts' => $this->serializePosts($feed['items'], $viewer),
            'meta' => [
                'page' => $feed['page'],
                'limit' => $feed['limit'],
                'total' => $feed['total'],
                'pages' => $feed['pages'],
            ],
        ]);
    }

    #[OA\Post(
        summary: 'Créer un post (session utilisateur requise)',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['title'],
                properties: [
                    new OA\Property(property: 'title', type: 'string'),
                    new OA\Property(property: 'description', type: 'string', nullable: true),
                    new OA\Property(property: 'tag', type: 'string', nullable: true),
                    new OA\Property(property: 'image_url', type: 'string', format: 'uri', nullable: true),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: 'Post créé'),
            new OA\Response(response: 422, description: 'Erreurs de validation (Validator)'),
        ]
    )]
    #[Route('/posts', name: 'api_posts_create', methods: ['POST'])]
    public function createPost(Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $dto = CreatePostInput::fromRequestArray($data);
        $violations = $this->validator->validate($dto);
        if (\count($violations) > 0) {
            return $this->json([
                'message' => 'Données invalides.',
                'errors' => $this->formatValidationErrors($violations),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $post = new Post();
        $post->setUserId($actor);
        $post->setTitle($dto->title);
        $post->setDescription($dto->description);
        $post->setImageUrl($dto->image_url);
        $tag = $dto->tag !== null ? trim($dto->tag) : '';
        $post->setTag($tag !== '' ? $tag : 'General');
        $post->setIsActive(array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true);
        $now = new \DateTime();
        $post->setCreatedAt($now);
        $post->setUpdatedAt($now);

        $this->em->persist($post);
        $this->em->flush();

        return $this->json($this->serializePost($post, $actor), Response::HTTP_CREATED);
    }

    #[Route('/posts/{id}', name: 'api_posts_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function onePost(int $id, Request $request): JsonResponse
    {
        $post = $this->posts->find($id);
        if (!$post instanceof Post) {
            return $this->json(['message' => 'Post introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $viewer = $this->getCommunityUserId($request);
        if (!$this->canViewPost($post, $viewer)) {
            return $this->json(['message' => 'Post indisponible.'], Response::HTTP_NOT_FOUND);
        }

        $commentEntities = $this->comments->findByPostIdOrdered($id);
        $cids = array_filter(array_map(fn (Comment $c) => $c->getId(), $commentEntities));

        return $this->json([
            'post' => $this->serializePost($post, $viewer),
            'comments' => $this->serializeComments($commentEntities, $viewer),
        ]);
    }

    #[Route('/posts/{id}', name: 'api_posts_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function updatePost(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->posts->find($id);
        if (!$post instanceof Post) {
            return $this->json(['message' => 'Post introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ($post->getUserId() !== $actor) {
            return $this->json(['message' => 'Modification réservée à l’auteur du post.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('title', $data)) {
            $t = trim((string) $data['title']);
            if ($t === '') {
                return $this->json(['message' => 'Le titre ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            if (mb_strlen($t) > self::MAX_TITLE_LEN) {
                return $this->json(['message' => 'Titre trop long.'], Response::HTTP_BAD_REQUEST);
            }
            $post->setTitle($t);
        }
        if (array_key_exists('description', $data)) {
            $d = $data['description'] !== null ? (string) $data['description'] : null;
            if ($d !== null && mb_strlen($d) > self::MAX_DESC_LEN) {
                return $this->json(['message' => 'Description trop longue.'], Response::HTTP_BAD_REQUEST);
            }
            $post->setDescription($d);
        }
        if (array_key_exists('image_url', $data)) {
            $u = $data['image_url'] !== null ? trim((string) $data['image_url']) : null;
            if ($u !== null && $u !== '') {
                if (mb_strlen($u) > self::MAX_URL_LEN || !filter_var($u, \FILTER_VALIDATE_URL)) {
                    return $this->json(['message' => 'URL image invalide ou trop longue.'], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $u = null;
            }
            $post->setImageUrl($u);
        }
        if (array_key_exists('tag', $data)) {
            $t = $data['tag'] !== null ? trim((string) $data['tag']) : '';
            if (mb_strlen($t) > self::MAX_TAG_LEN) {
                return $this->json(['message' => 'Tag trop long.'], Response::HTTP_BAD_REQUEST);
            }
            $post->setTag($t !== '' ? $t : 'General');
        }
        if (array_key_exists('is_active', $data)) {
            $post->setIsActive((bool) $data['is_active']);
        }

        $post->setUpdatedAt(new \DateTime());
        $this->em->flush();

        return $this->json($this->serializePost($post, $actor));
    }

    #[Route('/posts/{id}', name: 'api_posts_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deletePost(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->posts->find($id);
        if (!$post instanceof Post) {
            return $this->json(['message' => 'Post introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ($post->getUserId() !== $actor) {
            return $this->json(['message' => 'Suppression réservée à l’auteur du post.'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($post);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    #[Route('/posts/{postId}/comments', name: 'api_comments_create', methods: ['POST'], requirements: ['postId' => '\d+'])]
    public function createComment(int $postId, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->posts->find($postId);
        if (!$post instanceof Post || !$this->isPostActive($post)) {
            return $this->json(['message' => 'Post introuvable ou inactif.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $content = isset($data['content']) ? trim((string) $data['content']) : '';
        if ($content === '') {
            return $this->json(['message' => 'Champ requis: content.'], Response::HTTP_BAD_REQUEST);
        }
        if (mb_strlen($content) > self::MAX_COMMENT_LEN) {
            return $this->json(['message' => 'Commentaire trop long.'], Response::HTTP_BAD_REQUEST);
        }

        $parentId = null;
        if (!empty($data['parent_comment_id'])) {
            $parentId = (int) $data['parent_comment_id'];
            $parent = $this->comments->find($parentId);
            if (!$parent instanceof Comment || $parent->getPostId() !== $postId) {
                return $this->json(['message' => 'Commentaire parent invalide.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $comment = new Comment();
        $comment->setPostId($postId);
        $comment->setUserId($actor);
        $comment->setContent($content);
        $comment->setParentCommentId($parentId);
        $comment->setIsActive(array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true);
        $now = new \DateTime();
        $comment->setCreatedAt($now);
        $comment->setUpdatedAt($now);

        $this->em->persist($comment);
        $this->em->flush();

        return $this->json($this->serializeComment($comment, $actor), Response::HTTP_CREATED);
    }

    #[Route('/comments/{id}', name: 'api_comments_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function oneComment(int $id, Request $request): JsonResponse
    {
        $comment = $this->comments->find($id);
        if (!$comment instanceof Comment) {
            return $this->json(['message' => 'Commentaire introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $post = $this->posts->find((int) $comment->getPostId());
        if (!$post instanceof Post) {
            return $this->json(['message' => 'Post introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $viewer = $this->getCommunityUserId($request);
        if (!$this->canViewPost($post, $viewer)) {
            return $this->json(['message' => 'Commentaire indisponible.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json(['comment' => $this->serializeComment($comment, $viewer)]);
    }

    #[Route('/comments/{id}', name: 'api_comments_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function updateComment(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $comment = $this->comments->find($id);
        if (!$comment instanceof Comment) {
            return $this->json(['message' => 'Commentaire introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ($comment->getUserId() !== $actor) {
            return $this->json(['message' => 'Modification réservée à l’auteur.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        if (array_key_exists('content', $data)) {
            $c = trim((string) $data['content']);
            if ($c === '') {
                return $this->json(['message' => 'Le contenu ne peut pas être vide.'], Response::HTTP_BAD_REQUEST);
            }
            if (mb_strlen($c) > self::MAX_COMMENT_LEN) {
                return $this->json(['message' => 'Commentaire trop long.'], Response::HTTP_BAD_REQUEST);
            }
            $comment->setContent($c);
        }
        if (array_key_exists('is_active', $data)) {
            $comment->setIsActive((bool) $data['is_active']);
        }

        $comment->setUpdatedAt(new \DateTime());
        $this->em->flush();

        return $this->json($this->serializeComment($comment, $actor));
    }

    #[Route('/comments/{id}', name: 'api_comments_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteComment(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $comment = $this->comments->find($id);
        if (!$comment instanceof Comment) {
            return $this->json(['message' => 'Commentaire introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ($comment->getUserId() !== $actor) {
            return $this->json(['message' => 'Suppression réservée à l’auteur.'], Response::HTTP_FORBIDDEN);
        }

        $this->em->remove($comment);
        $this->em->flush();

        return $this->json(['ok' => true]);
    }

    // ——— Votes (post_votes / comment_votes) ———

    #[Route('/posts/{id}/vote', name: 'api_post_vote', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function votePost(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->posts->find($id);
        if (!$post instanceof Post || !$this->isPostActive($post)) {
            return $this->json(['message' => 'Post introuvable ou inactif.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }
        $type = isset($data['vote_type']) ? (string) $data['vote_type'] : '';
        if (!\in_array($type, ['up', 'down'], true)) {
            return $this->json(['message' => 'vote_type doit être "up" ou "down".'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->postVotes->findOneByPostAndUser($id, $actor);
        if ($existing instanceof PostVote) {
            if ($existing->getVoteType() === $type) {
                $this->em->remove($existing);
            } else {
                $existing->setVoteType($type);
                $existing->setCreatedAt(new \DateTime());
            }
        } else {
            $v = new PostVote();
            $v->setPostId($id);
            $v->setUserId($actor);
            $v->setVoteType($type);
            $v->setCreatedAt(new \DateTime());
            $this->em->persist($v);
        }
        $this->em->flush();

        $fresh = $this->posts->find($id);
        if (!$fresh instanceof Post) {
            return $this->json(['message' => 'Erreur.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->serializePost($fresh, $actor));
    }

    #[Route('/comments/{id}/vote', name: 'api_comment_vote', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function voteComment(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $comment = $this->comments->find($id);
        if (!$comment instanceof Comment || !$this->isCommentActive($comment)) {
            return $this->json(['message' => 'Commentaire introuvable ou inactif.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }
        $type = isset($data['vote_type']) ? (string) $data['vote_type'] : '';
        if (!\in_array($type, ['up', 'down'], true)) {
            return $this->json(['message' => 'vote_type doit être "up" ou "down".'], Response::HTTP_BAD_REQUEST);
        }

        $existing = $this->commentVotes->findOneByCommentAndUser($id, $actor);
        if ($existing instanceof CommentVote) {
            if ($existing->getVoteType() === $type) {
                $this->em->remove($existing);
            } else {
                $existing->setVoteType($type);
                $existing->setCreatedAt(new \DateTime());
            }
        } else {
            $v = new CommentVote();
            $v->setCommentId($id);
            $v->setUserId($actor);
            $v->setVoteType($type);
            $v->setCreatedAt(new \DateTime());
            $this->em->persist($v);
        }
        $this->em->flush();

        $fresh = $this->comments->find($id);
        if (!$fresh instanceof Comment) {
            return $this->json(['message' => 'Erreur.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        return $this->json($this->serializeComment($fresh, $actor));
    }

    private function getCommunityUserId(Request $request): ?int
    {
        if (!$request->hasSession()) {
            return null;
        }
        $session = $request->getSession();
        $v = $session->get(self::SESSION_USER_KEY);
        if (\is_int($v)) {
            return $v > 0 ? $v : null;
        }
        if (\is_string($v) && ctype_digit($v)) {
            $n = (int) $v;

            return $n > 0 ? $n : null;
        }

        return null;
    }

    /**
     * @return JsonResponse|int user id
     */
    private function requireActor(Request $request): JsonResponse|int
    {
        $id = $this->getCommunityUserId($request);
        if ($id === null || $id < 1) {
            return $this->json(
                ['message' => 'Session utilisateur requise. Appelez POST /api/community/session avec un user_id valide (clé étrangère utilisateur).'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        if (!$this->utilisateurs->existsId($id)) {
            return $this->json(['message' => 'Session invalide: utilisateur inconnu.'], Response::HTTP_UNAUTHORIZED);
        }

        return $id;
    }

    private function canViewPost(Post $post, ?int $viewerId): bool
    {
        if ($this->isPostActive($post)) {
            return true;
        }

        return $viewerId !== null && $post->getUserId() === $viewerId;
    }

    private function isPostActive(Post $post): bool
    {
        $a = $post->isActive();

        return $a === null || $a === true;
    }

    private function isCommentActive(Comment $c): bool
    {
        $a = $c->isActive();

        return $a === null || $a === true;
    }

    /**
     * @param Post[] $posts
     * @return array<int, array<string, mixed>>
     */
    private function serializePosts(array $posts, ?int $viewerId): array
    {
        $ids = array_filter(array_map(fn (Post $p) => $p->getId(), $posts));
        $pids = $ids;
        $userIds = array_unique(array_filter(array_map(fn (Post $p) => $p->getUserId(), $posts)));
        $names = $this->utilisateurs->getDisplayNamesByIds($userIds);
        $counts = $this->comments->countActiveByPostIds($pids);
        $votes = $this->postVotes->sumVotesByPostIds($pids);

        $userVotes = $viewerId !== null && $pids !== []
            ? $this->postVotes->mapUserVotesOnPosts($viewerId, $pids)
            : [];

        $out = [];
        foreach ($posts as $p) {
            $pid = $p->getId();
            if ($pid === null) {
                continue;
            }
            $uid = $p->getUserId();
            $out[] = $this->serializePostCore($p, $names[$uid] ?? 'Utilisateur #'.$uid, $counts[$pid] ?? 0, $votes[$pid] ?? ['up' => 0, 'down' => 0], $userVotes[$pid] ?? null);
        }

        return $out;
    }

    private function serializePost(Post $p, ?int $viewerId): array
    {
        $pid = $p->getId();
        if ($pid === null) {
            return [];
        }
        $uid = $p->getUserId();
        $names = $this->utilisateurs->getDisplayNamesByIds([$uid]);
        $counts = $this->comments->countActiveByPostIds([$pid]);
        $votes = $this->postVotes->sumVotesByPostIds([$pid]);
        $uv = null;
        if ($viewerId !== null) {
            $pv = $this->postVotes->findOneByPostAndUser($pid, $viewerId);
            $uv = $pv?->getVoteType();
        }

        return $this->serializePostCore($p, $names[$uid] ?? 'Utilisateur #'.$uid, $counts[$pid] ?? 0, $votes[$pid] ?? ['up' => 0, 'down' => 0], $uv);
    }

    /**
     * @param array{up: int, down: int} $voteBlock
     * @return array<string, mixed>
     */
    private function serializePostCore(Post $p, string $authorName, int $commentsCount, array $voteBlock, ?string $userVote): array
    {
        $up = $voteBlock['up'];
        $down = $voteBlock['down'];
        $authorId = $p->getUserId();

        return [
            'id' => $p->getId(),
            'user_id' => $authorId,
            'author_name' => $authorName,
            'author_initials' => $this->buildInitials($authorName),
            'author_avatar_url' => $this->buildAvatarDataUri($authorName, $authorId),
            'title' => $p->getTitle(),
            'description' => $p->getDescription(),
            'image_url' => $p->getImageUrl(),
            'tag' => $p->getTag(),
            'is_active' => $p->isActive(),
            'comments_count' => $commentsCount,
            'votes_up' => $up,
            'votes_down' => $down,
            'score' => $up - $down,
            'user_vote' => $userVote,
            'created_at' => $p->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $p->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @param Comment[] $list
     * @return array<int, array<string, mixed>>
     */
    private function serializeComments(array $list, ?int $viewerId): array
    {
        $cids = array_filter(array_map(fn (Comment $c) => $c->getId(), $list));
        $userIds = array_unique(array_filter(array_map(fn (Comment $c) => $c->getUserId(), $list)));
        $names = $this->utilisateurs->getDisplayNamesByIds($userIds);
        $votes = $this->commentVotes->sumVotesByCommentIds($cids);

        $userVotes = $viewerId !== null && $cids !== []
            ? $this->commentVotes->mapUserVotesOnComments($viewerId, $cids)
            : [];

        $out = [];
        foreach ($list as $c) {
            $cid = $c->getId();
            if ($cid === null) {
                continue;
            }
            $uid = $c->getUserId();
            $out[] = $this->serializeCommentCore(
                $c,
                $names[$uid] ?? 'Utilisateur #'.$uid,
                $votes[$cid] ?? ['up' => 0, 'down' => 0],
                $userVotes[$cid] ?? null
            );
        }

        return $out;
    }

    private function serializeComment(Comment $c, ?int $viewerId): array
    {
        $cid = $c->getId();
        if ($cid === null) {
            return [];
        }
        $uid = $c->getUserId();
        $names = $this->utilisateurs->getDisplayNamesByIds([$uid]);
        $votes = $this->commentVotes->sumVotesByCommentIds([$cid]);
        $uv = null;
        if ($viewerId !== null) {
            $cv = $this->commentVotes->findOneByCommentAndUser($cid, $viewerId);
            $uv = $cv?->getVoteType();
        }

        return $this->serializeCommentCore($c, $names[$uid] ?? 'Utilisateur #'.$uid, $votes[$cid] ?? ['up' => 0, 'down' => 0], $uv);
    }

    /**
     * @param array{up: int, down: int} $voteBlock
     * @return array<string, mixed>
     */
    private function serializeCommentCore(Comment $c, string $authorName, array $voteBlock, ?string $userVote): array
    {
        $up = $voteBlock['up'];
        $down = $voteBlock['down'];
        $authorId = $c->getUserId();

        return [
            'id' => $c->getId(),
            'post_id' => $c->getPostId(),
            'user_id' => $authorId,
            'author_name' => $authorName,
            'author_initials' => $this->buildInitials($authorName),
            'author_avatar_url' => $this->buildAvatarDataUri($authorName, $authorId),
            'parent_comment_id' => $c->getParentCommentId(),
            'content' => $c->getContent(),
            'is_active' => $c->isActive(),
            'votes_up' => $up,
            'votes_down' => $down,
            'score' => $up - $down,
            'user_vote' => $userVote,
            'created_at' => $c->getCreatedAt()?->format(\DateTimeInterface::ATOM),
            'updated_at' => $c->getUpdatedAt()?->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMe(int $userId): array
    {
        $names = $this->utilisateurs->getDisplayNamesByIds([$userId]);
        $n = isset($names[$userId]) ? trim((string) $names[$userId]) : '';
        if ($n === '') {
            $n = (string) $this->getParameter('app.community_profile_fallback_name');
        }

        return [
            'user_id' => $userId,
            'name' => $n,
            'initials' => $this->buildInitials($n),
            'avatar_url' => $this->buildAvatarDataUri($n, $userId),
        ];
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

        if ($letters === []) {
            return mb_strtoupper(mb_substr($name, 0, 1));
        }

        return implode('', $letters);
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
        $initials = htmlspecialchars($this->buildInitials($name), \ENT_QUOTES | \ENT_SUBSTITUTE, 'UTF-8');

        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="160" height="160" viewBox="0 0 160 160" role="img" aria-label="Avatar">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="$start"/>
      <stop offset="100%" stop-color="$end"/>
    </linearGradient>
  </defs>
  <rect width="160" height="160" rx="80" fill="url(#g)"/>
  <circle cx="80" cy="80" r="74" fill="none" stroke="rgba(255,255,255,0.18)" stroke-width="4"/>
  <text x="50%" y="54%" text-anchor="middle" dominant-baseline="middle" font-family="Segoe UI, Arial, sans-serif" font-size="56" font-weight="700" fill="#ffffff">$initials</text>
</svg>
SVG;

        return 'data:image/svg+xml;charset=UTF-8,'.rawurlencode($svg);
    }

    private function sanitizeSearchQuery(mixed $q): ?string
    {
        if (!\is_string($q)) {
            return null;
        }
        $q = trim($q);
        if ($q === '') {
            return null;
        }
        if (mb_strlen($q) > self::MAX_SEARCH_LEN) {
            $q = mb_substr($q, 0, self::MAX_SEARCH_LEN);
        }

        return $q;
    }

    private function sanitizeTagQuery(mixed $tag): ?string
    {
        if (!\is_string($tag)) {
            return null;
        }
        $tag = trim($tag);
        if ($tag === '') {
            return null;
        }
        if (mb_strlen($tag) > self::MAX_TAG_LEN) {
            return mb_substr($tag, 0, self::MAX_TAG_LEN);
        }

        return $tag;
    }

    /**
     * @return array<string, list<string>>
     */
    private function formatValidationErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $v) {
            $path = $v->getPropertyPath() !== '' ? $v->getPropertyPath() : '_global';
            $errors[$path][] = $v->getMessage();
        }

        return $errors;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(Request $request): ?array
    {
        $raw = $request->getContent();
        if ($raw === '' || $raw === '0') {
            return [];
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_array($data) ? $data : null;
    }
}
