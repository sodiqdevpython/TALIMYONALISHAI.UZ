<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['super_admin']);

$schools = db()->query("
    SELECT s.*,
           (SELECT COUNT(*) FROM users u WHERE u.school_id=s.id) AS users_count,
           (SELECT COUNT(*) FROM students st WHERE st.school_id=s.id) AS students_count
    FROM schools s
    ORDER BY s.id DESC
")->fetchAll();
?>
<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Maktablar</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
<header class="topbar">
    <div>
        <h1>Maktablar</h1>
        <p>Ro‘yxatdan o‘tgan maktablar va ularning direktor loginlari.</p>
    </div>
    <nav class="nav">
        <a href="index.php">Admin</a>
        <a href="school_create.php">+ Maktab qo‘shish</a>
        <a href="users.php">Foydalanuvchilar</a>
        <a href="../logout.php">Chiqish</a>
    </nav>
</header>

<section class="card">
    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>Kod</th>
            <th>Maktab</th>
            <th>Direktor</th>
            <th>Hudud</th>
            <th>Foydalanuvchi</th>
            <th>O‘quvchi</th>
            <th>Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($schools as $s): ?>
            <tr>
                <td><?=auth_h($s['id'])?></td>
                <td><?=auth_h($s['school_code'])?></td>
                <td><?=auth_h($s['school_name'])?></td>
                <td><?=auth_h($s['director_name'])?></td>
                <td><?=auth_h(($s['region'] ?? '').' / '.($s['district'] ?? ''))?></td>
                <td><?=auth_h($s['users_count'])?></td>
                <td><?=auth_h($s['students_count'])?></td>
                <td><?=auth_h($s['status'])?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</section>
</div>
</body>
</html>
