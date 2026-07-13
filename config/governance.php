<?php
// EduDirectionAI Enterprise Build 4.2 — AI Governance helpers

function ensure_governance_schema(PDO $pdo): void {
    static $done = false;
    if ($done) return;
    $done = true;

    $db = $pdo->query('SELECT DATABASE()')->fetchColumn();

    $tableExists = function(string $table) use ($pdo, $db): bool {
        $q=$pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA=? AND TABLE_NAME=?');
        $q->execute([$db,$table]);
        return (int)$q->fetchColumn()>0;
    };
    $colExists = function(string $table,string $col) use ($pdo,$db): bool {
        $q=$pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND COLUMN_NAME=?');
        $q->execute([$db,$table,$col]);
        return (int)$q->fetchColumn()>0;
    };
    $idxExists = function(string $table,string $idx) use ($pdo,$db): bool {
        $q=$pdo->prepare('SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA=? AND TABLE_NAME=? AND INDEX_NAME=?');
        $q->execute([$db,$table,$idx]);
        return (int)$q->fetchColumn()>0;
    };

    foreach([
        ['prediction_batches','academic_year','VARCHAR(20) NULL AFTER dataset_name'],
        ['prediction_batches','prediction_title','VARCHAR(255) NULL AFTER academic_year'],
        ['prediction_batches','lifecycle_status',"ENUM('active','inactive','archived','deleted') DEFAULT 'inactive' AFTER status"],
        ['prediction_batches','dataset_hash','VARCHAR(64) NULL AFTER dataset_path'],
        ['prediction_batches','archived_at','DATETIME NULL AFTER completed_at'],
        ['prediction_batches','deleted_at','DATETIME NULL AFTER archived_at'],
    ] as $c){
        try{ if(!$colExists($c[0],$c[1])) $pdo->exec("ALTER TABLE {$c[0]} ADD COLUMN {$c[1]} {$c[2]}"); }catch(Throwable $e){}
    }
    try{ if(!$idxExists('prediction_batches','idx_batch_year')) $pdo->exec('ALTER TABLE prediction_batches ADD INDEX idx_batch_year(academic_year)'); }catch(Throwable $e){}
    try{ if(!$idxExists('prediction_batches','idx_batch_lifecycle')) $pdo->exec('ALTER TABLE prediction_batches ADD INDEX idx_batch_lifecycle(lifecycle_status)'); }catch(Throwable $e){}
    try{ if(!$idxExists('prediction_batches','idx_batch_hash')) $pdo->exec('ALTER TABLE prediction_batches ADD INDEX idx_batch_hash(dataset_hash)'); }catch(Throwable $e){}

    if(!$tableExists('dataset_registry')){
        $pdo->exec("
            CREATE TABLE dataset_registry (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                school_id INT NOT NULL,
                batch_id INT NULL,
                dataset_name VARCHAR(255),
                academic_year VARCHAR(20),
                dataset_hash VARCHAR(64) NOT NULL,
                file_size BIGINT NULL,
                students_count INT DEFAULT 0,
                quality_json LONGTEXT NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_dataset_hash_school(school_id,dataset_hash),
                INDEX idx_dataset_school(school_id),
                INDEX idx_dataset_year(academic_year)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    if(!$tableExists('model_training_jobs')){
        $pdo->exec("
            CREATE TABLE model_training_jobs (
                id BIGINT AUTO_INCREMENT PRIMARY KEY,
                base_model_id INT NULL,
                prediction_batch_id INT NULL,
                new_model_id INT NULL,
                status ENUM('created','queued','training','completed','failed','rejected') DEFAULT 'created',
                metrics_before LONGTEXT NULL,
                metrics_after LONGTEXT NULL,
                recommendation VARCHAR(100) NULL,
                log_text LONGTEXT NULL,
                created_by INT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                completed_at DATETIME NULL,
                INDEX idx_mtj_batch(prediction_batch_id),
                INDEX idx_mtj_status(status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    try{ if(!$colExists('research_models','base_model_id')) $pdo->exec('ALTER TABLE research_models ADD COLUMN base_model_id INT NULL AFTER id'); }catch(Throwable $e){}
    try{ if(!$colExists('research_models','training_source')) $pdo->exec('ALTER TABLE research_models ADD COLUMN training_source VARCHAR(255) NULL AFTER model_path'); }catch(Throwable $e){}
    try{ if(!$colExists('research_models','status')) $pdo->exec("ALTER TABLE research_models ADD COLUMN status ENUM('active','inactive','archived','training','failed') DEFAULT 'inactive' AFTER is_active"); }catch(Throwable $e){}
}

function set_prediction_active(PDO $pdo, int $schoolId, int $batchId): void {
    ensure_governance_schema($pdo);
    $pdo->prepare('UPDATE prediction_batches SET is_active=0, lifecycle_status=IF(lifecycle_status="deleted","deleted",IF(lifecycle_status="archived","archived","inactive")) WHERE school_id=?')->execute([$schoolId]);
    $pdo->prepare('UPDATE prediction_batches SET is_active=1, lifecycle_status="active" WHERE id=? AND school_id=? AND status="completed" AND lifecycle_status<>"deleted"')->execute([$batchId,$schoolId]);
}

function set_prediction_inactive(PDO $pdo, int $schoolId, int $batchId): void {
    ensure_governance_schema($pdo);
    $pdo->prepare('UPDATE prediction_batches SET is_active=0, lifecycle_status="inactive" WHERE id=? AND school_id=? AND lifecycle_status<>"deleted"')->execute([$batchId,$schoolId]);
}

function archive_prediction(PDO $pdo, int $batchId): void {
    ensure_governance_schema($pdo);
    $pdo->prepare('UPDATE prediction_batches SET is_active=0, lifecycle_status="archived", archived_at=NOW() WHERE id=? AND lifecycle_status<>"deleted"')->execute([$batchId]);
}

function restore_prediction(PDO $pdo, int $batchId): void {
    ensure_governance_schema($pdo);
    $pdo->prepare('UPDATE prediction_batches SET lifecycle_status="inactive", archived_at=NULL WHERE id=? AND lifecycle_status="archived"')->execute([$batchId]);
}

function soft_delete_prediction(PDO $pdo, int $batchId): void {
    ensure_governance_schema($pdo);
    $pdo->prepare('UPDATE prediction_batches SET is_active=0, lifecycle_status="deleted", deleted_at=NOW() WHERE id=?')->execute([$batchId]);
}

function register_dataset(PDO $pdo, int $schoolId, int $batchId, string $datasetName, string $path, string $academicYear, int $userId, int $students=0): void {
    ensure_governance_schema($pdo);
    $hash = is_file($path) ? hash_file('sha256',$path) : hash('sha256',$datasetName.microtime(true));
    $size = is_file($path) ? filesize($path) : null;
    $quality = json_encode(['file_exists'=>is_file($path),'academic_year'=>$academicYear,'registered_at'=>date('c')], JSON_UNESCAPED_UNICODE);
    try{
        $pdo->prepare('INSERT INTO dataset_registry (school_id,batch_id,dataset_name,academic_year,dataset_hash,file_size,students_count,quality_json,created_by) VALUES (?,?,?,?,?,?,?,?,?)')
            ->execute([$schoolId,$batchId,$datasetName,$academicYear,$hash,$size,$students,$quality,$userId]);
    }catch(Throwable $e){
        $pdo->prepare('UPDATE dataset_registry SET batch_id=?, academic_year=?, students_count=?, quality_json=? WHERE school_id=? AND dataset_hash=?')
            ->execute([$batchId,$academicYear,$students,$quality,$schoolId,$hash]);
    }
    try{ $pdo->prepare('UPDATE prediction_batches SET dataset_hash=? WHERE id=?')->execute([$hash,$batchId]); }catch(Throwable $e){}
}
?>
