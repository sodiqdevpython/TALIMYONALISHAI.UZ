<?php session_start(); require_once __DIR__.'/_helpers.php';
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $login=trim($_POST['login']??''); $pass=trim($_POST['password']??'');
    $s=find_school_by_login($login);
    if($s && ($s['password']??'')===$pass){ $_SESSION['school_login']=$login; header('Location: school_dashboard.php'); exit; }
    $error='Login yoki parol noto‘g‘ri.';
}
if(isset($_GET['logout'])){ session_destroy(); header('Location: school_login.php'); exit; }
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>Maktab kirishi</title></head><body><div class="container"><header class="topbar"><h1>Maktab kabineti</h1><nav class="nav"><a href="index.php">Bosh sahifa</a></nav></header><section class="card"><h2>Kirish</h2><?php if($error): ?><p class="err"><?=h($error)?></p><?php endif; ?><form method="post"><label>Login</label><input type="text" name="login" placeholder="demo_school" required><label>Parol</label><input type="password" name="password" placeholder="school123" required><button>Kirish</button></form><p class="muted">Demo: <b>demo_school / school123</b></p></section></div></body></html>