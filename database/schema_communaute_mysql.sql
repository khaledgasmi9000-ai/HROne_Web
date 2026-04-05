-- Extrait aligné sur le dump phpMyAdmin hr_one (MariaDB 10.4).
-- Prérequis : base `hr_one` existante, table `utilisateur` déjà créée.
-- Ne pas exécuter si ces tables existent déjà (risque d’erreur duplicate).

USE `hr_one`;

SET FOREIGN_KEY_CHECKS = 0;

-- --------------------------------------------------------
-- Table posts (FK user_id -> utilisateur.ID_UTILISATEUR)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `posts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(255) NOT NULL,
  `description` text DEFAULT NULL,
  `image_url` varchar(255) DEFAULT NULL,
  `tag` varchar(50) DEFAULT 'General',
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_posts_user` (`user_id`),
  KEY `idx_posts_tag` (`tag`),
  KEY `idx_posts_active` (`is_active`),
  CONSTRAINT `FK_Posts_Utilisateur` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`ID_UTILISATEUR`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Table comments
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `parent_comment_id` int(11) DEFAULT NULL,
  `content` text NOT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_comments_post` (`post_id`),
  KEY `idx_comments_user` (`user_id`),
  KEY `idx_comments_parent` (`parent_comment_id`),
  CONSTRAINT `FK_Comments_Post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_Comments_Utilisateur` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`ID_UTILISATEUR`) ON DELETE CASCADE,
  CONSTRAINT `FK_Comments_Parent` FOREIGN KEY (`parent_comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------
-- Tables votes (optionnel, entités PostVote / CommentVote)
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `post_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `post_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('up','down') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_post_vote` (`post_id`,`user_id`),
  KEY `idx_post_votes_user` (`user_id`),
  CONSTRAINT `FK_PostVotes_Post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_PostVotes_Utilisateur` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`ID_UTILISATEUR`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE IF NOT EXISTS `comment_votes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `comment_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `vote_type` enum('up','down') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_comment_vote` (`comment_id`,`user_id`),
  KEY `idx_comment_votes_user` (`user_id`),
  CONSTRAINT `FK_CommentVotes_Comment` FOREIGN KEY (`comment_id`) REFERENCES `comments` (`id`) ON DELETE CASCADE,
  CONSTRAINT `FK_CommentVotes_Utilisateur` FOREIGN KEY (`user_id`) REFERENCES `utilisateur` (`ID_UTILISATEUR`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

SET FOREIGN_KEY_CHECKS = 1;
