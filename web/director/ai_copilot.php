<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../app/Services/AIQueryService.php';
$u=require_role(['director']);
$pdo=db();
$schoolId=(int)$u['school_id'];
$schoolQ=$pdo->prepare('SELECT * FROM schools WHERE id=? LIMIT 1'); $schoolQ->execute([$schoolId]); $school=$schoolQ->fetch();

$question=trim($_POST['question'] ?? '');
$result=null;
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_check();
    $result=edu_ai_answer($pdo,$schoolId,$question);
}
$default=edu_ai_answer($pdo,$schoolId,'summary');
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>EduDirectionAI GPT</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><div class="container enterprise">
<header class="topbar"><div><h1>EduDirectionAI GPT</h1><p><?=auth_h($school['school_name'] ?? '')?> · Natural Language Analytics · Build 0.9</p></div><nav class="nav"><a href="analytics.php">Analytics</a><a href="smart_monitoring.php">Monitoring</a><a href="index.php">Direktor</a><a href="../logout.php">Chiqish</a></nav></header>

<section class="copilot-chat">
<div class="chat-panel">
    <div class="msg ai"><b>AI:</b><br><?=auth_h($default['answer'])?></div>
    <?php if($question): ?><div class="msg user"><b>Direktor:</b><br><?=auth_h($question)?></div><?php endif; ?>
    <?php if($result): ?><div class="msg ai"><b>AI:</b><br><?=auth_h($result['answer'])?></div><?php endif; ?>

    <?php if($result && !empty($result['rows'])): ?>
    <div class="card"><h3>Natija jadvali</h3><div class="table-wrap small-table"><table>
    <thead><tr><?php foreach(array_keys($result['rows'][0]) as $h): ?><th><?=auth_h($h)?></th><?php endforeach; ?></tr></thead>
    <tbody><?php foreach($result['rows'] as $row): ?><tr><?php foreach($row as $v): ?><td><?=auth_h($v)?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody>
    </table></div></div>
    <?php endif; ?>

    <form method="post" class="chat-form"><input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><textarea name="question" class="textarea" placeholder="Masalan: Risk o‘quvchilarni chiqar yoki Grant nomzodlarini ko‘rsat"><?=auth_h($question)?></textarea><button class="btn">Yuborish</button></form>
</div>

<div class="card">
<h2>Tezkor AI savollar</h2>
<form method="post"><?php foreach(['Risk o‘quvchilarni chiqar','Qaysi sinf eng yaxshi?','Grantga nomzod o‘quvchilar bormi?','Yo‘nalishlar taqsimotini ko‘rsat','O‘quvchilar ro‘yxatini chiqar'] as $q): ?>
<input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><button class="quick" name="question" value="<?=auth_h($q)?>">✓ <?=auth_h($q)?></button>
<?php endforeach; ?></form>
<p class="muted">Build 0.9 qoidaviy NLQ engine bilan ishlaydi. Keyingi bosqichda tashqi LLM/API ulashga tayyor.</p>
</div>
</section>
</div></body></html>
