<?php

namespace App\Controller;

use App\Dto\Community\CreatePostInput;
use App\Service\Community\CommunityPostFeedService;
use App\Service\Community\CommunityStore;
use App\Service\CommunityAssistant;
use App\Service\CommunityMetrics;
use App\Service\TunisWeatherService;
use OpenApi\Attributes as OA;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api')]
#[OA\Tag(name: 'Communaute')]
class CommunityApiController extends AbstractController
{
    public const SESSION_USER_KEY = 'community_uid';

    private const MAX_TITLE_LEN = 255;
    private const MAX_DESC_LEN = 10000;
    private const MAX_TAG_LEN = 80;
    private const MAX_URL_LEN = 2048;
    private const MAX_COMMENT_LEN = 8000;
    private const MAX_SEARCH_LEN = 120;

    public function __construct(
        private readonly CommunityStore $store,
        private readonly CommunityMetrics $communityMetrics,
        private readonly TunisWeatherService $tunisWeather,
        private readonly CommunityAssistant $communityAssistant,
        private readonly ValidatorInterface $validator,
        private readonly CommunityPostFeedService $communityPostFeed,
    ) {
    }

    #[Route('/community/session', name: 'api_community_session_set', methods: ['POST'])]
    public function setCommunitySession(Request $request): JsonResponse
    {
        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        if ($userId < 1 || !$this->store->userExists($userId)) {
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
        if ($uid === null || !$this->store->userExists($uid)) {
            return $this->json(['user' => null]);
        }

        return $this->json(['user' => $this->serializeMe($uid)]);
    }

    #[Route('/community/weather', name: 'api_community_weather', methods: ['GET'])]
    public function weather(): JsonResponse
    {
        $weather = $this->tunisWeather->fetchCurrent();
        if ($weather === null) {
            return $this->json(['message' => 'Meteo indisponible pour le moment.'], Response::HTTP_SERVICE_UNAVAILABLE);
        }

        return $this->json(['weather' => $weather]);
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

        return $this->json(
            $this->communityAssistant->answer($message, $this->getCommunityUserId($request), $request->getLocale())
        );
    }

    #[Route('/community/dashboard/stats', name: 'api_community_dashboard_stats', methods: ['GET'])]
    public function dashboardStats(Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        return $this->json([
            'global' => $this->communityMetrics->buildGlobalStats(),
            'mine' => [
                'posts_count' => \count($this->store->findUserPostsFiltered($actor, null, null)),
                'comments_count' => \count($this->store->findUserCommentsFiltered($actor, null)),
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

        $posts = $this->store->findUserPostsFiltered(
            $actor,
            $this->sanitizeTagQuery($request->query->get('tag')),
            $this->sanitizeSearchQuery($request->query->get('q'))
        );

        return $this->json(['posts' => $this->serializePosts($posts, $actor)]);
    }

    #[Route('/community/dashboard/my-comments', name: 'api_community_dashboard_my_comments', methods: ['GET'])]
    public function dashboardMyComments(Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $comments = $this->store->findUserCommentsFiltered(
            $actor,
            $this->sanitizeSearchQuery($request->query->get('q'))
        );

        return $this->json(['comments' => $this->serializeComments($comments, $actor)]);
    }

    #[Route('/posts', name: 'api_posts_list', methods: ['GET'])]
    public function listPosts(Request $request): JsonResponse
    {
        $userFilter = $request->query->get('user_id');
        $userFilter = $userFilter !== null && $userFilter !== '' ? (int) $userFilter : null;
        if ($userFilter !== null && $userFilter < 1) {
            $userFilter = null;
        }

        $feed = $this->communityPostFeed->getPublicFeedPage(
            (int) $request->query->get('page', 1),
            (int) $request->query->get('limit', CommunityPostFeedService::DEFAULT_LIMIT),
            $this->sanitizeTagQuery($request->query->get('tag')),
            $userFilter,
            $this->sanitizeSearchQuery($request->query->get('q'))
        );

        return $this->json([
            'posts' => $this->serializePosts($feed['items'], $this->getCommunityUserId($request)),
            'meta' => [
                'page' => $feed['page'],
                'limit' => $feed['limit'],
                'total' => $feed['total'],
                'pages' => $feed['pages'],
            ],
        ]);
    }

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
                'message' => 'Donnees invalides.',
                'errors' => $this->formatValidationErrors($violations),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $tag = $dto->tag !== null ? trim($dto->tag) : '';
        $post = $this->store->createPost(
            $actor,
            $dto->title,
            $dto->description,
            $dto->image_url,
            $tag !== '' ? $tag : 'General',
            array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true
        );

        return $this->json($this->serializePost($post, $actor), Response::HTTP_CREATED);
    }

    #[Route('/posts/{id}', name: 'api_posts_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function onePost(int $id, Request $request): JsonResponse
    {
        $post = $this->store->findPostById($id);
        if ($post === null) {
            return $this->json(['message' => 'Post introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $viewer = $this->getCommunityUserId($request);
        if (!$this->canViewPost($post, $viewer)) {
            return $this->json(['message' => 'Post indisponible.'], Response::HTTP_NOT_FOUND);
        }

        return $this->json([
            'post' => $this->serializePost($post, $viewer),
            'comments' => $this->serializeComments($this->store->findCommentsByPostIdOrdered($id), $viewer),
        ]);
    }

    #[Route('/posts/{id}', name: 'api_posts_update', methods: ['PUT', 'PATCH'], requirements: ['id' => '\d+'])]
    public function updatePost(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->store->findPostById($id);
        if ($post === null) {
            return $this->json(['message' => 'Post introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ((int) $post['user_id'] !== $actor) {
            return $this->json(['message' => 'Modification reservee a l auteur du post.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $changes = [];
        if (array_key_exists('title', $data)) {
            $title = trim((string) $data['title']);
            if ($title === '') {
                return $this->json(['message' => 'Le titre ne peut pas etre vide.'], Response::HTTP_BAD_REQUEST);
            }
            if (mb_strlen($title) > self::MAX_TITLE_LEN) {
                return $this->json(['message' => 'Titre trop long.'], Response::HTTP_BAD_REQUEST);
            }
            $changes['title'] = $title;
        }
        if (array_key_exists('description', $data)) {
            $description = $data['description'] !== null ? (string) $data['description'] : null;
            if ($description !== null && mb_strlen($description) > self::MAX_DESC_LEN) {
                return $this->json(['message' => 'Description trop longue.'], Response::HTTP_BAD_REQUEST);
            }
            $changes['description'] = $description;
        }
        if (array_key_exists('image_url', $data)) {
            $imageUrl = $data['image_url'] !== null ? trim((string) $data['image_url']) : null;
            if ($imageUrl !== null && $imageUrl !== '') {
                if (mb_strlen($imageUrl) > self::MAX_URL_LEN || !filter_var($imageUrl, \FILTER_VALIDATE_URL)) {
                    return $this->json(['message' => 'URL image invalide ou trop longue.'], Response::HTTP_BAD_REQUEST);
                }
            } else {
                $imageUrl = null;
            }
            $changes['image_url'] = $imageUrl;
        }
        if (array_key_exists('tag', $data)) {
            $tag = $data['tag'] !== null ? trim((string) $data['tag']) : '';
            if (mb_strlen($tag) > self::MAX_TAG_LEN) {
                return $this->json(['message' => 'Tag trop long.'], Response::HTTP_BAD_REQUEST);
            }
            $changes['tag'] = $tag !== '' ? $tag : 'General';
        }
        if (array_key_exists('is_active', $data)) {
            $changes['is_active'] = (bool) $data['is_active'];
        }

        return $this->json($this->serializePost($this->store->updatePost($id, $changes) ?? $post, $actor));
    }

    #[Route('/posts/{id}', name: 'api_posts_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deletePost(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->store->findPostById($id);
        if ($post === null) {
            return $this->json(['message' => 'Post introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ((int) $post['user_id'] !== $actor) {
            return $this->json(['message' => 'Suppression reservee a l auteur du post.'], Response::HTTP_FORBIDDEN);
        }

        $this->store->deletePost($id);

        return $this->json(['ok' => true]);
    }

    #[Route('/posts/{postId}/comments', name: 'api_comments_create', methods: ['POST'], requirements: ['postId' => '\d+'])]
    public function createComment(int $postId, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->store->findPostById($postId);
        if ($post === null || !$this->isPostActive($post)) {
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
            $parent = $this->store->findCommentById($parentId);
            if ($parent === null || (int) $parent['post_id'] !== $postId) {
                return $this->json(['message' => 'Commentaire parent invalide.'], Response::HTTP_BAD_REQUEST);
            }
        }

        $comment = $this->store->createComment(
            $postId,
            $actor,
            $content,
            $parentId,
            array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true
        );

        return $this->json($this->serializeComment($comment, $actor), Response::HTTP_CREATED);
    }

    #[Route('/comments/{id}', name: 'api_comments_one', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function oneComment(int $id, Request $request): JsonResponse
    {
        $comment = $this->store->findCommentById($id);
        if ($comment === null) {
            return $this->json(['message' => 'Commentaire introuvable.'], Response::HTTP_NOT_FOUND);
        }

        $post = $this->store->findPostById((int) $comment['post_id']);
        if ($post === null) {
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

        $comment = $this->store->findCommentById($id);
        if ($comment === null) {
            return $this->json(['message' => 'Commentaire introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ((int) $comment['user_id'] !== $actor) {
            return $this->json(['message' => 'Modification reservee a l auteur.'], Response::HTTP_FORBIDDEN);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $changes = [];
        if (array_key_exists('content', $data)) {
            $content = trim((string) $data['content']);
            if ($content === '') {
                return $this->json(['message' => 'Le contenu ne peut pas etre vide.'], Response::HTTP_BAD_REQUEST);
            }
            if (mb_strlen($content) > self::MAX_COMMENT_LEN) {
                return $this->json(['message' => 'Commentaire trop long.'], Response::HTTP_BAD_REQUEST);
            }
            $changes['content'] = $content;
        }
        if (array_key_exists('is_active', $data)) {
            $changes['is_active'] = (bool) $data['is_active'];
        }

        return $this->json($this->serializeComment($this->store->updateComment($id, $changes) ?? $comment, $actor));
    }

    #[Route('/comments/{id}', name: 'api_comments_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteComment(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $comment = $this->store->findCommentById($id);
        if ($comment === null) {
            return $this->json(['message' => 'Commentaire introuvable.'], Response::HTTP_NOT_FOUND);
        }
        if ((int) $comment['user_id'] !== $actor) {
            return $this->json(['message' => 'Suppression reservee a l auteur.'], Response::HTTP_FORBIDDEN);
        }

        $this->store->deleteComment($id);

        return $this->json(['ok' => true]);
    }

    #[Route('/posts/{id}/vote', name: 'api_post_vote', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function votePost(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $post = $this->store->findPostById($id);
        if ($post === null || !$this->isPostActive($post)) {
            return $this->json(['message' => 'Post introuvable ou inactif.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $type = isset($data['vote_type']) ? (string) $data['vote_type'] : '';
        if (!\in_array($type, ['up', 'down'], true)) {
            return $this->json(['message' => 'vote_type doit etre "up" ou "down".'], Response::HTTP_BAD_REQUEST);
        }

        $this->store->savePostVote($id, $actor, $type);

        return $this->json($this->serializePost($this->store->findPostById($id) ?? $post, $actor));
    }

    #[Route('/comments/{id}/vote', name: 'api_comment_vote', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function voteComment(int $id, Request $request): JsonResponse
    {
        $actor = $this->requireActor($request);
        if ($actor instanceof JsonResponse) {
            return $actor;
        }

        $comment = $this->store->findCommentById($id);
        if ($comment === null || !$this->isCommentActive($comment)) {
            return $this->json(['message' => 'Commentaire introuvable ou inactif.'], Response::HTTP_NOT_FOUND);
        }

        $data = $this->decodeJson($request);
        if ($data === null) {
            return $this->json(['message' => 'Corps JSON invalide.'], Response::HTTP_BAD_REQUEST);
        }

        $type = isset($data['vote_type']) ? (string) $data['vote_type'] : '';
        if (!\in_array($type, ['up', 'down'], true)) {
            return $this->json(['message' => 'vote_type doit etre "up" ou "down".'], Response::HTTP_BAD_REQUEST);
        }

        $this->store->saveCommentVote($id, $actor, $type);

        return $this->json($this->serializeComment($this->store->findCommentById($id) ?? $comment, $actor));
    }

    private function getCommunityUserId(Request $request): ?int
    {
        if (!$request->hasSession()) {
            return null;
        }

        $value = $request->getSession()->get(self::SESSION_USER_KEY);
        if (\is_int($value)) {
            return $value > 0 ? $value : null;
        }
        if (\is_string($value) && ctype_digit($value)) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function requireActor(Request $request): JsonResponse|int
    {
        $id = $this->getCommunityUserId($request);
        if ($id === null || $id < 1) {
            return $this->json(
                ['message' => 'Session utilisateur requise. Appelez POST /api/community/session avec un user_id valide.'],
                Response::HTTP_UNAUTHORIZED
            );
        }
        if (!$this->store->userExists($id)) {
            return $this->json(['message' => 'Session invalide: utilisateur inconnu.'], Response::HTTP_UNAUTHORIZED);
        }

        return $id;
    }

    /**
     * @param array<string, mixed> $post
     */
    private function canViewPost(array $post, ?int $viewerId): bool
    {
        if ($this->isPostActive($post)) {
            return true;
        }

        return $viewerId !== null && (int) $post['user_id'] === $viewerId;
    }

    /**
     * @param array<string, mixed> $post
     */
    private function isPostActive(array $post): bool
    {
        return $post['is_active'] === null || $post['is_active'] === true;
    }

    /**
     * @param array<string, mixed> $comment
     */
    private function isCommentActive(array $comment): bool
    {
        return $comment['is_active'] === null || $comment['is_active'] === true;
    }

    /**
     * @param list<array<string, mixed>> $posts
     * @return array<int, array<string, mixed>>
     */
    private function serializePosts(array $posts, ?int $viewerId): array
    {
        $postIds = [];
        $userIds = [];
        foreach ($posts as $post) {
            $postIds[] = (int) $post['id'];
            $userIds[] = (int) $post['user_id'];
        }

        $names = $this->store->getDisplayNamesByIds($userIds);
        $counts = $this->store->countActiveCommentsByPostIds($postIds);
        $votes = $this->store->sumVotesByPostIds($postIds);
        $userVotes = $viewerId !== null ? $this->store->mapUserVotesOnPosts($viewerId, $postIds) : [];

        $out = [];
        foreach ($posts as $post) {
            $postId = (int) $post['id'];
            $authorId = (int) $post['user_id'];
            $out[] = $this->serializePostCore(
                $post,
                $names[$authorId] ?? 'Utilisateur #'.$authorId,
                $counts[$postId] ?? 0,
                $votes[$postId] ?? ['up' => 0, 'down' => 0],
                $userVotes[$postId] ?? null
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $post
     * @return array<string, mixed>
     */
    private function serializePost(array $post, ?int $viewerId): array
    {
        $postId = (int) $post['id'];
        $authorId = (int) $post['user_id'];
        $names = $this->store->getDisplayNamesByIds([$authorId]);
        $counts = $this->store->countActiveCommentsByPostIds([$postId]);
        $votes = $this->store->sumVotesByPostIds([$postId]);
        $userVote = $viewerId !== null ? $this->store->findPostVoteTypeByUser($postId, $viewerId) : null;

        return $this->serializePostCore(
            $post,
            $names[$authorId] ?? 'Utilisateur #'.$authorId,
            $counts[$postId] ?? 0,
            $votes[$postId] ?? ['up' => 0, 'down' => 0],
            $userVote
        );
    }

    /**
     * @param array<string, mixed> $post
     * @param array{up: int, down: int} $voteBlock
     * @return array<string, mixed>
     */
    private function serializePostCore(array $post, string $authorName, int $commentsCount, array $voteBlock, ?string $userVote): array
    {
        $authorId = (int) $post['user_id'];
        $up = $voteBlock['up'];
        $down = $voteBlock['down'];

        return [
            'id' => (int) $post['id'],
            'user_id' => $authorId,
            'author_name' => $authorName,
            'author_initials' => $this->buildInitials($authorName),
            'author_avatar_url' => $this->buildAvatarDataUri($authorName, $authorId),
            'title' => (string) $post['title'],
            'description' => $post['description'],
            'image_url' => $post['image_url'],
            'tag' => $post['tag'],
            'is_active' => $post['is_active'],
            'comments_count' => $commentsCount,
            'votes_up' => $up,
            'votes_down' => $down,
            'score' => $up - $down,
            'user_vote' => $userVote,
            'created_at' => $this->formatSqlDateAtom($post['created_at']),
            'updated_at' => $this->formatSqlDateAtom($post['updated_at']),
        ];
    }

    /**
     * @param list<array<string, mixed>> $comments
     * @return array<int, array<string, mixed>>
     */
    private function serializeComments(array $comments, ?int $viewerId): array
    {
        $commentIds = [];
        $userIds = [];
        foreach ($comments as $comment) {
            $commentIds[] = (int) $comment['id'];
            $userIds[] = (int) $comment['user_id'];
        }

        $names = $this->store->getDisplayNamesByIds($userIds);
        $votes = $this->store->sumVotesByCommentIds($commentIds);
        $userVotes = $viewerId !== null ? $this->store->mapUserVotesOnComments($viewerId, $commentIds) : [];

        $out = [];
        foreach ($comments as $comment) {
            $commentId = (int) $comment['id'];
            $authorId = (int) $comment['user_id'];
            $out[] = $this->serializeCommentCore(
                $comment,
                $names[$authorId] ?? 'Utilisateur #'.$authorId,
                $votes[$commentId] ?? ['up' => 0, 'down' => 0],
                $userVotes[$commentId] ?? null
            );
        }

        return $out;
    }

    /**
     * @param array<string, mixed> $comment
     * @return array<string, mixed>
     */
    private function serializeComment(array $comment, ?int $viewerId): array
    {
        $commentId = (int) $comment['id'];
        $authorId = (int) $comment['user_id'];
        $names = $this->store->getDisplayNamesByIds([$authorId]);
        $votes = $this->store->sumVotesByCommentIds([$commentId]);
        $userVote = $viewerId !== null ? $this->store->findCommentVoteTypeByUser($commentId, $viewerId) : null;

        return $this->serializeCommentCore(
            $comment,
            $names[$authorId] ?? 'Utilisateur #'.$authorId,
            $votes[$commentId] ?? ['up' => 0, 'down' => 0],
            $userVote
        );
    }

    /**
     * @param array<string, mixed> $comment
     * @param array{up: int, down: int} $voteBlock
     * @return array<string, mixed>
     */
    private function serializeCommentCore(array $comment, string $authorName, array $voteBlock, ?string $userVote): array
    {
        $authorId = (int) $comment['user_id'];
        $up = $voteBlock['up'];
        $down = $voteBlock['down'];

        return [
            'id' => (int) $comment['id'],
            'post_id' => (int) $comment['post_id'],
            'user_id' => $authorId,
            'author_name' => $authorName,
            'author_initials' => $this->buildInitials($authorName),
            'author_avatar_url' => $this->buildAvatarDataUri($authorName, $authorId),
            'parent_comment_id' => $comment['parent_comment_id'],
            'content' => (string) $comment['content'],
            'is_active' => $comment['is_active'],
            'votes_up' => $up,
            'votes_down' => $down,
            'score' => $up - $down,
            'user_vote' => $userVote,
            'created_at' => $this->formatSqlDateAtom($comment['created_at']),
            'updated_at' => $this->formatSqlDateAtom($comment['updated_at']),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMe(int $userId): array
    {
        $names = $this->store->getDisplayNamesByIds([$userId]);
        $name = trim((string) ($names[$userId] ?? ''));
        if ($name === '') {
            $name = (string) $this->getParameter('app.community_profile_fallback_name');
        }

        return [
            'user_id' => $userId,
            'name' => $name,
            'initials' => $this->buildInitials($name),
            'avatar_url' => $this->buildAvatarDataUri($name, $userId),
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

        return $letters === [] ? mb_strtoupper(mb_substr($name, 0, 1)) : implode('', $letters);
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

    private function sanitizeSearchQuery(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_strlen($value) > self::MAX_SEARCH_LEN ? mb_substr($value, 0, self::MAX_SEARCH_LEN) : $value;
    }

    private function sanitizeTagQuery(mixed $value): ?string
    {
        if (!\is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        return mb_strlen($value) > self::MAX_TAG_LEN ? mb_substr($value, 0, self::MAX_TAG_LEN) : $value;
    }

    /**
     * @return array<string, list<string>>
     */
    private function formatValidationErrors(ConstraintViolationListInterface $violations): array
    {
        $errors = [];
        foreach ($violations as $violation) {
            $path = $violation->getPropertyPath() !== '' ? $violation->getPropertyPath() : '_global';
            $errors[$path][] = $violation->getMessage();
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

    private function formatSqlDateAtom(mixed $value): ?string
    {
        if (!\is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return (new \DateTimeImmutable($value))->format(\DateTimeInterface::ATOM);
        } catch (\Exception) {
            return $value;
        }
    }
}
