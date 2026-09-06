-- DMMMSU USC additive patch: publication videos
-- Safe for an existing database. Does not alter or delete current posts/images.

CREATE TABLE IF NOT EXISTS `post_videos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `post_id` int NOT NULL,
  `file_path` varchar(255) NOT NULL,
  `original_name` varchar(255) DEFAULT NULL,
  `media_id` int DEFAULT NULL,
  `mime_type` varchar(100) NOT NULL,
  `file_size` bigint unsigned NOT NULL DEFAULT 0,
  `sort_order` int NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_post_videos_post_id` (`post_id`),
  KEY `idx_post_videos_media_id` (`media_id`),
  CONSTRAINT `usc_fk_post_videos_post` FOREIGN KEY (`post_id`) REFERENCES `posts` (`id`) ON DELETE CASCADE,
  CONSTRAINT `usc_fk_post_videos_media` FOREIGN KEY (`media_id`) REFERENCES `media_library` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
