<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['vice_director']);
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Zavuch Paneli</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container">
<header class="topbar"><div><h1>Zavuch Paneli</h1><p>Sinflar, o‘qituvchilar va yo‘nalishlar kesimidagi nazorat.</p></div><nav class="nav"><a href="../dashboard.php">Dashboard</a><a href="../logout.php">Chiqish</a></nav></header>
<section class="grid"><div class="card stat"><span>Foydalanuvchi</span><strong><?=auth_h($u['full_name'])?></strong></div><div class="card stat"><span>Rol</span><strong><?=auth_h($u['role_name'])?></strong></div><div class="card stat"><span>Maktab</span><strong><?=auth_h($u['school_name'] ?? 'Barcha maktablar')?></strong></div></section>
<section class="card"><h2>Modul tayyor</h2><p>Authentication muvaffaqiyatli ulandi. Keyingi bosqichda ushbu panelga funksional bo‘limlar qo‘shiladi.</p></section>
</div></body></html>
