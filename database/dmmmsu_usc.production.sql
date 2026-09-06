CREATE DATABASE IF NOT EXISTS dmmmsu_usc CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dmmmsu_usc;
CREATE TABLE IF NOT EXISTS admins (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(80) UNIQUE NOT NULL,
  email VARCHAR(180) NULL,
  profile_image VARCHAR(255) NULL,
  password_hash VARCHAR(255) NOT NULL,
  full_name VARCHAR(150) NOT NULL,
  role VARCHAR(40) NOT NULL DEFAULT 'admin',
  campus VARCHAR(20) NULL,
  status VARCHAR(20) NOT NULL DEFAULT 'pending',
  status_reason VARCHAR(255) NULL,
  status_changed_at DATETIME NULL,
  archived_at DATETIME NULL,
  force_password_change TINYINT(1) NOT NULL DEFAULT 0,
  created_by INT NULL,
  approved_at DATETIME NULL,
  approved_by INT NULL,
  last_login DATETIME NULL,
  last_activity_at DATETIME NULL,
  password_changed_at DATETIME NULL,
  two_factor_enabled TINYINT(1) NOT NULL DEFAULT 0,
  two_factor_secret TEXT NULL,
  security_note VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_admin_status_activity(status,last_activity_at)
);
CREATE TABLE IF NOT EXISTS posts (id INT AUTO_INCREMENT PRIMARY KEY, academic_year_id INT NULL, title VARCHAR(255) NOT NULL, slug VARCHAR(191) NULL, excerpt TEXT, content LONGTEXT, category ENUM('usc','nluc','mluc','sluc','ous') NOT NULL DEFAULT 'usc', label VARCHAR(100) DEFAULT 'USC', image VARCHAR(255) NULL, is_featured TINYINT(1) DEFAULT 0, is_university_featured TINYINT(1) NOT NULL DEFAULT 0, status ENUM('draft','review','published','archived') DEFAULT 'published', published_at DATETIME DEFAULT CURRENT_TIMESTAMP, scheduled_at DATETIME NULL, review_note TEXT NULL, published_by INT NULL, scheduled_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, deleted_at DATETIME NULL, deleted_by INT NULL, UNIQUE KEY uq_posts_slug(slug), INDEX idx_posts_public_schedule(status,scheduled_at,published_at,category), INDEX idx_posts_category_status_slug(category,status,slug), INDEX idx_posts_university_featured(is_university_featured,status,published_at));
CREATE TABLE IF NOT EXISTS post_images (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, file_path VARCHAR(255) NOT NULL, original_name VARCHAR(255) NULL, media_id INT NULL, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_post_images_post_id (post_id), INDEX idx_post_images_media_id (media_id), CONSTRAINT fk_post_images_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS post_videos (id INT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, file_path VARCHAR(255) NOT NULL, original_name VARCHAR(255) NULL, media_id INT NULL, mime_type VARCHAR(100) NOT NULL, file_size BIGINT UNSIGNED NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_post_videos_post_id (post_id), INDEX idx_post_videos_media_id (media_id), CONSTRAINT fk_post_videos_post FOREIGN KEY (post_id) REFERENCES posts(id) ON DELETE CASCADE) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS concerns (id INT AUTO_INCREMENT PRIMARY KEY, reference_code VARCHAR(40) UNIQUE NOT NULL, campus VARCHAR(50) NOT NULL, college VARCHAR(180) NULL, source_portal VARCHAR(10) NOT NULL DEFAULT 'USC', assigned_scope VARCHAR(10) NOT NULL DEFAULT 'USC', academic_year_id INT NULL, concern_type VARCHAR(50) NOT NULL, student_urgency VARCHAR(20) NULL, student_name VARCHAR(150) NULL, student_id VARCHAR(80) NULL, subject VARCHAR(255) NOT NULL, message TEXT NOT NULL, anonymous TINYINT(1) DEFAULT 0, sensitivity VARCHAR(20) NOT NULL DEFAULT 'standard', status ENUM('Submitted','Received','Under Review','Referred','In Progress','Action Taken','Resolved','Closed') DEFAULT 'Submitted', priority VARCHAR(20) NOT NULL DEFAULT 'Normal', assigned_to INT NULL, due_at DATETIME NULL, follow_up_at DATETIME NULL, first_response_at DATETIME NULL, last_public_update_at DATETIME NULL, admin_note TEXT NULL, internal_note TEXT NULL, resolution_summary TEXT NULL, escalation_reason TEXT NULL, resolved_at DATETIME NULL, retention_until DATE NULL, privacy_review_status VARCHAR(30) NOT NULL DEFAULT 'pending', privacy_reviewed_at DATETIME NULL, privacy_reviewed_by INT NULL, sensitive_view_count INT NOT NULL DEFAULT 0, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_concerns_source_portal(source_portal), INDEX idx_concerns_assigned_scope(assigned_scope), INDEX idx_concern_work_queue(assigned_scope,status,due_at,priority), INDEX idx_concern_assignee(assigned_to,status,due_at), INDEX idx_concern_created(created_at), INDEX idx_concern_privacy_review(privacy_review_status,retention_until), INDEX idx_concern_status_created(status,created_at), INDEX idx_concern_followup(status,follow_up_at));
CREATE TABLE IF NOT EXISTS admin_activity_logs (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_id INT NULL, admin_name VARCHAR(150) NULL,
 role VARCHAR(40) NULL, campus VARCHAR(20) NULL, module VARCHAR(50) NOT NULL,
 action VARCHAR(80) NOT NULL, description VARCHAR(500) NOT NULL,
 entity_type VARCHAR(50) NULL, entity_id INT NULL, old_values LONGTEXT NULL, new_values LONGTEXT NULL,
 ip_address VARCHAR(64) NULL, user_agent VARCHAR(500) NULL, prev_hash CHAR(64) NULL, record_hash CHAR(64) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_activity_hash(record_hash)
);
CREATE TABLE IF NOT EXISTS admin_notifications (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_id INT NULL, role_target VARCHAR(40) NULL,
 campus_target VARCHAR(20) NULL, title VARCHAR(180) NOT NULL, message VARCHAR(500) NOT NULL,
 link VARCHAR(255) NULL, kind VARCHAR(30) NOT NULL DEFAULT 'info', category VARCHAR(40) NOT NULL DEFAULT 'general', group_key VARCHAR(120) NULL, is_read TINYINT(1) NOT NULL DEFAULT 0, dismissed_at DATETIME NULL, expires_at DATETIME NULL,
 created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_notification_group(group_key,created_at), INDEX idx_notification_category(category,is_read,created_at)
);
CREATE TABLE IF NOT EXISTS media_library (
 id INT AUTO_INCREMENT PRIMARY KEY, file_path VARCHAR(255) NOT NULL, thumbnail_path VARCHAR(255) NULL, original_name VARCHAR(255) NULL,
 mime_type VARCHAR(100) NULL, file_size INT NULL, width INT NULL, height INT NULL, sha256 CHAR(64) NULL, last_verified_at DATETIME NULL, optimized_at DATETIME NULL, alt_text VARCHAR(255) NULL, uploaded_by INT NULL,
 campus VARCHAR(20) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, deleted_at DATETIME NULL, deleted_by INT NULL, INDEX idx_media_sha256(sha256)
);
CREATE TABLE IF NOT EXISTS system_settings (
 setting_key VARCHAR(100) PRIMARY KEY, setting_value TEXT NULL, updated_by INT NULL,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS concern_history (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, concern_id INT NOT NULL, admin_id INT NULL,
 actor_name VARCHAR(150) NULL, action VARCHAR(80) NOT NULL DEFAULT 'Updated concern',
 old_status VARCHAR(40) NULL, status VARCHAR(40) NOT NULL,
 old_assigned_scope VARCHAR(10) NULL, assigned_scope VARCHAR(10) NULL, assigned_to INT NULL,
 public_note TEXT NULL, internal_note TEXT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
CREATE TABLE IF NOT EXISTS admin_sessions (
 id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, session_key_hash CHAR(64) NOT NULL UNIQUE,
 php_session_hash CHAR(64) NULL, ip_address VARCHAR(64) NULL, user_agent VARCHAR(500) NULL,
 created_at DATETIME NOT NULL, last_seen_at DATETIME NOT NULL, revoked_at DATETIME NULL,
 INDEX idx_admin_sessions_admin(admin_id,revoked_at,last_seen_at)
);
CREATE TABLE IF NOT EXISTS admin_login_attempts (
 attempt_key CHAR(64) PRIMARY KEY, admin_id INT NULL, username VARCHAR(150) NULL, ip_address VARCHAR(64) NULL,
 attempts INT NOT NULL DEFAULT 0, locked_until DATETIME NULL, updated_at DATETIME NOT NULL,
 INDEX idx_admin_login_admin(admin_id), INDEX idx_admin_login_lock(locked_until)
);
CREATE TABLE IF NOT EXISTS admin_notification_preferences (
 admin_id INT NOT NULL, category VARCHAR(40) NOT NULL, is_enabled TINYINT(1) NOT NULL DEFAULT 1,
 PRIMARY KEY(admin_id,category)
);

CREATE TABLE IF NOT EXISTS hero_slides (
  id INT AUTO_INCREMENT PRIMARY KEY,
  portal_code VARCHAR(10) NOT NULL DEFAULT 'USC',
  theme VARCHAR(30) NOT NULL DEFAULT 'green',
  eyebrow VARCHAR(160) NOT NULL,
  title VARCHAR(220) NOT NULL,
  description TEXT NOT NULL,
  primary_label VARCHAR(80) NULL,
  primary_url VARCHAR(255) NULL,
  secondary_label VARCHAR(80) NULL,
  secondary_url VARCHAR(255) NULL,
  button_mode VARCHAR(20) NOT NULL DEFAULT 'both',
  panel_type VARCHAR(30) NOT NULL DEFAULT 'card',
  panel_kicker VARCHAR(80) NULL,
  panel_status VARCHAR(40) NULL,
  panel_title VARCHAR(180) NULL,
  panel_description TEXT NULL,
  panel_image VARCHAR(255) NULL,
  panel_media_id INT NULL,
  background_image VARCHAR(255) NULL,
  background_media_id INT NULL,
  background_position_x TINYINT UNSIGNED NOT NULL DEFAULT 50,
  background_position_y TINYINT UNSIGNED NOT NULL DEFAULT 50,
  display_mode VARCHAR(30) NOT NULL DEFAULT 'standard',
  sort_order INT NOT NULL DEFAULT 0,
  is_active TINYINT(1) NOT NULL DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_hero_portal_order(portal_code,sort_order,id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS hero_promotion_requests (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,
  source_slide_id INT NOT NULL,
  source_portal VARCHAR(10) NOT NULL,
  target_portal VARCHAR(10) NOT NULL DEFAULT 'USC',
  status VARCHAR(30) NOT NULL DEFAULT 'pending',
  request_note TEXT NULL,
  review_note TEXT NULL,
  requested_by INT NULL,
  requested_by_name VARCHAR(150) NULL,
  requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  reviewed_by INT NULL,
  reviewed_by_name VARCHAR(150) NULL,
  reviewed_at DATETIME NULL,
  display_from DATETIME NULL,
  display_until DATETIME NULL,
  display_order INT NOT NULL DEFAULT 1,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_hero_promo_source(source_slide_id,source_portal,status),
  INDEX idx_hero_promo_target(target_portal,status,display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- V70 reliability, privacy, workflow and recovery additions
CREATE TABLE IF NOT EXISTS schema_migrations (version VARCHAR(30) PRIMARY KEY, description VARCHAR(255) NOT NULL, safety_backup_filename VARCHAR(255) NULL, applied_by INT NULL, duration_ms INT NULL, result VARCHAR(30) NOT NULL DEFAULT 'applied', applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS concern_response_templates (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(150) NOT NULL, response_text TEXT NOT NULL, concern_type VARCHAR(50) NULL, campus VARCHAR(20) NULL, is_active TINYINT(1) NOT NULL DEFAULT 1, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, INDEX idx_response_template_scope(campus,is_active)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS privacy_access_log (id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_id INT NULL, concern_id INT NOT NULL, action VARCHAR(60) NOT NULL, purpose VARCHAR(255) NULL, ip_address VARCHAR(64) NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_privacy_concern(concern_id,created_at), INDEX idx_privacy_admin(admin_id,created_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS backup_history (id BIGINT AUTO_INCREMENT PRIMARY KEY, filename VARCHAR(255) NOT NULL, backup_type VARCHAR(30) NOT NULL DEFAULT 'database', file_size BIGINT NOT NULL DEFAULT 0, checksum_sha256 CHAR(64) NULL, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, restored_by INT NULL, restored_at DATETIME NULL, verified_at DATETIME NULL, verification_status VARCHAR(30) NULL, verification_message VARCHAR(500) NULL, offsite_copied_at DATETIME NULL, offsite_path VARCHAR(500) NULL, INDEX idx_backup_created(created_at), INDEX idx_backup_verify(verification_status,verified_at)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
-- V90 privacy, publishing, recovery-code, email and automation additions
CREATE TABLE IF NOT EXISTS concern_private_identity (
  concern_id INT PRIMARY KEY, student_name_cipher LONGTEXT NULL, student_id_cipher LONGTEXT NULL, contact_email_cipher LONGTEXT NULL,
  encryption_version VARCHAR(20) NOT NULL DEFAULT 'v1', created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS concern_attachments (
  id BIGINT AUTO_INCREMENT PRIMARY KEY, concern_id INT NOT NULL, file_path VARCHAR(255) NOT NULL,
  original_name VARCHAR(255) NOT NULL, mime_type VARCHAR(120) NOT NULL, file_size BIGINT NOT NULL DEFAULT 0,
  sha256 CHAR(64) NULL, uploaded_by INT NULL, source VARCHAR(20) NOT NULL DEFAULT 'student',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_concern_attachment_case(concern_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS submission_rate_limits (
  bucket_key CHAR(64) PRIMARY KEY, admin_id INT NULL, attempts INT NOT NULL DEFAULT 0, window_started_at DATETIME NOT NULL,
  last_attempt_at DATETIME NOT NULL, blocked_until DATETIME NULL, INDEX idx_rate_limit_admin(admin_id,last_attempt_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS post_revisions (
  id BIGINT AUTO_INCREMENT PRIMARY KEY, post_id INT NOT NULL, revision_no INT NOT NULL, snapshot_json LONGTEXT NOT NULL,
  change_note VARCHAR(255) NULL, created_by INT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_post_revision(post_id,revision_no), INDEX idx_post_revision_created(post_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admin_recovery_codes (
  id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, code_hash CHAR(64) NOT NULL, used_at DATETIME NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_recovery_admin(admin_id,used_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admin_password_history (
  id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_id INT NOT NULL, password_hash VARCHAR(255) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, INDEX idx_password_history_admin(admin_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS notification_email_outbox (
  id BIGINT AUTO_INCREMENT PRIMARY KEY, admin_id INT NULL, concern_id INT NULL, recipient VARCHAR(190) NOT NULL, subject VARCHAR(190) NOT NULL, body TEXT NOT NULL,
  category VARCHAR(40) NOT NULL DEFAULT 'general', status VARCHAR(30) NOT NULL DEFAULT 'queued', attempts INT NOT NULL DEFAULT 0,
  last_error VARCHAR(500) NULL, sent_at DATETIME NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_email_outbox(status,created_at), INDEX idx_email_outbox_admin(admin_id,created_at), INDEX idx_email_outbox_concern(concern_id,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO system_settings(setting_key,setting_value) VALUES ('esumbong_sla_urgent_hours','24'),('esumbong_sla_high_hours','48'),('esumbong_sla_normal_hours','120'),('esumbong_sla_low_hours','168'),('esumbong_suggestion_feedback_cooldown_hours','72'),('privacy_retention_days','730'),('password_max_age_days','180'),('dormant_account_days','90'),('backup_reminder_days','7'),('notification_email_enabled','0'),('security_require_2fa_for_admin','0'),('security_2fa_required_roles','admin'),('password_history_count','5'),('esumbong_attachment_max_files','3'),('esumbong_attachment_max_mb','5'),('esumbong_email_updates_enabled','1'),('auto_publish_scheduled','1'),('auto_backup_enabled','0'),('auto_backup_type','database'),('auto_backup_interval_hours','24'),('auto_backup_retention_count','14'),('email_from_name','DMMMSU USC'),('email_from_address','usc@dmmmsu.edu.ph'),('csp_enforcement','report-only') ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

-- V100 governance, lifecycle, permissions, announcements and recovery additions
CREATE TABLE IF NOT EXISTS academic_years (
  id INT AUTO_INCREMENT PRIMARY KEY,label VARCHAR(80) NOT NULL UNIQUE,start_date DATE NOT NULL,end_date DATE NOT NULL,
  is_active TINYINT(1) NOT NULL DEFAULT 0,is_archived TINYINT(1) NOT NULL DEFAULT 0,created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_academic_year_active(is_active,is_archived,start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS officers (
  id INT AUTO_INCREMENT PRIMARY KEY,academic_year_id INT NULL,portal_code VARCHAR(10) NOT NULL DEFAULT 'USC',full_name VARCHAR(150) NOT NULL,
  position_title VARCHAR(150) NOT NULL,office_name VARCHAR(150) NULL,email VARCHAR(180) NULL,photo_path VARCHAR(255) NULL,start_date DATE NULL,end_date DATE NULL,
  is_archived TINYINT(1) NOT NULL DEFAULT 0,sort_order INT NOT NULL DEFAULT 0,created_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_officers_scope_year(portal_code,academic_year_id,is_archived,sort_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS announcements (
  id INT AUTO_INCREMENT PRIMARY KEY,academic_year_id INT NULL,portal_code VARCHAR(10) NOT NULL DEFAULT 'ALL',title VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,priority VARCHAR(20) NOT NULL DEFAULT 'info',audience VARCHAR(20) NOT NULL DEFAULT 'public',status VARCHAR(20) NOT NULL DEFAULT 'draft',
  starts_at DATETIME NULL,ends_at DATETIME NULL,link_label VARCHAR(80) NULL,link_url VARCHAR(255) NULL,created_by INT NULL,updated_by INT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_announcements_public(status,audience,portal_code,starts_at,ends_at),INDEX idx_announcements_year(academic_year_id,status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS role_permission_overrides (
  role VARCHAR(40) NOT NULL,permission VARCHAR(100) NOT NULL,allowed TINYINT(1) NOT NULL,updated_by INT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(role,permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admin_permission_overrides (
  admin_id INT NOT NULL,permission VARCHAR(100) NOT NULL,allowed TINYINT(1) NOT NULL,updated_by INT NULL,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(admin_id,permission)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admin_dashboard_widgets (
  admin_id INT NOT NULL,widget_key VARCHAR(60) NOT NULL,is_visible TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(admin_id,widget_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS admin_login_events (
  id BIGINT AUTO_INCREMENT PRIMARY KEY,admin_id INT NULL,username VARCHAR(150) NULL,event_type VARCHAR(50) NOT NULL,result VARCHAR(20) NOT NULL,
  reason VARCHAR(255) NULL,ip_address VARCHAR(64) NULL,user_agent VARCHAR(500) NULL,device_label VARCHAR(120) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX idx_login_events_admin(admin_id,created_at),INDEX idx_login_events_result(result,created_at),INDEX idx_login_events_ip(ip_address,created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT INTO system_settings(setting_key,setting_value) VALUES
('active_academic_year_id','0'),('notification_retention_days','180'),('storage_warning_percent','85'),('storage_critical_percent','95'),
('offsite_backup_enabled','0'),('offsite_backup_path',''),('media_auto_optimize','1'),('media_thumbnail_enabled','1'),
('security_rate_limit_tracking_per_hour','30'),('security_rate_limit_uploads_per_hour','60'),('public_network_status','1')
ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key);

-- V102 operational relationship compatibility for existing installations
ALTER TABLE `submission_rate_limits`
  ADD COLUMN IF NOT EXISTS `admin_id` INT NULL AFTER `bucket_key`,
  ADD INDEX IF NOT EXISTS `idx_rate_limit_admin` (`admin_id`,`last_attempt_at`);
ALTER TABLE `notification_email_outbox`
  ADD COLUMN IF NOT EXISTS `admin_id` INT NULL AFTER `id`,
  ADD COLUMN IF NOT EXISTS `concern_id` INT NULL AFTER `admin_id`,
  ADD INDEX IF NOT EXISTS `idx_email_outbox_admin` (`admin_id`,`created_at`),
  ADD INDEX IF NOT EXISTS `idx_email_outbox_concern` (`concern_id`,`created_at`);

-- Idempotent foreign-key installer.
-- This prevents phpMyAdmin/MariaDB errno 121 when this schema is imported over an existing database
-- that already contains one or more of the same relationships.
DELIMITER $$
DROP PROCEDURE IF EXISTS `usc_add_fk_if_missing`$$
CREATE PROCEDURE `usc_add_fk_if_missing`(
  IN p_table VARCHAR(64),
  IN p_constraint VARCHAR(64),
  IN p_column VARCHAR(64),
  IN p_ref_table VARCHAR(64),
  IN p_ref_column VARCHAR(64),
  IN p_on_delete VARCHAR(20),
  IN p_on_update VARCHAR(20)
)
BEGIN
  DECLARE v_relation_exists INT DEFAULT 0;
  DECLARE v_constraint_exists INT DEFAULT 0;
  DECLARE v_table_exists INT DEFAULT 0;
  DECLARE v_column_exists INT DEFAULT 0;
  DECLARE v_ref_table_exists INT DEFAULT 0;
  DECLARE v_ref_column_exists INT DEFAULT 0;

  SELECT COUNT(*) INTO v_table_exists
    FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table;
  SELECT COUNT(*) INTO v_ref_table_exists
    FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_ref_table;
  SELECT COUNT(*) INTO v_column_exists
    FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_table AND COLUMN_NAME = p_column;
  SELECT COUNT(*) INTO v_ref_column_exists
    FROM information_schema.COLUMNS
   WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = p_ref_table AND COLUMN_NAME = p_ref_column;

  IF v_table_exists > 0 AND v_ref_table_exists > 0 AND v_column_exists > 0 AND v_ref_column_exists > 0 THEN
    SELECT COUNT(*) INTO v_relation_exists
      FROM information_schema.KEY_COLUMN_USAGE
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND TABLE_NAME = p_table
       AND COLUMN_NAME = p_column
       AND REFERENCED_TABLE_NAME = p_ref_table
       AND REFERENCED_COLUMN_NAME = p_ref_column;

    SELECT COUNT(*) INTO v_constraint_exists
      FROM information_schema.TABLE_CONSTRAINTS
     WHERE CONSTRAINT_SCHEMA = DATABASE()
       AND CONSTRAINT_NAME = p_constraint
       AND CONSTRAINT_TYPE = 'FOREIGN KEY';

    IF v_relation_exists = 0 AND v_constraint_exists = 0 THEN
      SET @usc_fk_sql = CONCAT(
        'ALTER TABLE `', REPLACE(p_table,'`','``'),
        '` ADD CONSTRAINT `', REPLACE(p_constraint,'`','``'),
        '` FOREIGN KEY (`', REPLACE(p_column,'`','``'),
        '`) REFERENCES `', REPLACE(p_ref_table,'`','``'),
        '` (`', REPLACE(p_ref_column,'`','``'),
        '`) ON UPDATE ', p_on_update,
        ' ON DELETE ', p_on_delete
      );
      PREPARE usc_fk_stmt FROM @usc_fk_sql;
      EXECUTE usc_fk_stmt;
      DEALLOCATE PREPARE usc_fk_stmt;
    END IF;
  END IF;
END$$
DELIMITER ;

-- V87 relational integrity / ERD relationships
CALL `usc_add_fk_if_missing`('admins','fk_admins_created_by','created_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('admin_activity_logs','fk_activity_admin','admin_id','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('admin_notifications','fk_notifications_admin','admin_id','admins','id','CASCADE','CASCADE');
CALL `usc_add_fk_if_missing`('admin_notification_preferences','fk_notification_preferences_admin','admin_id','admins','id','CASCADE','CASCADE');
CALL `usc_add_fk_if_missing`('admin_sessions','fk_sessions_admin','admin_id','admins','id','CASCADE','CASCADE');
CALL `usc_add_fk_if_missing`('admin_login_attempts','fk_login_attempts_admin','admin_id','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('backup_history','fk_backups_created_by','created_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('backup_history','fk_backups_restored_by','restored_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('concerns','fk_concerns_assigned_to','assigned_to','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('concern_history','fk_concern_history_concern','concern_id','concerns','id','CASCADE','CASCADE');
CALL `usc_add_fk_if_missing`('concern_history','fk_concern_history_admin','admin_id','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('concern_history','fk_concern_history_assignee','assigned_to','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('concern_response_templates','fk_response_templates_created_by','created_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('hero_promotion_requests','fk_promo_requested_by','requested_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('hero_promotion_requests','fk_promo_reviewed_by','reviewed_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('hero_slides','fk_hero_panel_media','panel_media_id','media_library','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('hero_slides','fk_hero_background_media','background_media_id','media_library','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('media_library','fk_media_uploaded_by','uploaded_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('media_library','fk_media_deleted_by','deleted_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('posts','fk_posts_deleted_by','deleted_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('post_images','fk_post_images_media','media_id','media_library','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('post_videos','usc_fk_post_videos_media','media_id','media_library','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('privacy_access_log','fk_privacy_admin','admin_id','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('privacy_access_log','fk_privacy_concern','concern_id','concerns','id','RESTRICT','CASCADE');
CALL `usc_add_fk_if_missing`('schema_migrations','fk_migrations_applied_by','applied_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('system_settings','fk_settings_updated_by','updated_by','admins','id','SET NULL','CASCADE');
-- V90 relational integrity
CALL `usc_add_fk_if_missing`('concern_private_identity','usc_fk_private_identity_concern','concern_id','concerns','id','CASCADE','RESTRICT');
CALL `usc_add_fk_if_missing`('concern_attachments','usc_fk_concern_attachments_concern','concern_id','concerns','id','CASCADE','RESTRICT');
CALL `usc_add_fk_if_missing`('concern_attachments','usc_fk_concern_attachments_admin','uploaded_by','admins','id','SET NULL','RESTRICT');
CALL `usc_add_fk_if_missing`('concerns','usc_fk_concern_privacy_reviewer','privacy_reviewed_by','admins','id','SET NULL','RESTRICT');
CALL `usc_add_fk_if_missing`('posts','usc_fk_posts_published_by','published_by','admins','id','SET NULL','RESTRICT');
CALL `usc_add_fk_if_missing`('posts','usc_fk_posts_scheduled_by','scheduled_by','admins','id','SET NULL','RESTRICT');
CALL `usc_add_fk_if_missing`('post_revisions','usc_fk_post_revisions_post','post_id','posts','id','CASCADE','RESTRICT');
CALL `usc_add_fk_if_missing`('post_revisions','usc_fk_post_revisions_admin','created_by','admins','id','SET NULL','RESTRICT');
CALL `usc_add_fk_if_missing`('admin_recovery_codes','usc_fk_recovery_codes_admin','admin_id','admins','id','CASCADE','RESTRICT');
CALL `usc_add_fk_if_missing`('admin_password_history','usc_fk_password_history_admin','admin_id','admins','id','CASCADE','RESTRICT');
CALL `usc_add_fk_if_missing`('admins','usc_fk_admins_approved_by','approved_by','admins','id','SET NULL','RESTRICT');
CALL `usc_add_fk_if_missing`('submission_rate_limits','usc_fk_rate_limit_admin','admin_id','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('notification_email_outbox','usc_fk_email_outbox_admin','admin_id','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('notification_email_outbox','usc_fk_email_outbox_concern','concern_id','concerns','id','SET NULL','CASCADE');


-- V100 governance relationships
CALL `usc_add_fk_if_missing`('academic_years','usc_fk_academic_created_by','created_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('officers','usc_fk_officers_year','academic_year_id','academic_years','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('officers','usc_fk_officers_created_by','created_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('announcements','usc_fk_announcements_year','academic_year_id','academic_years','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('announcements','usc_fk_announcements_created_by','created_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('announcements','usc_fk_announcements_updated_by','updated_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('role_permission_overrides','usc_fk_role_permission_updated_by','updated_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('admin_permission_overrides','usc_fk_admin_permission_admin','admin_id','admins','id','CASCADE','CASCADE');
CALL `usc_add_fk_if_missing`('admin_permission_overrides','usc_fk_admin_permission_updated_by','updated_by','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('admin_dashboard_widgets','usc_fk_dashboard_widgets_admin','admin_id','admins','id','CASCADE','CASCADE');
CALL `usc_add_fk_if_missing`('admin_login_events','usc_fk_login_events_admin','admin_id','admins','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('posts','usc_fk_posts_academic_year','academic_year_id','academic_years','id','SET NULL','CASCADE');
CALL `usc_add_fk_if_missing`('concerns','usc_fk_concerns_academic_year','academic_year_id','academic_years','id','SET NULL','CASCADE');

DROP PROCEDURE IF EXISTS `usc_add_fk_if_missing`;
