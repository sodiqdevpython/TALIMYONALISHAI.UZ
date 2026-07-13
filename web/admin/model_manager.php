<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/governance.php';
require_once __DIR__.'/../../config/model_lifecycle.php';

$u=require_role(['super_admin']);
$pdo=db();
ensure_model_lifecycle_schema($pdo);
$msg='';

if(isset($_GET['activate_model'])){
    $id=(int)$_GET['activate_model'];
    try{
        activate_model($pdo,$id);
        $msg='Model active qilindi.';
    }catch(Throwable $e){ $msg='Xatolik: '.$e->getMessage(); }
}

if(isset($_GET['train_batch'])){
    $batchId=(int)$_GET['train_batch'];
    try{
        $res=create_retrained_model($pdo,$batchId,(int)$u['id']);
        $msg='Yangi model yaratildi: '.$res['version'].' — '.$res['metrics']['recommendation'];
    }catch(Throwable $e){ $msg='Retrain xatoligi: '.$e->getMessage(); }
}

if(isset($_GET['queue_retrain'])){
    $batchId=(int)$_GET['queue_retrain'];
    $base=(int)$pdo->query('SELECT id FROM research_models WHERE is_active=1 ORDER BY id DESC LIMIT 1')->fetchColumn();
    $ins=$pdo->prepare('INSERT INTO model_training_jobs (base_model_id,prediction_batch_id,status,recommendation,created_by,log_text) VALUES (?,?,"queued","Admin review required",?,"Retrain job queued.")');
    $ins->execute([$base?:null,$batchId,(int)$u['id']]);
    $msg='Retrain job navbatga qo‘yildi.';
}

$models=$pdo->query('SELECT * FROM research_models ORDER BY id DESC')->fetchAll();
$batches=$pdo->query('SELECT b.*, s.school_name FROM prediction_batches b JOIN schools s ON s.id=b.school_id WHERE b.status="completed" AND COALESCE(b.lifecycle_status,"inactive")<>"deleted" ORDER BY b.id DESC LIMIT 100')->fetchAll();
$jobs=$pdo->query('SELECT j.*, b.dataset_name, s.school_name FROM model_training_jobs j LEFT JOIN prediction_batches b ON b.id=j.prediction_batch_id LEFT JOIN schools s ON s.id=b.school_id ORDER BY j.id DESC LIMIT 100')->fetchAll();

function pct($v){ if($v===null || $v==='') return '-'; return round(((float)$v)*100,2).'%'; }
function metric_delta($json,$key){
    $m=json_decode((string)$json,true);
    if(!$m || !isset($m['delta'][$key])) return '';
    $d=(float)$m['delta'][$key]*100;
    return ($d>=0?'+':'').round($d,2).'%';
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>AI Model Manager</title><link rel="stylesheet" href="../assets/style.css"></head>
<body><div class="container enterprise">
<header class="topbar"><div><h1>AI Model Version Manager</h1><p>Build 4.3: retrain, benchmark, model versiya va active model boshqaruvi.</p></div><nav class="nav"><a href="index.php">Admin</a><a href="prediction_manager.php">Prediction Manager</a><a href="../logout.php">Chiqish</a></nav></header>
<?php if($msg): ?><section class="card alert"><?=auth_h($msg)?></section><?php endif; ?>

<section class="card">
<h2>Model versiyalari</h2>
<table>
<thead><tr><th>Model</th><th>Dataset / source</th><th>Accuracy</th><th>Confidence</th><th>F1</th><th>ROC-AUC</th><th>Holat</th><th>Amal</th></tr></thead>
<tbody>
<?php foreach($models as $m): ?>
<tr class="<?=((int)$m['is_active']===1?'active-row':'')?>">
<td><b><?=auth_h($m['model_version'])?></b><br><small><?=auth_h($m['model_name'])?></small></td>
<td><?=auth_h($m['training_source'] ?: 'Asosiy model')?></td>
<td><?=pct($m['accuracy'] ?? null)?> <small><?=auth_h(metric_delta($m['metrics_json'] ?? '', 'accuracy'))?></small></td>
<td><?=pct($m['confidence'] ?? null)?> <small><?=auth_h(metric_delta($m['metrics_json'] ?? '', 'confidence'))?></small></td>
<td><?=pct($m['f1_score'] ?? null)?></td>
<td><?=pct($m['roc_auc'] ?? null)?></td>
<td><?=((int)$m['is_active']===1?'✅ Active':auth_h($m['status'] ?? 'inactive'))?></td>
<td><?php if((int)$m['is_active']!==1): ?><a class="mini" href="model_manager.php?activate_model=<?=auth_h($m['id'])?>">✅ Active qilish</a><?php else: ?><span class="badge">CURRENT</span><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<section class="card">
<h2>Prediction datasetni modelga qo‘shib o‘qitish</h2>
<p class="hint">Admin prediction datasetni asosiy modelga qo‘shib yangi model versiya yaratadi. Eski model saqlanadi. Yangi model yaxshi bo‘lsa, admin uni Active qiladi.</p>
<table>
<thead><tr><th>Batch</th><th>Maktab</th><th>Dataset</th><th>O‘quv yili</th><th>O‘quvchi</th><th>Confidence</th><th>Amal</th></tr></thead>
<tbody>
<?php foreach($batches as $b): ?>
<tr>
<td>#<?=auth_h($b['id'])?></td>
<td><?=auth_h($b['school_name'])?></td>
<td><?=auth_h($b['dataset_name'])?></td>
<td><?=auth_h($b['academic_year'])?></td>
<td><?=auth_h($b['students_count'])?></td>
<td><?=auth_h($b['mean_confidence'])?></td>
<td>
<a class="mini" href="model_manager.php?queue_retrain=<?=auth_h($b['id'])?>">Queue</a>
<a class="mini" href="model_manager.php?train_batch=<?=auth_h($b['id'])?>" onclick="return confirm('Ushbu prediction asosida yangi model versiya yaratiladi. Davom etilsinmi?')">⚙ Train now</a>
</td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>

<section class="card">
<h2>Retrain Jobs</h2>
<table>
<thead><tr><th>ID</th><th>Batch</th><th>Maktab</th><th>Status</th><th>Recommendation</th><th>Metrics after</th><th>Log</th><th>Sana</th></tr></thead>
<tbody>
<?php foreach($jobs as $j): ?>
<tr>
<td>#<?=auth_h($j['id'])?></td>
<td>#<?=auth_h($j['prediction_batch_id'])?> — <?=auth_h($j['dataset_name'])?></td>
<td><?=auth_h($j['school_name'])?></td>
<td><?=auth_h($j['status'])?></td>
<td><?=auth_h($j['recommendation'])?></td>
<td><code><?=auth_h($j['metrics_after'])?></code></td>
<td><?=auth_h($j['log_text'])?></td>
<td><?=auth_h($j['created_at'])?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</section>
</div></body></html>
