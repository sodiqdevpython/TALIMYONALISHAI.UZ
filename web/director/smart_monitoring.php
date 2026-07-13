<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['director']); $pdo=db(); $schoolId=(int)$u['school_id'];
$batchQ=$pdo->prepare('SELECT * FROM prediction_batches WHERE school_id=? AND status="completed" ORDER BY id DESC LIMIT 1'); $batchQ->execute([$schoolId]); $b=$batchQ->fetch(); $batchId=(int)($b['id']??0);
$rows=[];
if($batchId){
    $q=$pdo->prepare('SELECT id, student_name, class_name, recommended_direction, recommendation_confidence, growth_trend, academic_stability, temporal_coverage_ratio,
    ROUND(((1-recommendation_confidence)*45)+((CASE WHEN growth_trend<0 THEN ABS(growth_trend)*100 ELSE 0 END)*20)+((1-academic_stability)*25)+((1-temporal_coverage_ratio)*10),2) risk_score
    FROM student_predictions WHERE batch_id=? ORDER BY risk_score DESC LIMIT 100');
    $q->execute([$batchId]); $rows=$q->fetchAll();
}
function level($s){ if($s>=35) return ['High','risk-high']; if($s>=18) return ['Medium','risk-mid']; return ['Low','risk-low'];}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Smart Monitoring</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container enterprise">
<header class="topbar"><div><h1>Smart Risk Monitoring</h1><p>Confidence, growth, stability va coverage asosida risk scoring.</p></div><nav class="nav"><a href="ai_copilot.php">AI Copilot</a><a href="analytics.php">Analytics</a><a href="index.php">Direktor</a></nav></header>
<section class="card"><h2>Risk Students 2.0</h2><div class="table-wrap"><table><thead><tr><th>O‘quvchi</th><th>Sinf</th><th>Tavsiya</th><th>Confidence</th><th>Growth</th><th>Stability</th><th>Risk Score</th><th>Level</th><th>Plan</th></tr></thead><tbody>
<?php foreach($rows as $r): [$lv,$cls]=level((float)$r['risk_score']); ?><tr><td><?=auth_h($r['student_name'])?></td><td><?=auth_h($r['class_name'])?></td><td><?=auth_h($r['recommended_direction'])?></td><td><?=round((float)$r['recommendation_confidence']*100,1)?>%</td><td><?=auth_h($r['growth_trend'])?></td><td><?=round((float)$r['academic_stability']*100,1)?>%</td><td><b><?=auth_h($r['risk_score'])?></b></td><td><span class="<?=$cls?>"><?=$lv?></span></td><td><a class="mini" href="intervention_plan.php?prediction_id=<?=auth_h($r['id'])?>">Reja</a></td></tr><?php endforeach; ?>
</tbody></table></div></section>
</div></body></html>
