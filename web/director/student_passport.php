<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['director']);
$pdo = db();
$schoolId = (int)$u['school_id'];
$id = (int)($_GET['prediction_id'] ?? 0);
$stmt = $pdo->prepare('
    SELECT sp.*, b.dataset_name, b.created_at AS batch_date, b.id AS batch_no,
           m.model_name, m.model_version, s.school_name, s.school_code, s.region, s.district
    FROM student_predictions sp
    JOIN prediction_batches b ON b.id=sp.batch_id
    LEFT JOIN research_models m ON m.id=b.model_id
    LEFT JOIN schools s ON s.id=sp.school_id
    WHERE sp.id=? AND sp.school_id=? LIMIT 1
');
$stmt->execute([$id,$schoolId]);
$s=$stmt->fetch();
if(!$s) die('Passport topilmadi.');
function h($v){ return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function pct($v){ $x=(float)$v; if($x<=1) $x*=100; return round($x,1).'%'; }
$indices=['IT'=>$s['IT_Index'],'Muhandislik'=>$s['Engineering_Index'],'Tibbiyot'=>$s['Medicine_Index'],'Iqtisodiyot'=>$s['Economics_Index'],'Pedagogika'=>$s['Pedagogy_Index']];
arsort($indices);
$top = array_slice($indices,0,3,true);
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<title>AI Career Passport</title>
<style>
body{font-family:Arial,Helvetica,sans-serif;background:#eef2f7;margin:0;color:#172033}
.page{width:210mm;min-height:297mm;background:white;margin:12px auto;padding:22mm;box-shadow:0 8px 30px rgba(0,0,0,.12)}
.header{display:flex;justify-content:space-between;border-bottom:3px solid #2563eb;padding-bottom:16px}
h1{margin:0;color:#0f172a}.badge{background:#dbeafe;color:#1e40af;padding:7px 12px;border-radius:999px;font-weight:bold}
.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:18px}.card{border:1px solid #e5e7eb;border-radius:14px;padding:16px}
.big{font-size:34px;font-weight:800;color:#0f172a}.conf{font-size:46px;font-weight:900;color:#16a34a}
table{width:100%;border-collapse:collapse}td{padding:8px;border-bottom:1px solid #e5e7eb}
.bar{height:10px;background:#e5e7eb;border-radius:999px;overflow:hidden}.bar i{display:block;height:100%;background:#2563eb}
.footer{margin-top:30px;border-top:1px solid #e5e7eb;padding-top:14px;font-size:12px;color:#64748b}
@media print{body{background:white}.page{margin:0;box-shadow:none;width:auto;min-height:auto}.no-print{display:none}}
</style>
</head>
<body>
<div class="page">
<div class="header">
    <div>
        <h1>AI Career Passport</h1>
        <p><?=h($s['school_name'])?> · Prediction #<?=h(str_pad($s['batch_no'],6,'0',STR_PAD_LEFT))?></p>
    </div>
    <div><span class="badge">EduDirectionAI Enterprise</span></div>
</div>

<div class="grid">
    <div class="card">
        <h2>Student</h2>
        <p class="big"><?=h($s['student_name'])?></p>
        <table>
            <tr><td>Sinf</td><td><?=h($s['class_name'])?></td></tr>
            <tr><td>Student ID</td><td><?=h($s['external_student_code'] ?: $s['student_id'])?></td></tr>
            <tr><td>Dataset</td><td><?=h($s['dataset_name'])?></td></tr>
            <tr><td>Model</td><td><?=h(($s['model_name'] ?? '').' '.($s['model_version'] ?? ''))?></td></tr>
        </table>
    </div>
    <div class="card">
        <h2>AI Recommendation</h2>
        <p class="big"><?=h($s['recommended_direction'])?></p>
        <p class="conf"><?=pct($s['recommendation_confidence'])?></p>
        <p>Alternative: <b><?=h($s['alternative_direction'])?></b> — <?=pct($s['alternative_confidence'])?></p>
    </div>
</div>

<div class="grid">
    <div class="card">
        <h2>Top direction indices</h2>
        <?php foreach($top as $k=>$v): $vv=max(0,min(100,(float)$v)); ?>
            <p><b><?=h($k)?></b> — <?=round($vv,1)?></p>
            <div class="bar"><i style="width:<?=$vv?>%"></i></div>
        <?php endforeach; ?>
    </div>
    <div class="card">
        <h2>Reliability</h2>
        <table>
            <tr><td>Data coverage</td><td><?=pct($s['temporal_coverage_ratio'])?></td></tr>
            <tr><td>Temporal years</td><td><?=h($s['temporal_years_count'])?></td></tr>
            <tr><td>Academic stability</td><td><?=pct($s['academic_stability'])?></td></tr>
            <tr><td>Growth trend</td><td><?=h(round((float)$s['growth_trend'],3))?></td></tr>
        </table>
    </div>
</div>

<div class="card" style="margin-top:18px">
    <h2>Explainable AI</h2>
    <p><?=h($s['recommendation_reason'])?></p>
    <p><b>Tavsiya:</b> <?=h($s['selected_direction_advice'])?></p>
</div>

<div class="footer">
    Ushbu passport EduDirectionAI Professional Enterprise platformasi tomonidan avtomatik shakllantirildi. Natijalar tavsiya xarakteriga ega.
</div>
</div>
<div class="no-print" style="text-align:center;margin:18px"><button onclick="window.print()">Print / PDF saqlash</button></div>
</body>
</html>
