<?php
require_once __DIR__.'/../config/auth.php';
if (current_user()) { header('Location: '.role_home(current_user()['role_key'])); exit; }
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $username = trim($_POST['username'] ?? '');
    $password = (string)($_POST['password'] ?? '');
    $res = login_user($username, $password);
    if ($res['ok']) { header('Location: '.role_home($_SESSION['role_key'])); exit; }
    $error = $res['message'];
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Kirish — EduDirectionAI</title><link rel="stylesheet" href="assets/style.css"><style>.login-wrap{max-width:440px;margin:70px auto}.login-card{padding:32px}.input{width:100%;padding:13px;border:1px solid #d8e0ef;border-radius:12px;margin:8px 0 16px}.err{background:#ffe8e8;color:#9b1c1c;padding:12px;border-radius:10px;margin-bottom:15px}.demo{font-size:13px;background:#f5f7fb;padding:12px;border-radius:12px;line-height:1.7}.guide-box{margin-top:18px;background:#eef6ff;border:1px solid #bfdbfe;border-radius:14px;padding:14px}.guide-box a{font-weight:800;color:#0b4dbb;text-decoration:none}.guide-box p{margin:6px 0 0;color:#475569;font-size:13px;line-height:1.45}</style></head><body><div class="container login-wrap"><section class="card login-card"><h1>EduDirectionAI</h1><p class="hint">Professional v5.0 platformasiga kirish</p><?php if($error): ?><div class="err"><?=auth_h($error)?></div><?php endif; ?><form method="post"><input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><label>Login</label><input class="input" name="username" required autofocus><label>Parol</label><input class="input" type="password" name="password" required><button type="submit">Kirish</button></form><div class="guide-box"><a href="docs/edudirectionai_foydalanuvchi_qollanmasi.pdf" target="_blank">📘 Tizimdan foydalanish qo‘llanmasi</a><p>Tizimga kirishdan oldin qo‘llanmani yuklab olib, Super Admin, Direktor, O‘qituvchi va Parent Portal imkoniyatlari bilan tanishib chiqing.</p></div></body></html>
