<?php
require_once __DIR__.'/../../config/auth.php';
require_once __DIR__.'/../../config/master_registry.php';
$u = require_role(['director']);
$pdo = db();
$schoolId = (int)$u['school_id'];
$id = (int)($_GET['prediction_id'] ?? 0);

$stmt = $pdo->prepare('
    SELECT sp.*, b.dataset_name, b.created_at AS batch_date, b.completed_at, b.id AS batch_no,
           m.model_name, m.model_version, s.school_name, s.school_code, s.region, s.district
    FROM student_predictions sp
    JOIN prediction_batches b ON b.id = sp.batch_id
    LEFT JOIN research_models m ON m.id = b.model_id
    LEFT JOIN schools s ON s.id = sp.school_id
    WHERE sp.id = ? AND sp.school_id = ?
    LIMIT 1
');
$stmt->execute([$id, $schoolId]);
$s = $stmt->fetch();
if (!$s) die('O‘quvchi prediction profili topilmadi yoki sizga tegishli emas.');

function pctv($v, $digits=1) {
    if ($v === null || $v === '') return '-';
    $x = (float)$v;
    if ($x <= 1.0) $x *= 100;
    return round($x, $digits).'%';
}
function score100($v) {
    if ($v === null || $v === '') return 0;
    $x = (float)$v;
    if ($x <= 1.0) $x *= 100;
    return max(0, min(100, $x));
}
function star_level($v) {
    $x = score100($v);
    $full = (int)round($x / 20);
    return str_repeat('★', $full).str_repeat('☆', max(0, 5-$full));
}
function direction_pack($direction) {
    $d = (string)$direction;
    $packs = [
        'IT' => [
            'universities' => ['TATU', 'INHA University in Tashkent', 'Amity University Tashkent', 'Toshkent davlat texnika universiteti'],
            'careers' => ['Dasturchi', 'Data Analyst', 'AI/ML mutaxassisi', 'Cybersecurity mutaxassisi', 'Web developer'],
            'plan' => ['Matematika va algoritmik fikrlash', 'Python/Web dasturlash', 'Ingliz tili', 'Ma’lumotlar tahlili', 'Portfolio loyihalar']
        ],
        'Muhandislik' => [
            'universities' => ['Toshkent davlat texnika universiteti', 'Turin Polytechnic University in Tashkent', 'Andijon mashinasozlik instituti', 'Farg‘ona politexnika instituti'],
            'careers' => ['Muhandis-konstruktor', 'Mexatronika mutaxassisi', 'Energetik', 'Robototexnika muhandisi', 'Loyiha muhandisi'],
            'plan' => ['Matematika', 'Fizika', 'Texnologiya va chizmachilik', 'Laboratoriya ishlari', 'Amaliy loyiha yaratish']
        ],
        'Tibbiyot' => [
            'universities' => ['Toshkent tibbiyot akademiyasi', 'Samarqand davlat tibbiyot universiteti', 'Andijon davlat tibbiyot instituti', 'Buxoro davlat tibbiyot instituti'],
            'careers' => ['Shifokor', 'Kardiolog', 'Farmatsevt', 'Laboratoriya mutaxassisi', 'Biotexnolog'],
            'plan' => ['Biologiya', 'Kimyo', 'Ingliz tili', 'Mas’uliyat va kommunikatsiya', 'Sog‘liqni saqlashga oid amaliy faoliyat']
        ],
        'Iqtisodiyot' => [
            'universities' => ['Toshkent davlat iqtisodiyot universiteti', 'Jahon iqtisodiyoti va diplomatiya universiteti', 'Westminster International University in Tashkent', 'Toshkent moliya instituti'],
            'careers' => ['Iqtisodchi', 'Moliyachi', 'Biznes analyst', 'Marketing mutaxassisi', 'Tadbirkorlik konsultanti'],
            'plan' => ['Matematika', 'Iqtisodiyot asoslari', 'Moliyaviy savodxonlik', 'Kommunikatsiya', 'Excel va data analytics']
        ],
        'Pedagogika' => [
            'universities' => ['Nizomiy nomidagi TDPU', 'Chirchiq davlat pedagogika universiteti', 'Qo‘qon davlat pedagogika instituti', 'Samarqand davlat universiteti'],
            'careers' => ['O‘qituvchi', 'Metodist', 'Ta’lim menejeri', 'Pedagog-psixolog', 'EdTech mutaxassisi'],
            'plan' => ['Ona tili va adabiyot', 'Kommunikatsiya', 'Jamoada ishlash', 'Metodika', 'Bolalar bilan amaliy faoliyat']
        ],
    ];
    return $packs[$d] ?? ['universities'=>['Mahalliy OTMlar'], 'careers'=>['Tanlangan yo‘nalish mutaxassisi'], 'plan'=>['Asosiy fanlar', 'Soft skills', 'Ingliz tili']];
}
function explain_text($s, $indices) {
    $dir = $s['recommended_direction'] ?: 'tanlangan yo‘nalish';
    $alt = $s['alternative_direction'] ?: 'alternativ yo‘nalish';
    $top = array_key_first($indices);
    $parts = [];
    if ((float)$s['academic_mean'] >= 75) $parts[] = 'akademik o‘rtacha ko‘rsatkichlari yuqori';
    if ((float)$s['growth_trend'] > 0) $parts[] = 'o‘sish tendensiyasi ijobiy';
    if ((float)$s['academic_stability'] >= 0.75) $parts[] = 'akademik barqarorlik yaxshi';
    if ((float)$s['temporal_coverage_ratio'] >= 0.90) $parts[] = '11 yillik ma’lumot qamrovi to‘liq';
    if (!$parts) $parts[] = 'profil indekslari orasida moslik mavjud';
    return 'Mazkur o‘quvchiga '.$dir.' yo‘nalishi tavsiya qilindi, chunki '.implode(', ', $parts).'. Eng kuchli indeks '.$top.' bo‘lib, model ushbu profilni asosiy yo‘nalish sifatida baholadi. '.$alt.' yo‘nalishi ham alternativ variant sifatida ko‘riladi.';
}

$indices = [
    'IT' => $s['IT_Index'],
    'Muhandislik' => $s['Engineering_Index'],
    'Tibbiyot' => $s['Medicine_Index'],
    'Iqtisodiyot' => $s['Economics_Index'],
    'Pedagogika' => $s['Pedagogy_Index']
];
arsort($indices);

$rowJson = [];
if (!empty($s['full_json'])) {
    $tmp = json_decode($s['full_json'], true);
    if (is_array($tmp)) $rowJson = $tmp;
}
$history = [];
if (!empty($rowJson['temporal_history'])) {
    $tmp = json_decode($rowJson['temporal_history'], true);
    if (is_array($tmp)) $history = $tmp;
}
if (!$history && (int)$s['temporal_years_count'] > 0) {
    for ($g=1; $g <= (int)$s['temporal_years_count']; $g++) {
        $history[] = ['grade'=>$g, 'year_mean'=>round((float)$s['academic_mean'],2), 'direction'=>$s['recommended_direction'], 'score'=>round((float)$s['recommendation_confidence']*100,2)];
    }
}
$timelineLabels = array_map(fn($x)=>($x['grade'] ?? '').'-sinf', $history);
$timelineData = array_map(fn($x)=>(float)($x['year_mean'] ?? 0), $history);

$soft = [
    'Communication' => $rowJson['Communication'] ?? null,
    'Teamwork' => $rowJson['Teamwork'] ?? null,
    'Leadership' => $rowJson['Leadership'] ?? null,
    'Creativity' => $rowJson['Creativity'] ?? null,
    'Critical Thinking' => $rowJson['Critical_Thinking'] ?? null,
    'Adaptability' => $rowJson['Adaptability'] ?? null,
];
foreach ($soft as $k=>$v) if ($v === null || $v === '') unset($soft[$k]);
if (!$soft) {
    $soft = ['Academic Stability'=>score100($s['academic_stability']), 'Temporal Coverage'=>score100($s['temporal_coverage_ratio']), 'Confidence'=>score100($s['recommendation_confidence'])];
}
$pack = direction_pack($s['recommended_direction']);
$explain = explain_text($s, $indices);
$confidence = score100($s['recommendation_confidence']);
$reliability = round(($confidence*0.50) + (score100($s['temporal_coverage_ratio'])*0.25) + (score100($s['academic_stability'])*0.25), 1);
$matchLabel = $confidence >= 90 ? 'HIGH MATCH' : ($confidence >= 75 ? 'GOOD MATCH' : 'NEEDS REVIEW');
?>
<!doctype html>
<html lang="uz">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>AI Career Passport — <?=auth_h($s['student_name'])?></title>
<link rel="stylesheet" href="../assets/style.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
<div class="container enterprise student-intel">
<header class="topbar">
    <div>
        <h1>AI Career Passport</h1>
        <p><?=auth_h($s['student_name'])?> · <?=auth_h($s['class_name'])?> · Prediction #<?=auth_h(str_pad($s['batch_no'],6,'0',STR_PAD_LEFT))?></p>
    </div>
    <nav class="nav">
        <a href="prediction_results.php?batch_id=<?=auth_h($s['batch_id'])?>">Prediction</a>
        <a href="student_passport.php?prediction_id=<?=auth_h($s['id'])?>" target="_blank">PDF/Print Passport</a><a href="digital_twin.php?prediction_id=<?=auth_h($s['id'])?>">Digital Twin</a><a href="future_simulation.php?prediction_id=<?=auth_h($s['id'])?>">Future Simulation</a>
        <a href="index.php">Direktor</a>
        <a href="../logout.php">Chiqish</a>
    </nav>
</header>

<section class="passport-hero">
    <div class="identity-card">
        <div class="avatar"><?=auth_h(mb_substr($s['student_name'],0,1,'UTF-8'))?></div>
        <div>
            <h2><?=auth_h($s['student_name'])?></h2>
            <p><b>Maktab:</b> <?=auth_h($s['school_name'])?></p>
            <p><b>Sinf:</b> <?=auth_h($s['class_name'])?> · <b>Student ID:</b> <?=auth_h($s['external_student_code'] ?: $s['student_id'])?></p>
            <p><b>Model:</b> <?=auth_h(($s['model_name'] ?? '').' '.($s['model_version'] ?? ''))?> · <b>Dataset:</b> <?=auth_h($s['dataset_name'])?></p>
        </div>
    </div>
    <div class="match-card">
        <span><?=$matchLabel?></span>
        <strong><?=auth_h($s['recommended_direction'])?></strong>
        <em><?=pctv($s['recommendation_confidence'])?></em>
        <div class="gauge"><i style="width:<?=$confidence?>%"></i></div>
        <p>Alternative: <b><?=auth_h($s['alternative_direction'])?></b> — <?=pctv($s['alternative_confidence'])?></p>
    </div>
</section>

<section class="cards sic-cards">
    <div class="card stat"><span>Reliability</span><strong><?=$reliability?>%</strong><small>confidence + coverage + stability</small></div>
    <div class="card stat"><span>Data Coverage</span><strong><?=pctv($s['temporal_coverage_ratio'])?></strong><small><?=auth_h($s['temporal_years_count'])?> yil</small></div>
    <div class="card stat"><span>Academic Stability</span><strong><?=pctv($s['academic_stability'])?></strong><small>barqarorlik indikatori</small></div>
    <div class="card stat"><span>Growth Trend</span><strong><?=auth_h(round((float)$s['growth_trend'],3))?></strong><small>longitudinal dinamika</small></div>
</section>

<section class="grid2">
    <div class="card"><h2>Yo‘nalish indekslari</h2><canvas id="radar" height="250"></canvas></div>
    <div class="card"><h2>11 yillik Academic Timeline</h2><canvas id="timeline" height="250"></canvas></div>
</section>

<section class="grid2">
    <div class="card">
        <h2>Soft Skills DNA</h2>
        <?php foreach($soft as $name=>$val): $v=score100($val); ?>
            <div class="skill-line">
                <span><?=auth_h($name)?></span><b><?=round($v,1)?>%</b>
                <div class="bar"><i style="width:<?=$v?>%"></i></div>
                <em><?=star_level($v)?></em>
            </div>
        <?php endforeach; ?>
    </div>
    <div class="card">
        <h2>Explainable AI 2.0</h2>
        <p class="ai-text"><?=auth_h($explain)?></p>
        <div class="factor-list">
            <?php foreach($indices as $k=>$v): ?>
                <div><span><?=auth_h($k)?></span><b><?=round((float)$v,1)?></b><div class="bar"><i style="width:<?=max(0,min(100,(float)$v))?>%"></i></div></div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<section class="grid2">
    <div class="card">
        <h2>Recommended Universities</h2>
        <ul class="smart-list">
            <?php foreach($pack['universities'] as $item): ?><li><?=auth_h($item)?></li><?php endforeach; ?>
        </ul>
    </div>
    <div class="card">
        <h2>Recommended Careers</h2>
        <ul class="smart-list">
            <?php foreach($pack['careers'] as $item): ?><li><?=auth_h($item)?></li><?php endforeach; ?>
        </ul>
    </div>
</section>

<section class="card">
    <h2>12 oylik rivojlanish rejasi</h2>
    <div class="roadmap">
        <?php foreach($pack['plan'] as $i=>$item): ?>
            <div class="road-step"><b><?=($i+1)?></b><span><?=auth_h($item)?></span></div>
        <?php endforeach; ?>
    </div>
    <div class="advice"><b>AI tavsiya:</b><br><?=auth_h($s['selected_direction_advice'])?></div>
</section>

<script>
const idxLabels = <?=json_encode(array_keys($indices), JSON_UNESCAPED_UNICODE)?>;
const idxData = <?=json_encode(array_map('floatval', array_values($indices)))?>;
new Chart(document.getElementById('radar'),{type:'radar',data:{labels:idxLabels,datasets:[{label:'Direction Index',data:idxData}]},options:{scales:{r:{beginAtZero:true,max:100}}}});
new Chart(document.getElementById('timeline'),{type:'line',data:{labels:<?=json_encode($timelineLabels, JSON_UNESCAPED_UNICODE)?>,datasets:[{label:'Academic mean',data:<?=json_encode($timelineData)?>,tension:.35,fill:false}]},options:{scales:{y:{beginAtZero:true,max:100}}}});
</script>
</div>
</body>
</html>
