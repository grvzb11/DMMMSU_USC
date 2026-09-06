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

function maintenance_backup_dir(): string {
    $d = app_root('backups');
    if (!is_dir($d)) @mkdir($d, 0750, true);
    return $d;
}
function maintenance_safe_identifier(string $name): string {
    return '`'.str_replace('`', '``', $name).'`';
}
function maintenance_sql_value(PDO $pdo, mixed $value): string {
    if ($value === null) return 'NULL';
    if (is_int($value) || is_float($value)) return (string)$value;
    return $pdo->quote((string)$value);
}
function maintenance_create_database_backup(PDO $pdo, ?int $adminId = null): array {
    $dir = maintenance_backup_dir();
    $stamp = date('Ymd_His');
    $filename = 'dmmmsu_usc_'.$stamp.'_database.sql';
    $path = $dir.'/'.$filename;
    $fh = fopen($path, 'wb');
    if (!$fh) throw new RuntimeException('Unable to create backup file.');
    fwrite($fh, "-- DMMMSU USC protected database backup\n-- Generated: ".date(DATE_ATOM)."\nSET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");
    $tables = $pdo->query('SHOW FULL TABLES WHERE Table_type = \'BASE TABLE\'')->fetchAll(PDO::FETCH_NUM);
    foreach ($tables as $row) {
        $table = (string)$row[0];
        $create = $pdo->query('SHOW CREATE TABLE '.maintenance_safe_identifier($table))->fetch(PDO::FETCH_NUM);
        fwrite($fh, 'DROP TABLE IF EXISTS '.maintenance_safe_identifier($table).";\n".($create[1] ?? '').";\n");
        $st = $pdo->query('SELECT * FROM '.maintenance_safe_identifier($table));
        while ($data = $st->fetch(PDO::FETCH_ASSOC)) {
            $cols = array_map('maintenance_safe_identifier', array_keys($data));
            $vals = array_map(fn($v) => maintenance_sql_value($pdo, $v), array_values($data));
            fwrite($fh, 'INSERT INTO '.maintenance_safe_identifier($table).' ('.implode(',', $cols).') VALUES ('.implode(',', $vals).');'."\n");
        }
        fwrite($fh, "\n");
    }
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);
    $size = filesize($path)?:0;
    $hash = hash_file('sha256', $path)?:null;
    try {
        $st = $pdo->prepare('INSERT INTO backup_history(filename,backup_type,file_size,checksum_sha256,created_by) VALUES(?,?,?,?,?)');
        $st->execute([$filename, 'database', $size, $hash, $adminId]);
    } catch (Throwable $ignored) {
    }
    return ['filename' => $filename, 'path' => $path, 'size' => $size, 'sha256' => $hash, 'type' => 'database'];
}
function maintenance_create_full_backup(PDO $pdo, ?int $adminId = null): array {
    if (!class_exists('ZipArchive')) throw new RuntimeException('PHP ZipArchive is not enabled. Create a database backup and copy the uploads folder separately.');
    $db = maintenance_create_database_backup($pdo, $adminId);
    $dir = maintenance_backup_dir();
    $stamp = date('Ymd_His');
    $filename = 'dmmmsu_usc_'.$stamp.'_full.zip';
    $path = $dir.'/'.$filename;
    $zip = new ZipArchive();
    if ($zip->open($path, ZipArchive::CREATE|ZipArchive::OVERWRITE) !== true) throw new RuntimeException('Unable to create full backup archive.');
    $zip->addFile($db['path'], 'database/'.$db['filename']);
    $keyPath = app_storage_path('keys/data.key');
    if (is_file($keyPath)) $zip->addFile($keyPath, 'security/data.key');
    $uploads = app_root('uploads');
    if (is_dir($uploads)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $file) {
            $rel = 'uploads/'.str_replace('\\', '/', substr($file->getPathname(), strlen($uploads)+1));
            if ($file->isDir()) $zip->addEmptyDir($rel);
            else $zip->addFile($file->getPathname(), $rel);
        }
    }
    $zip->close();
    @unlink($db['path']);
    $size = filesize($path)?:0;
    $hash = hash_file('sha256', $path)?:null;
    try {
        $pdo->prepare('DELETE FROM backup_history WHERE filename=?')->execute([$db['filename']]);
        $st = $pdo->prepare('INSERT INTO backup_history(filename,backup_type,file_size,checksum_sha256,created_by) VALUES(?,?,?,?,?)');
        $st->execute([$filename, 'full', $size, $hash, $adminId]);
    } catch (Throwable $ignored) {
    }
    return ['filename' => $filename, 'path' => $path, 'size' => $size, 'sha256' => $hash, 'type' => 'full'];
}
function maintenance_list_backups(PDO $pdo): array {
    $rows = [];
    try {
        $rows = $pdo->query('SELECT b.*,a.full_name created_by_name,r.full_name restored_by_name FROM backup_history b LEFT JOIN admins a ON a.id=b.created_by LEFT JOIN admins r ON r.id=b.restored_by ORDER BY b.created_at DESC LIMIT 50')->fetchAll();
    } catch (Throwable $ignored) {
    }
    $known = [];
    foreach ($rows as $r) $known[$r['filename']] = true;
    foreach (glob(maintenance_backup_dir().'/*.{sql,zip}', GLOB_BRACE)?:[] as $path) {
        $name = basename($path);
        if (isset($known[$name])) continue;
        $rows[] = ['id' => 0, 'filename' => $name, 'backup_type' => str_ends_with($name, '.zip')?'full':'database', 'file_size' => filesize($path)?:0, 'checksum_sha256' => hash_file('sha256', $path)?:null, 'created_at' => date('Y-m-d H:i:s', filemtime($path)?:time()), 'created_by_name' => 'Filesystem', 'restored_at' => null, 'restored_by_name' => null];
    }
    usort($rows, fn($a, $b) => strcmp((string)$b['created_at'], (string)$a['created_at']));
    return $rows;
}
function maintenance_split_sql(string $sql): array {
    $statements = [];
    $buf = '';
    $quote = null;
    $escape = false;
    $len = strlen($sql);
    for ($i = 0; $i<$len; $i++) {
        $c = $sql[$i];
        if ($quote !== null) {
            $buf.=$c;
            if ($escape) {
                $escape = false;
                continue;
            }
            if ($c === '\\') {
                $escape = true;
                continue;
            }
            if ($c === $quote) $quote = null;
            continue;
        }
        if ($c === "'" || $c === '"' || $c === '`') {
            $quote = $c;
            $buf.=$c;
            continue;
        }
        if ($c === '-' && $i+1<$len && $sql[$i+1] === '-' && ($i === 0 || ctype_space($sql[$i-1]))) {
            while ($i<$len && $sql[$i] !== "\n") $i++;
            $buf.="\n";
            continue;
        }
        if ($c === ';') {
            if (trim($buf) !== '') $statements[] = trim($buf);
            $buf = '';
            continue;
        }
        $buf.=$c;
    }
    if (trim($buf) !== '') $statements[] = trim($buf);
    return $statements;
}
function maintenance_restore_database_backup(PDO $pdo, string $filename, ?int $adminId = null): int {
    if (!preg_match('/^[A-Za-z0-9_.-]+\.sql$/', $filename)) throw new InvalidArgumentException('Only protected SQL backups can be restored in the web interface.');
    $path = realpath(maintenance_backup_dir().'/'.$filename);
    $root = realpath(maintenance_backup_dir());
    if (!$path || !$root || !str_starts_with($path, $root.DIRECTORY_SEPARATOR)) throw new RuntimeException('Backup file not found.');
    $sql = file_get_contents($path);
    if ($sql === false || !str_contains($sql, 'DMMMSU USC protected database backup')) throw new RuntimeException('This is not a recognized system-generated backup.');
    $statements = maintenance_split_sql($sql);
    $count = 0;
    $pdo->exec('SET FOREIGN_KEY_CHECKS=0');
    try {
        foreach ($statements as $statement) {
            $t = trim($statement);
            if ($t === '' || str_starts_with(strtoupper($t), 'SET NAMES')) continue;
            $pdo->exec($statement);
            $count++;
        }
    } finally {
        $pdo->exec('SET FOREIGN_KEY_CHECKS=1');
    }
    try {
        $st = $pdo->prepare('UPDATE backup_history SET restored_by=?,restored_at=NOW() WHERE filename=?');
        $st->execute([$adminId, $filename]);
    } catch (Throwable $ignored) {
    }
    return $count;
}

/** Verify that a protected backup is unchanged and structurally recognizable. */
function maintenance_verify_backup(PDO $pdo, string $filename): array {
    if (!preg_match('/^[A-Za-z0-9_.-]+\.(sql|zip)$/i', $filename)) throw new InvalidArgumentException('Invalid backup filename.');
    $path = realpath(maintenance_backup_dir().'/'.$filename);
    $root = realpath(maintenance_backup_dir());
    if (!$path || !$root || !str_starts_with($path, $root.DIRECTORY_SEPARATOR) || !is_file($path)) throw new RuntimeException('Backup file not found.');
    $actual = hash_file('sha256', $path)?:'';
    $message = 'Checksum and structure verified.';
    $ok = true;
    try {
        $st = $pdo->prepare('SELECT checksum_sha256 FROM backup_history WHERE filename=? LIMIT 1');
        $st->execute([$filename]);
        $expected = (string)($st->fetchColumn()?:'');
        if ($expected !== '' && !hash_equals($expected, $actual)) {
            $ok = false;
            $message = 'Checksum mismatch: the backup file has changed since it was created.';
        }
    } catch (Throwable $ignored) {
    }
    if ($ok && str_ends_with(strtolower($filename), '.sql')) {
        $head = (string)file_get_contents($path, false, null, 0, 512);
        if (!str_contains($head, 'DMMMSU USC protected database backup')) {
            $ok = false;
            $message = 'The SQL file does not contain the expected protected-backup signature.';
        }
    }
    if ($ok && str_ends_with(strtolower($filename), '.zip')) {
        if (!class_exists('ZipArchive')) {
            $message = 'Checksum verified; ZIP contents could not be inspected because ZipArchive is unavailable.';
        } else {
            $zip = new ZipArchive();
            if ($zip->open($path) !== true) {
                $ok = false;
                $message = 'The ZIP archive cannot be opened.';
            } else {
                $hasDb = false;
                for ($i = 0; $i<$zip->numFiles; $i++) {
                    if (str_starts_with((string)$zip->getNameIndex($i), 'database/') && str_ends_with(strtolower((string)$zip->getNameIndex($i)), '.sql')) {
                        $hasDb = true;
                        break;
                    }
                }
                if (!$hasDb) {
                    $ok = false;
                    $message = 'The full backup does not contain a database SQL file.';
                }
                $zip->close();
            }
        }
    }
    try {
        if (db_column_exists($pdo, 'backup_history', 'verified_at')) {
            $pdo->prepare('UPDATE backup_history SET verified_at=NOW(),verification_status=?,verification_message=? WHERE filename=?')->execute([$ok?'verified':'failed', $message, $filename]);
        }
    } catch (Throwable $ignored) {
    }
    return ['ok' => $ok, 'message' => $message, 'sha256' => $actual, 'filename' => $filename];
}

/** Preservation-first retention audit. No backup is deleted automatically. */
function maintenance_retention_report(PDO $pdo): array {
    $keep = max(3, min(100, (int)(system_setting($pdo, 'auto_backup_retention_count', '14') ?? 14)));
    $files = maintenance_list_backups($pdo);
    $excess = max(0, count($files)-$keep);
    return ['target' => $keep, 'total' => count($files), 'excess' => $excess, 'action' => 'preserve'];
}

/** Run tasks suitable for Task Scheduler/cron. Safe to call repeatedly. */
function maintenance_run_scheduled_tasks(PDO $pdo): array {
    $result = ['published' => 0, 'emails' => 0, 'due_soon_reminders' => 0, 'overdue_reminders' => 0, 'followup_reminders' => 0, 'concern_retention' => ['archived' => 0, 'anonymized' => 0], 'notifications_dismissed' => 0, 'backup' => null, 'verification' => null, 'offsite' => null, 'retention' => null];
    $result['published'] = publish_scheduled_posts($pdo);
    $result['due_soon_reminders'] = function_exists('concern_run_due_soon_reminders')?concern_run_due_soon_reminders($pdo, null):0;
    $result['overdue_reminders'] = concern_run_overdue_escalation($pdo, null);
    $result['followup_reminders'] = concern_run_followup_reminders($pdo, null);
    $result['concern_retention'] = function_exists('concern_run_retention_maintenance')?concern_run_retention_maintenance($pdo):['archived' => 0, 'anonymized' => 0];
    $result['emails'] = admin_process_email_outbox($pdo, 25);
    if (function_exists('notification_cleanup_expired')) $result['notifications_dismissed'] = notification_cleanup_expired($pdo);
    $enabled = system_setting($pdo, 'auto_backup_enabled', '0') === '1';
    $interval = max(1, min(168, (int)(system_setting($pdo, 'auto_backup_interval_hours', '24') ?? 24)));
    $last = (string)(system_setting($pdo, 'maintenance_last_backup_at', '') ?? '');
    $due = $enabled && ($last === '' || strtotime($last) === false || strtotime($last) <= time()-$interval*3600);
    if ($due) {
        $type = (string)(system_setting($pdo, 'auto_backup_type', 'database') ?? 'database');
        $result['backup'] = $type === 'full'?maintenance_create_full_backup($pdo, null):maintenance_create_database_backup($pdo, null);
        save_system_setting($pdo, 'maintenance_last_backup_at', date('Y-m-d H:i:s'));
        $result['verification'] = maintenance_verify_backup($pdo, (string)$result['backup']['filename']);
        save_system_setting($pdo, 'maintenance_last_verification_at', date('Y-m-d H:i:s'));
        if (function_exists('offsite_backup_copy') && system_setting($pdo, 'offsite_backup_enabled', '0') === '1') $result['offsite'] = offsite_backup_copy($pdo, (string)$result['backup']['filename']);
    }
    $result['retention'] = maintenance_retention_report($pdo);
    save_system_setting($pdo, 'maintenance_last_run_at', date('Y-m-d H:i:s'));
    return $result;
}
