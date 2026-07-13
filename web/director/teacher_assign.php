<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/prediction_isolation.php';

$u = require_role(['director']);
$pdo = db();
ensure_prediction_isolation_schema($pdo);

$schoolId = (int)$u['school_id'];
$teacherId = (int)($_GET['teacher_id'] ?? 0);

$tq = $pdo->prepare("SELECT u.* FROM users u JOIN roles r ON r.id=u.role_id WHERE u.id=? AND u.school_id=? AND r.role_key='teacher' LIMIT 1");
$tq->execute([$teacherId, $schoolId]);
$t = $tq->fetch();
if (!$t) die('O‘qituvchi topilmadi yoki sizga tegishli emas.');

$bq = $pdo->prepare('SELECT id, dataset_name, students_count, mean_confidence, created_at, is_active FROM prediction_batches WHERE school_id=? AND status="completed" ORDER BY id DESC');
$bq->execute([$schoolId]);
$batches = $bq->fetchAll();

$selectedBatchId = (int)($_GET['batch_id'] ?? ($_POST['batch_id'] ?? 0));
if (!$selectedBatchId && $batches) {
    foreach ($batches as $b) {
        if ((int)($b['is_active'] ?? 0) === 1) {
            $selectedBatchId = (int)$b['id'];
            break;
        }
    }
    if (!$selectedBatchId) $selectedBatchId = (int)$batches[0]['id'];
}

$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $classIds = array_map('intval', $_POST['classes'] ?? []);
    $pdo->beginTransaction();
    try {
        $pdo->prepare('DELETE FROM teacher_classes WHERE teacher_id=? AND batch_id=?')->execute([$teacherId, $selectedBatchId]);

        $ins = $pdo->prepare('INSERT INTO teacher_classes (teacher_id, batch_id, class_id) VALUES (?, ?, ?)');
        foreach ($classIds as $cid) {
            $chk = $pdo->prepare('SELECT id FROM classes WHERE id=? AND school_id=? AND batch_id=? LIMIT 1');
            $chk->execute([$cid, $schoolId, $selectedBatchId]);
            if ($chk->fetch()) {
                $ins->execute([$teacherId, $selectedBatchId, $cid]);
            }
        }

        $pdo->commit();
        $msg = 'Sinflar Prediction #'.str_pad((string)$selectedBatchId, 6, '0', STR_PAD_LEFT).' bo‘yicha biriktirildi.';
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) $pdo->rollBack();
        $msg = 'Xatolik: '.$e->getMessage();
    }
}

$classes = [];
if ($selectedBatchId) {
    $classesQ = $pdo->prepare("
        SELECT c.*,
               (SELECT COUNT(*) FROM student_predictions sp
                WHERE sp.batch_id=c.batch_id AND sp.school_id=c.school_id AND sp.class_name=c.class_name) AS students_count
        FROM classes c
        WHERE c.school_id=? AND c.batch_id=?
        ORDER BY c.class_name
    ");
    $classesQ->execute([$schoolId, $selectedBatchId]);
    $classes = $classesQ->fetchAll();
}

$assignedQ = $pdo->prepare('SELECT class_id FROM teacher_classes WHERE teacher_id=? AND batch_id=?');
$assignedQ->execute([$teacherId, $selectedBatchId]);
$assigned = array_map('intval', $assignedQ->fetchAll(PDO::FETCH_COLUMN));
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Sinf biriktirish</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container enterprise">
<header class="topbar">
    <div>
        <h1>Sinf biriktirish</h1>
        <p><?=auth_h($t['full_name'])?> uchun prediction snapshot bo‘yicha sinflarni tanlang.</p>
    </div>
    <nav class="nav">
        <a href="teachers.php">O‘qituvchilar</a>
        <a href="prediction_history.php">Prediction tarixi</a>
        <a href="index.php">Direktor</a>
        <a href="../logout.php">Chiqish</a>
    </nav>
</header>

<?php if($msg): ?><section class="card alert"><?=auth_h($msg)?></section><?php endif; ?>

<section class="card">
    <h2>Prediction tanlash</h2>
    <form method="get" class="inline">
        <input type="hidden" name="teacher_id" value="<?=auth_h($teacherId)?>">
        <select name="batch_id" onchange="this.form.submit()">
            <?php foreach($batches as $b): ?>
                <option value="<?=auth_h($b['id'])?>" <?=((int)$b['id']===$selectedBatchId)?'selected':''?>>
                    #<?=auth_h(str_pad((string)$b['id'],6,'0',STR_PAD_LEFT))?> — <?=auth_h($b['dataset_name'])?> — <?=auth_h($b['students_count'])?> o‘quvchi <?=((int)($b['is_active'] ?? 0)===1)?'— ACTIVE':''?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>
    <p class="hint">Bir xil nomdagi sinflar har bir predictionda alohida saqlanadi. Masalan, Prediction #1 dagi 11-sinf va Prediction #2 dagi 11-sinf boshqa-boshqa obyekt hisoblanadi.</p>
</section>

<section class="card">
    <form method="post">
        <input type="hidden" name="batch_id" value="<?=auth_h($selectedBatchId)?>">
        <h2>Sinflar</h2>
        <div class="assign-grid">
            <?php foreach($classes as $c): ?>
                <label class="assign-card">
                    <input type="checkbox" name="classes[]" value="<?=auth_h($c['id'])?>" <?=in_array((int)$c['id'],$assigned,true)?'checked':''?>>
                    <b><?=auth_h($c['class_name'])?></b>
                    <span><?=auth_h($c['students_count'])?> o‘quvchi</span>
                    <em>Prediction #<?=auth_h(str_pad((string)$selectedBatchId,6,'0',STR_PAD_LEFT))?></em>
                </label>
            <?php endforeach; ?>
        </div>
        <?php if(!$classes): ?><p class="muted">Ushbu prediction bo‘yicha sinflar topilmadi.</p><?php endif; ?>
        <button class="btn" type="submit">Saqlash</button>
    </form>
</section>
</div>
</body>
</html>
