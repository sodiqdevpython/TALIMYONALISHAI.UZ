<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['super_admin']);
$pdo = db();

$counts = [
    'schools' => (int)$pdo->query("SELECT COUNT(*) FROM schools")->fetchColumn(),
    'users' => (int)$pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'batches' => (int)$pdo->query("SELECT COUNT(*) FROM prediction_batches")->fetchColumn(),
    'predictions' => (int)$pdo->query("SELECT COUNT(*) FROM student_predictions")->fetchColumn(),
];
?>
<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Super Admin</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
<header class="topbar">
    <div>
        <h1>Super Admin Panel</h1>
        <p>EduDirectionAI Professional Enterprise 1.0 boshqaruv markazi.</p>
    </div>
    <nav class="nav">
        <a href="school_create.php">+ Maktab qo‘shish</a>
        <a href="schools.php">Maktablar</a><a href="prediction_manager.php">Prediction Manager</a><a href="model_manager.php">Model Manager</a><a href="regional_dashboard.php">Regional</a><a href="ai_lab.php">AI Lab</a>
        <a href="users.php">Foydalanuvchilar</a>
        <a href="../logout.php">Chiqish</a>
    </nav>
</header>

<section class="cards">
    <div class="card"><h3>Maktablar</h3><b><?=$counts['schools']?></b></div>
    <div class="card"><h3>Foydalanuvchilar</h3><b><?=$counts['users']?></b></div>
    <div class="card"><h3>Prediction batch</h3><b><?=$counts['batches']?></b></div>
    <div class="card"><h3>O‘quvchi natijalari</h3><b><?=$counts['predictions']?></b></div>
</section>

<section class="card">
    <h2>Tezkor amallar</h2>
    <p><a class="btn" href="school_create.php">Yangi maktab + direktor yaratish</a></p>
</section>
</div>
</body>
</html>
