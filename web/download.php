<?php require_once __DIR__.'/_helpers.php'; 
$allowed=['student_results.csv','cluster_summary.csv','direction_summary.csv','class_direction_summary.csv','metrics.json','article_results_summary.md','school_recommendations.xlsx','student_logins.xlsx','school_full_report.xlsx','top_students.csv','low_confidence_students.csv','unclear_students.csv','student_temporal_history.csv','student_grade_history.csv','student_grade_history.xlsx'];
$file=basename($_GET['file'] ?? ''); 
if(!in_array($file,$allowed,true)) die('Not allowed'); 
$path=out_dir().DIRECTORY_SEPARATOR.$file; 
if(!file_exists($path)) die('File not found'); 
$ext=strtolower(pathinfo($file,PATHINFO_EXTENSION));
if($ext==='xlsx'){ header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); }
elseif($ext==='csv'){ header('Content-Type: text/csv; charset=utf-8'); }
elseif($ext==='json'){ header('Content-Type: application/json; charset=utf-8'); }
else{ header('Content-Type: application/octet-stream'); }
header('Content-Disposition: attachment; filename="'.$file.'"'); 
readfile($path); 
?>