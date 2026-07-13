<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/prediction_isolation.php';

$u = require_role(['teacher']);
$pdo = db();
ensure_prediction_isolation_schema($pdo);

$teacherId = (int)$u['id'];
$schoolId = (int)$u['school_id'];
$classId = (int)($_GET['class_id'] ?? 0);
$batchId = (int)($_GET['batch_id'] ?? 0);

$cq = $pdo->prepare('
    SELECT c.*, tc.batch_id, b.dataset_name, b.created_at AS batch_date
    FROM teacher_classes tc
    JOIN classes c ON c.id=tc.class_id
    LEFT JOIN prediction_batches b ON b.id=tc.batch_id
    WHERE tc.teacher_id=? AND c.id=? AND tc.batch_id=?
    LIMIT 1
');
$cq->execute([$teacherId, $classId, $batchId]);
$class = $cq->fetch();
if (!$class) die('Bu sinf yoki prediction sizga biriktirilmagan.');

$q = trim($_GET['q'] ?? '');
$where = 'sp.school_id=? AND sp.batch_id=? AND sp.class_name=?';
$params = [$schoolId, $batchId, $class['class_name']];

if ($q !== '') {
    $where .= ' AND (sp.student_name LIKE ? OR sp.external_student_code LIKE ?)';
    $params[]='%'.$q.'%';
    $params[]='%'.$q.'%';
}

$stmt = $pdo->prepare("SELECT sp.* FROM student_predictions sp WHERE $where ORDER BY sp.student_name LIMIT 300");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$dist=[]; foreach($rows as $r){ $d=$r['recommended_direction'] ?: '-'; $dist[$d]=($dist[$d]??0)+1; }
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?=auth_h($class['class_name'])?></title>
<link rel="stylesheet" href="../assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container enterprise">
<header class="topbar">
    <div>
        <h1><?=auth_h($class['class_name'])?> — AI monitoring</h1>
        <p>Prediction #<?=auth_h(str_pad((string)$batchId,6,'0',STR_PAD_LEFT))?> · <?=auth_h($class['dataset_name'])?> · faqat sizga biriktirilgan snapshot.</p>
    </div>
    <nav class="nav"><a href="index.php">O‘qituvchi</a><a href="../logout.php">Chiqish</a></nav>
</header>

<section class="grid2">
    <div class="card"><h2>Yo‘nalishlar</h2><canvas id="chart" height="220"></canvas></div>
    <div class="card"><h2>Snapshot ma’lumotlari</h2><p><b>Natijalar:</b> <?=count($rows)?></p><p><b>Batch:</b> #<?=str_pad((string)$batchId,6,'0',STR_PAD_LEFT)?></p><p><b>Dataset:</b> <?=auth_h($class['dataset_name'])?></p></div>
</section>

<section class="card">
    <h2>O‘quvchilar</h2>
    <form class="filters" method="get">
        <input type="hidden" name="class_id" value="<?=auth_h($classId)?>">
        <input type="hidden" name="batch_id" value="<?=auth_h($batchId)?>">
        <input name="q" placeholder="FIO yoki ID" value="<?=auth_h($q)?>">
        <button class="btn">Qidirish</button>
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>O‘quvchi</th><th>Tavsiya</th><th>Confidence</th><th>Alternativ</th><th>Profil</th></tr></thead>
            <tbody>
            <?php foreach($rows as $r): ?>
                <tr>
                    <td><?=auth_h($r['student_name'])?></td>
                    <td><span class="badge"><?=auth_h($r['recommended_direction'])?></span></td>
                    <td><?=round((float)$r['recommendation_confidence']*100,1)?>%</td>
                    <td><?=auth_h($r['alternative_direction'])?></td>
                    <td><a class="mini" href="student_view.php?prediction_id=<?=auth_h($r['id'])?>&class_id=<?=auth_h($classId)?>&batch_id=<?=auth_h($batchId)?>">Ko‘rish</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
new Chart(document.getElementById('chart'),{type:'doughnut',data:{labels:<?=json_encode(array_keys($dist),JSON_UNESCAPED_UNICODE)?>,datasets:[{data:<?=json_encode(array_values($dist))?>}]}});
</script>
</div>
</body>
</html>
