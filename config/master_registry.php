<?php
// EduDirectionAI Enterprise 4.0 — Student Master Registry helpers

function ensure_master_registry_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();

    $tableExists = function(string $table) use ($pdo, $db): bool {
        $q = $pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
        $q->execute([$db, $table]);
        return (int)$q->fetchColumn() > 0;
    };
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

    if (!$tableExists('master_students')) {
        $pdo->exec("
            CREATE TABLE master_students (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                national_student_id VARCHAR(100) NULL,
                pinfl VARCHAR(30) NULL,
                passport_no VARCHAR(50) NULL,
                fio VARCHAR(255) NOT NULL,
                birth_date DATE NULL,
                gender VARCHAR(20) NULL,
                identity_hash VARCHAR(64) NOT NULL,
                status ENUM('active','inactive','merged') DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_master_identity_hash(identity_hash),
                INDEX idx_master_national(national_student_id),
                INDEX idx_master_pinfl(pinfl),
                INDEX idx_master_fio(fio)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    if (!$tableExists('student_school_history')) {
        $pdo->exec("
            CREATE TABLE student_school_history (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                master_student_id BIGINT NOT NULL,
                school_id INT NOT NULL,
                batch_id INT NULL,
                class_name VARCHAR(50) NULL,
                academic_year VARCHAR(20) NULL,
                source_prediction_id BIGINT NULL,
                event_type ENUM('study','transfer_in','transfer_out','prediction_snapshot') DEFAULT 'prediction_snapshot',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (master_student_id) REFERENCES master_students(id) ON DELETE CASCADE ON UPDATE CASCADE,
                FOREIGN KEY (school_id) REFERENCES schools(id) ON DELETE CASCADE ON UPDATE CASCADE,
                INDEX idx_ssh_master(master_student_id),
                INDEX idx_ssh_school(school_id),
                INDEX idx_ssh_batch(batch_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    foreach ([
        ['students', 'master_student_id', 'BIGINT NULL AFTER id'],
        ['student_predictions', 'master_student_id', 'BIGINT NULL AFTER student_id'],
    ] as $c) {
        try {
            if (!$colExists($c[0], $c[1])) {
                $pdo->exec("ALTER TABLE {$c[0]} ADD COLUMN {$c[1]} {$c[2]}");
            }
        } catch (Throwable $e) {}
    }

    try { if (!$idxExists('students','idx_students_master')) $pdo->exec('ALTER TABLE students ADD INDEX idx_students_master(master_student_id)'); } catch(Throwable $e) {}
    try { if (!$idxExists('student_predictions','idx_pred_master')) $pdo->exec('ALTER TABLE student_predictions ADD INDEX idx_pred_master(master_student_id)'); } catch(Throwable $e) {}
}

function master_identity_hash(string $fio, string $birthDate='', string $pinfl='', string $nationalId=''): string {
    $fio = mb_strtolower(trim(preg_replace('/\s+/u', ' ', $fio)), 'UTF-8');
    $pinfl = trim($pinfl);
    $nationalId = trim($nationalId);
    $birthDate = trim($birthDate);

    if ($pinfl !== '') return hash('sha256', 'pinfl|'.$pinfl);
    if ($nationalId !== '') return hash('sha256', 'national|'.$nationalId);
    return hash('sha256', 'fallback|'.$fio.'|'.$birthDate);
}

function find_or_create_master_student(PDO $pdo, array $row, string $studentName): int {
    ensure_master_registry_schema($pdo);

    $national = trim((string)($row['national_student_id'] ?? $row['student_national_id'] ?? $row['master_student_id'] ?? ''));
    $pinfl = trim((string)($row['pinfl'] ?? $row['PINFL'] ?? $row['JSHSHIR'] ?? $row['jshshir'] ?? ''));
    $passport = trim((string)($row['passport'] ?? $row['passport_no'] ?? ''));
    $birth = trim((string)($row['birth_date'] ?? $row['tugilgan_sana'] ?? $row['dob'] ?? ''));
    $gender = trim((string)($row['gender'] ?? $row['jins'] ?? ''));
    $hash = master_identity_hash($studentName, $birth, $pinfl, $national);

    $q = $pdo->prepare('SELECT id FROM master_students WHERE identity_hash=? LIMIT 1');
    $q->execute([$hash]);
    $id = (int)$q->fetchColumn();
    if ($id > 0) return $id;

    $ins = $pdo->prepare('
        INSERT INTO master_students
        (national_student_id, pinfl, passport_no, fio, birth_date, gender, identity_hash)
        VALUES (?, ?, ?, ?, NULLIF(?, ""), ?, ?)
    ');
    $ins->execute([$national ?: null, $pinfl ?: null, $passport ?: null, $studentName, $birth ?: '', $gender ?: null, $hash]);
    return (int)$pdo->lastInsertId();
}

function log_student_school_history(PDO $pdo, int $masterId, int $schoolId, int $batchId, string $className, ?int $predictionId=null): void {
    ensure_master_registry_schema($pdo);

    $year = date('Y');
    try {
        $ins = $pdo->prepare('
            INSERT INTO student_school_history
            (master_student_id, school_id, batch_id, class_name, academic_year, source_prediction_id, event_type)
            VALUES (?, ?, ?, ?, ?, ?, "prediction_snapshot")
        ');
        $ins->execute([$masterId, $schoolId, $batchId ?: null, $className ?: null, $year, $predictionId]);
    } catch (Throwable $e) {}
}
?>
