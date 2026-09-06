<?php
/**
 * Developed by: George Rexy Vincent Z. Bacani
 * College: College of Information Technology
 * Role: System Developer / Front-End Developer
 * Development Year: 2026–2027
 * Institution: Don Mariano Marcos Memorial State University
 * Version: v1.0
 * Email: rexygeorge11@gmail.com
 * Copyright: © 2026–2027. All rights reserved.
 */
declare(strict_types=1);
require_once __DIR__.'/app.php';

function migration_table_exists(PDO $pdo, string $table): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=?");
    $st->execute([$table]);
    return (int)$st->fetchColumn()>0;
}
function migration_column_exists(PDO $pdo, string $table, string $column): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=?");
    $st->execute([$table, $column]);
    return (int)$st->fetchColumn()>0;
}
function migration_index_exists(PDO $pdo, string $table, string $index): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND INDEX_NAME=?");
    $st->execute([$table, $index]);
    return (int)$st->fetchColumn()>0;
}
function migration_foreign_key_exists(PDO $pdo, string $table, string $constraint): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE CONSTRAINT_SCHEMA=DATABASE() AND TABLE_NAME=? AND CONSTRAINT_NAME=? AND CONSTRAINT_TYPE='FOREIGN KEY'");
    $st->execute([$table, $constraint]);
    return (int)$st->fetchColumn()>0;
}
function migration_foreign_key_relation_exists(PDO $pdo, string $table, string $column, string $parentTable, string $parentColumn = 'id'): bool {
    $st = $pdo->prepare("SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=? AND COLUMN_NAME=? AND REFERENCED_TABLE_NAME=? AND REFERENCED_COLUMN_NAME=?");
    $st->execute([$table, $column, $parentTable, $parentColumn]);
    return (int)$st->fetchColumn()>0;
}
function migration_assert_reference_integrity(PDO $pdo, string $table, string $column, string $parentTable, string $parentColumn = 'id'): void {
    $sql = "SELECT COUNT(*) FROM `{$table}` c LEFT JOIN `{$parentTable}` p ON p.`{$parentColumn}`=c.`{$column}` WHERE c.`{$column}` IS NOT NULL AND p.`{$parentColumn}` IS NULL";
    $orphans = (int)$pdo->query($sql)->fetchColumn();
    if ($orphans>0) throw new RuntimeException("Cannot add relationship {$table}.{$column} -> {$parentTable}.{$parentColumn}: {$orphans} orphaned record(s) exist. No data was changed.");
}
function migration_add_foreign_key(PDO $pdo, string $table, string $constraint, string $column, string $parentTable, string $parentColumn = 'id', string $onDelete = 'RESTRICT'): void {
    if (migration_foreign_key_exists($pdo, $table, $constraint) || migration_foreign_key_relation_exists($pdo, $table, $column, $parentTable, $parentColumn)) return;
    migration_assert_reference_integrity($pdo, $table, $column, $parentTable, $parentColumn);
    $allowed = ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'];
    $onDelete = in_array(strtoupper($onDelete), $allowed, true)?strtoupper($onDelete):'RESTRICT';
    $pdo->exec("ALTER TABLE `{$table}` ADD CONSTRAINT `{$constraint}` FOREIGN KEY (`{$column}`) REFERENCES `{$parentTable}` (`{$parentColumn}`) ON UPDATE CASCADE ON DELETE {$onDelete}");
}
function migration_add_column(PDO $pdo, string $table, string $column, string $definition): void {
    if (!migration_column_exists($pdo, $table, $column)) $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
}
function migration_add_index(PDO $pdo, string $table, string $index, string $columns): void {
    if (!migration_index_exists($pdo, $table, $index)) $pdo->exec("ALTER TABLE `{$table}` ADD INDEX `{$index}` ({$columns})");
}
function migration_create_registry(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
    version VARCHAR(30) PRIMARY KEY,
    description VARCHAR(255) NOT NULL,
    applied_by INT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
}
function app_migrations(): array {
    return [
    '2026081900' => [
    'description' => 'Consolidate legacy runtime schema upgrades into the explicit Migration Center',
    'up' => function (PDO $pdo) : void {
        require_once __DIR__.'/helpers.php';
        if (migration_table_exists($pdo, 'concerns') && !migration_column_exists($pdo, 'concerns', 'college')) $pdo->exec("ALTER TABLE concerns ADD COLUMN college VARCHAR(180) NULL AFTER campus");
        if (!defined('APP_MIGRATION_MODE')) define('APP_MIGRATION_MODE', true);
        ensure_post_images_table($pdo);
        ensure_hero_slides_table($pdo);
        ensure_hero_promotion_requests_table($pdo);
        ensure_admin_platform_tables($pdo);
    }
    ],
    '2026081901' => [
    'description' => 'Administration reliability, case workflow, privacy, media verification, and backup metadata',
    'up' => function (PDO $pdo) : void {
        migration_add_column($pdo, 'admins', 'last_activity_at', 'DATETIME NULL AFTER last_login');
        migration_add_column($pdo, 'admins', 'security_note', 'VARCHAR(255) NULL AFTER two_factor_secret');
        migration_add_index($pdo, 'admins', 'idx_admin_status_activity', '`status`,`last_activity_at`');

        migration_add_column($pdo, 'concerns', 'due_at', 'DATETIME NULL AFTER assigned_to');
        migration_add_column($pdo, 'concerns', 'follow_up_at', 'DATETIME NULL AFTER due_at');
        migration_add_column($pdo, 'concerns', 'first_response_at', 'DATETIME NULL AFTER follow_up_at');
        migration_add_column($pdo, 'concerns', 'last_public_update_at', 'DATETIME NULL AFTER first_response_at');
        migration_add_column($pdo, 'concerns', 'sensitivity', 'VARCHAR(20) NOT NULL DEFAULT \'standard\' AFTER anonymous');
        migration_add_column($pdo, 'concerns', 'retention_until', 'DATE NULL AFTER resolved_at');
        migration_add_column($pdo, 'concerns', 'sensitive_view_count', 'INT NOT NULL DEFAULT 0 AFTER retention_until');
        migration_add_index($pdo, 'concerns', 'idx_concern_work_queue', '`assigned_scope`,`status`,`due_at`,`priority`');
        migration_add_index($pdo, 'concerns', 'idx_concern_assignee', '`assigned_to`,`status`,`due_at`');
        migration_add_index($pdo, 'concerns', 'idx_concern_created', '`created_at`');

        migration_add_column($pdo, 'admin_notifications', 'group_key', 'VARCHAR(120) NULL AFTER category');
        migration_add_column($pdo, 'admin_notifications', 'dismissed_at', 'DATETIME NULL AFTER is_read');
        migration_add_index($pdo, 'admin_notifications', 'idx_notification_group', '`group_key`,`created_at`');

        migration_add_column($pdo, 'media_library', 'width', 'INT NULL AFTER file_size');
        migration_add_column($pdo, 'media_library', 'height', 'INT NULL AFTER width');
        migration_add_column($pdo, 'media_library', 'sha256', 'CHAR(64) NULL AFTER height');
        migration_add_column($pdo, 'media_library', 'last_verified_at', 'DATETIME NULL AFTER sha256');
        migration_add_index($pdo, 'media_library', 'idx_media_sha256', '`sha256`');

        $pdo->exec("CREATE TABLE IF NOT EXISTS concern_response_templates (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(150) NOT NULL,
        response_text TEXT NOT NULL,
        concern_type VARCHAR(50) NULL,
        campus VARCHAR(20) NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 1,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_response_template_scope(campus,is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS privacy_access_log (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NULL,
        concern_id INT NOT NULL,
        action VARCHAR(60) NOT NULL,
        purpose VARCHAR(255) NULL,
        ip_address VARCHAR(64) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_privacy_concern(concern_id,created_at),
        INDEX idx_privacy_admin(admin_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS backup_history (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        filename VARCHAR(255) NOT NULL,
        backup_type VARCHAR(30) NOT NULL DEFAULT 'database',
        file_size BIGINT NOT NULL DEFAULT 0,
        checksum_sha256 CHAR(64) NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        restored_by INT NULL,
        restored_at DATETIME NULL,
        INDEX idx_backup_created(created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }
    ],
    '2026081902' => [
    'description' => 'Operational defaults, SLA targets, retention settings, and starter response templates',
    'up' => function (PDO $pdo) : void {
        $defaults = [
        'esumbong_sla_urgent_hours' => '24', 'esumbong_sla_high_hours' => '48', 'esumbong_sla_normal_hours' => '120', 'esumbong_sla_low_hours' => '168',
        'privacy_retention_days' => '730', 'privacy_notice_version' => '2026-08-19', 'password_max_age_days' => '180', 'dormant_account_days' => '90', 'backup_reminder_days' => '7',
        'notification_email_enabled' => '0', 'security_require_2fa_for_admin' => '0', 'csp_enforcement' => 'report-only'
        ];
        $st = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key)");
        foreach ($defaults as $k => $v) $st->execute([$k, $v]);
        foreach (['Urgent' => 24, 'High' => 48, 'Normal' => 120, 'Low' => 168] as $priority => $hours) {
            $hours = (int)$hours;
            $st = $pdo->prepare("UPDATE concerns SET due_at=DATE_ADD(created_at, INTERVAL {$hours} HOUR) WHERE due_at IS NULL AND status NOT IN ('Resolved','Closed') AND priority=?");
            $st->execute([$priority]);
        }
        $pdo->exec("UPDATE concerns SET first_response_at=updated_at WHERE first_response_at IS NULL AND status NOT IN ('Submitted')");
        $pdo->exec("UPDATE concerns SET last_public_update_at=updated_at WHERE last_public_update_at IS NULL AND admin_note IS NOT NULL AND TRIM(admin_note)<>''");
        $count = (int)$pdo->query("SELECT COUNT(*) FROM concern_response_templates")->fetchColumn();
        if ($count === 0) {
            $st = $pdo->prepare("INSERT INTO concern_response_templates(title,response_text,concern_type,campus,created_by) VALUES(?,?,?,?,NULL)");
            $st->execute(['Acknowledgement', 'Your concern has been received and is now under review. We will update this tracking page when additional action is recorded.', null, null]);
            $st->execute(['Referred to responsible unit', 'Your concern has been referred to the appropriate office/unit for further review and action.', null, null]);
            $st->execute(['Action completed', 'Action has been taken on this concern. Please review the latest status and public note on your tracking page.', null, null]);
        }
    }
    ],
    '2026081903' => [
    'description' => 'Reporting and audit performance indexes',
    'up' => function (PDO $pdo) : void {
        migration_add_index($pdo, 'admin_activity_logs', 'idx_activity_module_created', '`module`,`created_at`');
        migration_add_index($pdo, 'admin_notifications', 'idx_notification_category', '`category`,`is_read`,`created_at`');
        migration_add_index($pdo, 'posts', 'idx_posts_status_category_date', '`status`,`category`,`published_at`');
        migration_add_index($pdo, 'admin_sessions', 'idx_sessions_active_seen', '`revoked_at`,`last_seen_at`');
    }
    ],
    '2026081905' => [
    'description' => 'Restore global upload-limit scope after Media Library-specific limit correction',
    'up' => function (PDO $pdo) : void {
        $st = $pdo->prepare("UPDATE system_settings SET setting_value='50' WHERE setting_key='default_upload_limit' AND CAST(setting_value AS UNSIGNED)>50");
        $st->execute();
    }
    ],
    '2026081906' => [
    'description' => 'Add relational foreign keys for administration, concerns, media, privacy, and recovery records',
    'up' => function (PDO $pdo) : void {
        $relations = [
        ['admins', 'fk_admins_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['admin_activity_logs', 'fk_activity_admin', 'admin_id', 'admins', 'id', 'SET NULL'],
        ['admin_notifications', 'fk_notifications_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['admin_notification_preferences', 'fk_notification_preferences_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['admin_sessions', 'fk_sessions_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['backup_history', 'fk_backups_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['backup_history', 'fk_backups_restored_by', 'restored_by', 'admins', 'id', 'SET NULL'],
        ['concerns', 'fk_concerns_assigned_to', 'assigned_to', 'admins', 'id', 'SET NULL'],
        ['concern_history', 'fk_concern_history_concern', 'concern_id', 'concerns', 'id', 'CASCADE'],
        ['concern_history', 'fk_concern_history_admin', 'admin_id', 'admins', 'id', 'SET NULL'],
        ['concern_history', 'fk_concern_history_assignee', 'assigned_to', 'admins', 'id', 'SET NULL'],
        ['concern_response_templates', 'fk_response_templates_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['hero_promotion_requests', 'fk_promo_source_slide', 'source_slide_id', 'hero_slides', 'id', 'RESTRICT'],
        ['hero_promotion_requests', 'fk_promo_requested_by', 'requested_by', 'admins', 'id', 'SET NULL'],
        ['hero_promotion_requests', 'fk_promo_reviewed_by', 'reviewed_by', 'admins', 'id', 'SET NULL'],
        ['hero_slides', 'fk_hero_panel_media', 'panel_media_id', 'media_library', 'id', 'SET NULL'],
        ['hero_slides', 'fk_hero_background_media', 'background_media_id', 'media_library', 'id', 'SET NULL'],
        ['media_library', 'fk_media_uploaded_by', 'uploaded_by', 'admins', 'id', 'SET NULL'],
        ['media_library', 'fk_media_deleted_by', 'deleted_by', 'admins', 'id', 'SET NULL'],
        ['posts', 'fk_posts_deleted_by', 'deleted_by', 'admins', 'id', 'SET NULL'],
        ['post_images', 'fk_post_images_media', 'media_id', 'media_library', 'id', 'SET NULL'],
        ['privacy_access_log', 'fk_privacy_admin', 'admin_id', 'admins', 'id', 'SET NULL'],
        ['privacy_access_log', 'fk_privacy_concern', 'concern_id', 'concerns', 'id', 'RESTRICT'],
        ['schema_migrations', 'fk_migrations_applied_by', 'applied_by', 'admins', 'id', 'SET NULL'],
        ['system_settings', 'fk_settings_updated_by', 'updated_by', 'admins', 'id', 'SET NULL'],
        ];
        foreach ($relations as $r) migration_add_foreign_key($pdo, ...$r);
    }
    ],
    '2026081907' => [
    'description' => 'Connect login-attempt security records to administrator accounts',
    'up' => function (PDO $pdo) : void {
        migration_add_column($pdo, 'admin_login_attempts', 'admin_id', 'INT NULL AFTER `attempt_key`');
        $pdo->exec("UPDATE admin_login_attempts ala INNER JOIN admins a ON a.username=ala.username SET ala.admin_id=a.id WHERE ala.admin_id IS NULL");
        migration_add_index($pdo, 'admin_login_attempts', 'idx_admin_login_admin', '`admin_id`');
        migration_add_foreign_key($pdo, 'admin_login_attempts', 'fk_login_attempts_admin', 'admin_id', 'admins', 'id', 'SET NULL');
    }
    ],
    '2026081908' => [
    'description' => 'Repair any partially imported foreign-key relationships without deleting records',
    'up' => function (PDO $pdo) : void {
        migration_add_column($pdo, 'admin_login_attempts', 'admin_id', 'INT NULL AFTER `attempt_key`');
        $pdo->exec("UPDATE admin_login_attempts ala INNER JOIN admins a ON LOWER(TRIM(a.username))=LOWER(TRIM(ala.username)) SET ala.admin_id=a.id WHERE ala.admin_id IS NULL");
        $relations = [
        ['admins', 'usc_fk_admins_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['admin_activity_logs', 'usc_fk_activity_admin', 'admin_id', 'admins', 'id', 'SET NULL'],
        ['admin_notifications', 'usc_fk_notifications_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['admin_notification_preferences', 'usc_fk_notification_preferences_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['admin_sessions', 'usc_fk_sessions_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['backup_history', 'usc_fk_backups_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['backup_history', 'usc_fk_backups_restored_by', 'restored_by', 'admins', 'id', 'SET NULL'],
        ['concerns', 'usc_fk_concerns_assigned_to', 'assigned_to', 'admins', 'id', 'SET NULL'],
        ['concern_history', 'usc_fk_concern_history_concern', 'concern_id', 'concerns', 'id', 'CASCADE'],
        ['concern_history', 'usc_fk_concern_history_admin', 'admin_id', 'admins', 'id', 'SET NULL'],
        ['concern_history', 'usc_fk_concern_history_assignee', 'assigned_to', 'admins', 'id', 'SET NULL'],
        ['concern_response_templates', 'usc_fk_response_templates_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['hero_promotion_requests', 'usc_fk_promo_source_slide', 'source_slide_id', 'hero_slides', 'id', 'RESTRICT'],
        ['hero_promotion_requests', 'usc_fk_promo_requested_by', 'requested_by', 'admins', 'id', 'SET NULL'],
        ['hero_promotion_requests', 'usc_fk_promo_reviewed_by', 'reviewed_by', 'admins', 'id', 'SET NULL'],
        ['hero_slides', 'usc_fk_hero_panel_media', 'panel_media_id', 'media_library', 'id', 'SET NULL'],
        ['hero_slides', 'usc_fk_hero_background_media', 'background_media_id', 'media_library', 'id', 'SET NULL'],
        ['media_library', 'usc_fk_media_uploaded_by', 'uploaded_by', 'admins', 'id', 'SET NULL'],
        ['media_library', 'usc_fk_media_deleted_by', 'deleted_by', 'admins', 'id', 'SET NULL'],
        ['posts', 'usc_fk_posts_deleted_by', 'deleted_by', 'admins', 'id', 'SET NULL'],
        ['post_images', 'usc_fk_post_images_media', 'media_id', 'media_library', 'id', 'SET NULL'],
        ['post_images', 'usc_fk_post_images_post', 'post_id', 'posts', 'id', 'CASCADE'],
        ['privacy_access_log', 'usc_fk_privacy_admin', 'admin_id', 'admins', 'id', 'SET NULL'],
        ['privacy_access_log', 'usc_fk_privacy_concern', 'concern_id', 'concerns', 'id', 'RESTRICT'],
        ['schema_migrations', 'usc_fk_migrations_applied_by', 'applied_by', 'admins', 'id', 'SET NULL'],
        ['system_settings', 'usc_fk_settings_updated_by', 'updated_by', 'admins', 'id', 'SET NULL'],
        ];
        foreach ($relations as $r) migration_add_foreign_key($pdo, ...$r);
    }
    ],
    '2026081909' => [
    'description' => 'Advanced E-Sumbong privacy, evidence attachments, publication revisions, recovery codes, and backup verification',
    'up' => function (PDO $pdo) : void {
        require_once __DIR__.'/security.php';

        migration_add_column($pdo, 'concerns', 'resolution_summary', 'TEXT NULL AFTER `internal_note`');
        migration_add_column($pdo, 'concerns', 'escalation_reason', 'TEXT NULL AFTER `resolution_summary`');
        migration_add_column($pdo, 'concerns', 'privacy_review_status', 'VARCHAR(30) NOT NULL DEFAULT \'pending\' AFTER `retention_until`');
        migration_add_column($pdo, 'concerns', 'privacy_reviewed_at', 'DATETIME NULL AFTER `privacy_review_status`');
        migration_add_column($pdo, 'concerns', 'privacy_reviewed_by', 'INT NULL AFTER `privacy_reviewed_at`');
        migration_add_index($pdo, 'concerns', 'idx_concern_privacy_review', '`privacy_review_status`,`retention_until`');

        $pdo->exec("CREATE TABLE IF NOT EXISTS concern_private_identity (
        concern_id INT PRIMARY KEY,
        student_name_cipher LONGTEXT NULL,
        student_id_cipher LONGTEXT NULL,
        encryption_version VARCHAR(20) NOT NULL DEFAULT 'v1',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS concern_attachments (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        concern_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NOT NULL,
        mime_type VARCHAR(120) NOT NULL,
        file_size BIGINT NOT NULL DEFAULT 0,
        sha256 CHAR(64) NULL,
        uploaded_by INT NULL,
        source VARCHAR(20) NOT NULL DEFAULT 'student',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_concern_attachment_case(concern_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS submission_rate_limits (
        bucket_key CHAR(64) PRIMARY KEY,
        attempts INT NOT NULL DEFAULT 0,
        window_started_at DATETIME NOT NULL,
        last_attempt_at DATETIME NOT NULL,
        blocked_until DATETIME NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        migration_add_column($pdo, 'posts', 'slug', 'VARCHAR(191) NULL AFTER `title`');
        migration_add_column($pdo, 'posts', 'review_note', 'TEXT NULL AFTER `scheduled_at`');
        migration_add_column($pdo, 'posts', 'published_by', 'INT NULL AFTER `review_note`');
        migration_add_column($pdo, 'posts', 'scheduled_by', 'INT NULL AFTER `published_by`');
        migration_add_index($pdo, 'posts', 'idx_posts_public_schedule', '`status`,`scheduled_at`,`published_at`,`category`');
        if (!migration_index_exists($pdo, 'posts', 'uq_posts_slug')) $pdo->exec("ALTER TABLE posts ADD UNIQUE INDEX uq_posts_slug(slug)");

        $pdo->exec("CREATE TABLE IF NOT EXISTS post_revisions (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        revision_no INT NOT NULL,
        snapshot_json LONGTEXT NOT NULL,
        change_note VARCHAR(255) NULL,
        created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uq_post_revision(post_id,revision_no),
        INDEX idx_post_revision_created(post_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_recovery_codes (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        code_hash CHAR(64) NOT NULL,
        used_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_recovery_admin(admin_id,used_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_password_history (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_password_history_admin(admin_id,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        migration_add_column($pdo, 'admins', 'approved_at', 'DATETIME NULL AFTER `created_by`');
        migration_add_column($pdo, 'admins', 'approved_by', 'INT NULL AFTER `approved_at`');
        $pdo->exec("ALTER TABLE admins MODIFY two_factor_secret TEXT NULL");

        migration_add_column($pdo, 'backup_history', 'verified_at', 'DATETIME NULL AFTER `restored_at`');
        migration_add_column($pdo, 'backup_history', 'verification_status', 'VARCHAR(30) NULL AFTER `verified_at`');
        migration_add_column($pdo, 'backup_history', 'verification_message', 'VARCHAR(500) NULL AFTER `verification_status`');

        $pdo->exec("CREATE TABLE IF NOT EXISTS notification_email_outbox (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        recipient VARCHAR(190) NOT NULL,
        subject VARCHAR(190) NOT NULL,
        body TEXT NOT NULL,
        category VARCHAR(40) NOT NULL DEFAULT 'general',
        status VARCHAR(30) NOT NULL DEFAULT 'queued',
        attempts INT NOT NULL DEFAULT 0,
        last_error VARCHAR(500) NULL,
        sent_at DATETIME NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_email_outbox(status,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $defaults = [
        'esumbong_attachment_max_files' => '3', 'esumbong_attachment_max_mb' => '5',
        'privacy_encrypt_new_identity' => '1', 'password_history_count' => '5', 'security_2fa_required_roles' => 'admin',
        'auto_publish_scheduled' => '1', 'auto_backup_enabled' => '0', 'auto_backup_type' => 'database', 'auto_backup_interval_hours' => '24',
        'auto_backup_retention_count' => '14', 'maintenance_last_run_at' => '', 'maintenance_last_backup_at' => '', 'maintenance_last_verification_at' => '',
        'email_from_name' => 'DMMMSU USC', 'email_from_address' => 'usc@dmmmsu.edu.ph'
        ];
        $st = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key)");
        foreach ($defaults as $k => $v) $st->execute([$k, $v]);

        // Preserve existing identity values while adding an encrypted protected copy.
        try {
            $rows = $pdo->query("SELECT id,student_name,student_id FROM concerns WHERE (student_name IS NOT NULL AND TRIM(student_name)<>'') OR (student_id IS NOT NULL AND TRIM(student_id)<>'')")->fetchAll();
            $ins = $pdo->prepare("INSERT INTO concern_private_identity(concern_id,student_name_cipher,student_id_cipher) VALUES(?,?,?) ON DUPLICATE KEY UPDATE student_name_cipher=COALESCE(student_name_cipher,VALUES(student_name_cipher)),student_id_cipher=COALESCE(student_id_cipher,VALUES(student_id_cipher))");
            foreach ($rows as $r) $ins->execute([(int)$r['id'], app_encrypt_sensitive((string)($r['student_name'] ?? '')), app_encrypt_sensitive((string)($r['student_id'] ?? ''))]);
        } catch (Throwable $ignored) {
        }
        try {
            $rows = $pdo->query("SELECT id,two_factor_secret FROM admins WHERE two_factor_secret IS NOT NULL AND TRIM(two_factor_secret)<>''")->fetchAll();
            $up = $pdo->prepare('UPDATE admins SET two_factor_secret=? WHERE id=?');
            foreach ($rows as $r) {
                $secret = (string)$r['two_factor_secret'];
                if (!app_sensitive_is_encrypted($secret)) $up->execute([app_encrypt_sensitive($secret), (int)$r['id']]);
            }
        } catch (Throwable $ignored) {
        }

        // Generate unique slugs for existing publications without changing titles or IDs.
        try {
            $rows = $pdo->query('SELECT id,title,slug FROM posts ORDER BY id')->fetchAll();
            $used = [];
            $up = $pdo->prepare('UPDATE posts SET slug=? WHERE id=?');
            foreach ($rows as $r) {
                $existing = trim((string)($r['slug'] ?? ''));
                if ($existing !== '') {
                    $used[$existing] = true;
                    continue;
                }
                $base = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', (string)$r['title']), '-'))?:'publication';
                $slug = $base;
                $n = 2;
                while (isset($used[$slug])) $slug = $base.'-'.$n++;
                $used[$slug] = true;
                $up->execute([$slug, (int)$r['id']]);
            }
        } catch (Throwable $ignored) {
        }

        $relations = [
        ['concern_private_identity', 'usc_fk_private_identity_concern', 'concern_id', 'concerns', 'id', 'CASCADE'],
        ['concern_attachments', 'usc_fk_concern_attachments_concern', 'concern_id', 'concerns', 'id', 'CASCADE'],
        ['concern_attachments', 'usc_fk_concern_attachments_admin', 'uploaded_by', 'admins', 'id', 'SET NULL'],
        ['concerns', 'usc_fk_concern_privacy_reviewer', 'privacy_reviewed_by', 'admins', 'id', 'SET NULL'],
        ['posts', 'usc_fk_posts_published_by', 'published_by', 'admins', 'id', 'SET NULL'],
        ['posts', 'usc_fk_posts_scheduled_by', 'scheduled_by', 'admins', 'id', 'SET NULL'],
        ['post_revisions', 'usc_fk_post_revisions_post', 'post_id', 'posts', 'id', 'CASCADE'],
        ['post_revisions', 'usc_fk_post_revisions_admin', 'created_by', 'admins', 'id', 'SET NULL'],
        ['admin_recovery_codes', 'usc_fk_recovery_codes_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['admin_password_history', 'usc_fk_password_history_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['admins', 'usc_fk_admins_approved_by', 'approved_by', 'admins', 'id', 'SET NULL'],
        ];
        foreach ($relations as $r) migration_add_foreign_key($pdo, ...$r);
    }
    ],
    '2026081910' => [
    'description' => 'Performance indexes for public newsroom search and operational dashboards',
    'up' => function (PDO $pdo) : void {
        migration_add_index($pdo, 'posts', 'idx_posts_category_status_slug', '`category`,`status`,`slug`');
        migration_add_index($pdo, 'concerns', 'idx_concern_status_created', '`status`,`created_at`');
        migration_add_index($pdo, 'concerns', 'idx_concern_followup', '`status`,`follow_up_at`');
        migration_add_index($pdo, 'backup_history', 'idx_backup_verify', '`verification_status`,`verified_at`');
    }
    ],
    '2026081911' => [
    'description' => 'Optional encrypted student contact email for E-Sumbong status notifications',
    'up' => function (PDO $pdo) : void {
        migration_add_column($pdo, 'concern_private_identity', 'contact_email_cipher', 'LONGTEXT NULL AFTER `student_id_cipher`');
        $st = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key)");
        $st->execute(['esumbong_email_updates_enabled', '1']);
    }
    ],
    '2026081912' => [
    'description' => 'Pending-by-default administrator account approval policy',
    'up' => function (PDO $pdo) : void {
        try {
            $pdo->exec("ALTER TABLE admins MODIFY status VARCHAR(20) NOT NULL DEFAULT 'pending'");
        } catch (Throwable $ignored) {
        }
    }
    ],
    '2026081913' => [
    'description' => 'Governance, academic-year history, configurable permissions, announcements, login history, storage optimization, and disaster-recovery metadata',
    'up' => function (PDO $pdo) : void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS academic_years (
        id INT AUTO_INCREMENT PRIMARY KEY,label VARCHAR(80) NOT NULL UNIQUE,start_date DATE NOT NULL,end_date DATE NOT NULL,
        is_active TINYINT(1) NOT NULL DEFAULT 0,is_archived TINYINT(1) NOT NULL DEFAULT 0,created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_academic_year_active(is_active,is_archived,start_date)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS officers (
        id INT AUTO_INCREMENT PRIMARY KEY,academic_year_id INT NULL,portal_code VARCHAR(10) NOT NULL DEFAULT 'USC',full_name VARCHAR(150) NOT NULL,
        position_title VARCHAR(150) NOT NULL,office_name VARCHAR(150) NULL,email VARCHAR(180) NULL,start_date DATE NULL,end_date DATE NULL,
        is_archived TINYINT(1) NOT NULL DEFAULT 0,sort_order INT NOT NULL DEFAULT 0,created_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_officers_scope_year(portal_code,academic_year_id,is_archived,sort_order)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,academic_year_id INT NULL,portal_code VARCHAR(10) NOT NULL DEFAULT 'ALL',title VARCHAR(190) NOT NULL,
        message TEXT NOT NULL,priority VARCHAR(20) NOT NULL DEFAULT 'info',audience VARCHAR(20) NOT NULL DEFAULT 'public',status VARCHAR(20) NOT NULL DEFAULT 'draft',
        starts_at DATETIME NULL,ends_at DATETIME NULL,link_label VARCHAR(80) NULL,link_url VARCHAR(255) NULL,created_by INT NULL,updated_by INT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_announcements_public(status,audience,portal_code,starts_at,ends_at),INDEX idx_announcements_year(academic_year_id,status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS role_permission_overrides (
        role VARCHAR(40) NOT NULL,permission VARCHAR(100) NOT NULL,allowed TINYINT(1) NOT NULL,updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(role,permission)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_permission_overrides (
        admin_id INT NOT NULL,permission VARCHAR(100) NOT NULL,allowed TINYINT(1) NOT NULL,updated_by INT NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(admin_id,permission)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_dashboard_widgets (
        admin_id INT NOT NULL,widget_key VARCHAR(60) NOT NULL,is_visible TINYINT(1) NOT NULL DEFAULT 1,sort_order INT NOT NULL DEFAULT 0,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,PRIMARY KEY(admin_id,widget_key)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $pdo->exec("CREATE TABLE IF NOT EXISTS admin_login_events (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,admin_id INT NULL,username VARCHAR(150) NULL,event_type VARCHAR(50) NOT NULL,result VARCHAR(20) NOT NULL,
        reason VARCHAR(255) NULL,ip_address VARCHAR(64) NULL,user_agent VARCHAR(500) NULL,device_label VARCHAR(120) NULL,created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_login_events_admin(admin_id,created_at),INDEX idx_login_events_result(result,created_at),INDEX idx_login_events_ip(ip_address,created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        migration_add_column($pdo, 'admins', 'status_reason', 'VARCHAR(255) NULL AFTER `status`');
        migration_add_column($pdo, 'admins', 'status_changed_at', 'DATETIME NULL AFTER `status_reason`');
        migration_add_column($pdo, 'admins', 'archived_at', 'DATETIME NULL AFTER `status_changed_at`');
        migration_add_column($pdo, 'posts', 'academic_year_id', 'INT NULL AFTER `scheduled_by`');
        migration_add_column($pdo, 'concerns', 'academic_year_id', 'INT NULL AFTER `assigned_scope`');
        migration_add_column($pdo, 'media_library', 'thumbnail_path', 'VARCHAR(255) NULL AFTER `file_path`');
        migration_add_column($pdo, 'media_library', 'optimized_at', 'DATETIME NULL AFTER `last_verified_at`');
        migration_add_column($pdo, 'admin_activity_logs', 'prev_hash', 'CHAR(64) NULL AFTER `user_agent`');
        migration_add_column($pdo, 'admin_activity_logs', 'record_hash', 'CHAR(64) NULL AFTER `prev_hash`');
        migration_add_column($pdo, 'admin_notifications', 'expires_at', 'DATETIME NULL AFTER `dismissed_at`');
        migration_add_column($pdo, 'backup_history', 'offsite_copied_at', 'DATETIME NULL AFTER `verification_message`');
        migration_add_column($pdo, 'backup_history', 'offsite_path', 'VARCHAR(500) NULL AFTER `offsite_copied_at`');
        migration_add_column($pdo, 'schema_migrations', 'safety_backup_filename', 'VARCHAR(255) NULL AFTER `description`');
        migration_add_column($pdo, 'schema_migrations', 'duration_ms', 'INT NULL AFTER `applied_by`');
        migration_add_column($pdo, 'schema_migrations', 'result', "VARCHAR(30) NOT NULL DEFAULT 'applied' AFTER `duration_ms`");
        migration_add_index($pdo, 'posts', 'idx_posts_academic_year', '`academic_year_id`,`status`,`published_at`');
        migration_add_index($pdo, 'concerns', 'idx_concerns_academic_year', '`academic_year_id`,`status`,`created_at`');
        migration_add_index($pdo, 'admin_activity_logs', 'idx_activity_hash', '`record_hash`');

        $defaults = [
        'active_academic_year_id' => '0', 'notification_retention_days' => '180', 'storage_warning_percent' => '85', 'storage_critical_percent' => '95',
        'offsite_backup_enabled' => '0', 'offsite_backup_path' => '', 'media_auto_optimize' => '1', 'media_thumbnail_enabled' => '1',
        'security_rate_limit_tracking_per_hour' => '30', 'security_rate_limit_uploads_per_hour' => '60', 'public_network_status' => '1'
        ];
        $st = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key)");
        foreach ($defaults as $k => $v) $st->execute([$k, $v]);

        $relations = [
        ['academic_years', 'usc_fk_academic_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['officers', 'usc_fk_officers_year', 'academic_year_id', 'academic_years', 'id', 'SET NULL'], ['officers', 'usc_fk_officers_created_by', 'created_by', 'admins', 'id', 'SET NULL'],
        ['announcements', 'usc_fk_announcements_year', 'academic_year_id', 'academic_years', 'id', 'SET NULL'], ['announcements', 'usc_fk_announcements_created_by', 'created_by', 'admins', 'id', 'SET NULL'], ['announcements', 'usc_fk_announcements_updated_by', 'updated_by', 'admins', 'id', 'SET NULL'],
        ['role_permission_overrides', 'usc_fk_role_permission_updated_by', 'updated_by', 'admins', 'id', 'SET NULL'],
        ['admin_permission_overrides', 'usc_fk_admin_permission_admin', 'admin_id', 'admins', 'id', 'CASCADE'], ['admin_permission_overrides', 'usc_fk_admin_permission_updated_by', 'updated_by', 'admins', 'id', 'SET NULL'],
        ['admin_dashboard_widgets', 'usc_fk_dashboard_widgets_admin', 'admin_id', 'admins', 'id', 'CASCADE'],
        ['admin_login_events', 'usc_fk_login_events_admin', 'admin_id', 'admins', 'id', 'SET NULL'],
        ['posts', 'usc_fk_posts_academic_year', 'academic_year_id', 'academic_years', 'id', 'SET NULL'], ['concerns', 'usc_fk_concerns_academic_year', 'academic_year_id', 'academic_years', 'id', 'SET NULL']
        ];
        foreach ($relations as $r) migration_add_foreign_key($pdo, ...$r);
    }
    ],
    '2026081914' => [
    'description' => 'Create a current academic year plus a legacy bucket and classify existing records without deleting or rewriting content',
    'up' => function (PDO $pdo) : void {
        if (!migration_table_exists($pdo, 'academic_years')) return;
        $year = (int)date('Y');
        $month = (int)date('n');
        $startYear = $month >= 6?$year:$year-1;
        $label = $startYear.'-'.($startYear+1);
        $st = $pdo->prepare("INSERT INTO academic_years(label,start_date,end_date,is_active,is_archived) VALUES(?, ?, ?,1,0) ON DUPLICATE KEY UPDATE is_active=1,is_archived=0");
        $st->execute([$label, $startYear.'-06-01', ($startYear+1).'-05-31']);
        $activeId = (int)$pdo->query("SELECT id FROM academic_years WHERE label=".$pdo->quote($label)." LIMIT 1")->fetchColumn();
        $pdo->exec("UPDATE academic_years SET is_active=0 WHERE id<>".$activeId);
        $st = $pdo->prepare("INSERT INTO academic_years(label,start_date,end_date,is_active,is_archived) VALUES('Legacy / Imported','2000-01-01','2000-12-31',0,1) ON DUPLICATE KEY UPDATE label=VALUES(label)");
        $st->execute();
        $legacyId = (int)$pdo->query("SELECT id FROM academic_years WHERE label='Legacy / Imported' LIMIT 1")->fetchColumn();
        $set = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES('active_academic_year_id',?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
        $set->execute([(string)$activeId]);
        if (migration_column_exists($pdo, 'posts', 'academic_year_id')) $pdo->exec("UPDATE posts SET academic_year_id=CASE WHEN created_at>='{$startYear}-06-01' THEN {$activeId} ELSE {$legacyId} END WHERE academic_year_id IS NULL");
        if (migration_column_exists($pdo, 'concerns', 'academic_year_id')) $pdo->exec("UPDATE concerns SET academic_year_id=CASE WHEN created_at>='{$startYear}-06-01' THEN {$activeId} ELSE {$legacyId} END WHERE academic_year_id IS NULL");
    }
    ],
    '2026081915' => [
    'description' => 'Backfill tamper-evident audit hash chain for existing activity records',
    'up' => function (PDO $pdo) : void {
        if (!migration_column_exists($pdo, 'admin_activity_logs', 'record_hash')) return;
        $rows = $pdo->query('SELECT * FROM admin_activity_logs ORDER BY id')->fetchAll();
        $prev = str_repeat('0', 64);
        $up = $pdo->prepare('UPDATE admin_activity_logs SET prev_hash=?,record_hash=? WHERE id=?');
        foreach ($rows as $row) {
            $parts = [$prev, (string)($row['admin_id'] ?? ''), (string)($row['admin_name'] ?? ''), (string)($row['role'] ?? ''), (string)($row['campus'] ?? ''), (string)($row['module'] ?? ''), (string)($row['action'] ?? ''), (string)($row['description'] ?? ''), (string)($row['entity_type'] ?? ''), (string)($row['entity_id'] ?? ''), (string)($row['old_values'] ?? ''), (string)($row['new_values'] ?? ''), (string)($row['ip_address'] ?? ''), (string)($row['user_agent'] ?? ''), (string)($row['created_at'] ?? '')];
            $hash = hash('sha256', implode("\x1f", $parts));
            $up->execute([$prev, $hash, (int)$row['id']]);
            $prev = $hash;
        }
    }
    ],
    '2026081916' => [
    'description' => 'Additional operational indexes for announcements, login security, search, and long-term university records',
    'up' => function (PDO $pdo) : void {
        migration_add_index($pdo, 'admins', 'idx_admin_lifecycle', '`status`,`status_changed_at`,`archived_at`');
        migration_add_index($pdo, 'media_library', 'idx_media_optimized', '`optimized_at`,`created_at`');
        migration_add_index($pdo, 'admin_notifications', 'idx_notifications_expiry', '`expires_at`,`dismissed_at`,`created_at`');
    }
    ],
    '2026082001' => [
    'description' => 'Add optional officer portrait photos for the public Meet the Officers roster',
    'up' => function (PDO $pdo) : void {
        if (!migration_table_exists($pdo, 'officers')) return;
        migration_add_column($pdo, 'officers', 'photo_path', 'VARCHAR(255) NULL AFTER `email`');
    }
    ],
    '2026082002' => [
    'description' => 'Connect rate-limit and queued-email operational records to administrator and E-Sumbong entities',
    'up' => function (PDO $pdo) : void {
        if (migration_table_exists($pdo, 'submission_rate_limits')) {
            migration_add_column($pdo, 'submission_rate_limits', 'admin_id', 'INT NULL AFTER `bucket_key`');
            migration_add_index($pdo, 'submission_rate_limits', 'idx_rate_limit_admin', '`admin_id`,`last_attempt_at`');
            migration_add_foreign_key($pdo, 'submission_rate_limits', 'usc_fk_rate_limit_admin', 'admin_id', 'admins', 'id', 'SET NULL');
        }
        if (migration_table_exists($pdo, 'notification_email_outbox')) {
            migration_add_column($pdo, 'notification_email_outbox', 'admin_id', 'INT NULL AFTER `id`');
            migration_add_column($pdo, 'notification_email_outbox', 'concern_id', 'INT NULL AFTER `admin_id`');
            migration_add_index($pdo, 'notification_email_outbox', 'idx_email_outbox_admin', '`admin_id`,`created_at`');
            migration_add_index($pdo, 'notification_email_outbox', 'idx_email_outbox_concern', '`concern_id`,`created_at`');
            migration_add_foreign_key($pdo, 'notification_email_outbox', 'usc_fk_email_outbox_admin', 'admin_id', 'admins', 'id', 'SET NULL');
            migration_add_foreign_key($pdo, 'notification_email_outbox', 'usc_fk_email_outbox_concern', 'concern_id', 'concerns', 'id', 'SET NULL');
        }
    }
    ],
    '2026082003' => [
    'description' => 'Remove redundant duplicate login-attempt foreign-key relationship',
    'up' => function (PDO $pdo) : void {
        if (!migration_table_exists($pdo, 'admin_login_attempts')) return;
        $hasPrimary = migration_foreign_key_exists($pdo, 'admin_login_attempts', 'fk_login_attempts_admin');
        $hasDuplicate = migration_foreign_key_exists($pdo, 'admin_login_attempts', 'usc_fk_login_attempts_admin');
        if ($hasPrimary && $hasDuplicate) {
            $pdo->exec("ALTER TABLE `admin_login_attempts` DROP FOREIGN KEY `usc_fk_login_attempts_admin`");
        }
    }
    ],
    '2026083102' => [
    'description' => 'Add a separately controlled university-wide Featured flag for News & Updates',
    'up' => function (PDO $pdo) : void {
        if (!migration_table_exists($pdo, 'posts')) return;
        migration_add_column($pdo, 'posts', 'is_university_featured', 'TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_featured`');
        migration_add_index($pdo, 'posts', 'idx_posts_university_featured', '`is_university_featured`,`status`,`published_at`');
    }
    ],
    '2026083103' => [
    'description' => 'Remove the obsolete campus-to-USC News feature-request workflow if it was previously installed',
    'up' => function (PDO $pdo) : void {
        if (migration_table_exists($pdo, 'post_feature_requests')) $pdo->exec('DROP TABLE `post_feature_requests`');
    }
    ],
    '2026090101' => [
    'description' => 'Normalize independent per-portal and university-wide News Featured slots',
    'up' => function (PDO $pdo) : void {
        if (!migration_table_exists($pdo, 'posts')) return;
        foreach (['usc', 'nluc', 'mluc', 'sluc', 'ous'] as $category) {
            $st = $pdo->prepare("SELECT id FROM posts WHERE category=? AND is_featured=1 AND deleted_at IS NULL ORDER BY (status='published') DESC,published_at DESC,id DESC LIMIT 1");
            $st->execute([$category]);
            $keepId = (int)($st->fetchColumn() ?: 0);
            if ($keepId > 0) {
                $pdo->prepare('UPDATE posts SET is_featured=0 WHERE category=? AND is_featured=1 AND id<>?')->execute([$category, $keepId]);
            }
        }
        if (migration_column_exists($pdo, 'posts', 'is_university_featured')) {
            $keepId = (int)($pdo->query("SELECT id FROM posts WHERE is_university_featured=1 AND deleted_at IS NULL ORDER BY (status='published') DESC,published_at DESC,id DESC LIMIT 1")->fetchColumn() ?: 0);
            if ($keepId > 0) {
                $pdo->prepare('UPDATE posts SET is_university_featured=0 WHERE is_university_featured=1 AND id<>?')->execute([$keepId]);
            }
        }
    }
    ],
    '2026090102' => [
    'description' => 'Add publication video attachments with 250 MB application limit',
    'up' => function (PDO $pdo) : void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS post_videos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        post_id INT NOT NULL,
        file_path VARCHAR(255) NOT NULL,
        original_name VARCHAR(255) NULL,
        media_id INT NULL,
        mime_type VARCHAR(100) NOT NULL,
        file_size BIGINT UNSIGNED NOT NULL DEFAULT 0,
        sort_order INT NOT NULL DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_post_videos_post_id (post_id),
        INDEX idx_post_videos_media_id (media_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        migration_add_foreign_key($pdo, 'post_videos', 'usc_fk_post_videos_post', 'post_id', 'posts', 'id', 'CASCADE');
        if (migration_table_exists($pdo, 'media_library')) migration_add_foreign_key($pdo, 'post_videos', 'usc_fk_post_videos_media', 'media_id', 'media_library', 'id', 'SET NULL');
    }
    ],
    '2026090501' => [
    'description' => 'Add type-aware E-Sumbong submission policy, student urgency, and Suggestion/Feedback cooldown',
    'up' => function (PDO $pdo) : void {
        if (migration_table_exists($pdo, 'concerns')) {
            migration_add_column($pdo, 'concerns', 'student_urgency', 'VARCHAR(20) NULL AFTER `concern_type`');
        }
        if (migration_table_exists($pdo, 'system_settings')) {
            $st = $pdo->prepare("INSERT INTO system_settings(setting_key,setting_value) VALUES(?,?) ON DUPLICATE KEY UPDATE setting_key=VALUES(setting_key)");
            $st->execute(['esumbong_suggestion_feedback_cooldown_hours', '72']);
        }
    }
    ],
    '2026090503' => [
    'description' => 'Add controlled E-Sumbong student identity access requests and approvals',
    'up' => function (PDO $pdo) : void {
        $pdo->exec("CREATE TABLE IF NOT EXISTS concern_identity_access_requests (
        id BIGINT AUTO_INCREMENT PRIMARY KEY,
        concern_id INT NOT NULL,
        requested_by INT NULL,
        requester_name VARCHAR(160) NULL,
        reason VARCHAR(500) NOT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'pending',
        requested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_by INT NULL,
        reviewed_at DATETIME NULL,
        review_note VARCHAR(500) NULL,
        approved_until DATETIME NULL,
        last_viewed_at DATETIME NULL,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_identity_request_case_status(concern_id,status,requested_at),
        INDEX idx_identity_request_admin_status(requested_by,status,requested_at),
        INDEX idx_identity_request_approval(status,approved_until)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        if (migration_table_exists($pdo, 'concerns')) {
            migration_add_foreign_key($pdo, 'concern_identity_access_requests', 'usc_fk_identity_request_concern', 'concern_id', 'concerns', 'id', 'CASCADE');
        }
        if (migration_table_exists($pdo, 'admins')) {
            migration_add_foreign_key($pdo, 'concern_identity_access_requests', 'usc_fk_identity_request_requester', 'requested_by', 'admins', 'id', 'SET NULL');
            migration_add_foreign_key($pdo, 'concern_identity_access_requests', 'usc_fk_identity_request_reviewer', 'reviewed_by', 'admins', 'id', 'SET NULL');
        }
    }
    ],
    ];
}
function migration_applied_versions(PDO $pdo): array {
    if (!migration_table_exists($pdo, 'schema_migrations')) return [];
    $rows = $pdo->query('SELECT version,description,applied_by,applied_at FROM schema_migrations ORDER BY version')->fetchAll();
    $out = [];
    foreach ($rows as $r) $out[$r['version']] = $r;
    return $out;
}
function migration_status(PDO $pdo): array {
    $applied = migration_applied_versions($pdo);
    $all = app_migrations();
    $rows = [];
    foreach ($all as $version => $migration) $rows[] = ['version' => $version, 'description' => $migration['description'], 'applied' => isset($applied[$version]), 'applied_at' => $applied[$version]['applied_at'] ?? null];
    return $rows;
}
function migration_pending_count(PDO $pdo): int {
    return count(array_filter(migration_status($pdo), fn($m) => !$m['applied']));
}
function migration_run_pending(PDO $pdo, ?int $adminId = null): array {
    migration_create_registry($pdo);
    $applied = migration_applied_versions($pdo);
    $ran = [];
    foreach (app_migrations() as $version => $migration) {
        if (isset($applied[$version])) continue;
        // MySQL DDL causes implicit commits, so migrations are deliberately not wrapped
        // in a transaction. Each migration is idempotent and is recorded only after success.
        try {
            $started = microtime(true);
            ($migration['up'])($pdo);
            $duration = (int)round((microtime(true)-$started)*1000);
            if (migration_column_exists($pdo, 'schema_migrations', 'duration_ms')) {
                $st = $pdo->prepare('INSERT INTO schema_migrations(version,description,applied_by,duration_ms,result) VALUES(?,?,?,?,?)');
                $st->execute([$version, $migration['description'], $adminId, $duration, 'applied']);
            }
            else {
                $st = $pdo->prepare('INSERT INTO schema_migrations(version,description,applied_by) VALUES(?,?,?)');
                $st->execute([$version, $migration['description'], $adminId]);
            }
            $ran[] = $version;
        } catch (Throwable $e) {
            throw $e;
        }
    }
    return $ran;
}
