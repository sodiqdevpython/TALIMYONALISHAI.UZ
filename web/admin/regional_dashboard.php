<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['super_admin']);
$pdo=db();

$summary=$pdo->query('
SELECT 
  COUNT(DISTINCT s.id) schools,
  COUNT(DISTINCT st.id) students,
  COUNT(DISTINCT b.id) batches,
  COUNT(sp.id) predictions,
  ROUND(AVG(sp.recommendation_confidence),4) avg_conf,
  ROUND(AVG(sp.temporal_coverage_ratio),4) avg_coverage,
  ROUND(AVG(sp.academic_stability),4) avg_stability
FROM schools s
LEFT JOIN students st ON st.school_id=s.id
LEFT JOIN prediction_batches b ON b.school_id=s.id AND b.status="completed"
LEFT JOIN student_predictions sp ON sp.school_id=s.id
')->fetch();

$regions=$pdo->query('
SELECT 
  COALESCE(s.region,"Noma’lum") region,
  COUNT(DISTINCT s.id) schools,
  COUNT(DISTINCT st.id) students,
  COUNT(sp.id) predictions,
  ROUND(AVG(sp.recommendation_confidence),4) avg_conf,
  ROUND(AVG(sp.temporal_coverage_ratio),4) coverage,
  ROUND(AVG(sp.academic_stability),4) stability
FROM schools s
LEFT JOIN students st ON st.school_id=s.id
LEFT JOIN student_predictions sp ON sp.school_id=s.id
GROUP BY COALESCE(s.region,"Noma’lum")
ORDER BY avg_conf DESC
')->fetchAll();

$schools=$pdo->query('
SELECT 
  s.id, s.school_code, s.school_name, s.region, s.district,
  COUNT(DISTINCT st.id) students,
  COUNT(DISTINCT b.id) batches,
  COUNT(sp.id) predictions,
  ROUND(AVG(sp.recommendation_confidence),4) avg_conf,
  ROUND(AVG(sp.temporal_coverage_ratio),4) coverage,
  ROUND(AVG(sp.academic_stability),4) stability
FROM schools s
LEFT JOIN students st ON st.school_id=s.id
LEFT JOIN prediction_batches b ON b.school_id=s.id AND b.status="completed"
LEFT JOIN student_predictions sp ON sp.school_id=s.id
GROUP BY s.id
ORDER BY avg_conf DESC, s.school_name
')->fetchAll();

$dirs=$pdo->query('
SELECT recommended_direction, COUNT(*) c 
FROM student_predictions 
GROUP BY recommended_direction 
ORDER BY c DESC
')->fetchAll();

function health_score($conf,$cov,$stab,$pred){
    if($pred<=0) return 0;
    return round(((float)$conf*100*.50)+((float)$cov*100*.25)+((float)$stab*100*.25),1);
}
$totalScore=health_score($summary['avg_conf'],$summary['avg_coverage'],$summary['avg_stability'],$summary['predictions']);
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Regional Intelligence Center</title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head>
<body><div class="container enterprise">
<header class="topbar"><div><h1>Regional Intelligence Center</h1><p>Respublika / viloyat / maktab kesimida AI monitoring · Enterprise 1.0</p></div><nav class="nav"><a href="index.php">Admin</a><a href="schools.php">Maktablar</a><a href="ai_lab.php">AI Lab</a><a href="../logout.php">Chiqish</a></nav></header>

<section class="analytics-hero">
 <div class="score-card"><span>National AI Health Score</span><strong><?=$totalScore?></strong><em>/100</em><div class="gauge"><i style="width:<?=max(0,min(100,$totalScore))?>%"></i></div><p>Barcha maktablar kesimidagi umumiy AI ko‘rsatkich.</p></div>
 <div class="card"><h3>Maktablar</h3><p class="bigrec"><?=auth_h($summary['schools'])?></p><p>Platformaga ulangan maktablar.</p></div>
 <div class="card"><h3>Prediction natijalari</h3><p class="bigrec"><?=auth_h($summary['predictions'])?></p><p>Umumiy student prediction yozuvlari.</p></div>
</section>

<section class="grid2">
 <div class="card"><h2>Hududlar reytingi</h2><canvas id="regionChart" height="260"></canvas></div>
 <div class="card"><h2>Yo‘nalishlar taqsimoti</h2><canvas id="directionChart" height="260"></canvas></div>
</section>

<section class="card"><h2>Regional Ranking Table</h2><div class="table-wrap"><table><thead><tr><th>Hudud</th><th>Maktab</th><th>O‘quvchi</th><th>Prediction</th><th>Confidence</th><th>Coverage</th><th>Stability</th><th>Score</th></tr></thead><tbody>
<?php foreach($regions as $r): $score=health_score($r['avg_conf'],$r['coverage'],$r['stability'],$r['predictions']); ?>
<tr><td><?=auth_h($r['region'])?></td><td><?=auth_h($r['schools'])?></td><td><?=auth_h($r['students'])?></td><td><?=auth_h($r['predictions'])?></td><td><?=round((float)$r['avg_conf']*100,1)?>%</td><td><?=round((float)$r['coverage']*100,1)?>%</td><td><?=round((float)$r['stability']*100,1)?>%</td><td><b><?=$score?></b></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>

<section class="card"><h2>School Ranking</h2><div class="table-wrap"><table><thead><tr><th>Maktab</th><th>Hudud</th><th>O‘quvchi</th><th>Batch</th><th>Prediction</th><th>AI Score</th><th>Detail</th></tr></thead><tbody>
<?php foreach($schools as $s): $score=health_score($s['avg_conf'],$s['coverage'],$s['stability'],$s['predictions']); ?>
<tr><td><?=auth_h($s['school_code'].' — '.$s['school_name'])?></td><td><?=auth_h(($s['region'] ?? '').' / '.($s['district'] ?? ''))?></td><td><?=auth_h($s['students'])?></td><td><?=auth_h($s['batches'])?></td><td><?=auth_h($s['predictions'])?></td><td><b><?=$score?></b></td><td><a class="mini" href="school_intelligence.php?school_id=<?=auth_h($s['id'])?>">Ko‘rish</a></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>

<script>
new Chart(document.getElementById('regionChart'),{type:'bar',data:{labels:<?=json_encode(array_column($regions,'region'),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'AI Score',data:<?=json_encode(array_map(fn($x)=>health_score($x['avg_conf'],$x['coverage'],$x['stability'],$x['predictions']),$regions))?>}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
new Chart(document.getElementById('directionChart'),{type:'doughnut',data:{labels:<?=json_encode(array_column($dirs,'recommended_direction'),JSON_UNESCAPED_UNICODE)?>,datasets:[{data:<?=json_encode(array_map('intval',array_column($dirs,'c')))?>}]}});
</script>
</div></body></html>
