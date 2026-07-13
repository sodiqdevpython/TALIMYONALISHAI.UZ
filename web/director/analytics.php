<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['director']);
$pdo = db();
$schoolId = (int)$u['school_id'];

$schoolQ=$pdo->prepare('SELECT * FROM schools WHERE id=? LIMIT 1');
$schoolQ->execute([$schoolId]);
$school=$schoolQ->fetch();

$batchQ=$pdo->prepare('SELECT * FROM prediction_batches WHERE school_id=? AND status="completed" ORDER BY id DESC LIMIT 1');
$batchQ->execute([$schoolId]);
$lastBatch=$batchQ->fetch();
$batchId=(int)($lastBatch['id'] ?? 0);

$batchesQ=$pdo->prepare('SELECT id, dataset_name, students_count, mean_confidence, temporal_coverage_mean, created_at FROM prediction_batches WHERE school_id=? AND status="completed" ORDER BY id ASC');
$batchesQ->execute([$schoolId]);
$batches=$batchesQ->fetchAll();

$overview=[
 'students'=>0,'avg_conf'=>0,'avg_coverage'=>0,'avg_stability'=>0,'low'=>0,'mid'=>0,'high'=>0,'risk'=>0,'score'=>0
];
$directions=[]; $classes=[]; $hist=[]; $riskRows=[]; $teacherRows=[]; $earlyWarnings=[];

if($batchId){
    $q=$pdo->prepare('SELECT COUNT(*), AVG(recommendation_confidence), AVG(temporal_coverage_ratio), AVG(academic_stability) FROM student_predictions WHERE batch_id=?');
    $q->execute([$batchId]);
    $r=$q->fetch(PDO::FETCH_NUM);
    $overview['students']=(int)$r[0];
    $overview['avg_conf']=(float)$r[1];
    $overview['avg_coverage']=(float)$r[2];
    $overview['avg_stability']=(float)$r[3];

    $q=$pdo->prepare('SELECT recommended_direction, COUNT(*) c, ROUND(AVG(recommendation_confidence),4) avgc FROM student_predictions WHERE batch_id=? GROUP BY recommended_direction ORDER BY c DESC');
    $q->execute([$batchId]); $directions=$q->fetchAll();

    $q=$pdo->prepare('SELECT class_name, COUNT(*) c, ROUND(AVG(recommendation_confidence),4) avgc, ROUND(AVG(academic_stability),4) stability, ROUND(AVG(growth_trend),4) growth FROM student_predictions WHERE batch_id=? GROUP BY class_name ORDER BY avgc DESC');
    $q->execute([$batchId]); $classes=$q->fetchAll();

    $q=$pdo->prepare('SELECT 
        SUM(recommendation_confidence>=0.95) h95,
        SUM(recommendation_confidence>=0.90 AND recommendation_confidence<0.95) h90,
        SUM(recommendation_confidence>=0.80 AND recommendation_confidence<0.90) h80,
        SUM(recommendation_confidence>=0.70 AND recommendation_confidence<0.80) h70,
        SUM(recommendation_confidence<0.70) hlow
        FROM student_predictions WHERE batch_id=?');
    $q->execute([$batchId]);
    $hist=$q->fetch();
    $overview['high']=(int)$hist['h95']+(int)$hist['h90'];
    $overview['mid']=(int)$hist['h80']+(int)$hist['h70'];
    $overview['low']=(int)$hist['hlow'];
    $overview['risk']=$overview['low'];

    $q=$pdo->prepare('SELECT id, student_name, class_name, recommended_direction, recommendation_confidence, alternative_direction, growth_trend, academic_stability FROM student_predictions WHERE batch_id=? AND (recommendation_confidence<0.80 OR growth_trend<0 OR academic_stability<0.70) ORDER BY recommendation_confidence ASC LIMIT 25');
    $q->execute([$batchId]); $riskRows=$q->fetchAll();

    $q=$pdo->prepare('
        SELECT u.full_name, COUNT(DISTINCT c.id) class_count, COUNT(sp.id) prediction_count, ROUND(AVG(sp.recommendation_confidence),4) avgc
        FROM users u
        JOIN roles r ON r.id=u.role_id AND r.role_key="teacher"
        LEFT JOIN teacher_classes tc ON tc.teacher_id=u.id
        LEFT JOIN classes c ON c.id=tc.class_id
        LEFT JOIN student_predictions sp ON sp.school_id=u.school_id AND sp.class_name=c.class_name AND sp.batch_id=?
        WHERE u.school_id=?
        GROUP BY u.id
        ORDER BY avgc DESC, u.full_name
    ');
    $q->execute([$batchId,$schoolId]); $teacherRows=$q->fetchAll();

    $overview['score']=round(
        (($overview['avg_conf']*100)*0.45) +
        (($overview['avg_coverage']*100)*0.25) +
        (($overview['avg_stability']*100)*0.20) +
        ((1 - min(1,$overview['low']/max(1,$overview['students'])))*100*0.10)
    ,1);

    if($overview['low']>0) $earlyWarnings[]=$overview['low'].' nafar o‘quvchida confidence 70% dan past. Qo‘shimcha konsultatsiya tavsiya etiladi.';
    foreach($classes as $c){
        if((float)$c['growth']<0) $earlyWarnings[]=$c['class_name'].' sinfida o‘sish tendensiyasi pasayish belgilarini ko‘rsatmoqda.';
        if((float)$c['avgc']<0.80) $earlyWarnings[]=$c['class_name'].' sinfida o‘rtacha ishonchlilik pastroq.';
    }
    if(!$earlyWarnings) $earlyWarnings[]='Oxirgi prediction bo‘yicha kritik risk aniqlanmadi. Monitoringni davom ettirish tavsiya etiladi.';
}
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>AI Analytics Center</title>
<link rel="stylesheet" href="../assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body><div class="container enterprise">
<header class="topbar"><div><h1>AI Analytics Center</h1><p><?=auth_h($school['school_name'] ?? '')?> · Enterprise Build 0.8</p></div><nav class="nav"><a href="index.php">Direktor</a><a href="ai_copilot.php">AI Copilot</a><a href="prediction_history.php">Tarix</a><a href="ai_report.php" target="_blank">AI Report</a><a href="../logout.php">Chiqish</a></nav></header>

<?php if(!$batchId): ?>
<section class="card"><h2>Hali prediction mavjud emas</h2><p>Analytics Center prediction natijalaridan keyin ishlaydi.</p></section>
<?php else: ?>

<section class="analytics-hero">
    <div class="score-card">
        <span>School AI Health Score</span>
        <strong><?=$overview['score']?></strong>
        <em>/100</em>
        <div class="gauge"><i style="width:<?=max(0,min(100,$overview['score']))?>%"></i></div>
        <p>Confidence, coverage, stability va risk ko‘rsatkichlari asosida.</p>
    </div>
    <div class="card"><h3>Oxirgi batch</h3><p><b>#<?=str_pad((string)$batchId,6,'0',STR_PAD_LEFT)?></b></p><p><?=auth_h($lastBatch['dataset_name'])?></p><p><?=auth_h($lastBatch['created_at'])?></p></div>
    <div class="card"><h3>AI Early Warning</h3><?php foreach(array_slice($earlyWarnings,0,4) as $w): ?><p class="warn">⚠️ <?=auth_h($w)?></p><?php endforeach; ?></div>
</section>

<section class="cards sic-cards">
    <div class="card stat"><span>O‘quvchilar</span><strong><?=$overview['students']?></strong><small>oxirgi prediction</small></div>
    <div class="card stat"><span>Mean Confidence</span><strong><?=round($overview['avg_conf']*100,1)?>%</strong><small>o‘rtacha ishonchlilik</small></div>
    <div class="card stat"><span>Coverage</span><strong><?=round($overview['avg_coverage']*100,1)?>%</strong><small>ma’lumot qamrovi</small></div>
    <div class="card stat"><span>Risk Students</span><strong><?=$overview['risk']?></strong><small>confidence/risk monitoring</small></div>
</section>

<section class="grid2">
    <div class="card"><h2>Confidence Distribution</h2><canvas id="hist" height="260"></canvas></div>
    <div class="card"><h2>Direction Distribution</h2><canvas id="dir" height="260"></canvas></div>
</section>

<section class="grid2">
    <div class="card"><h2>Prediction Trend</h2><canvas id="trend" height="240"></canvas></div>
    <div class="card"><h2>Class Ranking</h2><canvas id="classRank" height="240"></canvas></div>
</section>

<section class="card"><h2>Risk Students</h2><div class="table-wrap small-table"><table><thead><tr><th>O‘quvchi</th><th>Sinf</th><th>Tavsiya</th><th>Confidence</th><th>Growth</th><th>Stability</th><th>Profil</th></tr></thead><tbody>
<?php foreach($riskRows as $r): ?><tr><td><?=auth_h($r['student_name'])?></td><td><?=auth_h($r['class_name'])?></td><td><?=auth_h($r['recommended_direction'])?></td><td><?=round((float)$r['recommendation_confidence']*100,1)?>%</td><td><?=auth_h($r['growth_trend'])?></td><td><?=round((float)$r['academic_stability']*100,1)?>%</td><td><a class="mini" href="student_profile.php?prediction_id=<?=auth_h($r['id'])?>">Profil</a></td></tr><?php endforeach; ?>
<?php if(!$riskRows): ?><tr><td colspan="7">Risk holatlari topilmadi.</td></tr><?php endif; ?>
</tbody></table></div></section>

<section class="grid2">
    <div class="card"><h2>Class Intelligence Table</h2><table><thead><tr><th>Sinf</th><th>Natija</th><th>Confidence</th><th>Stability</th><th>Growth</th></tr></thead><tbody><?php foreach($classes as $c): ?><tr><td><?=auth_h($c['class_name'])?></td><td><?=auth_h($c['c'])?></td><td><?=round((float)$c['avgc']*100,1)?>%</td><td><?=round((float)$c['stability']*100,1)?>%</td><td><?=auth_h($c['growth'])?></td></tr><?php endforeach; ?></tbody></table></div>
    <div class="card"><h2>Teacher Effectiveness</h2><table><thead><tr><th>O‘qituvchi</th><th>Sinf</th><th>Natija</th><th>AI score</th></tr></thead><tbody><?php foreach($teacherRows as $t): ?><tr><td><?=auth_h($t['full_name'])?></td><td><?=auth_h($t['class_count'])?></td><td><?=auth_h($t['prediction_count'])?></td><td><?=($t['avgc']!==null?round((float)$t['avgc']*100,1).'%':'-')?></td></tr><?php endforeach; ?></tbody></table></div>
</section>

<script>
new Chart(document.getElementById('hist'),{type:'bar',data:{labels:['95-100%','90-95%','80-90%','70-80%','<70%'],datasets:[{label:'Students',data:[<?= (int)$hist['h95']?>,<?= (int)$hist['h90']?>,<?= (int)$hist['h80']?>,<?= (int)$hist['h70']?>,<?= (int)$hist['hlow']?>]}]},options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}});
new Chart(document.getElementById('dir'),{type:'doughnut',data:{labels:<?=json_encode(array_column($directions,'recommended_direction'),JSON_UNESCAPED_UNICODE)?>,datasets:[{data:<?=json_encode(array_map('intval',array_column($directions,'c')))?>}]}});
new Chart(document.getElementById('trend'),{type:'line',data:{labels:<?=json_encode(array_map(fn($x)=>'#'.$x['id'],$batches),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Mean confidence',data:<?=json_encode(array_map(fn($x)=>(float)$x['mean_confidence']*100,$batches))?>,tension:.35},{label:'Coverage',data:<?=json_encode(array_map(fn($x)=>(float)$x['temporal_coverage_mean']*100,$batches))?>,tension:.35}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
new Chart(document.getElementById('classRank'),{type:'bar',data:{labels:<?=json_encode(array_column($classes,'class_name'),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Class confidence',data:<?=json_encode(array_map(fn($x)=>(float)$x['avgc']*100,$classes))?>}]},options:{indexAxis:'y',scales:{x:{beginAtZero:true,max:100}}}});
</script>
<?php endif; ?>
</div></body></html>
