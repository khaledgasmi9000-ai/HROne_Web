<?php

namespace App\Service;

use App\Service\Community\CommunityStore;

/**
 * Agrégats métier pour le tableau de bord communauté (réutilisé API + PDF).
 */
class CommunityMetrics
{
    public function __construct(
        private readonly CommunityStore $store,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildGlobalStats(): array
    {
        return [
            'posts_total' => $this->store->countPostsAll(),
            'posts_active_public' => $this->store->countPostsActivePublic(),
            'posts_inactive' => $this->store->countPostsInactive(),
            'posts_distinct_authors' => $this->store->countDistinctPostAuthors(),
            'comments_total' => $this->store->countCommentsAll(),
            'comments_active_public' => $this->store->countCommentsActivePublic(),
            'comments_inactive' => $this->store->countCommentsInactive(),
            'comments_roots' => $this->store->countRootComments(),
            'comments_replies' => $this->store->countReplyComments(),
            'votes_posts_total' => $this->store->countPostVotesAll(),
            'votes_comments_total' => $this->store->countCommentVotesAll(),
            'tags_top' => $this->store->countGroupedByTag(15),
        ];
    }
}
