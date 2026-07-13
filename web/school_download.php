<?php require_once __DIR__.'/_helpers.php';
$schoolId=$_GET['school_id']??''; $file=basename($_GET['file']??'');
$allowed=['school_prediction_results.xlsx','school_prediction_results.csv','school_student_logins.xlsx','school_direction_summary.csv','school_class_direction_summary.csv','school_prediction_summary.json'];
if(!in_array($file,$allowed,true)) die('Not allowed');
$path=school_out_dir($schoolId).DIRECTORY_SEPARATOR.$file; if(!file_exists($path)) die('File not found');
$ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
if($ext==='xlsx') header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
elseif($ext==='json') header('Content-Type: application/json; charset=utf-8');
else header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="'.$file.'"'); readfile($path);
?>