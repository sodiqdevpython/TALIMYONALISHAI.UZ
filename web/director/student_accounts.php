<?php
require_once __DIR__.'/../../config/auth.php';
$u = require_role(['director']);
$pdo = db();
$schoolId=(int)$u['school_id'];
if(!$schoolId) die('Direktor maktabga biriktirilmagan.');

$msg='';
$roleQ=$pdo->prepare("SELECT id FROM roles WHERE role_key='student' LIMIT 1");
$roleQ->execute();
$studentRoleId=(int)$roleQ->fetchColumn();

if($_SERVER['REQUEST_METHOD']==='POST' && ($_POST['action'] ?? '')==='generate') {
    csrf_check();
    $students=$pdo->prepare('SELECT * FROM students WHERE school_id=? AND status="active"');
    $students->execute([$schoolId]);
    $rows=$students->fetchAll();
    $created=0; $updated=0;
    foreach($rows as $st){
        $username=$st['username'];
        if(!$username){
            $clean=preg_replace('/[^A-Za-z0-9_]/','',(string)$st['student_code']);
            if($clean==='') $clean=substr(md5($st['student_code']),0,8);
            $username='st'.$schoolId.'_'.$clean;
            $pdo->prepare('UPDATE students SET username=? WHERE id=?')->execute([$username,$st['id']]);
        }
        $hash=$st['password_hash'] ?: password_hash('edu'.substr(md5((string)$st['student_code']),0,6), PASSWORD_DEFAULT);
        if(!$st['password_hash']) $pdo->prepare('UPDATE students SET password_hash=? WHERE id=?')->execute([$hash,$st['id']]);

        $ex=$pdo->prepare('SELECT id FROM users WHERE username=? LIMIT 1');
        $ex->execute([$username]);
        $uid=$ex->fetchColumn();
        if($uid){
            $pdo->prepare('UPDATE users SET school_id=?, role_id=?, full_name=?, password_hash=?, status="active" WHERE id=?')
                ->execute([$schoolId,$studentRoleId,$st['student_name'],$hash,$uid]);
            $updated++;
        } else {
            $pdo->prepare('INSERT INTO users (school_id, role_id, full_name, username, password_hash, status) VALUES (?, ?, ?, ?, ?, "active")')
                ->execute([$schoolId,$studentRoleId,$st['student_name'],$username,$hash]);
            $created++;
        }
    }
    $pdo->prepare('INSERT INTO activity_logs (user_id, school_id, action, description, entity_type, entity_id, ip_address) VALUES (?, ?, "student_accounts_generated", ?, "student", NULL, ?)')
        ->execute([$u['id'],$schoolId,"Student loginlar yaratildi: created=$created, updated=$updated",$_SERVER['REMOTE_ADDR'] ?? '']);
    $msg="Student loginlar tayyorlandi. Yangi: $created, yangilangan: $updated.";
}

$studentsQ=$pdo->prepare('
    SELECT st.*, c.class_name,
           (SELECT sp.id FROM student_predictions sp WHERE sp.student_id=st.id ORDER BY sp.id DESC LIMIT 1) AS last_prediction_id,
           (SELECT sp.recommended_direction FROM student_predictions sp WHERE sp.student_id=st.id ORDER BY sp.id DESC LIMIT 1) AS last_direction,
           (SELECT sp.recommendation_confidence FROM student_predictions sp WHERE sp.student_id=st.id ORDER BY sp.id DESC LIMIT 1) AS last_confidence,
           (SELECT sp.full_json FROM student_predictions sp WHERE sp.student_id=st.id ORDER BY sp.id DESC LIMIT 1) AS last_json,
           (SELECT COUNT(*) FROM users u JOIN roles r ON r.id=u.role_id WHERE u.username=st.username AND r.role_key="student") AS user_exists
    FROM students st
    LEFT JOIN classes c ON c.id=st.class_id
    WHERE st.school_id=?
    ORDER BY c.class_name, st.student_name
    LIMIT 1000
');
$studentsQ->execute([$schoolId]);
$students=$studentsQ->fetchAll();

function plain_pass_from_json($json){
    $a=json_decode((string)$json,true);
    return is_array($a) ? ($a['student_password'] ?? '') : '';
}
?>
<!doctype html><html lang="uz"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Student loginlar</title><link rel="stylesheet" href="../assets/style.css"></head><body><div class="container enterprise">
<header class="topbar"><div><h1>Student Portal Accounts</h1><p>O‘quvchilar uchun shaxsiy kabinet login/parollarini boshqarish.</p></div><nav class="nav"><a href="index.php">Direktor</a><a href="prediction_history.php">Tarix</a><a href="../logout.php">Chiqish</a></nav></header>
<?php if($msg): ?><section class="card alert"><?=auth_h($msg)?></section><?php endif; ?>
<section class="card"><h2>Loginlarni yaratish/sinxronlash</h2><p class="muted">Prediction import qilingan o‘quvchilar asosida `users` jadvalida student rolidagi akkauntlar yaratiladi.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=csrf_token()?>"><input type="hidden" name="action" value="generate"><button class="btn">Student loginlarni yaratish</button></form></section>
<section class="card"><h2>O‘quvchilar loginlari</h2><div class="table-wrap"><table><thead><tr><th>Sinf</th><th>O‘quvchi</th><th>Login</th><th>Parol</th><th>Tavsiya</th><th>Status</th></tr></thead><tbody>
<?php foreach($students as $s): $plain=plain_pass_from_json($s['last_json']); ?>
<tr><td><?=auth_h($s['class_name'])?></td><td><?=auth_h($s['student_name'])?></td><td><b><?=auth_h($s['username'])?></b></td><td><?=auth_h($plain ?: 'oldin yaratilgan parol')?></td><td><?=auth_h($s['last_direction'])?> <?= $s['last_confidence']!==null ? '('.round((float)$s['last_confidence']*100,1).'%)' : '' ?></td><td><?=((int)$s['user_exists']>0)?'<span class="badge">active</span>':'<span class="badge">not created</span>'?></td></tr>
<?php endforeach; ?>
</tbody></table></div></section>
</div></body></html>
