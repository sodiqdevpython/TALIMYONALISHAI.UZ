# EduDirectionAI Professional v4.0

v4.0 — ko‘p maktabli AI Educational Decision Support System.

## Asosiy arxitektura

### 1. Research Mode
Master dataset asosida modelni o‘qitadi va tayyor model fayllarini saqlaydi:

- `outputs/models/classification_pipeline.joblib`
- `outputs/models/clustering_pipeline.joblib`

Bu rejim faqat Super Admin uchun.

### 2. Prediction Mode
Yangi maktab datasetini tayyor model orqali qayta o‘qitmasdan tahlil qiladi.

Natijalar maktab bo‘yicha alohida saqlanadi:

- `outputs/schools/<school_id>/school_prediction_results.xlsx`
- `outputs/schools/<school_id>/school_student_logins.xlsx`

### 3. School Login
Har bir maktab o‘z login/paroli bilan kiradi va faqat o‘z natijalarini ko‘radi.

Demo:

- login: `demo_school`
- password: `school123`

### 4. Adaptive Temporal Feature Engineering
Datasetda 11 yillik ma’lumot bo‘lmasa ham ishlaydi:

- `temporal_years_count`
- `temporal_coverage_ratio`
- `temporal_coverage_level`
- confidence moslashtirish

### 5. School Configuration
Ixtisoslashtirilgan maktablar uchun mavjud yo‘nalishlar belgilanadi.
Tizim ikki xil tavsiya beradi:

- Global recommendation
- School recommendation

### 6. Direction Manager
Agar K > 5 bo‘lsa, yangi yo‘nalish qo‘shish uchun fanlar, soft-skills, formula va tavsiya matni kiritiladi.

## Muhim sahifalar

- `web/index.php` — umumiy dashboard
- `web/research_mode.php` — modelni o‘qitish
- `web/prediction_mode.php` — tayyor model bilan yangi maktab datasetini tahlil qilish
- `web/school_login.php` — maktab kabineti
- `web/school_dashboard.php` — maktab dashboardi
- `web/school_students.php` — maktab o‘quvchilari
- `web/school_config.php` — maktab sozlamalari
- `web/direction_manager.php` — yo‘nalishlar boshqaruvi
- `web/master_dataset.php` — master dataset
- `web/student_login.php` — o‘quvchi kabineti

## Ishga tushirish

1. `install.bat`
2. `one_click_start.bat`
3. Brauzerda oching:

`http://localhost:8000/web/index.php`

## Ishlash tartibi

1. Research Mode orqali 20 000 o‘quvchi datasetida modelni o‘qiting.
2. Prediction Mode orqali yangi maktab datasetini yuklang.
3. Maktab dashboardida natijalarni ko‘ring.
4. Kerak bo‘lsa, Super Admin yangi datasetlarni master datasetga qo‘shib modelni yangilaydi.
