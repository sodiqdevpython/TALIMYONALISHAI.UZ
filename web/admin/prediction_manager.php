<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/governance.php';

$u = require_role(['super_admin']);
$pdo = db();
ensure_governance_schema($pdo);

$msg='';

if (isset($_GET['archive'])) {
    $id=(int)$_GET['archive'];
    archive_prediction($pdo,$id);
    $msg='Prediction archived.';
}
if (isset($_GET['restore'])) {
    $id=(int)$_GET['restore'];
    restore_prediction($pdo,$id);
    $msg='Prediction restored.';
}
if (isset($_GET['soft_delete'])) {
    $id=(int)$_GET['soft_delete'];
    soft_delete_prediction($pdo,$id);
    $msg='Prediction soft-deleted.';
}
if (isset($_GET['activate'])) {
    $id=(int)$_GET['activate'];
    $q=$pdo->prepare('SELECT school_id FROM prediction_batches WHERE id=? LIMIT 1');
    $q->execute([$id]);
    $sid=(int)$q->fetchColumn();
    if($sid) set_prediction_active($pdo,$sid,$id);
    $msg='Prediction activated.';
}

$schoolId=(int)($_GET['school_id'] ?? 0);
$where='1=1';
$params=[];
if($schoolId>0){ $where.=' AND b.school_id=?'; $params[]=$schoolId; }

$stmt=$pdo->prepare("
SELECT b.*, s.school_name, m.model_name, m.model_version,
       (SELECT COUNT(*) FROM student_predictions sp WHERE sp.batch_id=b.id) pred_count
FROM prediction_batches b
JOIN schools s ON s.id=b.school_id
LEFT JOIN research_models m ON m.id=b.model_id
WHERE $where
ORDER BY b.id DESC
LIMIT 500
");
$stmt->execute($params);
$rows=$stmt->fetchAll();

$schools=$pdo->query('SELECT id, school_name FROM schools ORDER BY school_name')->fetchAll();
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Prediction Manager</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><div class="container enterprise">
<header class="topbar"><div><h1>Prediction Lifecycle Manager</h1><p>Super Admin: archive, restore, soft delete va active model/prediction boshqaruvi.</p></div><nav class="nav"><a href="index.php">Admin</a><a href="model_manager.php">Model Manager</a><a href="../logout.php">Chiqish</a></nav></header>
<?php if($msg): ?><section class="card alert"><?=auth_h($msg)?></section><?php endif; ?>
<section class="card"><form method="get" class="filters"><select name="school_id"><option value="0">Barcha maktablar</option><?php foreach($schools as $s): ?><option value="<?=auth_h($s['id'])?>" <?=($schoolId===(int)$s['id']?'selected':'')?>><?=auth_h($s['school_name'])?></option><?php endforeach; ?></select><button class="btn">Filtrlash</button></form></section>
<section class="card"><table><thead><tr><th>ID</th><th>Maktab</th><th>Dataset</th><th>O‘quv yili</th><th>O‘quvchi</th><th>Status</th><th>Lifecycle</th><th>Active</th><th>Amallar</th></tr></thead><tbody>
<?php foreach($rows as $r): $life=$r['lifecycle_status'] ?? 'inactive'; ?>
<tr class="<?=($life==='deleted'?'deleted-row':($life==='archived'?'muted-row':''))?>">
<td>#<?=auth_h($r['id'])?></td>
<td><?=auth_h($r['school_name'])?></td>
<td><b><?=auth_h($r['prediction_title'] ?: $r['dataset_name'])?></b><br><small><?=auth_h($r['dataset_name'])?></small></td>
<td><?=auth_h($r['academic_year'])?></td>
<td><?=auth_h($r['students_count'])?></td>
<td><?=auth_h($r['status'])?></td>
<td><span class="badge"><?=auth_h(strtoupper($life))?></span></td>
<td><?=((int)$r['is_active']===1?'✅':'')?></td>
<td class="actions">
<?php if($life!=='deleted'): ?>
<a class="mini" href="prediction_manager.php?activate=<?=auth_h($r['id'])?>">Active</a>
<a class="mini" href="prediction_manager.php?archive=<?=auth_h($r['id'])?>">Archive</a>
<a class="mini danger" onclick="return confirm('Soft delete qilinsinmi?')" href="prediction_manager.php?soft_delete=<?=auth_h($r['id'])?>">Soft Delete</a>
<a class="mini" href="../director/prediction_results.php?batch_id=<?=auth_h($r['id'])?>">Natija</a>
<?php elseif($life==='deleted'): ?>
<span class="muted">Deleted</span>
<?php endif; ?>
<?php if($life==='archived'): ?><a class="mini" href="prediction_manager.php?restore=<?=auth_h($r['id'])?>">Restore</a><?php endif; ?>
</td></tr>
<?php endforeach; ?></tbody></table></section>
</div></body></html>
