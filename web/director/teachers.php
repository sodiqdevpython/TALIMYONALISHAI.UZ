<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['director']);
$pdo = db();
$schoolId=(int)$u['school_id'];

$teachersQ=$pdo->prepare("
    SELECT u.*, 
           (SELECT COUNT(*) FROM teacher_classes tc WHERE tc.teacher_id=u.id) class_count,
           (SELECT COUNT(DISTINCT tc.batch_id) FROM teacher_classes tc WHERE tc.teacher_id=u.id) batch_count
    FROM users u
    JOIN roles r ON r.id=u.role_id
    WHERE u.school_id=? AND r.role_key='teacher'
    ORDER BY u.full_name
");
$teachersQ->execute([$schoolId]);
$teachers=$teachersQ->fetchAll();
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>O‘qituvchilar</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container enterprise">
<header class="topbar"><div><h1>O‘qituvchilar</h1><p>Maktab o‘qituvchilari va ularga biriktirilgan sinflar.</p></div><nav class="nav"><a href="teacher_create.php">+ O‘qituvchi</a><a href="index.php">Direktor</a><a href="../logout.php">Chiqish</a></nav></header>
<section class="card"><table><thead><tr><th>F.I.O.</th><th>Login</th><th>Telefon</th><th>Sinf snapshotlari</th><th>Prediction</th><th>Status</th><th>Amal</th></tr></thead><tbody>
<?php foreach($teachers as $t): ?><tr>
<td><?=auth_h($t['full_name'])?></td><td><?=auth_h($t['username'])?></td><td><?=auth_h($t['phone'])?></td><td><?=auth_h($t['class_count'])?></td><td><?=auth_h($t['batch_count'])?></td><td><?=auth_h($t['status'])?></td>
<td><a class="mini" href="teacher_assign.php?teacher_id=<?=auth_h($t['id'])?>">Sinflar</a></td>
</tr><?php endforeach; ?>
</tbody></table></section>
</div></body></html>
