<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/master_registry.php';
$u = require_role(['student']);
$pdo = db();
$schoolId=(int)$u['school_id'];

$stQ=$pdo->prepare('SELECT st.*, c.class_name, s.school_name FROM students st LEFT JOIN classes c ON c.id=st.class_id LEFT JOIN schools s ON s.id=st.school_id WHERE st.school_id=? AND st.username=? LIMIT 1');
$stQ->execute([$schoolId,$u['username']]);
$student=$stQ->fetch();
if(!$student) die('Student profili topilmadi. Direktor student loginlarni sinxronlashtirishi kerak.');

$predQ=$pdo->prepare('
    SELECT sp.*, b.dataset_name, b.id AS batch_no, b.created_at AS batch_date, m.model_name, m.model_version
    FROM student_predictions sp
    JOIN prediction_batches b ON b.id=sp.batch_id
    LEFT JOIN research_models m ON m.id=b.model_id
    WHERE sp.student_id=? AND sp.school_id=?
    ORDER BY sp.id DESC
    LIMIT 1
');
$predQ->execute([$student['id'],$schoolId]);
$p=$predQ->fetch();

function pctv($v,$d=1){ if($v===null || $v==='') return '-'; $x=(float)$v; if($x<=1)$x*=100; return round($x,$d).'%';}
function score100($v){ if($v===null || $v==='') return 0; $x=(float)$v; if($x<=1)$x*=100; return max(0,min(100,$x));}
function student_direction_plan($direction){
    $m=[
    'IT'=>['Python/Web dasturlash','Matematika','Ingliz tili','Portfolio loyiha'],
    'Muhandislik'=>['Matematika','Fizika','Texnologiya','Laboratoriya amaliyoti'],
    'Tibbiyot'=>['Biologiya','Kimyo','Ingliz tili','Mas’uliyat va kommunikatsiya'],
    'Iqtisodiyot'=>['Matematika','Iqtisodiyot','Moliyaviy savodxonlik','Data analytics'],
    'Pedagogika'=>['Kommunikatsiya','Ona tili/adabiyot','Metodika','Bolalar bilan ishlash']
    ];
    return $m[$direction] ?? ['Asosiy fanlar','Soft skills','Ingliz tili'];
}
$indices=[];
$rowJson=[];
$history=[];
$soft=[];
if($p){
    $indices=['IT'=>$p['IT_Index'],'Muhandislik'=>$p['Engineering_Index'],'Tibbiyot'=>$p['Medicine_Index'],'Iqtisodiyot'=>$p['Economics_Index'],'Pedagogika'=>$p['Pedagogy_Index']];
    arsort($indices);
    $rowJson=json_decode((string)$p['full_json'],true) ?: [];
    if(!empty($rowJson['temporal_history'])){
        $tmp=json_decode($rowJson['temporal_history'],true);
        if(is_array($tmp)) $history=$tmp;
    }
    if(!$history && (int)$p['temporal_years_count']>0){
        for($g=1;$g<=(int)$p['temporal_years_count'];$g++) $history[]=['grade'=>$g,'year_mean'=>round((float)$p['academic_mean'],2)];
    }
    $soft=[
        'Communication'=>$rowJson['Communication'] ?? null,
        'Teamwork'=>$rowJson['Teamwork'] ?? null,
        'Leadership'=>$rowJson['Leadership'] ?? null,
        'Creativity'=>$rowJson['Creativity'] ?? null,
        'Critical Thinking'=>$rowJson['Critical_Thinking'] ?? null,
        'Adaptability'=>$rowJson['Adaptability'] ?? null
    ];
    foreach($soft as $k=>$v) if($v===null || $v==='') unset($soft[$k]);
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mening AI pasportim</title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head><body><div class="container enterprise student-intel">
<header class="topbar"><div><h1>Mening AI Career Passportim</h1><p><?=auth_h($student['student_name'])?> · <?=auth_h($student['class_name'])?> · <?=auth_h($student['school_name'])?></p></div><nav class="nav"><a href="passport.php" target="_blank">PDF/Print</a><a href="../logout.php">Chiqish</a></nav></header>
<?php if(!$p): ?><section class="card"><h2>Natija hali mavjud emas</h2><p>Direktor prediction qilgandan keyin natija shu yerda ko‘rinadi.</p></section><?php else: ?>
<section class="passport-hero"><div class="identity-card"><div class="avatar"><?=auth_h(mb_substr($student['student_name'],0,1,'UTF-8'))?></div><div><h2><?=auth_h($student['student_name'])?></h2><p><b>Student ID:</b> <?=auth_h($student['student_code'])?></p><p><b>Sinf:</b> <?=auth_h($student['class_name'])?> · <b>Model:</b> <?=auth_h($p['model_version'])?></p><p><b>Prediction:</b> #<?=str_pad((string)$p['batch_no'],6,'0',STR_PAD_LEFT)?> · <?=auth_h($p['dataset_name'])?></p></div></div><div class="match-card"><span>AI RECOMMENDATION</span><strong><?=auth_h($p['recommended_direction'])?></strong><em><?=pctv($p['recommendation_confidence'])?></em><div class="gauge"><i style="width:<?=score100($p['recommendation_confidence'])?>%"></i></div><p>Alternative: <b><?=auth_h($p['alternative_direction'])?></b> — <?=pctv($p['alternative_confidence'])?></p></div></section>
<section class="grid2"><div class="card"><h2>Yo‘nalish indekslari</h2><canvas id="radar" height="250"></canvas></div><div class="card"><h2>Academic Timeline</h2><canvas id="timeline" height="250"></canvas></div></section>
<section class="grid2"><div class="card"><h2>Soft Skills</h2><?php foreach($soft as $k=>$v): $vv=score100($v); ?><div class="skill-line"><span><?=auth_h($k)?></span><b><?=round($vv,1)?>%</b><div class="bar"><i style="width:<?=$vv?>%"></i></div></div><?php endforeach; ?></div><div class="card"><h2>AI izoh</h2><p class="ai-text"><?=auth_h($p['recommendation_reason'])?></p><div class="advice"><b>Tavsiya:</b><br><?=auth_h($p['selected_direction_advice'])?></div></div></section>
<section class="card"><h2>Mening 12 oylik rivojlanish rejam</h2><div class="roadmap"><?php foreach(student_direction_plan($p['recommended_direction']) as $i=>$x): ?><div class="road-step"><b><?=($i+1)?></b><span><?=auth_h($x)?></span></div><?php endforeach; ?></div></section>
<script>
new Chart(document.getElementById('radar'),{type:'radar',data:{labels:<?=json_encode(array_keys($indices),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Index',data:<?=json_encode(array_map('floatval',array_values($indices)))?>}]},options:{scales:{r:{beginAtZero:true,max:100}}}});
new Chart(document.getElementById('timeline'),{type:'line',data:{labels:<?=json_encode(array_map(fn($x)=>($x['grade']??'').'-sinf',$history),JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Academic mean',data:<?=json_encode(array_map(fn($x)=>(float)($x['year_mean']??0),$history))?>,tension:.35}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
</script>
<?php endif; ?>
</div></body></html>
