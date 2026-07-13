<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/master_registry.php';
$u=require_role(['director']);
$pdo=db();
ensure_master_registry_schema($pdo);
$schoolId=(int)$u['school_id'];
$id=(int)($_GET['prediction_id'] ?? 0);

$stmt=$pdo->prepare('
SELECT sp.*, b.id batch_no, b.dataset_name, b.created_at batch_date, m.model_name, m.model_version, s.school_name
FROM student_predictions sp
JOIN prediction_batches b ON b.id=sp.batch_id
LEFT JOIN research_models m ON m.id=b.model_id
LEFT JOIN schools s ON s.id=sp.school_id
WHERE sp.id=? AND sp.school_id=? LIMIT 1');
$stmt->execute([$id,$schoolId]);
$p=$stmt->fetch();
if(!$p) die('Digital Twin profili topilmadi.');

function score100($v){ if($v===null || $v==='') return 0; $x=(float)$v; if($x<=1)$x*=100; return max(0,min(100,$x));}
function h($v){ return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8'); }
function academic100($v): float {
    $x = (float)$v;
    // Datasetlarda baholar ba’zan 0–5, ba’zan 0–100 shkalada keladi.
    // Digital Twin uchun hammasi 0–100 shkalaga keltiriladi.
    if ($x <= 1 && $x >= 0) return round($x * 100, 2);
    if ($x <= 5 && $x >= 0) return round($x * 20, 2);
    if ($x <= 10 && $x >= 0) return round($x * 10, 2);
    return round(max(0, min(100, $x)), 2);
}

function ai_event_label(int $i, array $academic, array $confidence, array $career): array {
    $v = (float)($academic[$i] ?? 0);
    $c = (float)($confidence[$i] ?? 0);
    $prev = $i > 0 ? (float)($academic[$i-1] ?? $v) : $v;
    $delta = $v - $prev;
    $dirChanged = $i > 0 && (($career[$i] ?? '') !== ($career[$i-1] ?? ''));

    if ($i === 0) {
        return ['🟢', 'Baseline', 'Boshlang‘ich ko‘rsatkich', 'risk-low'];
    }
    if ($delta <= -8) {
        return ['🔴', 'Academic Drop', 'Akademik ko‘rsatkich '.round(abs($delta),1).' ball pasaydi', 'risk-high'];
    }
    if ($delta >= 8) {
        return ['📈', 'Academic Growth', 'Akademik ko‘rsatkich '.round($delta,1).' ball oshdi', 'risk-low'];
    }
    if ($dirChanged) {
        return ['🔄', 'Direction Shift', 'Yo‘nalish profili o‘zgargan', 'risk-mid'];
    }
    if ($c < 75) {
        return ['🟡', 'Confidence Decrease', 'AI confidence pastroq', 'risk-mid'];
    }
    if ($v >= 85 && $c >= 85) {
        return ['⭐', 'High Performance', 'Yuqori akademik va AI ishonchlilik', 'risk-low'];
    }
    if ($i > 0 && $delta > 2) {
        return ['💪', 'Recovery', 'Ijobiy tiklanish kuzatildi', 'risk-low'];
    }
    return ['🟢', 'Stable Progress', 'Barqaror rivojlanish', 'risk-low'];
}


$row=json_decode((string)$p['full_json'],true) ?: [];

// Build 4.1: Unified Longitudinal History.
// Agar o‘quvchi boshqa maktablarda ham o‘qigan bo‘lsa va master_student_id bir xil bo‘lsa,
// Digital Twin barcha predictionlardan 1–11-sinf tarixini yig‘adi.
$history=[];
$unifiedSources=[];
$masterId = (int)($p['master_student_id'] ?? 0);

if ($masterId > 0) {
    $hq = $pdo->prepare('
        SELECT sp.*, b.id AS batch_no, b.dataset_name, b.created_at AS batch_date, s.school_name
        FROM student_predictions sp
        JOIN prediction_batches b ON b.id=sp.batch_id
        LEFT JOIN schools s ON s.id=sp.school_id
        WHERE sp.master_student_id=?
        ORDER BY b.created_at ASC, sp.id ASC
    ');
    $hq->execute([$masterId]);
    $allPreds = $hq->fetchAll();

    $byGrade = [];
    foreach ($allPreds as $ap) {
        $rj = json_decode((string)$ap['full_json'], true) ?: [];
        $tmp = [];
        if (!empty($rj['temporal_history'])) {
            $decoded = json_decode((string)$rj['temporal_history'], true);
            if (is_array($decoded)) $tmp = $decoded;
        }
        if (!$tmp) {
            $years = max(1, (int)($ap['temporal_years_count'] ?? 1));
            for ($i=1; $i<=$years; $i++) {
                $base=max(50,min(98,(float)$ap['academic_mean'] + ($i-6)*(float)$ap['growth_trend']*10));
                $tmp[]=['grade'=>$i,'year_mean'=>round($base,2),'direction'=>$ap['recommended_direction']];
            }
        }

        foreach ($tmp as $item) {
            $g = (int)($item['grade'] ?? 0);
            if ($g <= 0) continue;
            $item['school_name'] = $ap['school_name'] ?? '';
            $item['dataset_name'] = $ap['dataset_name'] ?? '';
            $item['batch_no'] = $ap['batch_no'] ?? '';
            $item['direction'] = $item['direction'] ?? $ap['recommended_direction'];
            // Duplicate sinf bo‘lsa, keyingi maktab/batchdagi yozuv ustun turadi.
            $byGrade[$g] = $item;
            $unifiedSources[$ap['school_id']] = $ap['school_name'] ?? ('School #'.$ap['school_id']);
        }
    }

    if ($byGrade) {
        ksort($byGrade);
        $history = array_values($byGrade);
    }
}

if (!$history) {
    if(!empty($row['temporal_history'])){
        $tmp=json_decode($row['temporal_history'],true);
        if(is_array($tmp)) $history=$tmp;
    }
}

if(!$history){
    for($i=1;$i<=max(1,(int)$p['temporal_years_count']);$i++){
        $base=max(50,min(98,(float)$p['academic_mean'] + ($i-6)*(float)$p['growth_trend']*10));
        $history[]=['grade'=>$i,'year_mean'=>round($base,2),'direction'=>$p['recommended_direction'], 'school_name'=>$p['school_name'] ?? ''];
    }
}

$unifiedCoverage = min(1, count($history) / 11);
$labels=array_map(fn($x)=>($x['grade'] ?? '').'-sinf',$history);
$academic=array_map(fn($x)=>academic100($x['year_mean'] ?? $p['academic_mean']),$history);
$confidence=[];
$career=[];
$dirs=['Pedagogika','Iqtisodiyot','Muhandislik','IT','Tibbiyot'];
foreach($history as $i=>$x){
    $confidence[]=round(min(100,max(45,($academic[$i]??70)*0.7 + score100($p['academic_stability'])*0.15 + score100($p['temporal_coverage_ratio'])*0.15)),1);
    $career[]=$x['direction'] ?? $dirs[min(count($dirs)-1,(int)floor($i/2))];
}
$potential=round(
    score100($p['recommendation_confidence'])*0.30 +
    score100($p['academic_stability'])*0.20 +
    score100($unifiedCoverage)*0.20 +
    min(100,max(0,50+((float)$p['growth_trend']*100)))*0.15 +
    max(0,min(100,(float)$p['academic_mean']))*0.15
,1);

$events=[];
foreach($history as $i=>$x){
    $grade=$x['grade'] ?? ($i+1);
    $val=academic100($x['year_mean'] ?? 0);
    if($i>0){
        $prev=academic100($history[$i-1]['year_mean'] ?? $val);
        if($val-$prev>=3) $events[]="$grade-sinf: akademik o‘sish kuchaydi (+".round($val-$prev,1).").";
        if($prev-$val>=3) $events[]="$grade-sinf: akademik pasayish kuzatildi (-".round($prev-$val,1).").";
    }
}
if(!$events) $events[]='11 yillik timeline barqaror rivojlanishni ko‘rsatmoqda.';
$indices=['IT'=>$p['IT_Index'],'Muhandislik'=>$p['Engineering_Index'],'Tibbiyot'=>$p['Medicine_Index'],'Iqtisodiyot'=>$p['Economics_Index'],'Pedagogika'=>$p['Pedagogy_Index']];
arsort($indices);
$riskScore = round(max(0, min(100,
    (100-score100($p['recommendation_confidence']))*0.35 +
    (100-score100($p['academic_stability']))*0.25 +
    (100-score100($unifiedCoverage))*0.15 +
    max(0, -(float)$p['growth_trend']*100)*0.25
)),1);
$growthScore = round(min(100,max(0,50+((float)$p['growth_trend']*1000))),1);
$analysisMetrics = [
    'Reliability' => round(score100($p['recommendation_confidence']),1),
    'Growth' => $growthScore,
    'Stability' => round(score100($p['academic_stability']),1),
    'Consistency' => round(score100($unifiedCoverage),1),
    'Risk' => $riskScore
];
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AI Digital Twin</title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head>
<body><div class="container enterprise student-intel">
<header class="topbar"><div><h1>AI Digital Twin</h1><p><?=h($p['student_name'])?> · <?=h($p['class_name'])?> · <?=h($p['school_name'])?></p></div><nav class="nav"><a href="student_profile.php?prediction_id=<?=h($p['id'])?>">AI Passport</a><a href="future_simulation.php?prediction_id=<?=h($p['id'])?>">Future Simulation</a><a href="prediction_results.php?batch_id=<?=h($p['batch_id'])?>">Prediction</a><a href="index.php">Direktor</a><a href="../logout.php">Chiqish</a></nav></header>

<section class="passport-hero">
<div class="identity-card"><div class="avatar"><?=h(mb_substr($p['student_name'],0,1,'UTF-8'))?></div><div><h2><?=h($p['student_name'])?></h2><p><b>Digital Twin ID:</b> DT-<?=h(str_pad($p['id'],6,'0',STR_PAD_LEFT))?></p><p><b>Model:</b> <?=h(($p['model_name'] ?? '').' '.($p['model_version'] ?? ''))?> · <b>Dataset:</b> <?=h($p['dataset_name'])?></p><p><b>Career State:</b> <?=h($p['recommended_direction'])?> · <?=round((float)$p['recommendation_confidence']*100,1)?>%</p></div></div>
<div class="match-card"><span>AI POTENTIAL INDEX</span><strong><?=$potential?></strong><em>/100</em><div class="gauge"><i style="width:<?=$potential?>%"></i></div><p>Academic DNA + Soft Skills + Growth + Confidence</p></div>
</section>

<section class="card digital-twin-brain"><h2>AI Brain Summary</h2><div class="brain-grid"><div>🧠 Career DNA: <b><?=h($p['recommended_direction'])?></b></div><div>📈 Potential: <b><?=$potential?>/100</b></div><div>🧬 Top Index: <b><?=h(array_key_first($indices))?></b></div><div>🔮 Simulation: <a class="mini" href="future_simulation.php?prediction_id=<?=h($p['id'])?>">What-if ochish</a></div></div></section>
<?php if($masterId>0 && count($unifiedSources)>1): ?>
<section class="card snapshot-note">
    <h2>Unified Student History</h2>
    <p><b>Master ID:</b> MS-<?=h(str_pad((string)$masterId,6,'0',STR_PAD_LEFT))?> · <b>Qamrov:</b> <?=count($history)?>/11 sinf</p>
    <p><b>Birlashtirilgan maktablar:</b> <?=h(implode(' → ', array_values($unifiedSources)))?></p>
    <p class="hint">Ushbu Digital Twin bir nechta maktabdagi prediction tarixlarini yagona o‘quvchi profili ostida birlashtirdi.</p>
</section>
<?php endif; ?>
<section class="cards sic-cards">
<div class="card stat"><span>Academic DNA</span><strong><?=round((float)$p['academic_mean'],1)?></strong><small>o‘rtacha akademik ko‘rsatkich</small></div>
<div class="card stat"><span>Growth DNA</span><strong><?=h(round((float)$p['growth_trend'],3))?></strong><small>o‘sish tendensiyasi</small></div>
<div class="card stat"><span>Stability DNA</span><strong><?=round(score100($p['academic_stability']),1)?>%</strong><small>barqarorlik</small></div>
<div class="card stat"><span>Coverage DNA</span><strong><?=round(score100($unifiedCoverage),1)?>%</strong><small>temporal qamrov</small></div>
</section>

<section class="grid2">
<div class="card"><h2>Academic Evolution</h2><canvas id="academic" height="260"></canvas></div>
<div class="card"><h2>AI Confidence Evolution</h2><canvas id="confidence" height="260"></canvas></div>
</section>

<section class="card dt-timeline-card">
    <h2>Career Evolution Timeline</h2>
    <p class="muted">1-sinfdan 11-sinfgacha yo‘nalishlar evolyutsiyasi va AI confidence dinamikasi.</p>
    <div class="dt-timeline">
        <?php foreach($career as $i=>$d): ?>
            <div class="dt-step">
                <span><?=h($labels[$i] ?? '')?></span>
                <b><?=h($d)?></b>
                <em><?=h(round($confidence[$i] ?? 0,1))?>%</em>
                <?php if(!empty($history[$i]['school_name'])): ?><small><?=h($history[$i]['school_name'])?></small><?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section class="card dt-analysis-card">
    <div class="dt-analysis-head">
        <div>
            <h2>🤖 AI Analysis & Explainable Timeline</h2>
            <p>Digital Twin sahifasining AI izohi endi to‘liq eni bo‘yicha chiqadi va matn siqilmaydi.</p>
        </div>
        <div class="dt-risk-pill <?=($riskScore>=35?'risk-high':($riskScore>=18?'risk-mid':'risk-low'))?>">Risk: <?=$riskScore?>%</div>
    </div>

    <div class="dt-analysis-grid">
        <div class="dt-metrics">
            <?php foreach($analysisMetrics as $name=>$val): ?>
                <div class="dt-metric">
                    <span><?=h($name)?></span>
                    <b><?=h($val)?>%</b>
                    <div class="bar"><i style="width:<?=max(0,min(100,(float)$val))?>%"></i></div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="dt-explain">
            <h3>Explainable Timeline</h3>
            <?php foreach(array_slice($events,0,8) as $e): ?><p class="check">✅ <?=h($e)?></p><?php endforeach; ?>
            <div class="advice">
                <b>AI xulosa:</b><br>
                O‘quvchining raqamli nusxasi <?=h($p['recommended_direction'])?> yo‘nalishiga yuqori moslikni ko‘rsatmoqda.
                Eng kuchli indeks: <?=h(array_key_first($indices))?>. 11 yillik rivojlanish, stability va temporal coverage indikatorlari tavsiyaning asosiy izohlanadigan omillari hisoblanadi.
            </div>
        </div>
    </div>
</section>

<section class="grid2">
<div class="card"><h2>Direction DNA</h2><?php foreach($indices as $k=>$v): ?><div class="skill-line"><span><?=h($k)?></span><b><?=round((float)$v,1)?></b><div class="bar"><i style="width:<?=max(0,min(100,(float)$v))?>%"></i></div></div><?php endforeach; ?></div>
<div class="card"><h2>AI Event Timeline</h2>
<p class="muted">Har bir sinf bo‘yicha AI hodisa: o‘sish, pasayish, yo‘nalish o‘zgarishi yoki barqarorlik.</p>
<div class="event-timeline">
<?php foreach($academic as $i=>$v):
    [$icon, $label, $desc, $cls] = ai_event_label($i, $academic, $confidence, $career);
?>
    <div class="event-row <?=$cls?>">
        <div class="event-grade"><b><?=h($labels[$i])?></b><small><?=h(round($v,1))?>/100</small></div>
        <div class="event-body"><strong><?=$icon?> <?=h($label)?></strong><span><?=h($desc)?></span></div>
    </div>
<?php endforeach; ?>
</div>
</div>
</section>

<script>
new Chart(document.getElementById('academic'),{type:'line',data:{labels:<?=json_encode($labels,JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Academic mean',data:<?=json_encode($academic)?>,tension:.35,fill:false}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
new Chart(document.getElementById('confidence'),{type:'line',data:{labels:<?=json_encode($labels,JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'AI confidence simulation',data:<?=json_encode($confidence)?>,tension:.35,fill:false}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
</script>
</div></body></html>
