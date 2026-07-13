<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/prediction_isolation.php';

$u = require_role(['teacher']);
$pdo = db();
ensure_prediction_isolation_schema($pdo);

$teacherId = (int)$u['id'];
$schoolId = (int)$u['school_id'];

$classesQ = $pdo->prepare("
    SELECT c.*, tc.batch_id,
           b.dataset_name, b.created_at AS batch_date, b.is_active,
           (SELECT COUNT(*) FROM student_predictions sp
            WHERE sp.batch_id=tc.batch_id AND sp.school_id=c.school_id AND sp.class_name=c.class_name) AS students_count,
           (SELECT COUNT(*) FROM student_predictions sp
            WHERE sp.batch_id=tc.batch_id AND sp.school_id=c.school_id AND sp.class_name=c.class_name) AS prediction_count
    FROM teacher_classes tc
    JOIN classes c ON c.id=tc.class_id
    LEFT JOIN prediction_batches b ON b.id=tc.batch_id
    WHERE tc.teacher_id=?
    ORDER BY b.id DESC, c.class_name
");
$classesQ->execute([$teacherId]);
$classes = $classesQ->fetchAll();

$stats=[
    'classes'=>count($classes),
    'students'=>array_sum(array_map(fn($x)=>(int)$x['students_count'],$classes)),
    'predictions'=>array_sum(array_map(fn($x)=>(int)$x['prediction_count'],$classes)),
    'batches'=>count(array_unique(array_map(fn($x)=>(int)$x['batch_id'],$classes))),
];
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Teacher Intelligence Center</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container enterprise">
<header class="topbar">
    <div>
        <h1>Teacher Intelligence Center</h1>
        <p><?=auth_h($u['full_name'])?> · <?=auth_h($u['school_name'])?></p>
    </div>
    <nav class="nav"><a href="../dashboard.php">Dashboard</a><a href="../logout.php">Chiqish</a></nav>
</header>

<section class="cards sic-cards">
    <div class="card stat"><span>Biriktirilgan sinf snapshotlari</span><strong><?=$stats['classes']?></strong></div>
    <div class="card stat"><span>Predictionlar</span><strong><?=$stats['batches']?></strong></div>
    <div class="card stat"><span>O‘quvchilar</span><strong><?=$stats['students']?></strong></div>
    <div class="card stat"><span>Natijalar</span><strong><?=$stats['predictions']?></strong></div>
</section>

<section class="card">
    <h2>Mening sinflarim</h2>
    <div class="class-grid">
        <?php foreach($classes as $c): ?>
            <a class="class-card" href="class_view.php?class_id=<?=auth_h($c['id'])?>&batch_id=<?=auth_h($c['batch_id'])?>">
                <b><?=auth_h($c['class_name'])?></b>
                <span><?=auth_h($c['students_count'])?> o‘quvchi</span>
                <em>Prediction #<?=auth_h(str_pad((string)$c['batch_id'],6,'0',STR_PAD_LEFT))?> <?=((int)($c['is_active'] ?? 0)===1)?'— ACTIVE':''?></em>
                <small><?=auth_h($c['dataset_name'])?></small>
            </a>
        <?php endforeach; ?>
        <?php if(!$classes): ?><p class="muted">Sizga hali sinf biriktirilmagan. Direktor panelidan prediction bo‘yicha sinf biriktiring.</p><?php endif; ?>
    </div>
</section>
</div>
</body>
</html>
