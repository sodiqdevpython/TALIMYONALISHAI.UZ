<?php session_start(); require_once __DIR__.'/_helpers.php';
$path=config_dir().DIRECTORY_SEPARATOR.'schools.json'; $cfg=schools_config(); $msg='';
if($_SERVER['REQUEST_METHOD']==='POST'){
    $id=trim($_POST['school_id']??'');
    if($id){
        $dirs=$_POST['directions']??[];
        $found=false;
        foreach($cfg['schools'] as &$s){
            if(($s['school_id']??'')===$id){
                $s['school_name']=trim($_POST['school_name']??$s['school_name']);
                $s['region']=trim($_POST['region']??'');
                $s['district']=trim($_POST['district']??'');
                $s['login']=trim($_POST['login']??$s['login']);
                $s['password']=trim($_POST['password']??$s['password']);
                $s['type']=trim($_POST['type']??'Oddiy maktab');
                $s['directions']=$dirs;
                $found=true;
            }
        }
        if(!$found){
            $cfg['schools'][]=['school_id'=>$id,'school_name'=>trim($_POST['school_name']??''),'region'=>trim($_POST['region']??''),'district'=>trim($_POST['district']??''),'login'=>trim($_POST['login']??''),'password'=>trim($_POST['password']??''),'type'=>trim($_POST['type']??'Oddiy maktab'),'directions'=>$dirs];
        }
        file_put_contents($path, json_encode($cfg, JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT));
        $msg='Maktab konfiguratsiyasi saqlandi.';
    }
}
$dirs=array_column(directions_config()['directions']??[], 'name');
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="assets/style.css"><title>School Configuration</title></head><body><div class="container">
<header class="topbar"><h1>School Configuration</h1><nav class="nav"><a href="index.php">Bosh sahifa</a><a href="prediction_mode.php">Prediction Mode</a></nav></header>
<?php if($msg): ?><section class="card alert"><?=h($msg)?></section><?php endif; ?>
<section class="card"><h2>Maktab qo‘shish / tahrirlash</h2><form method="post"><label>School ID</label><input type="text" name="school_id" placeholder="SIR-001" required><label>Maktab nomi</label><input type="text" name="school_name" placeholder="Guliston 1-maktab"><label>Viloyat</label><input type="text" name="region"><label>Tuman/shahar</label><input type="text" name="district"><label>Login</label><input type="text" name="login"><label>Parol</label><input type="text" name="password"><label>Maktab turi</label><select name="type"><option>Oddiy maktab</option><option>Ixtisoslashtirilgan maktab</option><option>Akademik litsey</option></select><h3>Mavjud yo‘nalishlar</h3><?php foreach($dirs as $d): ?><label class="check"><input type="checkbox" name="directions[]" value="<?=h($d)?>" checked> <?=h($d)?></label><?php endforeach; ?><button>Saqlash</button></form></section>
<section class="card"><h2>Mavjud maktablar</h2><table><thead><tr><th>ID</th><th>Nomi</th><th>Login</th><th>Yo‘nalishlar</th></tr></thead><tbody><?php foreach(($cfg['schools']??[]) as $s): ?><tr><td><?=h($s['school_id']??'')?></td><td><?=h($s['school_name']??'')?></td><td><?=h($s['login']??'')?></td><td><?=h(implode(', ', $s['directions']??[]))?></td></tr><?php endforeach; ?></tbody></table></section>
</div></body></html>