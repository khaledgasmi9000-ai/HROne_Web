<?php

namespace App\Service\Community;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;

class CommunityStore
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function userExists(int $userId): bool
    {
        if ($userId < 1) {
            return false;
        }

        $value = $this->connection->fetchOne(
            'SELECT 1 FROM utilisateur WHERE ID_UTILISATEUR = ? LIMIT 1',
            [$userId]
        );

        return $value !== false && $value !== null;
    }

    /**
     * @param int[] $ids
     * @return array<int, string>
     */
    public function getDisplayNamesByIds(array $ids): array
    {
        $ids = $this->sanitizeIds($ids);
        if ($ids === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT ID_UTILISATEUR, Nom_Utilisateur FROM utilisateur WHERE ID_UTILISATEUR IN (?)',
            [$ids],
            [ArrayParameterType::INTEGER]
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['ID_UTILISATEUR']] = trim((string) ($row['Nom_Utilisateur'] ?? ''));
        }

        return $out;
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, limit: int, pages: int}
     */
    public function getPublicFeedPage(int $page, int $limit, ?string $tag, ?int $userId, ?string $titleSearch): array
    {
        $limit = max(1, min($limit, CommunityPostFeedService::MAX_LIMIT));
        $page = max(1, $page);
        $offset = ($page - 1) * $limit;
        [$whereSql, $params] = $this->buildPostFeedWhere($tag, $userId, $titleSearch);

        $total = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM posts p '.$whereSql,
            $params
        );

        $rows = $this->connection->fetchAllAssociative(
            'SELECT p.* FROM posts p '.$whereSql.' ORDER BY p.created_at DESC LIMIT '.$limit.' OFFSET '.$offset,
            $params
        );

        $pages = $total > 0 ? (int) ceil($total / $limit) : 1;

        return [
            'items' => array_map($this->normalizePostRow(...), $rows),
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => $pages,
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findUserPostsFiltered(int $userId, ?string $tag, ?string $titleSearch): array
    {
        $sql = 'SELECT * FROM posts WHERE user_id = ?';
        $params = [$userId];

        if ($tag !== null && $tag !== '') {
            $sql .= ' AND tag = ?';
            $params[] = $tag;
        }

        if ($titleSearch !== null && $titleSearch !== '') {
            $sql .= ' AND LOWER(title) LIKE ?';
            $params[] = '%'.mb_strtolower($this->escapeLikePattern($titleSearch)).'%';
        }

        $sql .= ' ORDER BY created_at DESC';

        return array_map($this->normalizePostRow(...), $this->connection->fetchAllAssociative($sql, $params));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findUserCommentsFiltered(int $userId, ?string $contentSearch): array
    {
        $sql = 'SELECT * FROM comments WHERE user_id = ?';
        $params = [$userId];

        if ($contentSearch !== null && $contentSearch !== '') {
            $sql .= ' AND LOWER(content) LIKE ?';
            $params[] = '%'.mb_strtolower($this->escapeLikePattern($contentSearch)).'%';
        }

        $sql .= ' ORDER BY created_at DESC';

        return array_map($this->normalizeCommentRow(...), $this->connection->fetchAllAssociative($sql, $params));
    }

    public function findPostById(int $postId): ?array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM posts WHERE id = ? LIMIT 1', [$postId]);

        return $row === false ? null : $this->normalizePostRow($row);
    }

    public function findCommentById(int $commentId): ?array
    {
        $row = $this->connection->fetchAssociative('SELECT * FROM comments WHERE id = ? LIMIT 1', [$commentId]);

        return $row === false ? null : $this->normalizeCommentRow($row);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function findCommentsByPostIdOrdered(int $postId): array
    {
        return array_map(
            $this->normalizeCommentRow(...),
            $this->connection->fetchAllAssociative(
                'SELECT * FROM comments WHERE post_id = ? ORDER BY created_at ASC',
                [$postId]
            )
        );
    }

    public function createPost(int $userId, string $title, ?string $description, ?string $imageUrl, string $tag, bool $isActive): array
    {
        $now = $this->nowString();
        $this->connection->insert('posts', [
            'user_id' => $userId,
            'title' => $title,
            'description' => $description,
            'image_url' => $imageUrl,
            'tag' => $tag,
            'is_active' => $isActive ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findPostById((int) $this->connection->lastInsertId()) ?? [];
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function updatePost(int $postId, array $changes): ?array
    {
        $payload = [];
        foreach (['title', 'description', 'image_url', 'tag'] as $column) {
            if (array_key_exists($column, $changes)) {
                $payload[$column] = $changes[$column];
            }
        }
        if (array_key_exists('is_active', $changes)) {
            $payload['is_active'] = $changes['is_active'] === null ? null : ((bool) $changes['is_active'] ? 1 : 0);
        }
        if ($payload === []) {
            return $this->findPostById($postId);
        }

        $payload['updated_at'] = $this->nowString();
        $this->connection->update('posts', $payload, ['id' => $postId]);

        return $this->findPostById($postId);
    }

    public function deletePost(int $postId): void
    {
        $commentIds = $this->sanitizeIds($this->connection->fetchFirstColumn('SELECT id FROM comments WHERE post_id = ?', [$postId]));
        if ($commentIds !== []) {
            $this->connection->executeStatement(
                'DELETE FROM comment_votes WHERE comment_id IN (?)',
                [$commentIds],
                [ArrayParameterType::INTEGER]
            );
            $this->connection->executeStatement(
                'DELETE FROM comments WHERE id IN (?)',
                [$commentIds],
                [ArrayParameterType::INTEGER]
            );
        }

        $this->connection->delete('post_votes', ['post_id' => $postId]);
        $this->connection->delete('posts', ['id' => $postId]);
    }

    public function createComment(int $postId, int $userId, string $content, ?int $parentCommentId, bool $isActive): array
    {
        $now = $this->nowString();
        $this->connection->insert('comments', [
            'post_id' => $postId,
            'user_id' => $userId,
            'parent_comment_id' => $parentCommentId,
            'content' => $content,
            'is_active' => $isActive ? 1 : 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->findCommentById((int) $this->connection->lastInsertId()) ?? [];
    }

    /**
     * @param array<string, mixed> $changes
     */
    public function updateComment(int $commentId, array $changes): ?array
    {
        $payload = [];
        if (array_key_exists('content', $changes)) {
            $payload['content'] = $changes['content'];
        }
        if (array_key_exists('is_active', $changes)) {
            $payload['is_active'] = $changes['is_active'] === null ? null : ((bool) $changes['is_active'] ? 1 : 0);
        }
        if ($payload === []) {
            return $this->findCommentById($commentId);
        }

        $payload['updated_at'] = $this->nowString();
        $this->connection->update('comments', $payload, ['id' => $commentId]);

        return $this->findCommentById($commentId);
    }

    public function deleteComment(int $commentId): void
    {
        $allIds = $this->collectCommentTreeIds($commentId);
        if ($allIds === []) {
            return;
        }

        $this->connection->executeStatement(
            'DELETE FROM comment_votes WHERE comment_id IN (?)',
            [$allIds],
            [ArrayParameterType::INTEGER]
        );
        $this->connection->executeStatement(
            'DELETE FROM comments WHERE id IN (?)',
            [$allIds],
            [ArrayParameterType::INTEGER]
        );
    }

    public function findPostVoteTypeByUser(int $postId, int $userId): ?string
    {
        $value = $this->connection->fetchOne(
            'SELECT vote_type FROM post_votes WHERE post_id = ? AND user_id = ? LIMIT 1',
            [$postId, $userId]
        );

        return $value === false ? null : (string) $value;
    }

    public function savePostVote(int $postId, int $userId, string $voteType): void
    {
        $existing = $this->findPostVoteTypeByUser($postId, $userId);
        if ($existing === null) {
            $this->connection->insert('post_votes', [
                'post_id' => $postId,
                'user_id' => $userId,
                'vote_type' => $voteType,
                'created_at' => $this->nowString(),
            ]);

            return;
        }

        if ($existing === $voteType) {
            $this->connection->delete('post_votes', ['post_id' => $postId, 'user_id' => $userId]);

            return;
        }

        $this->connection->update('post_votes', [
            'vote_type' => $voteType,
            'created_at' => $this->nowString(),
        ], [
            'post_id' => $postId,
            'user_id' => $userId,
        ]);
    }

    public function findCommentVoteTypeByUser(int $commentId, int $userId): ?string
    {
        $value = $this->connection->fetchOne(
            'SELECT vote_type FROM comment_votes WHERE comment_id = ? AND user_id = ? LIMIT 1',
            [$commentId, $userId]
        );

        return $value === false ? null : (string) $value;
    }

    public function saveCommentVote(int $commentId, int $userId, string $voteType): void
    {
        $existing = $this->findCommentVoteTypeByUser($commentId, $userId);
        if ($existing === null) {
            $this->connection->insert('comment_votes', [
                'comment_id' => $commentId,
                'user_id' => $userId,
                'vote_type' => $voteType,
                'created_at' => $this->nowString(),
            ]);

            return;
        }

        if ($existing === $voteType) {
            $this->connection->delete('comment_votes', ['comment_id' => $commentId, 'user_id' => $userId]);

            return;
        }

        $this->connection->update('comment_votes', [
            'vote_type' => $voteType,
            'created_at' => $this->nowString(),
        ], [
            'comment_id' => $commentId,
            'user_id' => $userId,
        ]);
    }

    /**
     * @param int[] $postIds
     * @return array<int, int>
     */
    public function countActiveCommentsByPostIds(array $postIds): array
    {
        $postIds = $this->sanitizeIds($postIds);
        if ($postIds === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT post_id AS pid, COUNT(id) AS cnt
             FROM comments
             WHERE post_id IN (?) AND (is_active = 1 OR is_active IS NULL)
             GROUP BY post_id',
            [$postIds],
            [ArrayParameterType::INTEGER]
        );

        $out = array_fill_keys($postIds, 0);
        foreach ($rows as $row) {
            $out[(int) $row['pid']] = (int) $row['cnt'];
        }

        return $out;
    }

    /**
     * @param int[] $postIds
     * @return array<int, array{up: int, down: int}>
     */
    public function sumVotesByPostIds(array $postIds): array
    {
        $postIds = $this->sanitizeIds($postIds);
        if ($postIds === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT post_id AS pid, vote_type AS vt, COUNT(id) AS cnt
             FROM post_votes
             WHERE post_id IN (?)
             GROUP BY post_id, vote_type',
            [$postIds],
            [ArrayParameterType::INTEGER]
        );

        $out = [];
        foreach ($postIds as $postId) {
            $out[$postId] = ['up' => 0, 'down' => 0];
        }
        foreach ($rows as $row) {
            $pid = (int) $row['pid'];
            $type = (string) $row['vt'];
            if (!isset($out[$pid][$type])) {
                continue;
            }
            $out[$pid][$type] = (int) $row['cnt'];
        }

        return $out;
    }

    /**
     * @param int[] $commentIds
     * @return array<int, array{up: int, down: int}>
     */
    public function sumVotesByCommentIds(array $commentIds): array
    {
        $commentIds = $this->sanitizeIds($commentIds);
        if ($commentIds === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT comment_id AS cid, vote_type AS vt, COUNT(id) AS cnt
             FROM comment_votes
             WHERE comment_id IN (?)
             GROUP BY comment_id, vote_type',
            [$commentIds],
            [ArrayParameterType::INTEGER]
        );

        $out = [];
        foreach ($commentIds as $commentId) {
            $out[$commentId] = ['up' => 0, 'down' => 0];
        }
        foreach ($rows as $row) {
            $cid = (int) $row['cid'];
            $type = (string) $row['vt'];
            if (!isset($out[$cid][$type])) {
                continue;
            }
            $out[$cid][$type] = (int) $row['cnt'];
        }

        return $out;
    }

    /**
     * @param int[] $postIds
     * @return array<int, string>
     */
    public function mapUserVotesOnPosts(int $userId, array $postIds): array
    {
        $postIds = $this->sanitizeIds($postIds);
        if ($postIds === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT post_id AS pid, vote_type AS vt FROM post_votes WHERE user_id = ? AND post_id IN (?)',
            [$userId, $postIds],
            [\PDO::PARAM_INT, ArrayParameterType::INTEGER]
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['pid']] = (string) $row['vt'];
        }

        return $out;
    }

    /**
     * @param int[] $commentIds
     * @return array<int, string>
     */
    public function mapUserVotesOnComments(int $userId, array $commentIds): array
    {
        $commentIds = $this->sanitizeIds($commentIds);
        if ($commentIds === []) {
            return [];
        }

        $rows = $this->connection->fetchAllAssociative(
            'SELECT comment_id AS cid, vote_type AS vt FROM comment_votes WHERE user_id = ? AND comment_id IN (?)',
            [$userId, $commentIds],
            [\PDO::PARAM_INT, ArrayParameterType::INTEGER]
        );

        $out = [];
        foreach ($rows as $row) {
            $out[(int) $row['cid']] = (string) $row['vt'];
        }

        return $out;
    }

    public function countPostsAll(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM posts');
    }

    public function countPostsActivePublic(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM posts WHERE is_active = 1 OR is_active IS NULL');
    }

    public function countPostsInactive(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM posts WHERE is_active = 0');
    }

    public function countDistinctPostAuthors(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(DISTINCT user_id) FROM posts');
    }

    public function countCommentsAll(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM comments');
    }

    public function countCommentsActivePublic(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM comments WHERE is_active = 1 OR is_active IS NULL');
    }

    public function countCommentsInactive(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM comments WHERE is_active = 0');
    }

    public function countRootComments(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM comments WHERE parent_comment_id IS NULL');
    }

    public function countReplyComments(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM comments WHERE parent_comment_id IS NOT NULL');
    }

    public function countPostVotesAll(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM post_votes');
    }

    public function countCommentVotesAll(): int
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM comment_votes');
    }

    /**
     * @return list<array{tag: string, count: int}>
     */
    public function countGroupedByTag(int $limit = 20): array
    {
        $limit = max(1, $limit);
        $rows = $this->connection->fetchAllAssociative(
            'SELECT COALESCE(NULLIF(tag, \'\'), \'General\') AS tag_label, COUNT(id) AS cnt
             FROM posts
             GROUP BY tag_label
             ORDER BY cnt DESC
             LIMIT '.$limit
        );

        $out = [];
        foreach ($rows as $row) {
            $out[] = [
                'tag' => (string) $row['tag_label'],
                'count' => (int) $row['cnt'],
            ];
        }

        return $out;
    }

    /**
     * @return array{0: string, 1: list<mixed>}
     */
    private function buildPostFeedWhere(?string $tag, ?int $userId, ?string $titleSearch): array
    {
        $clauses = ['(p.is_active = 1 OR p.is_active IS NULL)'];
        $params = [];

        if ($tag !== null && $tag !== '') {
            $clauses[] = 'p.tag = ?';
            $params[] = $tag;
        }
        if ($userId !== null && $userId > 0) {
            $clauses[] = 'p.user_id = ?';
            $params[] = $userId;
        }
        if ($titleSearch !== null && $titleSearch !== '') {
            $clauses[] = 'LOWER(p.title) LIKE ?';
            $params[] = '%'.mb_strtolower($this->escapeLikePattern($titleSearch)).'%';
        }

        return ['WHERE '.implode(' AND ', $clauses), $params];
    }

    /**
     * @return list<int>
     */
    private function collectCommentTreeIds(int $rootCommentId): array
    {
        $all = [];
        $queue = [$rootCommentId];

        while ($queue !== []) {
            $currentBatch = $this->sanitizeIds($queue);
            $queue = [];
            foreach ($currentBatch as $id) {
                if (\in_array($id, $all, true)) {
                    continue;
                }
                $all[] = $id;
            }

            $children = $currentBatch === []
                ? []
                : $this->sanitizeIds($this->connection->fetchFirstColumn(
                    'SELECT id FROM comments WHERE parent_comment_id IN (?)',
                    [$currentBatch],
                    [ArrayParameterType::INTEGER]
                ));

            foreach ($children as $childId) {
                if (!\in_array($childId, $all, true)) {
                    $queue[] = $childId;
                }
            }
        }

        return $all;
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizePostRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'user_id' => (int) $row['user_id'],
            'title' => (string) $row['title'],
            'description' => $row['description'] !== null ? (string) $row['description'] : null,
            'image_url' => $row['image_url'] !== null ? (string) $row['image_url'] : null,
            'tag' => $row['tag'] !== null ? (string) $row['tag'] : null,
            'is_active' => $this->normalizeNullableBool($row['is_active'] ?? null),
            'created_at' => $row['created_at'] !== null ? (string) $row['created_at'] : null,
            'updated_at' => $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
        ];
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeCommentRow(array $row): array
    {
        return [
            'id' => (int) $row['id'],
            'post_id' => (int) $row['post_id'],
            'user_id' => (int) $row['user_id'],
            'parent_comment_id' => $row['parent_comment_id'] !== null ? (int) $row['parent_comment_id'] : null,
            'content' => (string) $row['content'],
            'is_active' => $this->normalizeNullableBool($row['is_active'] ?? null),
            'created_at' => $row['created_at'] !== null ? (string) $row['created_at'] : null,
            'updated_at' => $row['updated_at'] !== null ? (string) $row['updated_at'] : null,
        ];
    }

    private function normalizeNullableBool(mixed $value): ?bool
    {
        if ($value === null) {
            return null;
        }

        return (bool) $value;
    }

    /**
     * @param list<mixed> $ids
     * @return list<int>
     */
    private function sanitizeIds(array $ids): array
    {
        $out = [];
        foreach ($ids as $id) {
            $n = (int) $id;
            if ($n > 0 && !\in_array($n, $out, true)) {
                $out[] = $n;
            }
        }

        return $out;
    }

    private function nowString(): string
    {
        return (new \DateTimeImmutable())->format('Y-m-d H:i:s');
    }

    private function escapeLikePattern(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $value);
    }
}
