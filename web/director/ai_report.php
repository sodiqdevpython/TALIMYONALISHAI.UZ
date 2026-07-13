<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['director']);
$pdo=db();
$schoolId=(int)$u['school_id'];
$schoolQ=$pdo->prepare('SELECT * FROM schools WHERE id=? LIMIT 1'); $schoolQ->execute([$schoolId]); $school=$schoolQ->fetch();
$batchQ=$pdo->prepare('SELECT * FROM prediction_batches WHERE school_id=? AND status="completed" ORDER BY id DESC LIMIT 1'); $batchQ->execute([$schoolId]); $b=$batchQ->fetch();
if(!$b) die('Prediction mavjud emas.');
$batchId=(int)$b['id'];
$q=$pdo->prepare('SELECT recommended_direction, COUNT(*) c, ROUND(AVG(recommendation_confidence),4) avgc FROM student_predictions WHERE batch_id=? GROUP BY recommended_direction ORDER BY c DESC'); $q->execute([$batchId]); $dirs=$q->fetchAll();
$q=$pdo->prepare('SELECT class_name, COUNT(*) c, ROUND(AVG(recommendation_confidence),4) avgc, ROUND(AVG(growth_trend),4) growth FROM student_predictions WHERE batch_id=? GROUP BY class_name ORDER BY avgc DESC'); $q->execute([$batchId]); $classes=$q->fetchAll();
$q=$pdo->prepare('SELECT COUNT(*), AVG(recommendation_confidence), AVG(temporal_coverage_ratio), AVG(academic_stability) FROM student_predictions WHERE batch_id=?'); $q->execute([$batchId]); $o=$q->fetch(PDO::FETCH_NUM);
$score=round(((float)$o[1]*100*.45)+((float)$o[2]*100*.25)+((float)$o[3]*100*.20)+10,1);
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><title>AI School Report</title><style>
body{font-family:Arial;background:#eef2f7;margin:0;color:#162033}.page{width:210mm;min-height:297mm;background:white;margin:12px auto;padding:20mm;box-shadow:0 8px 30px #0002}.head{border-bottom:3px solid #2563eb;padding-bottom:14px}.badge{background:#dbeafe;color:#1e40af;padding:7px 12px;border-radius:999px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:14px}.card{border:1px solid #e5e7eb;border-radius:12px;padding:14px;margin-top:14px}.score{font-size:48px;font-weight:900;color:#2563eb}table{width:100%;border-collapse:collapse}td,th{padding:8px;border-bottom:1px solid #e5e7eb;text-align:left}.bar{height:10px;background:#e5e7eb;border-radius:999px;overflow:hidden}.bar i{display:block;height:100%;background:#2563eb}@media print{body{background:white}.page{margin:0;box-shadow:none}.no-print{display:none}}
</style></head><body><div class="page"><div class="head"><h1>AI School Intelligence Report</h1><p><?=auth_h($school['school_name'])?> · Batch #<?=str_pad((string)$batchId,6,'0',STR_PAD_LEFT)?> · <?=auth_h($b['dataset_name'])?></p><span class="badge">EduDirectionAI Enterprise Build 0.7</span></div>
<div class="grid"><div class="card"><h2>School AI Health Score</h2><div class="score"><?=$score?>/100</div><p>Confidence, coverage, stability va data quality asosida.</p></div><div class="card"><h2>Summary</h2><table><tr><td>Students</td><td><?=auth_h($o[0])?></td></tr><tr><td>Mean confidence</td><td><?=round((float)$o[1]*100,1)?>%</td></tr><tr><td>Coverage</td><td><?=round((float)$o[2]*100,1)?>%</td></tr><tr><td>Stability</td><td><?=round((float)$o[3]*100,1)?>%</td></tr></table></div></div>
<div class="card"><h2>Directions</h2><?php foreach($dirs as $d): $pct=round(((int)$d['c']/max(1,(int)$o[0]))*100,1); ?><p><b><?=auth_h($d['recommended_direction'])?></b> — <?=auth_h($d['c'])?> (<?=$pct?>%)</p><div class="bar"><i style="width:<?=$pct?>%"></i></div><?php endforeach; ?></div>
<div class="card"><h2>Class Ranking</h2><table><tr><th>Sinf</th><th>Natija</th><th>Confidence</th><th>Growth</th></tr><?php foreach($classes as $c): ?><tr><td><?=auth_h($c['class_name'])?></td><td><?=auth_h($c['c'])?></td><td><?=round((float)$c['avgc']*100,1)?>%</td><td><?=auth_h($c['growth'])?></td></tr><?php endforeach; ?></table></div>
<div class="card"><h2>AI xulosa</h2><p>Ushbu hisobot oxirgi prediction natijalari asosida avtomatik shakllantirildi. Past confidence va salbiy growth ko‘rsatkichlariga ega o‘quvchilar bilan individual ishlash tavsiya etiladi. Yuqori confidence guruhlari uchun yo‘nalishga mos chuqurlashtirilgan fanlar va kasbiy tayyorgarlik rejasi ishlab chiqilishi mumkin.</p></div>
</div><div class="no-print" style="text-align:center;margin:18px"><button onclick="window.print()">Print / PDF saqlash</button></div></body></html>
