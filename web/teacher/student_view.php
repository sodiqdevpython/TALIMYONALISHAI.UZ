<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/prediction_isolation.php';
$u = require_role(['teacher']);
$pdo = db();
ensure_prediction_isolation_schema($pdo);
$teacherId=(int)$u['id'];
$schoolId=(int)$u['school_id'];
$id=(int)($_GET['prediction_id'] ?? 0);

$stmt=$pdo->prepare('
SELECT sp.*, b.id batch_no, b.dataset_name, m.model_version
FROM student_predictions sp
JOIN prediction_batches b ON b.id=sp.batch_id
LEFT JOIN research_models m ON m.id=b.model_id
JOIN classes c ON c.school_id=sp.school_id AND c.class_name=sp.class_name AND c.batch_id=sp.batch_id
JOIN teacher_classes tc ON tc.class_id=c.id AND tc.teacher_id=? AND tc.batch_id=sp.batch_id
WHERE sp.id=? AND sp.school_id=? LIMIT 1');
$stmt->execute([$teacherId,$id,$schoolId]);
$s=$stmt->fetch();
if(!$s) die('O‘quvchi sizga biriktirilgan sinfda emas.');

$indices=['IT'=>$s['IT_Index'],'Muhandislik'=>$s['Engineering_Index'],'Tibbiyot'=>$s['Medicine_Index'],'Iqtisodiyot'=>$s['Economics_Index'],'Pedagogika'=>$s['Pedagogy_Index']];
arsort($indices);
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title><?=auth_h($s['student_name'])?></title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head><body><div class="container enterprise">
<header class="topbar"><div><h1><?=auth_h($s['student_name'])?></h1><p><?=auth_h($s['class_name'])?> · Teacher View · Prediction #<?=str_pad((string)$s['batch_no'],6,'0',STR_PAD_LEFT)?></p></div><nav class="nav"><a href="class_view.php?class_id=<?=auth_h($_GET['class_id'] ?? '')?>&batch_id=<?=auth_h($s['batch_no'])?>">Sinf</a><a href="index.php">O‘qituvchi</a><a href="../logout.php">Chiqish</a></nav></header>
<section class="passport-hero"><div class="identity-card"><div class="avatar"><?=auth_h(mb_substr($s['student_name'],0,1,'UTF-8'))?></div><div><h2><?=auth_h($s['student_name'])?></h2><p><b>Tavsiya:</b> <?=auth_h($s['recommended_direction'])?> — <?=round((float)$s['recommendation_confidence']*100,1)?>%</p><p><b>Alternativ:</b> <?=auth_h($s['alternative_direction'])?> — <?=round((float)$s['alternative_confidence']*100,1)?>%</p><p><b>Model:</b> <?=auth_h($s['model_version'])?></p></div></div><div class="match-card"><span>TEACHER MONITORING</span><strong><?=auth_h($s['recommended_direction'])?></strong><em><?=round((float)$s['recommendation_confidence']*100,1)?>%</em><div class="gauge"><i style="width:<?=max(0,min(100,(float)$s['recommendation_confidence']*100))?>%"></i></div></div></section>
<section class="grid2"><div class="card"><h2>Yo‘nalish indekslari</h2><canvas id="radar" height="250"></canvas></div><div class="card"><h2>AI izoh</h2><p class="ai-text"><?=auth_h($s['recommendation_reason'])?></p><div class="advice"><b>Tavsiya:</b><br><?=auth_h($s['selected_direction_advice'])?></div></div></section>
<section class="card"><h2>Teacher Notes</h2><p class="muted">Build 0.6 da bu yerga o‘qituvchi izohi, monitoring va rivojlanish statusi qo‘shiladi.</p></section>
<script>new Chart(document.getElementById('radar'),{type:'radar',data:{labels:<?=json_encode(array_keys($indices),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Index',data:<?=json_encode(array_map('floatval',array_values($indices)))?>}]},options:{scales:{r:{beginAtZero:true,max:100}}}});</script>
</div></body></html>
