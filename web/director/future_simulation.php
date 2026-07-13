<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['director']);
$pdo=db();
$schoolId=(int)$u['school_id'];
$id=(int)($_GET['prediction_id'] ?? $_POST['prediction_id'] ?? 0);

$stmt=$pdo->prepare('
SELECT sp.*, b.id batch_no, b.dataset_name, m.model_version, s.school_name
FROM student_predictions sp
JOIN prediction_batches b ON b.id=sp.batch_id
LEFT JOIN research_models m ON m.id=b.model_id
LEFT JOIN schools s ON s.id=sp.school_id
WHERE sp.id=? AND sp.school_id=? LIMIT 1');
$stmt->execute([$id,$schoolId]);
$p=$stmt->fetch();
if(!$p) die('Simulation uchun o‘quvchi topilmadi.');

function h($v){return htmlspecialchars((string)$v,ENT_QUOTES,'UTF-8');}
function score100($v){$x=(float)$v; if($x<=1)$x*=100; return max(0,min(100,$x));}
function direction_weights($d){
    $base=[
        'Tibbiyot'=>['biology'=>.34,'chemistry'=>.28,'math'=>.10,'english'=>.10,'leadership'=>.08,'communication'=>.10],
        'IT'=>['math'=>.36,'informatics'=>.34,'english'=>.12,'critical'=>.10,'creativity'=>.08],
        'Muhandislik'=>['math'=>.34,'physics'=>.32,'informatics'=>.10,'critical'=>.10,'creativity'=>.06,'leadership'=>.08],
        'Iqtisodiyot'=>['math'=>.26,'english'=>.18,'communication'=>.18,'critical'=>.18,'leadership'=>.10,'creativity'=>.10],
        'Pedagogika'=>['communication'=>.30,'leadership'=>.20,'english'=>.12,'creativity'=>.16,'critical'=>.12,'teamwork'=>.10],
    ];
    return $base[$d] ?? ['math'=>.2,'english'=>.2,'leadership'=>.2,'communication'=>.2,'critical'=>.2];
}
$directions=['Tibbiyot','IT','Muhandislik','Iqtisodiyot','Pedagogika'];
$idx=['Tibbiyot'=>$p['Medicine_Index'],'IT'=>$p['IT_Index'],'Muhandislik'=>$p['Engineering_Index'],'Iqtisodiyot'=>$p['Economics_Index'],'Pedagogika'=>$p['Pedagogy_Index']];
arsort($idx);
$scenario=[
 'biology'=>(float)($_POST['biology'] ?? 0),
 'chemistry'=>(float)($_POST['chemistry'] ?? 0),
 'math'=>(float)($_POST['math'] ?? 0),
 'physics'=>(float)($_POST['physics'] ?? 0),
 'informatics'=>(float)($_POST['informatics'] ?? 0),
 'english'=>(float)($_POST['english'] ?? 0),
 'leadership'=>(float)($_POST['leadership'] ?? 0),
 'communication'=>(float)($_POST['communication'] ?? 0),
 'critical'=>(float)($_POST['critical'] ?? 0),
 'creativity'=>(float)($_POST['creativity'] ?? 0),
 'teamwork'=>(float)($_POST['teamwork'] ?? 0),
];
$sim=[];
foreach($directions as $d){
    $base=(float)$idx[$d];
    $impact=0;
    foreach(direction_weights($d) as $k=>$w){ $impact += ($scenario[$k] ?? 0) * $w; }
    $new=max(0,min(100,$base + $impact));
    $sim[$d]=round($new,2);
}
arsort($sim);
$best=array_key_first($sim);
$delta=round($sim[$best] - (float)$idx[$best],2);
$submitted=$_SERVER['REQUEST_METHOD']==='POST';
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AI Future Simulation</title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head>
<body><div class="container enterprise future-sim">
<header class="topbar"><div><h1>AI Future Simulation</h1><p><?=h($p['student_name'])?> · <?=h($p['class_name'])?> · What-if Analysis</p></div><nav class="nav"><a href="digital_twin.php?prediction_id=<?=h($p['id'])?>">Digital Twin</a><a href="student_profile.php?prediction_id=<?=h($p['id'])?>">AI Passport</a><a href="index.php">Direktor</a></nav></header>

<section class="sim-hero">
<div class="score-card"><span>Current Direction</span><strong><?=h($p['recommended_direction'])?></strong><em><?=round((float)$p['recommendation_confidence']*100,1)?>%</em><div class="gauge"><i style="width:<?=score100($p['recommendation_confidence'])?>%"></i></div></div>
<div class="score-card sim-alt"><span>Simulated Best Direction</span><strong><?=h($best)?></strong><em><?=h($sim[$best])?>/100</em><div class="gauge"><i style="width:<?=h($sim[$best])?>%"></i></div><p>Impact: <?=($delta>=0?'+':'')?><?=h($delta)?> point</p></div>
</section>

<section class="grid2">
<div class="card"><h2>Scenario parametrlarini kiriting</h2><form method="post"><input type="hidden" name="prediction_id" value="<?=h($p['id'])?>">
<div class="sim-grid">
<?php foreach(['biology'=>'Biologiya','chemistry'=>'Kimyo','math'=>'Matematika','physics'=>'Fizika','informatics'=>'Informatika','english'=>'Ingliz tili','leadership'=>'Leadership','communication'=>'Communication','critical'=>'Critical Thinking','creativity'=>'Creativity','teamwork'=>'Teamwork'] as $k=>$label): ?>
<label><?=h($label)?> +%<input type="number" step="1" min="-30" max="30" name="<?=h($k)?>" value="<?=h($scenario[$k])?>"></label>
<?php endforeach; ?>
</div><button class="btn">Simulyatsiya qilish</button></form></div>
<div class="card"><h2>Simulation Result</h2><canvas id="simChart" height="280"></canvas></div>
</section>

<section class="card"><h2>AI Scenario Explanation</h2>
<?php if($submitted): ?>
<p class="ai-text">Berilgan scenario asosida eng yuqori moslik <b><?=h($best)?></b> yo‘nalishida kuzatildi. <?=h($best)?> indeksi <?=h($sim[$best])?>/100 ga yetdi. Bu natija fanlar va soft-skills bo‘yicha kiritilgan o‘sish foizlari yo‘nalish og‘irliklari bilan qayta hisoblangani asosida shakllandi.</p>
<?php else: ?>
<p class="ai-text">Parametrlarni kiriting. Masalan: biologiya +10, kimyo +8, ingliz tili +5. Tizim yo‘nalish indekslarini qayta hisoblab, qaysi kasbiy yo‘nalish kuchayishini ko‘rsatadi.</p>
<?php endif; ?>
<div class="direction-grid">
<?php foreach($sim as $d=>$v): ?><div class="dir-card"><b><?=h($d)?></b><strong><?=h($v)?></strong><span>simulated index</span><div class="bar"><i style="width:<?=h($v)?>%"></i></div></div><?php endforeach; ?>
</div>
</section>

<script>
new Chart(document.getElementById('simChart'),{type:'bar',data:{labels:<?=json_encode(array_keys($sim),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Simulated index',data:<?=json_encode(array_values($sim))?>}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
</script>
</div></body></html>
