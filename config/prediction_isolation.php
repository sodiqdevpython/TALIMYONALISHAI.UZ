<?php
// EduDirectionAI Enterprise 3.1 — Prediction Isolation helpers

function ensure_prediction_isolation_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();

    $colExists = function(string $table, string $column) use ($pdo, $db): bool {
        $q = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
        $q->execute([$db, $table, $column]);
        return (int)$q->fetchColumn() > 0;
    };
    $idxExists = function(string $table, string $index) use ($pdo, $db): bool {
        $q = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=?');
        $q->execute([$db, $table, $index]);
        return (int)$q->fetchColumn() > 0;
    };

    try {
        if (!$colExists('classes', 'batch_id')) {
            $pdo->exec('ALTER TABLE classes ADD COLUMN batch_id INT NULL AFTER school_id');
        }
    } catch (Throwable $e) {}

    try {
        if (!$idxExists('classes', 'idx_classes_batch')) {
            $pdo->exec('ALTER TABLE classes ADD INDEX idx_classes_batch(batch_id)');
        }
    } catch (Throwable $e) {}

    try {
        if (!$colExists('students', 'batch_id')) {
            $pdo->exec('ALTER TABLE students ADD COLUMN batch_id INT NULL AFTER school_id');
        }
    } catch (Throwable $e) {}

    try {
        if (!$colExists('students', 'external_student_code')) {
            $pdo->exec('ALTER TABLE students ADD COLUMN external_student_code VARCHAR(100) NULL AFTER student_code');
        }
    } catch (Throwable $e) {}

    try {
        if (!$idxExists('students', 'idx_students_batch')) {
            $pdo->exec('ALTER TABLE students ADD INDEX idx_students_batch(batch_id)');
        }
    } catch (Throwable $e) {}

    try {
        if (!$colExists('teacher_classes', 'batch_id')) {
            $pdo->exec('ALTER TABLE teacher_classes ADD COLUMN batch_id INT NULL AFTER teacher_id');
        }
    } catch (Throwable $e) {}

    try {
        if (!$idxExists('teacher_classes', 'idx_teacher_classes_batch')) {
            $pdo->exec('ALTER TABLE teacher_classes ADD INDEX idx_teacher_classes_batch(batch_id)');
        }
    } catch (Throwable $e) {}

    try {
        if (!$colExists('prediction_batches', 'is_active')) {
            $pdo->exec('ALTER TABLE prediction_batches ADD COLUMN is_active TINYINT(1) DEFAULT 0 AFTER status');
        }
    } catch (Throwable $e) {}
}

function latest_completed_batch_id(PDO $pdo, int $schoolId): int {
    ensure_prediction_isolation_schema($pdo);
    $q = $pdo->prepare('SELECT id FROM prediction_batches WHERE school_id=? AND status="completed" ORDER BY id DESC LIMIT 1');
    $q->execute([$schoolId]);
    return (int)$q->fetchColumn();
}

function active_or_latest_batch_id(PDO $pdo, int $schoolId): int {
    ensure_prediction_isolation_schema($pdo);
    try {
        $q = $pdo->prepare('SELECT id FROM prediction_batches WHERE school_id=? AND status="completed" AND is_active=1 ORDER BY id DESC LIMIT 1');
        $q->execute([$schoolId]);
        $id = (int)$q->fetchColumn();
        if ($id > 0) return $id;
    } catch (Throwable $e) {}
    return latest_completed_batch_id($pdo, $schoolId);
}

function set_active_prediction_batch(PDO $pdo, int $schoolId, int $batchId): void {
    ensure_prediction_isolation_schema($pdo);
    $pdo->prepare('UPDATE prediction_batches SET is_active=0 WHERE school_id=?')->execute([$schoolId]);
    $pdo->prepare('UPDATE prediction_batches SET is_active=1 WHERE id=? AND school_id=?')->execute([$batchId, $schoolId]);
}
?>
