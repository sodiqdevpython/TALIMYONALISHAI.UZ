<?php
function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
function base_dir(){ return realpath(__DIR__ . '/..'); }
function out_dir(){ return base_dir() . DIRECTORY_SEPARATOR . 'outputs'; }
function metrics(){ $p=out_dir().DIRECTORY_SEPARATOR.'metrics.json'; return file_exists($p) ? json_decode(file_get_contents($p), true) : null; }
function read_csv_assoc($file){
    $rows=[]; if(!file_exists($file)) return $rows; $fh=fopen($file,'r'); if(!$fh) return $rows;
    $header=fgetcsv($fh); if(!$header){fclose($fh); return $rows;}
    $header=array_map(function($v){ return trim(str_replace("\xEF\xBB\xBF",'', $v)); }, $header);
    while(($data=fgetcsv($fh))!==false){
        if(count($data)!=count($header)) continue;
        $rows[]=array_combine($header,$data);
    }
    fclose($fh); return $rows;
}
function is_admin(){
    if(!isset($_SESSION['admin']) || $_SESSION['admin']!==true) return false;
    if(isset($_SESSION['last_seen']) && time()-$_SESSION['last_seen']>3600){ session_destroy(); return false; }
    $_SESSION['last_seen']=time(); return true;
}
function fmt($v,$digits=4){ if($v==='' || $v===null) return '-'; if(is_numeric($v)) return rtrim(rtrim(number_format((float)$v,$digits,'.',''),'0'),'.'); return h($v); }
function pct($v,$digits=2){ if($v==='' || $v===null || !is_numeric($v)) return '-'; return number_format(((float)$v)*100,$digits,'.','').'%'; }
function pct_raw($v,$digits=2){ if($v==='' || $v===null || !is_numeric($v)) return '-'; return number_format((float)$v,$digits,'.','').'%'; }
function result_rows(){ return read_csv_assoc(out_dir().DIRECTORY_SEPARATOR.'student_results.csv'); }
function direction_advice_php($direction){
    $adv=[
        'IT'=>"Informatika, matematika, algoritmik fikrlash, Python/web dasturlash, ma'lumotlar tahlili va ingliz tilini kuchaytirish tavsiya etiladi.",
        'Muhandislik'=>"Matematika, fizika, texnologiya, amaliy loyihalash, robototexnika va laboratoriya mashg'ulotlariga ko'proq e'tibor berish tavsiya etiladi.",
        'Tibbiyot'=>"Biologiya, kimyo, akademik barqarorlik, mas'uliyat, laboratoriya ishlari va sog'liqni saqlash yo'nalishidagi bilimlarni kuchaytirish tavsiya etiladi.",
        'Iqtisodiyot'=>"Matematika, iqtisodiyot, moliyaviy savodxonlik, mantiqiy fikrlash, kommunikatsiya va liderlik ko'nikmalarini rivojlantirish tavsiya etiladi.",
        'Pedagogika'=>"Kommunikativlik, jamoada ishlash, ona tili, adabiyot, kreativlik, liderlik va ta'lim metodikasi bo'yicha ko'nikmalarni rivojlantirish tavsiya etiladi."
    ];
    return $adv[$direction] ?? "Tanlangan yo'nalish bo'yicha asosiy fanlar va soft-skills ko'nikmalarini rivojlantirish tavsiya etiladi.";
}

function config_dir(){ return base_dir() . DIRECTORY_SEPARATOR . 'config'; }
function read_json_file($path){ return file_exists($path) ? json_decode(file_get_contents($path), true) : null; }
function schools_config(){ return read_json_file(config_dir().DIRECTORY_SEPARATOR.'schools.json') ?: ['schools'=>[]]; }
function directions_config(){ return read_json_file(config_dir().DIRECTORY_SEPARATOR.'directions.json') ?: ['directions'=>[]]; }
function find_school_by_login($login){
    foreach((schools_config()['schools'] ?? []) as $s){ if(($s['login'] ?? '') === $login) return $s; }
    return null;
}
function current_school(){
    if(empty($_SESSION['school_login'])) return null;
    return find_school_by_login($_SESSION['school_login']);
}
function school_out_dir($school_id){
    $p = out_dir().DIRECTORY_SEPARATOR.'schools'.DIRECTORY_SEPARATOR.preg_replace('/[^A-Za-z0-9_\-]/','_', $school_id);
    if(!is_dir($p)) mkdir($p,0777,true);
    return $p;
}
function read_school_results($school_id){
    return read_csv_assoc(school_out_dir($school_id).DIRECTORY_SEPARATOR.'school_prediction_results.csv');
}
?>