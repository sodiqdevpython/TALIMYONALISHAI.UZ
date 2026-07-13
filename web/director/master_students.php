<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/master_registry.php';

$u = require_role(['director']);
$pdo = db();
ensure_master_registry_schema($pdo);

$schoolId = (int)$u['school_id'];
$q = trim($_GET['q'] ?? '');

$where = 'EXISTS (SELECT 1 FROM student_school_history h WHERE h.master_student_id=ms.id AND h.school_id=?)';
$params = [$schoolId];

if ($q !== '') {
    $where .= ' AND (ms.fio LIKE ? OR ms.national_student_id LIKE ? OR ms.pinfl LIKE ?)';
    $params[] = '%'.$q.'%';
    $params[] = '%'.$q.'%';
    $params[] = '%'.$q.'%';
}

$stmt = $pdo->prepare("
    SELECT ms.*,
           (SELECT COUNT(DISTINCT h.school_id) FROM student_school_history h WHERE h.master_student_id=ms.id) AS school_count,
           (SELECT GROUP_CONCAT(DISTINCT CONCAT(s.school_name, ' / ', h.class_name) ORDER BY h.id SEPARATOR ' → ')
            FROM student_school_history h
            JOIN schools s ON s.id=h.school_id
            WHERE h.master_student_id=ms.id) AS route_text,
           (SELECT COUNT(*) FROM student_predictions sp WHERE sp.master_student_id=ms.id) AS prediction_count
    FROM master_students ms
    WHERE $where
    ORDER BY ms.fio
    LIMIT 300
");
$stmt->execute($params);
$rows = $stmt->fetchAll();
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Student Master Registry</title>
<link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container enterprise">
<header class="topbar">
    <div>
        <h1>Student Master Registry</h1>
        <p>O‘quvchi maktab almashtirsa ham, uning akademik va soft-skills tarixi yagona Master ID orqali bog‘lanadi.</p>
    </div>
    <nav class="nav">
        <a href="prediction_upload.php">+ Prediction</a>
        <a href="prediction_history.php">Prediction tarixi</a>
        <a href="index.php">Direktor</a>
        <a href="../logout.php">Chiqish</a>
    </nav>
</header>

<section class="card">
    <form method="get" class="filters">
        <input name="q" placeholder="FIO, PINFL yoki National ID" value="<?=auth_h($q)?>">
        <button class="btn">Qidirish</button>
    </form>
    <p class="hint">Datasetda `national_student_id`, `pinfl`, `JSHSHIR` yoki `birth_date` mavjud bo‘lsa, tizim o‘quvchini boshqa maktabdagi tarixi bilan avtomatik bog‘laydi.</p>
</section>

<section class="card">
<table>
<thead>
<tr><th>Master ID</th><th>F.I.O.</th><th>National ID</th><th>PINFL</th><th>Maktablar</th><th>Prediction</th><th>Ta’lim yo‘li</th></tr>
</thead>
<tbody>
<?php foreach($rows as $r): ?>
<tr>
<td>MS-<?=auth_h(str_pad((string)$r['id'],6,'0',STR_PAD_LEFT))?></td>
<td><?=auth_h($r['fio'])?></td>
<td><?=auth_h($r['national_student_id'])?></td>
<td><?=auth_h($r['pinfl'])?></td>
<td><?=auth_h($r['school_count'])?></td>
<td><?=auth_h($r['prediction_count'])?></td>
<td><?=auth_h($r['route_text'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
<?php if(!$rows): ?><p class="muted">Hozircha master registry yozuvlari topilmadi. Yangi prediction bajarilgach avtomatik shakllanadi.</p><?php endif; ?>
</section>
</div>
</body>
</html>
