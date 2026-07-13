<?php require_once __DIR__.'/_helpers.php'; $m=metrics(); $rows=result_rows();
$dirs=[]; foreach($rows as $r){$d=$r['recommended_direction']??''; if($d)$dirs[$d]=($dirs[$d]??0)+1;} arsort($dirs);
$top=$low=$unclear=[];
foreach($rows as $r){
    $conf=(float)($r['recommendation_confidence']??0); $alt=(float)($r['alternative_confidence']??0);
    if($conf<0.70) $low[]=$r;
    if(($conf-$alt)<0.15) $unclear[]=$r;
    $top[]=$r;
}
usort($top,fn($a,$b)=>(float)($b['recommendation_confidence']??0)<=>(float)($a['recommendation_confidence']??0));
$top=array_slice($top,0,20);
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>Direktor dashboardi</title></head><body><div class="container">
<header class="topbar"><div><h1>Maktab direktori dashboardi <span class="badge">v3.0</span></h1><p>Model metrikalarisiz, rahbariyat uchun tushunarli statistikalar va hisobotlar.</p></div><nav class="nav"><a href="index.php">Bosh sahifa</a><a href="class_analysis.php">Sinf tahlili</a><a href="grades.php">1–11-sinf baholari</a><a href="dataset.php">Dataset</a></nav></header>
<?php if(!$m): ?><section class="card">Avval dataset yuklab modelni o‘qiting.</section><?php else: ?>
<section class="grid">
<div class="card stat"><span>Jami o‘quvchi</span><strong><?=count($rows)?></strong></div>
<div class="card stat"><span>Sinflar soni</span><strong><?=h($m['director_dashboard']['class_count']??'-')?></strong></div>
<div class="card stat"><span>Past ishonchlilik</span><strong><?=count($low)?></strong></div>
<div class="card stat"><span>Noaniq yo‘nalish</span><strong><?=count($unclear)?></strong></div>
</section>
<section class="card"><h2>Maktab bo‘yicha yo‘nalishlar taqsimoti</h2><div class="direction-grid"><?php foreach($dirs as $d=>$n): $p=count($rows)?$n/count($rows)*100:0; ?><div class="dir-card"><b><?=h($d)?></b><strong><?=h($n)?></strong><span><?=number_format($p,1)?>%</span><div class="bar"><i style="width:<?=min(100,$p)?>%"></i></div></div><?php endforeach; ?></div></section>
<section class="grid2"><div class="card"><h2>TOP-20 yuqori salohiyatli o‘quvchilar</h2><div class="table-wrap small-table"><table><thead><tr><th>F.I.O</th><th>Sinf</th><th>Yo‘nalish</th><th>Ishonch</th></tr></thead><tbody><?php foreach($top as $r): ?><tr><td><a href="student.php?student_id=<?=urlencode($r['student_id']??'')?>"><?=h($r['FIO']??'')?></a></td><td><?=h($r['Sinf']??'')?></td><td><?=h($r['recommended_direction']??'')?></td><td><?=pct($r['recommendation_confidence']??'')?></td></tr><?php endforeach; ?></tbody></table></div></div>
<div class="card"><h2>E’tibor talab qiladigan o‘quvchilar</h2><p><b>Past ishonchlilik:</b> <?=count($low)?> nafar</p><p><b>Yo‘nalishi yaqin/noaniq:</b> <?=count($unclear)?> nafar</p><div class="downloads"><a href="download.php?file=low_confidence_students.csv">Past ishonchlilik ro‘yxati</a><a href="download.php?file=unclear_students.csv">Noaniq yo‘nalish ro‘yxati</a></div></div></section>
<section class="card downloads"><h2>Maktab hisobotlari</h2><a href="download.php?file=school_full_report.xlsx">To‘liq Excel hisobot</a><a href="download.php?file=school_recommendations.xlsx">Tavsiyalar Excel</a><a href="download.php?file=student_logins.xlsx">O‘quvchi loginlari</a><a href="print_school_report.php" target="_blank">Chop etiladigan hisobot</a></section>
<?php endif; ?></div></body></html>