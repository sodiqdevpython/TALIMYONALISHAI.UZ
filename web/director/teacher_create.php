<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['director']);
$pdo = db();
$schoolId = (int)$u['school_id'];
if (!$schoolId) die('Direktor maktabga biriktirilmagan.');

$errors=[]; $success='';
function pv($k,$d=''){ return trim($_POST[$k] ?? $d); }

$roles = $pdo->prepare("SELECT id FROM roles WHERE role_key='teacher' LIMIT 1");
$roles->execute();
$teacherRoleId = (int)$roles->fetchColumn();

if ($_SERVER['REQUEST_METHOD']==='POST') {
    $full = pv('full_name');
    $username = pv('username');
    $password = pv('password');
    $phone = pv('phone');
    $email = pv('email');

    if ($full==='') $errors[]='O‘qituvchi F.I.O. kiritilmadi.';
    if ($username==='') $errors[]='Login kiritilmadi.';
    if (mb_strlen($password)<6) $errors[]='Parol kamida 6 ta belgidan iborat bo‘lsin.';

    if (!$errors) {
        $ch=$pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
        $ch->execute([$username]);
        if ($ch->fetch()) $errors[]='Bu login allaqachon mavjud.';
    }

    if (!$errors) {
        try {
            $stmt=$pdo->prepare('INSERT INTO users (school_id, role_id, full_name, username, password_hash, phone, email, status) VALUES (?, ?, ?, ?, ?, ?, ?, "active")');
            $stmt->execute([$schoolId, $teacherRoleId, $full, $username, password_hash($password, PASSWORD_DEFAULT), $phone, $email]);
            $teacherId=(int)$pdo->lastInsertId();
            $log=$pdo->prepare('INSERT INTO activity_logs (user_id, school_id, action, description, entity_type, entity_id, ip_address) VALUES (?, ?, "teacher_created", ?, "user", ?, ?)');
            $log->execute([$u['id'],$schoolId,'Yangi o‘qituvchi yaratildi: '.$full,$teacherId,$_SERVER['REMOTE_ADDR'] ?? '']);
            $success='O‘qituvchi yaratildi. Login: '.$username;
            $_POST=[];
        } catch(Throwable $e) { $errors[]='Bazaga yozishda xatolik: '.$e->getMessage(); }
    }
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>O‘qituvchi qo‘shish</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container enterprise">
<header class="topbar"><div><h1>O‘qituvchi qo‘shish</h1><p>Maktab o‘qituvchisi uchun login/parol yaratiladi.</p></div><nav class="nav"><a href="teachers.php">O‘qituvchilar</a><a href="index.php">Direktor</a><a href="../logout.php">Chiqish</a></nav></header>
<?php if($errors): ?><section class="card error"><b>Xatolik:</b><ul><?php foreach($errors as $e): ?><li><?=auth_h($e)?></li><?php endforeach; ?></ul></section><?php endif; ?>
<?php if($success): ?><section class="card alert"><?=auth_h($success)?></section><?php endif; ?>
<section class="card"><form method="post"><h2>O‘qituvchi ma’lumotlari</h2><div class="grid">
<label>F.I.O.<input name="full_name" required value="<?=auth_h($_POST['full_name'] ?? '')?>"></label>
<label>Login<input name="username" required value="<?=auth_h($_POST['username'] ?? '')?>"></label>
<label>Parol<input name="password" required type="text" placeholder="kamida 6 belgi"></label>
<label>Telefon<input name="phone" value="<?=auth_h($_POST['phone'] ?? '')?>"></label>
<label>Email<input name="email" type="email" value="<?=auth_h($_POST['email'] ?? '')?>"></label>
</div><button class="btn" type="submit">O‘qituvchi yaratish</button></form></section>
</div></body></html>
