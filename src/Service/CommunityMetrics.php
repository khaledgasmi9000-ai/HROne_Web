<?php

namespace App\Service;

use App\Repository\CommentRepository;
use App\Repository\CommentVoteRepository;
use App\Repository\PostRepository;
use App\Repository\PostVoteRepository;

/**
 * Agrégats métier pour le tableau de bord communauté (réutilisé API + PDF).
 */
class CommunityMetrics
{
    public function __construct(
        private readonly PostRepository $posts,
        private readonly CommentRepository $comments,
        private readonly PostVoteRepository $postVotes,
        private readonly CommentVoteRepository $commentVotes,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function buildGlobalStats(): array
    {
        return [
            'posts_total' => $this->posts->countAll(),
            'posts_active_public' => $this->posts->countActivePublic(),
            'posts_inactive' => $this->posts->countInactive(),
            'posts_distinct_authors' => $this->posts->countDistinctAuthors(),
            'comments_total' => $this->comments->countAll(),
            'comments_active_public' => $this->comments->countActivePublic(),
            'comments_inactive' => $this->comments->countInactive(),
            'comments_roots' => $this->comments->countRootComments(),
            'comments_replies' => $this->comments->countReplyComments(),
            'votes_posts_total' => $this->postVotes->countAll(),
            'votes_comments_total' => $this->commentVotes->countAll(),
            'tags_top' => $this->posts->countGroupedByTag(15),
        ];
    }
}
