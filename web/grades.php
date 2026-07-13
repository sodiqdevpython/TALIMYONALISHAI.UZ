<?php require_once __DIR__.'/_helpers.php';
$file=out_dir().DIRECTORY_SEPARATOR.'student_grade_history.csv';
$rows=read_csv_assoc($file);
$grade=trim($_GET['grade']??''); $q=trim($_GET['q']??'');
$grades=[]; foreach($rows as $r){ if(isset($r['grade'])) $grades[$r['grade']]=1; }
uksort($grades, function($a,$b){ return (int)$a <=> (int)$b; });
$filtered=[];
foreach($rows as $r){
    if($grade!=='' && (string)($r['grade']??'')!==$grade) continue;
    if($q!=='' && stripos(($r['FIO']??'').' '.($r['student_id']??''), $q)===false) continue;
    $filtered[]=$r;
}
$limit=500; $show=array_slice($filtered,0,$limit);
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>1–11-sinf baholari</title></head><body><div class="container">
<header class="topbar"><div><h1>1–11-sinf baholari jadvali</h1><p>Datasetdagi barcha sinflar kesimidagi yillik akademik ko‘rsatkichlar.</p></div><nav class="nav"><a href="index.php">Dashboard</a><a href="dataset.php">Yakuniy tavsiyalar</a><a href="director_dashboard.php">Direktor dashboardi</a></nav></header>
<?php if(!$rows): ?><section class="card"><p>Hali sinflar tarixi shakllanmagan. Modelni qayta o‘qiting.</p></section><?php else: ?>
<section class="card"><form method="get" class="filters"><input type="text" name="q" value="<?=h($q)?>" placeholder="F.I.O yoki ID bo‘yicha qidirish"><select name="grade"><option value="">1–11 barcha sinflar</option><?php foreach(array_keys($grades) as $g): ?><option value="<?=h($g)?>" <?=$grade===(string)$g?'selected':''?>><?=h($g)?>-sinf</option><?php endforeach; ?></select><button>Saralash</button><a class="btn secondary" href="grades.php">Tozalash</a></form><p class="muted">Topildi: <?=count($filtered)?> ta qator. Jadvalda birinchi <?=$limit?> ta ko‘rsatiladi.</p></section>
<section class="card downloads"><a href="download.php?file=student_grade_history.xlsx">1–11-sinf Excel</a><a href="download.php?file=student_grade_history.csv">1–11-sinf CSV</a></section>
<section class="card"><div class="table-wrap"><table><thead><tr><th>ID</th><th>F.I.O</th><th>Sinf</th><th>Yillik o‘rtacha</th><th>Texnik</th><th>Ijodiy</th><th>Ijtimoiy</th><th>Amaliy</th><th>Tabiiy fanlar</th><th>Yakuniy tavsiya</th><th>Ishonch</th></tr></thead><tbody>
<?php foreach($show as $r): ?><tr>
<td><?=h($r['student_id']??'')?></td><td><?=h($r['FIO']??'')?></td><td><?=h(($r['grade']??'').'-sinf')?></td>
<td><?=fmt($r['year_mean']??'')?></td><td><?=fmt($r['technical_academic']??'')?></td><td><?=fmt($r['creative_academic']??'')?></td><td><?=fmt($r['social_academic']??'')?></td><td><?=fmt($r['practical_academic']??'')?></td><td><?=fmt($r['science_academic']??'')?></td><td><?=h($r['recommended_direction']??'')?></td><td><?=pct($r['recommendation_confidence']??'')?></td>
</tr><?php endforeach; ?>
</tbody></table></div></section>
<?php endif; ?></div></body></html>