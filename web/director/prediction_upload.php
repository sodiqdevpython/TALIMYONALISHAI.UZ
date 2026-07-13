<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/prediction_isolation.php';
require_once __DIR__.'/../../config/master_registry.php';
require_once __DIR__.'/../../config/governance.php';

$u = require_role(['director']);
$pdo = db();
ensure_prediction_isolation_schema($pdo);
ensure_master_registry_schema($pdo);
ensure_governance_schema($pdo);

$schoolId = (int)$u['school_id'];
if (!$schoolId) die('Direktor maktabga biriktirilmagan.');

$st = $pdo->prepare('SELECT * FROM schools WHERE id=? LIMIT 1');
$st->execute([$schoolId]);
$school = $st->fetch();
if (!$school) die('Maktab topilmadi.');

function project_base_dir(): string { return realpath(__DIR__.'/../../') ?: dirname(__DIR__,2); }
function ensure_dir(string $p): void { if (!is_dir($p)) mkdir($p, 0777, true); }
function fval($v): ?float { $v = trim((string)$v); if ($v==='') return null; return is_numeric($v) ? (float)$v : null; }
function ival($v): ?int { $v = trim((string)$v); if ($v==='') return null; return is_numeric($v) ? (int)$v : null; }
function csv_row_get(array $row, string $key, $default='') { return $row[$key] ?? $default; }

function make_student_username(int $schoolId, int $batchId, string $code): string {
    $clean = preg_replace('/[^A-Za-z0-9_]/', '', $code);
    if ($clean === '') $clean = substr(md5($code), 0, 8);
    return 'st'.$schoolId.'_b'.$batchId.'_'.$clean;
}

function import_prediction_csv(PDO $pdo, string $csvPath, int $batchId, int $schoolId, string $academicYear): int {
    ensure_prediction_isolation_schema($pdo);

    if (!is_file($csvPath)) throw new Exception('Prediction CSV topilmadi: '.$csvPath);
    $fh = fopen($csvPath, 'r');
    if (!$fh) throw new Exception('CSV ochilmadi.');

    $header = fgetcsv($fh);
    if (!$header) throw new Exception('CSV header topilmadi.');
    $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', $header[0]);
    $header = array_map('trim', $header);

    // IMPORTANT: Build 3.1 Prediction Isolation
    // Same class name in different prediction batches becomes a different class snapshot.
    $classFind = $pdo->prepare('SELECT id FROM classes WHERE school_id=? AND batch_id=? AND class_name=? LIMIT 1');
    $classIns  = $pdo->prepare('INSERT INTO classes (school_id, batch_id, class_name, academic_year) VALUES (?, ?, ?, ?)');

    // Student record is also stored as a prediction snapshot.
    // Original school student code remains in external_student_code and student_predictions.external_student_code.
    $studFind  = $pdo->prepare('SELECT id FROM students WHERE school_id=? AND batch_id=? AND student_code=? LIMIT 1');
    $studIns   = $pdo->prepare('INSERT INTO students (master_student_id, school_id, batch_id, class_id, student_code, external_student_code, student_name, username, password_hash, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, "active")');
    $studUpd   = $pdo->prepare('UPDATE students SET master_student_id=?, class_id=?, student_name=?, external_student_code=? WHERE id=?');

    $predIns = $pdo->prepare('
        INSERT INTO student_predictions
        (batch_id, school_id, student_id, master_student_id, external_student_code, student_name, class_name,
         recommended_direction, alternative_direction, recommendation_confidence, raw_model_confidence, alternative_confidence,
         IT_Index, Engineering_Index, Medicine_Index, Economics_Index, Pedagogy_Index,
         academic_mean, academic_std, growth_trend, learning_dynamics, academic_stability, temporal_consistency,
         temporal_years_count, temporal_coverage_ratio, temporal_coverage_level,
         recommendation_reason, selected_direction_advice, full_json)
        VALUES
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ');

    $count = 0;
    $year = $academicYear !== '' ? $academicYear : 'batch_'.$batchId;

    while (($data = fgetcsv($fh)) !== false) {
        if (count($data) === 1 && trim((string)$data[0]) === '') continue;
        $row = [];
        foreach ($header as $i=>$h) $row[$h] = $data[$i] ?? '';

        $externalStudentCode = trim((string)csv_row_get($row, 'student_id', ''));
        if ($externalStudentCode === '') $externalStudentCode = 'row_'.$batchId.'_'.($count+1);

        $studentCode = 'B'.$batchId.'_'.$externalStudentCode;

        $studentName = trim((string)csv_row_get($row, 'FIO', ''));
        if ($studentName === '') $studentName = trim((string)csv_row_get($row, 'student_name', ''));
        if ($studentName === '') $studentName = 'Noma’lum o‘quvchi';

        $className = trim((string)csv_row_get($row, 'Sinf', ''));
        if ($className === '') $className = trim((string)csv_row_get($row, 'class_name', ''));
        if ($className === '') $className = 'Noma’lum';

        $classFind->execute([$schoolId, $batchId, $className]);
        $classId = $classFind->fetchColumn();

        if (!$classId) {
            $classIns->execute([$schoolId, $batchId, $className, $year]);
            $classId = (int)$pdo->lastInsertId();
        } else {
            $classId = (int)$classId;
        }

        $masterStudentId = find_or_create_master_student($pdo, $row, $studentName);

        $studFind->execute([$schoolId, $batchId, $studentCode]);
        $studentDbId = $studFind->fetchColumn();

        if (!$studentDbId) {
            $plainPass = trim((string)csv_row_get($row, 'student_password', ''));
            if ($plainPass === '') $plainPass = 'edu'.substr(md5($studentCode), 0, 6);
            $username = make_student_username($schoolId, $batchId, $externalStudentCode);
            $hash = password_hash($plainPass, PASSWORD_DEFAULT);
            try {
                $studIns->execute([$masterStudentId, $schoolId, $batchId, $classId, $studentCode, $externalStudentCode, $studentName, $username, $hash]);
            } catch (Throwable $e) {
                $username = make_student_username($schoolId, $batchId, $externalStudentCode).'_'.substr(md5((string)microtime(true)),0,4);
                $studIns->execute([$masterStudentId, $schoolId, $batchId, $classId, $studentCode, $externalStudentCode, $studentName, $username, $hash]);
            }
            $studentDbId = (int)$pdo->lastInsertId();
        } else {
            $studentDbId = (int)$studentDbId;
            $studUpd->execute([$masterStudentId, $classId, $studentName, $externalStudentCode, $studentDbId]);
        }

        $fullJson = json_encode($row, JSON_UNESCAPED_UNICODE);
        $predIns->execute([
            $batchId, $schoolId, $studentDbId, $masterStudentId, $externalStudentCode, $studentName, $className,
            csv_row_get($row,'recommended_direction'), csv_row_get($row,'alternative_direction'),
            fval(csv_row_get($row,'recommendation_confidence')), fval(csv_row_get($row,'raw_model_confidence')), fval(csv_row_get($row,'alternative_confidence')),
            fval(csv_row_get($row,'IT_Index')), fval(csv_row_get($row,'Engineering_Index')), fval(csv_row_get($row,'Medicine_Index')), fval(csv_row_get($row,'Economics_Index')), fval(csv_row_get($row,'Pedagogy_Index')),
            fval(csv_row_get($row,'academic_mean')), fval(csv_row_get($row,'academic_std')), fval(csv_row_get($row,'growth_trend')), fval(csv_row_get($row,'learning_dynamics')), fval(csv_row_get($row,'academic_stability')), fval(csv_row_get($row,'temporal_consistency')),
            ival(csv_row_get($row,'temporal_years_count')), fval(csv_row_get($row,'temporal_coverage_ratio')), csv_row_get($row,'temporal_coverage_level'),
            csv_row_get($row,'recommendation_reason'), csv_row_get($row,'selected_direction_advice'), $fullJson
        ]);
        log_student_school_history($pdo, $masterStudentId, $schoolId, $batchId, $className, (int)$pdo->lastInsertId());
        $count++;
    }

    fclose($fh);
    return $count;
}

$errors = [];
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $academicYear = trim($_POST['academic_year'] ?? '');
    $predictionTitle = trim($_POST['prediction_title'] ?? '');
    if ($academicYear === '' || !preg_match('/^20[0-9]{2}-20[0-9]{2}$/', $academicYear)) {
        $errors[] = 'O‘quv yilini 2025-2026 formatida kiriting.';
    }
    if (!isset($_FILES['dataset']) || $_FILES['dataset']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Excel dataset yuklanmadi.';
    } else {
        $ext = strtolower(pathinfo($_FILES['dataset']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'xlsx') $errors[] = 'Faqat .xlsx fayl yuklang.';
    }

    if (!$errors) {
        $base = project_base_dir();
        $uploads = $base.'/uploads/datasets/'.$school['school_code'];
        ensure_dir($uploads);
        $safeName = date('Ymd_His').'_'.preg_replace('/[^A-Za-z0-9_.-]/', '_', $_FILES['dataset']['name']);
        $inputPath = $uploads.'/'.$safeName;
        if (!move_uploaded_file($_FILES['dataset']['tmp_name'], $inputPath)) {
            $errors[] = 'Faylni saqlashda xatolik.';
        }
    }

    if (!$errors) {
        $model = $pdo->query("SELECT * FROM research_models WHERE is_active=1 ORDER BY id DESC LIMIT 1")->fetch();
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare('INSERT INTO prediction_batches (school_id, model_id, uploaded_by, dataset_name, academic_year, prediction_title, dataset_path, mode, status, lifecycle_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, "predict", "processing", "inactive", NOW())');
            $stmt->execute([$schoolId, $model['id'] ?? null, $u['id'], $_FILES['dataset']['name'], $academicYear, $predictionTitle, $inputPath]);
            $batchId = (int)$pdo->lastInsertId();
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Batch yaratishda xatolik: '.$e->getMessage();
        }
    }

    if (!$errors) {
        $outDir = $base.'/outputs/enterprise/school_'.$schoolId.'/batch_'.$batchId;
        ensure_dir($outDir);
        $script = $base.'/python/predict_dataset.py';
        $modelDir = $base.'/outputs/models';
        $python = 'python';
        $cmd = $python.' '.escapeshellarg($script)
             .' --input '.escapeshellarg($inputPath)
             .' --out '.escapeshellarg($outDir)
             .' --school_id '.escapeshellarg($school['school_code'])
             .' --school_name '.escapeshellarg($school['school_name'])
             .' --region '.escapeshellarg($school['region'] ?? '')
             .' --district '.escapeshellarg($school['district'] ?? '')
             .' --model_dir '.escapeshellarg($modelDir)
             .' 2>&1';
        $started = microtime(true);
        $output = [];
        $code = 0;
        exec($cmd, $output, $code);
        $duration = round(microtime(true)-$started, 3);

        if ($code !== 0) {
            $err = implode("\n", $output);
            $up = $pdo->prepare('UPDATE prediction_batches SET status="failed", error_message=?, completed_at=NOW() WHERE id=?');
            $up->execute([$err, $batchId]);
            $errors[] = 'Python prediction xatoligi: '.$err;
        } else {
            try {
                $pdo->beginTransaction();
                $csv = $outDir.'/school_prediction_results.csv';
                $imported = import_prediction_csv($pdo, $csv, $batchId, $schoolId, $academicYear);
                $summaryPath = $outDir.'/school_prediction_summary.json';
                $summary = is_file($summaryPath) ? json_decode(file_get_contents($summaryPath), true) : [];
                $meanConf = $summary['mean_confidence'] ?? null;
                $coverage = $summary['temporal_coverage_mean'] ?? null;
                $up = $pdo->prepare('UPDATE prediction_batches SET students_count=?, mean_confidence=?, temporal_coverage_mean=?, status="completed", completed_at=NOW() WHERE id=?');
                $up->execute([$imported, $meanConf, $coverage, $batchId]);
                set_prediction_active($pdo, $schoolId, $batchId);
                register_dataset($pdo, $schoolId, $batchId, $_FILES['dataset']['name'], $inputPath, $academicYear, (int)$u['id'], $imported);
                $log = $pdo->prepare('INSERT INTO activity_logs (user_id, school_id, action, description, entity_type, entity_id, ip_address) VALUES (?, ?, "prediction_completed", ?, "prediction_batch", ?, ?)');
                $log->execute([$u['id'], $schoolId, 'Prediction yakunlandi. Batch #'.$batchId.', o‘quv yili: '.$academicYear.', o‘quvchilar: '.$imported.', duration: '.$duration.' sec', $batchId, $_SERVER['REMOTE_ADDR'] ?? '']);
                $pdo->commit();
                header('Location: prediction_results.php?batch_id='.$batchId);
                exit;
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $pdo->prepare('UPDATE prediction_batches SET status="failed", error_message=?, completed_at=NOW() WHERE id=?')->execute([$e->getMessage(), $batchId]);
                $errors[] = 'Natijani bazaga yozishda xatolik: '.$e->getMessage();
            }
        }
    }
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Prediction boshlash</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container">
<header class="topbar"><div><h1>Dataset yuklash va Prediction</h1><p><?=auth_h($school['school_name'])?> uchun tayyor model asosida tahlil.</p></div><nav class="nav"><a href="index.php">Direktor</a><a href="prediction_history.php">Prediction tarixi</a><a href="../logout.php">Chiqish</a></nav></header>
<?php if($errors): ?><section class="card error"><h3>Xatolik</h3><pre><?=auth_h(implode("\n", $errors))?></pre></section><?php endif; ?>
<section class="card"><h2>Excel dataset (.xlsx)</h2>
<form method="post" enctype="multipart/form-data" class="upload-form">
<label>O‘quv yili <span class="required">*</span></label>
<input type="text" name="academic_year" placeholder="2025-2026" value="<?=auth_h($_POST['academic_year'] ?? '')?>" required pattern="20[0-9]{2}-20[0-9]{2}">
<label>Prediction nomi</label>
<input type="text" name="prediction_title" placeholder="Masalan: 11-sinf yakuniy dataset" value="<?=auth_h($_POST['prediction_title'] ?? '')?>">
<label>Excel dataset (.xlsx)</label>
<input type="file" name="dataset" accept=".xlsx" required>
<p class="hint">O‘quv yili majburiy. Bu qiymat prediction, sinflar va transfer tarixiga yoziladi.</p>
<button class="btn" type="submit">Prediction boshlash</button>
</form></section>
<section class="card"><h2>Jarayon qanday ishlaydi?</h2><ol><li>Dataset serverga saqlanadi.</li><li>Prediction batch yaratiladi.</li><li>Python AI Engine ishga tushadi.</li><li>Natijalar MySQL bazaga yoziladi.</li><li>Dashboard va history avtomatik yangilanadi.</li></ol></section>
</div></body></html>
