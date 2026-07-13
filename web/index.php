<?php
require_once __DIR__.'/../config/auth.php';
require_role(['super_admin']);

require_once __DIR__.'/_helpers.php';
$m = metrics();
$rows = result_rows();
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>EduDirectionAI v2.1</title><link rel="stylesheet" href="assets/style.css"></head><body><div class="container">
<header class="topbar"><div><h1>EduDirectionAI <span class="badge">Professional v4.0</span></h1><p>Maktab ma’muriyati uchun tushunarli dashboard: o‘quvchilar, yo‘nalishlar, ishonchlilik, jadval va hisobot.</p></div><nav class="nav"><a href="index.php">Dashboard</a><a href="research_mode.php">Research Mode</a><a href="prediction_mode.php">Prediction Mode</a><a href="school_login.php">Maktab kirishi</a><a href="director_dashboard.php">Direktor dashboardi</a><a href="class_analysis.php">Sinf tahlili</a><a href="grades.php">1–11-sinf baholari</a><a href="dataset.php">Dataset jadvali</a><a href="model_dashboard.php">Model dashboard</a><a href="direction_manager.php">Yo‘nalishlar</a><a href="school_config.php">Maktablar</a><a href="admin.php">Admin</a></nav></header>

<section class="card"><h2>Dataset yuklash va modelni o‘qitish</h2><form action="run.php" method="post" enctype="multipart/form-data"><label>Excel dataset (.xlsx):</label><input type="file" name="dataset" accept=".xlsx"><button type="submit">Dataset yuklash va modelni o‘qitish</button></form><p class="hint">Fayl tanlanmasa, loyiha ichidagi <b>data/dataset.xlsx</b> ishlatiladi.</p></section>

<?php if($m): 
$rec=$m['recommendation'] ?? []; $best=$m['classification']['best_model'] ?? '-'; 
$dirs=[]; foreach($rows as $r){ $d=$r['recommended_direction']??''; if($d){$dirs[$d]=($dirs[$d]??0)+1;} }
arsort($dirs);
?>
<section class="grid">
<div class="card stat"><span>O‘quvchilar soni</span><strong><?=h($m['dataset']['students'] ?? count($rows))?></strong></div>
<div class="card stat"><span>Yo‘nalishlar soni</span><strong><?=count($dirs)?></strong></div>
<div class="card stat"><span>O‘rtacha ishonchlilik</span><strong><?=pct($rec['mean_confidence'] ?? '')?></strong></div>
<div class="card stat"><span>Eng yaxshi model</span><strong><?=h($best)?></strong></div>
</section>

<section class="card"><h2>Har bir yo‘nalish bo‘yicha o‘quvchilar soni</h2><div class="direction-grid">
<?php foreach($dirs as $d=>$n): $p=$m['dataset']['students']?($n/$m['dataset']['students']*100):0; ?>
<div class="dir-card"><b><?=h($d)?></b><strong><?=h($n)?></strong><span><?=number_format($p,1)?>%</span><div class="bar"><i style="width:<?=min(100,$p)?>%"></i></div></div>
<?php endforeach; ?>
</div></section>

<section class="grid2"><div class="card"><h2>Maktab uchun asosiy natija</h2><p>Dataset yuklangandan so‘ng tizim har bir o‘quvchiga 5 ta yo‘nalish bo‘yicha moslik foizini hisoblaydi va eng mos yo‘nalishni tavsiya qiladi.</p><p><b>Formula:</b> <?=h($rec['formula'] ?? 'Recommendation = argmax P(Y=j|X)')?></p><a class="btn" href="dataset.php">O‘quvchilar jadvalini ko‘rish</a></div><div class="card"><h2>Hisobotlar</h2><p>Maktab ma’muriyati uchun Excel fayl tayyorlanadi.</p><div class="downloads"><a href="download.php?file=school_recommendations.xlsx">Excel hisobot</a><a href="download.php?file=student_logins.xlsx">O‘quvchi loginlari</a><a href="download.php?file=student_grade_history.xlsx">1–11-sinf baholari Excel</a><a href="download.php?file=student_results.csv">CSV natijalar</a></div></div></section>

<?php else: ?><section class="card"><p>Hali natija yo‘q. Avval dataset yuklang va modelni o‘qiting.</p></section><?php endif; ?>
</div></body></html>