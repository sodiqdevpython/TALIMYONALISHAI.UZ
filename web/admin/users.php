<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['super_admin']);

$users = db()->query("
    SELECT u.id, u.full_name, u.username, u.phone, u.email, u.status, u.created_at,
           r.role_name, r.role_key,
           s.school_name, s.school_code
    FROM users u
    JOIN roles r ON r.id=u.role_id
    LEFT JOIN schools s ON s.id=u.school_id
    ORDER BY u.id DESC
")->fetchAll();
?>
<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Foydalanuvchilar</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
<header class="topbar">
    <div>
        <h1>Foydalanuvchilar</h1>
        <p>Admin, direktor, zavuch va o‘qituvchilar ro‘yxati.</p>
    </div>
    <nav class="nav">
        <a href="index.php">Admin</a>
        <a href="schools.php">Maktablar</a>
        <a href="../logout.php">Chiqish</a>
    </nav>
</header>

<section class="card">
<table>
<thead>
<tr>
    <th>ID</th>
    <th>F.I.O.</th>
    <th>Login</th>
    <th>Rol</th>
    <th>Maktab</th>
    <th>Telefon</th>
    <th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($users as $x): ?>
<tr>
    <td><?=auth_h($x['id'])?></td>
    <td><?=auth_h($x['full_name'])?></td>
    <td><?=auth_h($x['username'])?></td>
    <td><?=auth_h($x['role_name'])?></td>
    <td><?=auth_h(($x['school_code'] ? $x['school_code'].' — ' : '').($x['school_name'] ?? ''))?></td>
    <td><?=auth_h($x['phone'])?></td>
    <td><?=auth_h($x['status'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>
</div>
</body>
</html>
