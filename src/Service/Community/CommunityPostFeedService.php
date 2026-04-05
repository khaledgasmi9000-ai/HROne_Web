<?php

namespace App\Service\Community;

use App\Entity\Post;
use App\Repository\PostRepository;

/**
 * Cas d’usage métier : fil d’actualité public avec pagination (couche application au-dessus du repository).
 */
class CommunityPostFeedService
{
    public const DEFAULT_LIMIT = 15;

    public const MAX_LIMIT = 50;

    public function __construct(
        private readonly PostRepository $posts,
    ) {
    }

    /**
     * @return array{items: list<Post>, total: int, page: int, limit: int, pages: int}
     */
    public function getPublicFeedPage(int $page, int $limit, ?string $tag, ?int $userId, ?string $titleSearch): array
    {
        $limit = max(1, min($limit, self::MAX_LIMIT));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        $total = $this->posts->countFeedOrdered($tag, $userId, $titleSearch);
        $items = $this->posts->findFeedOrderedPaged($tag, $userId, $titleSearch, $offset, $limit);
        $pages = $total > 0 ? (int) ceil($total / $limit) : 1;

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }
}
