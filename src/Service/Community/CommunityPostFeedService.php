<?php

namespace App\Service\Community;

/**
 * Cas d’usage métier : fil d’actualité public avec pagination (couche application au-dessus du repository).
 */
class CommunityPostFeedService
{
    public const DEFAULT_LIMIT = 15;

    public const MAX_LIMIT = 50;

    public function __construct(
        private readonly CommunityStore $store,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, limit: int, pages: int}
     */
    public function getPublicFeedPage(int $page, int $limit, ?string $tag, ?int $userId, ?string $titleSearch): array
    {
        return $this->store->getPublicFeedPage($page, $limit, $tag, $userId, $titleSearch);
    }
}
