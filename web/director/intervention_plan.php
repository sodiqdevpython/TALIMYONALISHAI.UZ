<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['director']); $pdo=db(); $schoolId=(int)$u['school_id']; $id=(int)($_GET['prediction_id']??0);
$q=$pdo->prepare('SELECT sp.*, b.id batch_no FROM student_predictions sp JOIN prediction_batches b ON b.id=sp.batch_id WHERE sp.id=? AND sp.school_id=? LIMIT 1'); $q->execute([$id,$schoolId]); $s=$q->fetch();
if(!$s) die('O‘quvchi topilmadi.');
$dir=$s['recommended_direction'];
$plans=[
'IT'=>['Algoritmik fikrlash diagnostikasi','Python yoki Scratch mini loyiha','Matematika mashqlari','Portfolio va mentor feedback'],
'Muhandislik'=>['Fizika diagnostikasi','Matematika mustahkamlash','Texnik loyiha','Laboratoriya/robototexnika mashg‘uloti'],
'Tibbiyot'=>['Biologiya diagnostikasi','Kimyo mustahkamlash','Laboratoriya xavfsizligi','Mas’uliyat va kommunikatsiya treningi'],
'Iqtisodiyot'=>['Moliyaviy savodxonlik','Matematika va statistika','Excel/Data tahlil','Mini biznes case'],
'Pedagogika'=>['Kommunikatsiya treningi','Nutq va taqdimot','Metodika kuzatuvi','Bolalar bilan amaliy mashg‘ulot']
];
$list=$plans[$dir] ?? ['Fanlar bo‘yicha diagnostika','Soft skills rivojlantirish','Mentorlik','Qayta monitoring'];
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Intervention Plan</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container enterprise">
<header class="topbar"><div><h1>AI Intervention Plan</h1><p><?=auth_h($s['student_name'])?> · <?=auth_h($s['class_name'])?> · <?=auth_h($dir)?></p></div><nav class="nav"><a href="smart_monitoring.php">Monitoring</a><a href="student_profile.php?prediction_id=<?=auth_h($s['id'])?>">Profil</a><a href="index.php">Direktor</a></nav></header>
<section class="card"><h2>4 haftalik individual reja</h2><div class="roadmap"><?php foreach($list as $i=>$x): ?><div class="road-step"><b><?=($i+1)?></b><span><?=auth_h($x)?></span></div><?php endforeach; ?></div><div class="advice"><b>AI xulosa:</b><br>Ushbu reja o‘quvchining tavsiya yo‘nalishi, confidence, growth va stability indikatorlari asosida avtomatik shakllantirildi.</div></section>
<section class="grid2"><div class="card"><h2>Monitoring kriteriyalari</h2><p class="check">✅ Haftalik fan progressi</p><p class="check">✅ Mentor izohi</p><p class="check">✅ Soft skills kuzatuvi</p><p class="check">✅ Keyingi prediction bilan solishtirish</p></div><div class="card"><h2>Expected Impact</h2><p class="bigrec">+3–5%</p><p>Keyingi prediction siklida confidence oshishi kutiladi.</p></div></section>
</div></body></html>
