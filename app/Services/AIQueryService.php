<?php
// EduDirectionAI Enterprise 0.9 — AI Query Service
// Rule-based NLQ engine: maps common Uzbek/Russian style questions to safe analytics queries.

function edu_ai_latest_batch(PDO $pdo, int $schoolId): ?array {
    $q=$pdo->prepare('SELECT * FROM prediction_batches WHERE school_id=? AND status="completed" ORDER BY id DESC LIMIT 1');
    $q->execute([$schoolId]);
    $b=$q->fetch();
    return $b ?: null;
}

function edu_ai_answer(PDO $pdo, int $schoolId, string $question): array {
    $qText = mb_strtolower(trim($question), 'UTF-8');
    $batch = edu_ai_latest_batch($pdo, $schoolId);
    if (!$batch) return ['answer'=>'Hali prediction mavjud emas. Avval dataset yuklab, prediction bajaring.', 'rows'=>[], 'type'=>'empty'];
    $batchId = (int)$batch['id'];

    if (str_contains($qText,'ro‘yxat') || str_contains($qText,'royxat') || str_contains($qText,'o‘quvchi') || str_contains($qText,'talaba')) {
        $stmt=$pdo->prepare('SELECT student_name, class_name, recommended_direction, ROUND(recommendation_confidence*100,1) confidence FROM student_predictions WHERE batch_id=? ORDER BY class_name, student_name LIMIT 50');
        $stmt->execute([$batchId]);
        $rows=$stmt->fetchAll();
        return ['answer'=>'Oxirgi prediction bo‘yicha o‘quvchilar ro‘yxati chiqarildi. Birinchi 50 ta yozuv ko‘rsatilmoqda.', 'rows'=>$rows, 'type'=>'table'];
    }

    if (str_contains($qText,'risk') || str_contains($qText,'xavf') || str_contains($qText,'past') || str_contains($qText,'pasay')) {
        $stmt=$pdo->prepare('SELECT student_name, class_name, recommended_direction, ROUND(recommendation_confidence*100,1) confidence, growth_trend, ROUND(academic_stability*100,1) stability FROM student_predictions WHERE batch_id=? AND (recommendation_confidence<0.80 OR growth_trend<0 OR academic_stability<0.70) ORDER BY recommendation_confidence ASC LIMIT 30');
        $stmt->execute([$batchId]);
        $rows=$stmt->fetchAll();
        return ['answer'=>count($rows).' nafar o‘quvchi risk monitoring ro‘yxatiga tushdi. Ular uchun individual mentorlik va 4 haftalik intervention plan tavsiya etiladi.', 'rows'=>$rows, 'type'=>'risk'];
    }

    if (str_contains($qText,'sinf') || str_contains($qText,'class')) {
        $stmt=$pdo->prepare('SELECT class_name, COUNT(*) students, ROUND(AVG(recommendation_confidence)*100,1) confidence, ROUND(AVG(growth_trend),4) growth, ROUND(AVG(academic_stability)*100,1) stability FROM student_predictions WHERE batch_id=? GROUP BY class_name ORDER BY confidence DESC');
        $stmt->execute([$batchId]);
        $rows=$stmt->fetchAll();
        return ['answer'=>'Sinf kesimidagi AI monitoring jadvali tayyor. Confidence, growth va stability indikatorlari bo‘yicha solishtirish mumkin.', 'rows'=>$rows, 'type'=>'class'];
    }

    if (str_contains($qText,'grant') || str_contains($qText,'scholarship') || str_contains($qText,'stipend')) {
        $stmt=$pdo->prepare('SELECT student_name, class_name, recommended_direction, ROUND(recommendation_confidence*100,1) confidence, ROUND(academic_stability*100,1) stability FROM student_predictions WHERE batch_id=? AND recommendation_confidence>=0.95 AND academic_stability>=0.85 ORDER BY recommendation_confidence DESC LIMIT 30');
        $stmt->execute([$batchId]);
        $rows=$stmt->fetchAll();
        return ['answer'=>'Grant va yuqori salohiyat monitoringi bo‘yicha yuqori confidence hamda barqarorlikka ega o‘quvchilar ro‘yxati shakllantirildi.', 'rows'=>$rows, 'type'=>'scholarship'];
    }

    if (str_contains($qText,'yo‘nalish') || str_contains($qText,'yonalish') || str_contains($qText,'tibbiyot') || str_contains($qText,'it') || str_contains($qText,'muhandis')) {
        $stmt=$pdo->prepare('SELECT recommended_direction, COUNT(*) students, ROUND(AVG(recommendation_confidence)*100,1) confidence FROM student_predictions WHERE batch_id=? GROUP BY recommended_direction ORDER BY students DESC');
        $stmt->execute([$batchId]);
        $rows=$stmt->fetchAll();
        $top = $rows[0]['recommended_direction'] ?? '-';
        return ['answer'=>'Yo‘nalishlar taqsimoti tahlil qilindi. Eng ustun yo‘nalish: '.$top.'. Yo‘nalishlarni rivojlantirish uchun mos fan to‘garaklari va kasbiy orientatsiya mashg‘ulotlari tavsiya qilinadi.', 'rows'=>$rows, 'type'=>'direction'];
    }

    $stmt=$pdo->prepare('SELECT COUNT(*) students, ROUND(AVG(recommendation_confidence)*100,1) confidence, ROUND(AVG(temporal_coverage_ratio)*100,1) coverage, ROUND(AVG(academic_stability)*100,1) stability FROM student_predictions WHERE batch_id=?');
    $stmt->execute([$batchId]);
    $summary=$stmt->fetch();
    return [
        'answer'=>"Oxirgi prediction bo‘yicha {$summary['students']} nafar o‘quvchi tahlil qilingan. O‘rtacha confidence {$summary['confidence']}%, coverage {$summary['coverage']}%, stability {$summary['stability']}%. Aniqroq javob uchun 'risk o‘quvchilar', 'sinf reytingi', 'grant nomzodlari' yoki 'yo‘nalishlar' deb so‘rashingiz mumkin.",
        'rows'=>[$summary],
        'type'=>'summary'
    ];
}
?>