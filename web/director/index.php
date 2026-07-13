<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['director']);
$pdo = db();
$schoolId = (int)$u['school_id'];
$school = null;
if ($schoolId) { $st = $pdo->prepare('SELECT * FROM schools WHERE id=? LIMIT 1'); $st->execute([$schoolId]); $school = $st->fetch(); }
$stats = ['students'=>0,'classes'=>0,'teachers'=>0,'batches'=>0,'predictions'=>0,'last_conf'=>'-'];
$batches=[]; $dist=[]; $trend=[]; $lowConf=0; $highConf=0;
if ($schoolId) {
    $q=$pdo->prepare('SELECT COUNT(*) FROM students WHERE school_id=?'); $q->execute([$schoolId]); $stats['students']=(int)$q->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*) FROM classes WHERE school_id=?'); $q->execute([$schoolId]); $stats['classes']=(int)$q->fetchColumn();
    $q=$pdo->prepare("SELECT COUNT(*) FROM users WHERE school_id=? AND role_id=(SELECT id FROM roles WHERE role_key='teacher')"); $q->execute([$schoolId]); $stats['teachers']=(int)$q->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*) FROM prediction_batches WHERE school_id=?'); $q->execute([$schoolId]); $stats['batches']=(int)$q->fetchColumn();
    $q=$pdo->prepare('SELECT COUNT(*) FROM student_predictions WHERE school_id=?'); $q->execute([$schoolId]); $stats['predictions']=(int)$q->fetchColumn();
    $b=$pdo->prepare('SELECT * FROM prediction_batches WHERE school_id=? ORDER BY id DESC LIMIT 7'); $b->execute([$schoolId]); $batches=$b->fetchAll();
    if ($batches) $stats['last_conf']=$batches[0]['mean_confidence'];
    $lastId = $batches[0]['id'] ?? 0;
    if ($lastId) {
        $d=$pdo->prepare('SELECT recommended_direction, COUNT(*) c, ROUND(AVG(recommendation_confidence),4) avgc FROM student_predictions WHERE batch_id=? GROUP BY recommended_direction ORDER BY c DESC'); $d->execute([$lastId]); $dist=$d->fetchAll();
        $lc=$pdo->prepare('SELECT COUNT(*) FROM student_predictions WHERE batch_id=? AND recommendation_confidence < 0.70'); $lc->execute([$lastId]); $lowConf=(int)$lc->fetchColumn();
        $hc=$pdo->prepare('SELECT COUNT(*) FROM student_predictions WHERE batch_id=? AND recommendation_confidence >= 0.90'); $hc->execute([$lastId]); $highConf=(int)$hc->fetchColumn();
    }
    $t=$pdo->prepare('SELECT id, students_count, mean_confidence, DATE(created_at) d FROM prediction_batches WHERE school_id=? ORDER BY id ASC LIMIT 20'); $t->execute([$schoolId]); $trend=$t->fetchAll();
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Direktor paneli</title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head><body><div class="container enterprise">
<header class="topbar"><div><h1>Direktor paneli</h1><p><?=auth_h($school['school_name'] ?? 'Maktab biriktirilmagan')?> · Enterprise Build 0.8</p></div><nav class="nav"><a href="prediction_upload.php">+ Prediction</a><a href="prediction_history.php">Tarix</a><a href="master_students.php">Master Registry</a><a href="analytics.php">Analytics</a><a href="master_students.php">Master Registry</a><a href="ai_copilot.php">AI Copilot</a><a href="smart_monitoring.php">Smart Monitoring</a><a href="ai_report.php" target="_blank">AI Report</a><a href="teachers.php">O‘qituvchilar</a><a href="student_accounts.php">Student loginlar</a><a href="../logout.php">Chiqish</a></nav></header>
<section class="cards grid"><div class="card stat accent"><span>O‘quvchilar</span><strong><?=$stats['students']?></strong><small>bazadagi faol profil</small></div><div class="card stat"><span>Sinflar</span><strong><?=$stats['classes']?></strong><small>avtomatik yaratilgan</small></div><div class="card stat"><span>O‘qituvchilar</span><strong><?=$stats['teachers']?></strong><small>biriktirilgan foydalanuvchi</small></div><div class="card stat"><span>Prediction</span><strong><?=$stats['batches']?></strong><small>batch tarixi</small></div></section>
<section class="grid2"><div class="card"><h2>Maktab profili</h2><p><b>Kod:</b> <?=auth_h($school['school_code'] ?? '-')?></p><p><b>Direktor:</b> <?=auth_h($school['director_name'] ?? $u['full_name'])?></p><p><b>Hudud:</b> <?=auth_h(($school['region'] ?? '').' / '.($school['district'] ?? ''))?></p><p><b>Oxirgi confidence:</b> <span class="badge"><?=auth_h($stats['last_conf'])?></span></p><p><a class="btn" href="prediction_upload.php">Dataset yuklash va Prediction boshlash</a></p></div><div class="card"><h2>Confidence monitoring</h2><div class="metric-grid"><div class="metric"><span>Yuqori ishonch ≥0.90</span><strong><?=$highConf?></strong></div><div class="metric"><span>Past ishonch &lt;0.70</span><strong><?=$lowConf?></strong></div><div class="metric"><span>Natijalar jami</span><strong><?=$stats['predictions']?></strong></div></div><p class="hint">Past confidence bo‘lgan o‘quvchilar uchun qo‘shimcha konsultatsiya tavsiya etiladi.</p></div></section>
<?php if($dist): ?><section class="grid2"><div class="card"><h2>Oxirgi prediction yo‘nalishlari</h2><canvas id="dirPie" height="220"></canvas></div><div class="card"><h2>Prediction trend</h2><canvas id="trendLine" height="220"></canvas></div></section><?php endif; ?>
<section class="card"><h2>Oxirgi predictionlar</h2><?php if(!$batches): ?><p class="hint">Hali prediction tarixi yo‘q.</p><?php else: ?><table><tr><th>ID</th><th>Dataset</th><th>O‘quvchi</th><th>Confidence</th><th>Status</th><th>Sana</th><th></th></tr><?php foreach($batches as $b): ?><tr><td>#<?=auth_h(str_pad($b['id'],6,'0',STR_PAD_LEFT))?></td><td><?=auth_h($b['dataset_name'])?></td><td><?=auth_h($b['students_count'])?></td><td><?=auth_h($b['mean_confidence'])?></td><td><span class="badge"><?=auth_h($b['status'])?></span></td><td><?=auth_h($b['created_at'])?></td><td><a class="mini" href="prediction_results.php?batch_id=<?=auth_h($b['id'])?>">Ko‘rish</a></td></tr><?php endforeach; ?></table><?php endif; ?></section>
<script>
const distLabels = <?=json_encode(array_column($dist,'recommended_direction'), JSON_UNESCAPED_UNICODE)?>;
const distData = <?=json_encode(array_map('intval', array_column($dist,'c')), JSON_UNESCAPED_UNICODE)?>;
const trendLabels = <?=json_encode(array_map(fn($x)=>'#'.$x['id'], $trend), JSON_UNESCAPED_UNICODE)?>;
const trendData = <?=json_encode(array_map(fn($x)=>(float)$x['mean_confidence'], $trend), JSON_UNESCAPED_UNICODE)?>;
if(document.getElementById('dirPie')) new Chart(document.getElementById('dirPie'), {type:'doughnut', data:{labels:distLabels,datasets:[{data:distData}]}, options:{plugins:{legend:{position:'bottom'}}}});
if(document.getElementById('trendLine')) new Chart(document.getElementById('trendLine'), {type:'line', data:{labels:trendLabels,datasets:[{label:'Mean confidence',data:trendData,tension:.35}]}, options:{scales:{y:{min:0,max:1}}}});
</script>
</div></body></html>
