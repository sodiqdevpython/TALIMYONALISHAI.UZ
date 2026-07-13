<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['super_admin']);

$errors = [];
$success = '';

function postv($key, $default='') {
    return trim($_POST[$key] ?? $default);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $school_code   = postv('school_code');
    $school_name   = postv('school_name');
    $director_name = postv('director_name');
    $region        = postv('region');
    $district      = postv('district');
    $address       = postv('address');
    $phone         = postv('phone');
    $email         = postv('email');

    $director_username = postv('director_username');
    $director_password = postv('director_password');

    if ($school_code === '')   $errors[] = 'Maktab kodi kiritilmadi.';
    if ($school_name === '')   $errors[] = 'Maktab nomi kiritilmadi.';
    if ($director_name === '') $errors[] = 'Direktor F.I.O. kiritilmadi.';
    if ($director_username === '') $errors[] = 'Direktor login kiritilmadi.';
    if (mb_strlen($director_password) < 6) $errors[] = 'Direktor paroli kamida 6 ta belgidan iborat bo‘lsin.';

    if (!$errors) {
        $pdo = db();

        $check = $pdo->prepare("SELECT id FROM schools WHERE school_code=? LIMIT 1");
        $check->execute([$school_code]);
        if ($check->fetch()) $errors[] = 'Bu maktab kodi allaqachon mavjud.';

        $check = $pdo->prepare("SELECT id FROM users WHERE username=? LIMIT 1");
        $check->execute([$director_username]);
        if ($check->fetch()) $errors[] = 'Bu direktor login allaqachon mavjud.';
    }

    if (!$errors) {
        try {
            $pdo = db();
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO schools
                (school_code, school_name, director_name, region, district, address, phone, email, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $school_code, $school_name, $director_name,
                $region, $district, $address, $phone, $email
            ]);
            $school_id = (int)$pdo->lastInsertId();

            $role = $pdo->prepare("SELECT id FROM roles WHERE role_key='director' LIMIT 1");
            $role->execute();
            $director_role_id = (int)$role->fetchColumn();

            if (!$director_role_id) {
                throw new Exception("director roli bazadan topilmadi.");
            }

            $password_hash = password_hash($director_password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("
                INSERT INTO users
                (school_id, role_id, full_name, username, password_hash, phone, email, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([
                $school_id, $director_role_id, $director_name,
                $director_username, $password_hash, $phone, $email
            ]);

            $log = $pdo->prepare("
                INSERT INTO activity_logs (user_id, school_id, action, description, entity_type, entity_id, ip_address)
                VALUES (?, ?, 'school_created', ?, 'school', ?, ?)
            ");
            $log->execute([
                $u['id'], $school_id,
                'Yangi maktab va direktor login yaratildi: '.$school_name,
                $school_id,
                $_SERVER['REMOTE_ADDR'] ?? ''
            ]);

            $pdo->commit();
            $success = "Maktab va direktor login muvaffaqiyatli yaratildi. Login: ".$director_username;
            $_POST = [];
        } catch (Throwable $e) {
            if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Bazaga yozishda xatolik: '.$e->getMessage();
        }
    }
}
?>
<!doctype html>
<html lang="uz">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Maktab qo‘shish</title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
<div class="container">
<header class="topbar">
    <div>
        <h1>Maktab qo‘shish</h1>
        <p>Maktab ro‘yxatdan o‘tadi va direktor uchun login/parol yaratiladi.</p>
    </div>
    <nav class="nav">
        <a href="schools.php">Maktablar</a>
        <a href="index.php">Admin</a>
        <a href="../logout.php">Chiqish</a>
    </nav>
</header>

<?php if($errors): ?>
<section class="card" style="border-left:4px solid #dc2626">
    <b>Xatolik:</b>
    <ul>
        <?php foreach($errors as $e): ?><li><?=auth_h($e)?></li><?php endforeach; ?>
    </ul>
</section>
<?php endif; ?>

<?php if($success): ?>
<section class="card" style="border-left:4px solid #16a34a">
    <?=auth_h($success)?>
</section>
<?php endif; ?>

<section class="card">
<form method="post">
    <h2>Maktab ma’lumotlari</h2>
    <div class="grid">
        <label>Maktab kodi
            <input name="school_code" required placeholder="MASALAN: DEMO-002" value="<?=auth_h($_POST['school_code'] ?? '')?>">
        </label>
        <label>Maktab nomi
            <input name="school_name" required placeholder="81-son umumiy o‘rta ta’lim maktabi" value="<?=auth_h($_POST['school_name'] ?? '')?>">
        </label>
        <label>Direktor F.I.O.
            <input name="director_name" required placeholder="Aliyev Ali Valiyevich" value="<?=auth_h($_POST['director_name'] ?? '')?>">
        </label>
        <label>Viloyat
            <input name="region" placeholder="Sirdaryo" value="<?=auth_h($_POST['region'] ?? '')?>">
        </label>
        <label>Tuman/shahar
            <input name="district" placeholder="Guliston" value="<?=auth_h($_POST['district'] ?? '')?>">
        </label>
        <label>Telefon
            <input name="phone" placeholder="+998..." value="<?=auth_h($_POST['phone'] ?? '')?>">
        </label>
        <label>Email
            <input name="email" type="email" placeholder="school@example.uz" value="<?=auth_h($_POST['email'] ?? '')?>">
        </label>
        <label>Manzil
            <input name="address" placeholder="To‘liq manzil" value="<?=auth_h($_POST['address'] ?? '')?>">
        </label>
    </div>

    <h2>Direktor login/paroli</h2>
    <div class="grid">
        <label>Direktor login
            <input name="director_username" required placeholder="director_81" value="<?=auth_h($_POST['director_username'] ?? '')?>">
        </label>
        <label>Direktor parol
            <input name="director_password" required type="text" placeholder="kamida 6 belgi">
        </label>
    </div>

    <button class="btn" type="submit">Maktabni ro‘yxatdan o‘tkazish</button>
</form>
</section>
</div>
</body>
</html>
