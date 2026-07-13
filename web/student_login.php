<?php session_start(); require_once __DIR__.'/_helpers.php';
if(isset($_GET['logout'])){ session_destroy(); header('Location: student_login.php'); exit; }
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=trim($_POST['student_id']??''); if(preg_match('/^\d+$/',$id)) $id='OQ-'.str_pad($id,5,'0',STR_PAD_LEFT);
    $pass=trim($_POST['password']??'');
    foreach(result_rows() as $r){
        if(($r['student_id']??'')===$id && ($r['student_password']??'')===$pass){
            $_SESSION['student_id']=$id; header('Location: student_portal.php'); exit;
        }
    }
    $error='ID yoki parol noto‘g‘ri.';
}
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>O‘quvchi kabineti</title></head><body><div class="container"><header class="topbar"><h1>O‘quvchi kabineti</h1><nav class="nav"><a href="index.php">Dashboard</a></nav></header><section class="card"><h2>Tizimga kirish</h2><?php if($error): ?><p class="err"><?=h($error)?></p><?php endif; ?><form method="post"><label>O‘quvchi ID</label><input type="text" name="student_id" placeholder="Masalan: OQ-00001 yoki 1" required><label>Parol</label><input type="password" name="password" placeholder="Masalan: edu00001" required><button>Kirish</button></form><p class="muted">O‘quvchi login-parollari admin hisobotidagi <b>student_logins.xlsx</b> faylida beriladi.</p></section></div></body></html>