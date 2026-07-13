<?php
require_once __DIR__.'/../../config/auth.php';
$u=require_role(['student']); $pdo=db(); $schoolId=(int)$u['school_id'];
$st=$pdo->prepare('SELECT id FROM students WHERE school_id=? AND username=? LIMIT 1'); $st->execute([$schoolId,$u['username']]); $sid=(int)$st->fetchColumn();
$q=$pdo->prepare('SELECT id FROM student_predictions WHERE student_id=? AND school_id=? ORDER BY id DESC LIMIT 1'); $q->execute([$sid,$schoolId]); $pid=(int)$q->fetchColumn();
if(!$pid) die('Digital Twin uchun prediction topilmadi.');
// Student uchun qisqa ko‘rinish: asosiy Student Portal orqali ochiladi.
header('Location: index.php');
exit;
?>