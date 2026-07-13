<?php require_once __DIR__.'/_helpers.php';
set_time_limit(0); ini_set('max_execution_time','0'); ini_set('memory_limit','2048M');

$base=base_dir(); $dataDir=$base.DIRECTORY_SEPARATOR.'data'; $outDir=$base.DIRECTORY_SEPARATOR.'outputs';
if(!is_dir($dataDir)) mkdir($dataDir,0777,true); if(!is_dir($outDir)) mkdir($outDir,0777,true);

$mode=$_POST['mode'] ?? 'research';
$python='python';
$out=[]; $code=0; $title='';

if($mode==='predict'){
    $schoolId=$_POST['school_id'] ?? 'UNKNOWN';
    $schools=schools_config()['schools'] ?? []; $school=null;
    foreach($schools as $s){ if(($s['school_id']??'')===$schoolId){$school=$s;break;} }
    if(!$school){ die('School not found'); }
    $input=$dataDir.DIRECTORY_SEPARATOR.'school_'.$schoolId.'.xlsx';
    if(isset($_FILES['dataset']) && $_FILES['dataset']['error']===UPLOAD_ERR_OK){ move_uploaded_file($_FILES['dataset']['tmp_name'], $input); }
    else { die('Dataset required'); }
    $schoolOut=school_out_dir($schoolId);
    $script=$base.DIRECTORY_SEPARATOR.'python'.DIRECTORY_SEPARATOR.'predict_dataset.py';
    $cmd=$python.' '.escapeshellarg($script)
        .' --input '.escapeshellarg($input)
        .' --out '.escapeshellarg($schoolOut)
        .' --school_id '.escapeshellarg($school['school_id'])
        .' --school_name '.escapeshellarg($school['school_name'])
        .' --region '.escapeshellarg($school['region'] ?? '')
        .' --district '.escapeshellarg($school['district'] ?? '')
        .' --model_dir '.escapeshellarg($outDir.DIRECTORY_SEPARATOR.'models')
        .' 2>&1';
    $title='Prediction Mode natijasi';
    exec($cmd,$out,$code);
    $back='school_dashboard.php?school_id='.urlencode($schoolId);
}else{
    $input=$dataDir.DIRECTORY_SEPARATOR.'dataset.xlsx';
    if(isset($_FILES['dataset']) && $_FILES['dataset']['error']===UPLOAD_ERR_OK){ move_uploaded_file($_FILES['dataset']['tmp_name'], $input); }
    $script=$base.DIRECTORY_SEPARATOR.'python'.DIRECTORY_SEPARATOR.'train_model.py';
    $cmd=$python.' '.escapeshellarg($script).' --input '.escapeshellarg($input).' --out '.escapeshellarg($outDir).' 2>&1';
    $title='Research Mode natijasi';
    exec($cmd,$out,$code);
    $back='index.php';
}
?><!doctype html><html lang="uz"><head><meta charset="utf-8"><link rel="stylesheet" href="assets/style.css"><title><?=h($title)?></title></head><body><div class="container"><section class="card <?= $code===0?'':'error' ?>"><h1><?= $code===0?'Jarayon muvaffaqiyatli tugadi':'Xatolik chiqdi' ?></h1><p><b>Rejim:</b> <?=h($mode)?></p><pre><?=h(implode("\n",$out))?></pre><a class="btn" href="<?=h($back)?>">Natijani ko‘rish</a></section></div></body></html>