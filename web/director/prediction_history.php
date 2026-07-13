<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/prediction_isolation.php';
require_once __DIR__.'/../../config/governance.php';

$u = require_role(['director']);
$pdo = db();
ensure_prediction_isolation_schema($pdo);
ensure_governance_schema($pdo);

$schoolId = (int)$u['school_id'];

if (isset($_GET['set_active'])) {
    $bid = (int)$_GET['set_active'];
    $chk = $pdo->prepare('SELECT id FROM prediction_batches WHERE id=? AND school_id=? AND status="completed" AND lifecycle_status<>"deleted" AND lifecycle_status<>"archived" LIMIT 1');
    $chk->execute([$bid, $schoolId]);
    if ($chk->fetch()) set_prediction_active($pdo, $schoolId, $bid);
    header('Location: prediction_history.php');
    exit;
}
if (isset($_GET['set_inactive'])) {
    $bid = (int)$_GET['set_inactive'];
    $chk = $pdo->prepare('SELECT id FROM prediction_batches WHERE id=? AND school_id=? LIMIT 1');
    $chk->execute([$bid, $schoolId]);
    if ($chk->fetch()) set_prediction_inactive($pdo, $schoolId, $bid);
    header('Location: prediction_history.php');
    exit;
}

$stmt = $pdo->prepare('
    SELECT b.*, m.model_name, m.model_version, u.full_name AS uploaded_by_name
    FROM prediction_batches b
    LEFT JOIN research_models m ON m.id=b.model_id
    LEFT JOIN users u ON u.id=b.uploaded_by
    WHERE b.school_id=? AND COALESCE(b.lifecycle_status,"inactive")<>"deleted"
    ORDER BY b.id DESC
');
$stmt->execute([$schoolId]);
$batches = $stmt->fetchAll();
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Prediction tarixi</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
<header class="topbar">
    <div><h1>Prediction tarixi</h1><p>Build 4.2: Active/Inactive boshqaruvi va o‘quv yili nazorati.</p></div>
    <nav class="nav"><a href="index.php">Direktor</a><a href="analytics.php">Analytics</a><a href="prediction_upload.php">+ Prediction</a><a href="../logout.php">Chiqish</a></nav>
</header>
<section class="card">
<table>
<thead><tr><th>ID</th><th>Dataset</th><th>O‘quv yili</th><th>Model</th><th>O‘quvchi</th><th>Confidence</th><th>Lifecycle</th><th>Active</th><th>Sana</th><th>Ko‘rish</th><th>Teacher Assign</th></tr></thead>
<tbody>
<?php foreach($batches as $b): $life=$b['lifecycle_status'] ?? 'inactive'; ?>
<tr class="<?=($life==='archived'?'muted-row':'')?>">
<td>#<?=auth_h($b['id'])?></td>
<td><b><?=auth_h($b['prediction_title'] ?: $b['dataset_name'])?></b><br><small><?=auth_h($b['dataset_name'])?></small></td>
<td><?=auth_h($b['academic_year'] ?? '-')?></td>
<td><?=auth_h(trim(($b['model_name'] ?? '').' '.($b['model_version'] ?? '')))?></td>
<td><?=auth_h($b['students_count'])?></td>
<td><?=auth_h($b['mean_confidence'])?></td>
<td><span class="badge"><?=auth_h(strtoupper($life))?></span></td>
<td>
    <?php if((int)($b['is_active'] ?? 0)===1): ?>
        <a class="mini danger" href="prediction_history.php?set_inactive=<?=auth_h($b['id'])?>">Noaktiv qilish</a>
    <?php elseif($b['status']==='completed' && $life!=='archived'): ?>
        <a class="mini" href="prediction_history.php?set_active=<?=auth_h($b['id'])?>">Aktiv qilish</a>
    <?php else: ?>-<?php endif; ?>
</td>
<td><?=auth_h($b['created_at'])?></td>
<td><a class="btn" href="prediction_results.php?batch_id=<?=auth_h($b['id'])?>">Natija</a></td>
<td><a class="mini" href="teachers.php">O‘qituvchi tanlash</a></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php if(!$batches): ?><p class="hint">Hali prediction bajarilmagan.</p><?php endif; ?>
</section>
<section class="card hint">
<b>Eslatma:</b> Direktor predictionni o‘chira olmaydi. Xato dataset bo‘lsa, uni Noaktiv qiling. Archive/Delete faqat Super Admin panelida.
</section>
</div>
</body>
</html>
