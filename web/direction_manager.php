<?php session_start(); require_once __DIR__.'/_helpers.php';
$path=config_dir().DIRECTORY_SEPARATOR.'directions.json'; $cfg=directions_config();
$msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $name=trim($_POST['name']??''); $code=trim($_POST['code']??'');
    if($name && $code){
        $cfg['directions'][]=[
            'name'=>$name,'code'=>$code,
            'subjects'=>array_map('trim', explode(',', $_POST['subjects']??'')),
            'skills'=>array_map('trim', explode(',', $_POST['skills']??'')),
            'formula'=>trim($_POST['formula']??''),
            'advice'=>trim($_POST['advice']??'')
        ];
        file_put_contents($path, json_encode($cfg, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $msg='Yangi yo‘nalish qo‘shildi. Modelni qayta o‘qitishdan oldin feature formula va dataset ustunlari mosligini tekshiring.';
    }
}
$k=count($cfg['directions']??[]);
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>Direction Manager</title></head><body><div class="container">
<header class="topbar"><div><h1>Direction Manager</h1><p>Yangi ta’lim yo‘nalishlarini boshqarish.</p></div><nav class="nav"><a href="index.php">Bosh sahifa</a><a href="research_mode.php">Research Mode</a></nav></header>
<?php if($msg): ?><section class="card alert"><?=h($msg)?></section><?php endif; ?>
<section class="card"><h2>Mavjud yo‘nalishlar: K = <?=h($k)?></h2><table><thead><tr><th>Nom</th><th>Kod</th><th>Fanlar</th><th>Soft-skills</th></tr></thead><tbody><?php foreach(($cfg['directions']??[]) as $d): ?><tr><td><?=h($d['name']??'')?></td><td><?=h($d['code']??'')?></td><td><?=h(implode(', ', $d['subjects']??[]))?></td><td><?=h(implode(', ', $d['skills']??[]))?></td></tr><?php endforeach; ?></tbody></table></section>
<section class="card"><h2>Yangi yo‘nalish qo‘shish</h2><p class="alert">Agar K &gt; 5 qilmoqchi bo‘lsangiz, yangi yo‘nalish nomi, fanlar, soft-skills va formula kiritilishi kerak.</p><form method="post"><label>Yo‘nalish nomi</label><input type="text" name="name" placeholder="Masalan: Huquq"><label>Kod</label><input type="text" name="code" placeholder="LAW"><label>Mos fanlar</label><input type="text" name="subjects" placeholder="Tarix, Huquq, Ona tili"><label>Mos soft-skills</label><input type="text" name="skills" placeholder="Critical_Thinking, Communication"><label>Indeks formulasi</label><input type="text" name="formula" placeholder="0.35*history + 0.25*law + 0.20*critical + 0.20*communication"><label>Tavsiya matni</label><input type="text" name="advice" placeholder="Huquq yo‘nalishi uchun ..."><button>Yo‘nalishni qo‘shish</button></form></section>
</div></body></html>