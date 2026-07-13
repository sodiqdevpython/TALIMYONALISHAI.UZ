<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['super_admin']);
$pdo=db();

$models=$pdo->query('SELECT * FROM research_models ORDER BY is_active DESC, id DESC')->fetchAll();
$batches=$pdo->query('
SELECT b.*, s.school_name, m.model_name, m.model_version
FROM prediction_batches b
LEFT JOIN schools s ON s.id=b.school_id
LEFT JOIN research_models m ON m.id=b.model_id
ORDER BY b.id DESC LIMIT 50
')->fetchAll();

$metrics=$pdo->query('
SELECT m.model_code, m.model_version, COUNT(b.id) batches, ROUND(AVG(b.mean_confidence),4) mean_conf, ROUND(AVG(b.temporal_coverage_mean),4) coverage, SUM(b.students_count) students
FROM research_models m
LEFT JOIN prediction_batches b ON b.model_id=m.id AND b.status="completed"
GROUP BY m.id
ORDER BY mean_conf DESC
')->fetchAll();
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AI Laboratory</title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head>
<body><div class="container enterprise"><header class="topbar"><div><h1>AI Laboratory</h1><p>Model registry, experiment monitoring va research edition metrikalari.</p></div><nav class="nav"><a href="index.php">Admin</a><a href="regional_dashboard.php">Regional</a><a href="../logout.php">Chiqish</a></nav></header>
<section class="grid2"><div class="card"><h2>Model Registry</h2><table><tr><th>Model</th><th>Version</th><th>Status</th><th>Path</th></tr><?php foreach($models as $m): ?><tr><td><?=auth_h($m['model_name'])?></td><td><?=auth_h($m['model_version'])?></td><td><?=((int)$m['is_active']===1?'<span class="badge">active</span>':'inactive')?></td><td><?=auth_h($m['model_path'])?></td></tr><?php endforeach; ?></table></div><div class="card"><h2>Model Performance</h2><canvas id="modelChart" height="250"></canvas></div></section>
<section class="card"><h2>Experiment / Prediction Runs</h2><div class="table-wrap"><table><thead><tr><th>ID</th><th>School</th><th>Model</th><th>Dataset</th><th>Students</th><th>Confidence</th><th>Status</th><th>Date</th></tr></thead><tbody><?php foreach($batches as $b): ?><tr><td>#<?=str_pad((string)$b['id'],6,'0',STR_PAD_LEFT)?></td><td><?=auth_h($b['school_name'])?></td><td><?=auth_h(($b['model_name'] ?? '').' '.($b['model_version'] ?? ''))?></td><td><?=auth_h($b['dataset_name'])?></td><td><?=auth_h($b['students_count'])?></td><td><?=round((float)$b['mean_confidence']*100,1)?>%</td><td><?=auth_h($b['status'])?></td><td><?=auth_h($b['created_at'])?></td></tr><?php endforeach; ?></tbody></table></div></section>
<script>new Chart(document.getElementById('modelChart'),{type:'bar',data:{labels:<?=json_encode(array_map(fn($x)=>$x['model_code'].' '.$x['model_version'],$metrics),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Mean Confidence',data:<?=json_encode(array_map(fn($x)=>(float)$x['mean_conf']*100,$metrics))?>},{label:'Coverage',data:<?=json_encode(array_map(fn($x)=>(float)$x['coverage']*100,$metrics))?>}]},options:{scales:{y:{beginAtZero:true,max:100}}}});</script>
</div></body></html>
