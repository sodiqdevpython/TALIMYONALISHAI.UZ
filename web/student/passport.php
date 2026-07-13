<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['student']);
$pdo = db();
$schoolId=(int)$u['school_id'];
$stQ=$pdo->prepare('SELECT st.*, c.class_name, s.school_name FROM students st LEFT JOIN classes c ON c.id=st.class_id LEFT JOIN schools s ON s.id=st.school_id WHERE st.school_id=? AND st.username=? LIMIT 1');
$stQ->execute([$schoolId,$u['username']]);
$student=$stQ->fetch();
if(!$student) die('Student topilmadi.');
$predQ=$pdo->prepare('SELECT sp.*, b.id batch_no, b.dataset_name, m.model_version FROM student_predictions sp JOIN prediction_batches b ON b.id=sp.batch_id LEFT JOIN research_models m ON m.id=b.model_id WHERE sp.student_id=? AND sp.school_id=? ORDER BY sp.id DESC LIMIT 1');
$predQ->execute([$student['id'],$schoolId]);
$p=$predQ->fetch();
if(!$p) die('Prediction topilmadi.');
function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function pct($v){$x=(float)$v;if($x<=1)$x*=100;return round($x,1).'%';}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><title>Student AI Passport</title><style>body{font-family:Arial;background:#eef2f7;margin:0}.page{background:white;width:210mm;min-height:297mm;margin:12px auto;padding:22mm;box-shadow:0 8px 30px #0002}.head{border-bottom:3px solid #2563eb;padding-bottom:16px}.badge{background:#dbeafe;color:#1e40af;padding:7px 12px;border-radius:999px}.grid{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-top:18px}.card{border:1px solid #e5e7eb;border-radius:14px;padding:16px}.big{font-size:32px;font-weight:800}.conf{font-size:44px;color:#16a34a;font-weight:900}@media print{body{background:white}.page{margin:0;box-shadow:none}.no-print{display:none}}</style></head><body><div class="page"><div class="head"><h1>AI Career Passport</h1><span class="badge">EduDirectionAI Student Portal</span></div><div class="grid"><div class="card"><h2>Student</h2><p class="big"><?=h($student['student_name'])?></p><p><?=h($student['school_name'])?></p><p><?=h($student['class_name'])?> · <?=h($student['student_code'])?></p></div><div class="card"><h2>AI Recommendation</h2><p class="big"><?=h($p['recommended_direction'])?></p><p class="conf"><?=pct($p['recommendation_confidence'])?></p><p>Alternative: <b><?=h($p['alternative_direction'])?></b> — <?=pct($p['alternative_confidence'])?></p></div></div><div class="card" style="margin-top:18px"><h2>AI izoh</h2><p><?=h($p['recommendation_reason'])?></p><p><b>Tavsiya:</b> <?=h($p['selected_direction_advice'])?></p></div></div><div class="no-print" style="text-align:center"><button onclick="window.print()">Print / PDF saqlash</button></div></body></html>
