<?php require_once __DIR__.'/_helpers.php';
$file=out_dir().DIRECTORY_SEPARATOR.'student_results.csv'; 
$id=trim($_GET['student_id'] ?? ''); 
if(preg_match('/^\d+$/',$id)) $id='OQ-'.str_pad($id,5,'0',STR_PAD_LEFT); 
$row=null; foreach(read_csv_assoc($file) as $r){ if(trim($r['student_id'] ?? '')===$id){$row=$r; break;} }
$dirs=['IT','Muhandislik','Tibbiyot','Iqtisodiyot','Pedagogika'];
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>O‘quvchi natijasi</title></head><body><div class="container"><section class="card"><a href="dataset.php">← Dataset jadvaliga qaytish</a><?php if($row): ?>
<h1><?=h($row['FIO'] ?? '')?></h1>
<div class="profile-head"><div><p><b>F.I.O:</b> <?=h($row['FIO'] ?? '')?></p><p><b>ID:</b> <?=h($row['student_id'] ?? '')?></p><p><b>Sinf:</b> <?=h($row['Sinf'] ?? '')?></p></div><div class="rec-box"><span>Yakuniy tavsiya etilgan yo‘nalish</span><strong><?=h($row['recommended_direction'] ?? '')?></strong><em>Ishonchlilik: <?=pct($row['recommendation_confidence'] ?? '')?></em></div></div>

<section class="grid2"><div class="card"><h2>5 ta yo‘nalish bo‘yicha moslik foizlari</h2><?php foreach($dirs as $d): $val=(float)($row['prob_'.$d] ?? 0)*100; ?><div class="prob-row"><span><?=h($d)?></span><b><?=number_format($val,2)?>%</b><div class="bar"><i style="width:<?=min(100,$val)?>%"></i></div></div><?php endforeach; ?></div>
<div class="card"><h2>Tavsiya sababi</h2><p><?=h($row['recommendation_reason'] ?? '')?></p><p><b>Alternativ yo‘nalish:</b> <?=h($row['alternative_direction'] ?? '')?> — <?=pct($row['alternative_confidence'] ?? '')?></p><p class="muted"><?=h($row['selected_direction_advice'] ?? '')?></p></div></section>

<section class="card"><h2>Temporal ko‘rsatkichlar va rivojlanish dinamikasi</h2><div class="metric-grid">
<?php foreach(['academic_mean'=>'Akademik o‘rtacha','growth_trend'=>'GrowthTrend','learning_dynamics'=>'LearningDynamics','academic_stability'=>'AcademicStability','temporal_consistency'=>'Consistency','skill_evolution'=>'SkillEvolution'] as $k=>$v): ?>
<div class="metric"><span><?=h($v)?></span><strong><?=fmt($row[$k] ?? '')?></strong></div><?php endforeach; ?></div></section>


<section class="card"><h2>1–11-sinf rivojlanish trayektoriyasi</h2>
<?php $hist=[]; if(!empty($row['temporal_history'])){ $hist=json_decode($row['temporal_history'], true); if(!is_array($hist)) $hist=[]; } ?>
<?php if($hist): ?><div class="timeline"><?php foreach($hist as $it): ?><div class="tl-item"><span><?=h(($it['grade']??'').'-sinf')?></span><b><?=h($it['direction']??'')?></b><em>O‘rtacha: <?=h($it['year_mean']??'')?></em></div><?php endforeach; ?></div><?php else: ?><p class="muted">Temporal tarix mavjud emas.</p><?php endif; ?></section>

<section class="card"><h2>Profil indekslari</h2><div class="metric-grid">
<?php foreach(['IT_Index'=>'IT','Engineering_Index'=>'Muhandislik','Medicine_Index'=>'Tibbiyot','Economics_Index'=>'Iqtisodiyot','Pedagogy_Index'=>'Pedagogika'] as $k=>$v): ?>
<div class="metric"><span><?=h($v)?></span><strong><?=fmt($row[$k] ?? '')?></strong></div><?php endforeach; ?></div></section>

<section class="card"><h2>Yo‘nalish bo‘yicha tavsiyalar</h2><form method="get"><input type="hidden" name="student_id" value="<?=h($row['student_id']??'')?>"><select name="dir"><?php foreach($dirs as $d): ?><option value="<?=h($d)?>" <?=($_GET['dir']??($row['recommended_direction']??''))===$d?'selected':''?>><?=h($d)?></option><?php endforeach; ?></select><button>Tanlangan yo‘nalish bo‘yicha tavsiya</button></form><?php $chosen=$_GET['dir']??($row['recommended_direction']??''); ?><div class="advice"><b><?=h($chosen)?></b>: <?=h(direction_advice_php($chosen))?></div></section>
<?php else: ?><h1>O‘quvchi topilmadi</h1><p>ID ni tekshiring yoki avval modelni ishga tushiring.</p><p>Masalan: <b>OQ-00001</b> yoki <b>1</b>.</p><?php endif; ?></section></div></body></html>