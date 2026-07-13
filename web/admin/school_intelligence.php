<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['super_admin']);
$pdo=db();
$schoolId=(int)($_GET['school_id'] ?? 0);
$st=$pdo->prepare('SELECT * FROM schools WHERE id=? LIMIT 1'); $st->execute([$schoolId]); $school=$st->fetch();
if(!$school) die('Maktab topilmadi.');

$summaryQ=$pdo->prepare('
SELECT COUNT(DISTINCT st.id) students, COUNT(DISTINCT b.id) batches, COUNT(sp.id) predictions,
ROUND(AVG(sp.recommendation_confidence),4) avg_conf, ROUND(AVG(sp.temporal_coverage_ratio),4) coverage, ROUND(AVG(sp.academic_stability),4) stability
FROM schools s
LEFT JOIN students st ON st.school_id=s.id
LEFT JOIN prediction_batches b ON b.school_id=s.id AND b.status="completed"
LEFT JOIN student_predictions sp ON sp.school_id=s.id
WHERE s.id=?
'); $summaryQ->execute([$schoolId]); $sum=$summaryQ->fetch();

$classesQ=$pdo->prepare('SELECT class_name, COUNT(*) c, ROUND(AVG(recommendation_confidence),4) avgc, ROUND(AVG(growth_trend),4) growth FROM student_predictions WHERE school_id=? GROUP BY class_name ORDER BY avgc DESC');
$classesQ->execute([$schoolId]); $classes=$classesQ->fetchAll();

$dirsQ=$pdo->prepare('SELECT recommended_direction, COUNT(*) c FROM student_predictions WHERE school_id=? GROUP BY recommended_direction ORDER BY c DESC');
$dirsQ->execute([$schoolId]); $dirs=$dirsQ->fetchAll();

function score($c,$co,$s,$p){ if($p<=0)return 0; return round(((float)$c*100*.50)+((float)$co*100*.25)+((float)$s*100*.25),1); }
$score=score($sum['avg_conf'],$sum['coverage'],$sum['stability'],$sum['predictions']);
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>School Intelligence</title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head>
<body><div class="container enterprise"><header class="topbar"><div><h1>School Intelligence Passport</h1><p><?=auth_h($school['school_name'])?> · <?=auth_h($school['region'].' / '.$school['district'])?></p></div><nav class="nav"><a href="regional_dashboard.php">Regional</a><a href="index.php">Admin</a><a href="../logout.php">Chiqish</a></nav></header>
<section class="analytics-hero"><div class="score-card"><span>School AI Score</span><strong><?=$score?></strong><em>/100</em><div class="gauge"><i style="width:<?=$score?>%"></i></div><p>Confidence, coverage va stability asosida.</p></div><div class="card"><h3>Students</h3><p class="bigrec"><?=auth_h($sum['students'])?></p></div><div class="card"><h3>Predictions</h3><p class="bigrec"><?=auth_h($sum['predictions'])?></p></div></section>
<section class="grid2"><div class="card"><h2>Direction Distribution</h2><canvas id="d" height="260"></canvas></div><div class="card"><h2>Class Ranking</h2><canvas id="c" height="260"></canvas></div></section>
<section class="card"><h2>Class Intelligence</h2><table><thead><tr><th>Sinf</th><th>Natija</th><th>Confidence</th><th>Growth</th></tr></thead><tbody><?php foreach($classes as $c): ?><tr><td><?=auth_h($c['class_name'])?></td><td><?=auth_h($c['c'])?></td><td><?=round((float)$c['avgc']*100,1)?>%</td><td><?=auth_h($c['growth'])?></td></tr><?php endforeach; ?></tbody></table></section>
<script>
new Chart(document.getElementById('d'),{type:'doughnut',data:{labels:<?=json_encode(array_column($dirs,'recommended_direction'),JSON_UNESCAPED_UNICODE)?>,datasets:[{data:<?=json_encode(array_map('intval',array_column($dirs,'c')))?>}]}});
new Chart(document.getElementById('c'),{type:'bar',data:{labels:<?=json_encode(array_column($classes,'class_name'),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Confidence',data:<?=json_encode(array_map(fn($x)=>(float)$x['avgc']*100,$classes))?>}]},options:{indexAxis:'y',scales:{x:{beginAtZero:true,max:100}}}});
</script></div></body></html>
