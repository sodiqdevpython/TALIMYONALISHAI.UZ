<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['director']);
$pdo = db();
$schoolId = (int)$u['school_id'];
$batchId = (int)($_GET['batch_id'] ?? 0);
$bs = $pdo->prepare('SELECT b.*, m.model_name, m.model_version FROM prediction_batches b LEFT JOIN research_models m ON m.id=b.model_id WHERE b.id=? AND b.school_id=? LIMIT 1');
$bs->execute([$batchId, $schoolId]);
$batch = $bs->fetch();
if (!$batch) die('Prediction batch topilmadi yoki sizga tegishli emas.');

$dist = $pdo->prepare('SELECT recommended_direction, COUNT(*) c, ROUND(AVG(recommendation_confidence),4) avg_conf FROM student_predictions WHERE batch_id=? GROUP BY recommended_direction ORDER BY c DESC');
$dist->execute([$batchId]);
$directions = $dist->fetchAll();

$classq = $pdo->prepare('SELECT COALESCE(class_name,"-") class_name, recommended_direction, COUNT(*) c FROM student_predictions WHERE batch_id=? GROUP BY class_name, recommended_direction ORDER BY class_name, c DESC');
$classq->execute([$batchId]);
$classRows = $classq->fetchAll();

$q = trim($_GET['q'] ?? '');
$dir = trim($_GET['direction'] ?? '');
$class = trim($_GET['class_name'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 50; $offset = ($page-1)*$limit;
$where = ['batch_id=?']; $params = [$batchId];
if ($q !== '') { $where[] = '(student_name LIKE ? OR external_student_code LIKE ?)'; $params[]='%'.$q.'%'; $params[]='%'.$q.'%'; }
if ($dir !== '') { $where[] = 'recommended_direction=?'; $params[]=$dir; }
if ($class !== '') { $where[] = 'class_name=?'; $params[]=$class; }
$whereSql = implode(' AND ', $where);
$countStmt=$pdo->prepare("SELECT COUNT(*) FROM student_predictions WHERE $whereSql"); $countStmt->execute($params); $total=(int)$countStmt->fetchColumn();
$studentsQ = $pdo->prepare("SELECT * FROM student_predictions WHERE $whereSql ORDER BY class_name, student_name LIMIT $limit OFFSET $offset");
$studentsQ->execute($params); $students = $studentsQ->fetchAll();
$dirs=$pdo->prepare('SELECT DISTINCT recommended_direction FROM student_predictions WHERE batch_id=? ORDER BY recommended_direction'); $dirs->execute([$batchId]); $dirOpts=$dirs->fetchAll(PDO::FETCH_COLUMN);
$classes=$pdo->prepare('SELECT DISTINCT class_name FROM student_predictions WHERE batch_id=? AND class_name IS NOT NULL ORDER BY class_name'); $classes->execute([$batchId]); $classOpts=$classes->fetchAll(PDO::FETCH_COLUMN);
$pages = max(1, (int)ceil($total/$limit));
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Prediction #<?=$batchId?></title><link rel="stylesheet" href="../assets/style.css"><script src="https://cdn.jsdelivr.net/npm/chart.js"></script></head><body><div class="container enterprise">
<header class="topbar"><div><h1>Prediction #<?=auth_h(str_pad($batchId,6,'0',STR_PAD_LEFT))?></h1><p><?=auth_h($batch['dataset_name'])?> · <?=auth_h($batch['status'])?> · <?=auth_h(($batch['model_name'] ?? '').' '.($batch['model_version'] ?? ''))?></p></div><nav class="nav"><a href="prediction_history.php">Tarix</a><a href="prediction_upload.php">Yangi prediction</a><a href="index.php">Direktor</a><a href="../logout.php">Chiqish</a></nav></header>
<section class="cards grid"><div class="card stat accent"><span>O‘quvchilar</span><strong><?=auth_h($batch['students_count'])?></strong></div><div class="card stat"><span>Mean confidence</span><strong><?=auth_h($batch['mean_confidence'])?></strong></div><div class="card stat"><span>Coverage</span><strong><?=auth_h($batch['temporal_coverage_mean'])?></strong></div><div class="card stat"><span>Model</span><strong><?=auth_h(($batch['model_version'] ?? '-'))?></strong></div></section>
<section class="grid2"><div class="card"><h2>Yo‘nalishlar taqsimoti</h2><canvas id="dirPie" height="230"></canvas></div><div class="card"><h2>Yo‘nalishlar jadvali</h2><div class="direction-grid mini-dir"><?php foreach($directions as $d): $pct=$batch['students_count']?round($d['c']*100/$batch['students_count'],1):0; ?><div class="dir-card"><b><?=auth_h($d['recommended_direction'])?></b><strong><?=auth_h($d['c'])?></strong><span><?=auth_h($pct)?>% · conf <?=auth_h($d['avg_conf'])?></span><div class="bar"><i style="width:<?=$pct?>%"></i></div></div><?php endforeach; ?></div></div></section>
<section class="card"><h2>O‘quvchilar natijalari</h2><form class="filters" method="get"><input type="hidden" name="batch_id" value="<?=auth_h($batchId)?>"><label>Qidiruv<input name="q" type="text" value="<?=auth_h($q)?>" placeholder="FIO yoki ID"></label><label>Yo‘nalish<select name="direction"><option value="">Barchasi</option><?php foreach($dirOpts as $o): ?><option <?=$dir===$o?'selected':''?> value="<?=auth_h($o)?>"><?=auth_h($o)?></option><?php endforeach; ?></select></label><label>Sinf<select name="class_name"><option value="">Barchasi</option><?php foreach($classOpts as $o): ?><option <?=$class===$o?'selected':''?> value="<?=auth_h($o)?>"><?=auth_h($o)?></option><?php endforeach; ?></select></label><button type="submit">Filter</button></form>
<p class="hint">Topildi: <?=$total?> ta. Sahifa <?=$page?> / <?=$pages?>.</p><div class="table-wrap"><table><thead><tr><th>Sinf</th><th>O‘quvchi</th><th>Tavsiya</th><th>Confidence</th><th>Alternativ</th><th>Profil</th><th>Digital Twin</th><th>Simulation</th></tr></thead><tbody><?php foreach($students as $s): ?><tr><td><?=auth_h($s['class_name'])?></td><td><?=auth_h($s['student_name'])?></td><td><span class="badge"><?=auth_h($s['recommended_direction'])?></span></td><td><?=auth_h($s['recommendation_confidence'])?></td><td><?=auth_h($s['alternative_direction'])?> (<?=auth_h($s['alternative_confidence'])?>)</td><td><a class="mini" href="student_profile.php?prediction_id=<?=auth_h($s['id'])?>">Profil</a></td></tr><?php endforeach; ?></tbody></table></div><p><?php if($page>1): ?><a class="mini" href="?batch_id=<?=$batchId?>&q=<?=urlencode($q)?>&direction=<?=urlencode($dir)?>&class_name=<?=urlencode($class)?>&page=<?=$page-1?>">Oldingi</a><?php endif; ?> <?php if($page<$pages): ?><a class="mini" href="?batch_id=<?=$batchId?>&q=<?=urlencode($q)?>&direction=<?=urlencode($dir)?>&class_name=<?=urlencode($class)?>&page=<?=$page+1?>">Keyingi</a><?php endif; ?></p></section>
<section class="card"><h2>Sinf kesimi</h2><div class="table-wrap small-table"><table><tr><th>Sinf</th><th>Yo‘nalish</th><th>Soni</th></tr><?php foreach($classRows as $r): ?><tr><td><?=auth_h($r['class_name'])?></td><td><?=auth_h($r['recommended_direction'])?></td><td><?=auth_h($r['c'])?></td></tr><?php endforeach; ?></table></div></section>
<script>const labels=<?=json_encode(array_column($directions,'recommended_direction'), JSON_UNESCAPED_UNICODE)?>;const data=<?=json_encode(array_map('intval', array_column($directions,'c')))?>;new Chart(document.getElementById('dirPie'),{type:'doughnut',data:{labels:labels,datasets:[{data:data}]},options:{plugins:{legend:{position:'bottom'}}}});</script>
</div></body></html>
