<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../app/Services/AIQueryService.php';
header('Content-Type: application/json; charset=utf-8');
$u = require_role(['director']);
$question = trim($_POST['question'] ?? $_GET['question'] ?? '');
if ($question==='') {
    echo json_encode(['ok'=>false,'message'=>'Savol bo‘sh.'], JSON_UNESCAPED_UNICODE);
    exit;
}
try {
    $result = edu_ai_answer(db(), (int)$u['school_id'], $question);
    try {
        db()->prepare('INSERT INTO activity_logs (user_id, school_id, action, description, entity_type, entity_id, ip_address) VALUES (?, ?, "ai_nlq_query", ?, "api", NULL, ?)')
            ->execute([$u['id'], $u['school_id'], $question, $_SERVER['REMOTE_ADDR'] ?? '']);
    } catch(Throwable $e) {}
    echo json_encode(['ok'=>true] + $result, JSON_UNESCAPED_UNICODE);
} catch(Throwable $e) {
    echo json_encode(['ok'=>false,'message'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>