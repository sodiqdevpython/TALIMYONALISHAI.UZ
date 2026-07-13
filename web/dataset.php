<?php require_once __DIR__.'/_helpers.php'; $rows=result_rows(); 
$q=trim($_GET['q']??''); $class=trim($_GET['class']??''); $dirs=['IT','Muhandislik','Tibbiyot','Iqtisodiyot','Pedagogika'];
$classes=[]; foreach($rows as $r){ if(!empty($r['Sinf'])) $classes[$r['Sinf']]=1; }
ksort($classes);
$filtered=[];
foreach($rows as $r){
    if($class!=='' && ($r['Sinf']??'')!==$class) continue;
    if($q!=='' && stripos(($r['FIO']??'').' '.($r['student_id']??''), $q)===false) continue;
    $filtered[]=$r;
}
$limit=300; $show=array_slice($filtered,0,$limit);
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>Dataset jadvali</title></head><body><div class="container">
<header class="topbar"><h1>Dataset jadvali</h1><nav class="nav"><a href="index.php">Dashboard</a><a href="model_dashboard.php">Model dashboard</a></nav></header>
<section class="card"><form method="get" class="filters"><input type="text" name="q" value="<?=h($q)?>" placeholder="F.I.O yoki ID bo‘yicha qidirish"><select name="class"><option value="">Barcha sinflar</option><?php foreach(array_keys($classes) as $c): ?><option value="<?=h($c)?>" <?=$class===$c?'selected':''?>><?=h($c)?></option><?php endforeach; ?></select><button>Saralash</button><a class="btn secondary" href="dataset.php">Tozalash</a></form><p class="muted">Topildi: <?=count($filtered)?> ta. Jadvalda birinchi <?=$limit?> ta ko‘rsatiladi.</p></section>
<section class="card"><div class="table-wrap"><table><thead><tr><th>ID</th><th>F.I.O</th><th>Sinf</th><th>Tavsiya</th><th>Ishonchlilik</th><?php foreach($dirs as $d): ?><th><?=h($d)?></th><?php endforeach; ?><th>Amal</th></tr></thead><tbody>
<?php foreach($show as $r): ?><tr><td><?=h($r['student_id']??'')?></td><td><?=h($r['FIO']??'')?></td><td><?=h($r['Sinf']??'')?></td><td><b><?=h($r['recommended_direction']??'')?></b></td><td><?=pct($r['recommendation_confidence']??'')?></td><?php foreach($dirs as $d): ?><td><?=pct($r['prob_'.$d]??'')?></td><?php endforeach; ?><td><a class="mini" href="student.php?student_id=<?=urlencode($r['student_id']??'')?>">Ko‘rish</a></td></tr><?php endforeach; ?>
</tbody></table></div></section></div></body></html>