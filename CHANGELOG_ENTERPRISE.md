## Enterprise Build 4.3 — AI Model Lifecycle Manager

### Added
- Haqiqiy Model Version Manager workflow:
  - Prediction datasetdan yangi model versiya yaratish.
  - Eski modelni saqlash.
  - Yangi model metrikalarini hisoblash.
  - Accuracy, Confidence, F1, ROC-AUC, Training Samples.
  - Eski va yangi model delta ko‘rsatkichlari.
  - Admin tomonidan Active modelni tanlash.
  - Rollback: avvalgi modelni qayta Active qilish.
- `config/model_lifecycle.php`.
- Migration: `database/migrations/006_model_lifecycle_build_4_3.sql`.

### Notes
- Build 4.3 deterministik benchmark/retrain workflow yaratadi.
- Python real retrain engine keyingi buildda shu helperga ulanadi.

## Enterprise Build 4.2 — AI Governance Platform

### Added
- Academic Year Manager: predictiondan oldin `O‘quv yili` majburiy kiritiladi.
- Prediction Lifecycle Manager: Active / Inactive direktor tomonidan boshqariladi.
- Super Admin Prediction Manager:
  - Active
  - Archive
  - Restore
  - Soft Delete
- Dataset Registry:
  - dataset hash
  - academic year
  - file size
  - quality JSON
- AI Model Version Manager:
  - model versiyalari
  - active model tanlash
  - prediction datasetni retrain navbatiga qo‘yish
- Model Training Jobs jadvali.
- Governance helper: `config/governance.php`.

### Changed
- Prediction batch endi `academic_year`, `prediction_title`, `lifecycle_status`, `dataset_hash` saqlaydi.
- Sinflar va transfer history prediction formadagi o‘quv yiliga bog‘lanadi.
- Direktor predictionni o‘chira olmaydi; o‘chirish/archivlash faqat Super Admin uchun.

### Notes
- Real Python retrain engine keyingi buildda ulanadi; Build 4.2 retrain workflow va job tracking poydevorini yaratadi.

## Enterprise Build 4.1 — Unified Longitudinal Digital Twin

### Fixed
- Bir o‘quvchi 20-maktabda 1–5-sinf, 21-maktabda 5–11-sinf o‘qigan holatda Digital Twin faqat joriy maktab tarixini ko‘rsatayotgan edi.
- Endi `master_student_id` bir xil bo‘lsa, Digital Twin barcha maktablardagi prediction tarixlarini birlashtirib, 1–11-sinf timeline hosil qiladi.

### Added
- Unified Student History kartasi.
- Timeline elementlarida qaysi sinf qaysi maktabdan kelgani ko‘rsatiladi.
- Coverage DNA endi joriy dataset emas, birlashtirilgan 11 yillik qamrov bo‘yicha hisoblanadi.

## Enterprise Build 4.0 — Student Master Registry

### Added
- Milliy/markaziy Student Master Registry arxitekturasi.
- `master_students` jadvali.
- `student_school_history` jadvali.
- `students.master_student_id`.
- `student_predictions.master_student_id`.
- `config/master_registry.php` helper.
- Direktor uchun `Master Registry` sahifasi: `web/director/master_students.php`.
- Prediction import jarayonida `national_student_id`, `pinfl`, `JSHSHIR`, `birth_date` kabi identifikatorlar orqali o‘quvchini yagona Master ID ga bog‘lash.

### Purpose
- O‘quvchi 13-maktabdan 20-maktabga o‘tsa ham, akademik va soft-skills tarixi yagona Digital Twin ostida birlashtirilishi uchun poydevor yaratildi.

## Enterprise Build 3.3 — AI Event Timeline

### Added
- `Risk Timeline` o‘rniga mazmunli `AI Event Timeline`.
- Har bir sinf uchun AI hodisa chiqariladi:
  - Baseline
  - Stable Progress
  - Academic Growth
  - Academic Drop
  - Direction Shift
  - Confidence Decrease
  - Recovery
  - High Performance
- Hodisalar rangli risk darajasi bilan ko‘rsatiladi.

### Improved
- Timeline endi faqat xavfni emas, rivojlanish trayektoriyasini ham tushuntiradi.

## Enterprise Build 3.2.1 — Digital Twin Risk Timeline Hotfix

### Fixed
- Risk Timeline har bir sinfni noto‘g‘ri `Academic Drop` deb chiqarishi tuzatildi.
- Sabab: `temporal_history.year_mean` ayrim datasetlarda 0–5 shkalada saqlangan, sahifa esa uni 0–100 deb qabul qilgan.
- Digital Twin endi akademik qiymatlarni avtomatik 0–100 shkalaga normallashtiradi.
- Risk Timeline endi faqat haqiqiy pasayish yoki past confidence bo‘lsa ogohlantiradi.

## Enterprise Build 3.2 — Digital Twin Intelligence UI

### Fixed
- `web/director/digital_twin.php` sahifasida Explainable Timeline o‘ng chetda siqilib ko‘rinishi tuzatildi.
- Explainable Timeline pastga, to‘liq eni bo‘yicha joylashtirildi.

### Added
- Digital Twin AI Analysis Card.
- Reliability, Growth, Stability, Consistency va Risk progress indikatorlari.
- Responsive timeline layout.
- Risk badge: Low / Medium / High.

## Enterprise Build 3.1.1 — Admin Protection Hotfix

### Fixed
- `web/index.php` sahifasi login/parolsiz ochilishi muammosi tuzatildi.
- Endi `web/index.php` faqat `super_admin` roli bilan kirganda ochiladi.
- Login qilinmagan foydalanuvchi avtomatik `web/login.php` sahifasiga yo‘naltiriladi.

## Enterprise Build 3.1 — Prediction Isolation Architecture

### Fixed
- Bir xil nomdagi sinflar (`11-sinf`) yangi predictionlarda avvalgi prediction sinfiga qo‘shilib ketishi muammosi tuzatildi.
- Yangi dataset predict qilinganda eski sinf va eski student yozuvlari bilan aralashish oldi olindi.

### Added
- `config/prediction_isolation.php` helper.
- `classes.batch_id`
- `students.batch_id`
- `students.external_student_code`
- `teacher_classes.batch_id`
- `prediction_batches.is_active`
- Prediction History sahifasida `Active qilish` funksiyasi.
- Teacher Assign sahifasida prediction tanlash.
- O‘qituvchi sinflari endi prediction snapshot bo‘yicha ko‘rsatiladi.
- Migration: `database/migrations/003_prediction_isolation.sql`

### Architecture
- Har bir prediction alohida snapshot sifatida ishlaydi.
- Bir xil sinf nomi turli predictionlarda alohida obyekt bo‘ladi.
- Teacher assignment endi `teacher_id + batch_id + class_id` mantiqi bilan ishlaydi.


## Enterprise 3.0 — AI Future Simulation

### Added
- AI Future Simulation / What-if Analysis: `web/director/future_simulation.php`
- Scenario parameters for subjects and soft skills.
- Simulated direction indices.
- AI scenario explanation.
- Digital Twin page includes AI Brain Summary.
- Prediction Results page includes `Sim` button.
- Student Profile page includes Future Simulation navigation.

### Preserved
- Enterprise 2.0 Digital Twin and all previous modules remain compatible.
- No database migration required.


## Enterprise 2.0 — AI Digital Twin Engine

### Added
- Student AI Digital Twin page: `web/director/digital_twin.php`
- AI Potential Index.
- Academic Evolution chart.
- AI Confidence Evolution simulation.
- Career Evolution Timeline.
- Explainable Timeline.
- Direction DNA.
- Risk Timeline.
- Prediction Results page includes Digital Twin link.
- Student Profile page includes Digital Twin navigation.

### Preserved
- Enterprise 1.0, Regional Intelligence, AI Lab, Copilot, Student/Teacher portals remain compatible.
- No database migration required.


## Enterprise 1.0 — Regional Intelligence & AI Laboratory

### Added
- Regional Intelligence Center: `web/admin/regional_dashboard.php`
- School Intelligence Passport: `web/admin/school_intelligence.php`
- AI Laboratory / Model Registry: `web/admin/ai_lab.php`
- National / regional / school AI Health Score.
- School ranking and region ranking.
- Direction distribution across all schools.
- Model registry and experiment monitoring.
- Build promoted from experimental 0.x to Enterprise 1.0 milestone.

### Preserved
- Build 0.1–0.9 modules remain compatible.
- No database migration required.


## Build 0.9 — EduDirectionAI GPT / Natural Language Analytics

### Added
- `app/Services/AIQueryService.php`
- Natural Language Query engine for Director Copilot.
- `web/api/ai_query.php` JSON API endpoint.
- Chat-style `web/director/ai_copilot.php`.
- Quick prompts: risk students, class ranking, grant candidates, directions, student list.
- Knowledge Base seed: `data/knowledge_base/edu_directionai_kb.json`.
- Activity log for NLQ queries.

### Preserved
- Build 0.1–0.8 modules remain compatible.
- No database migration required.


## Build 0.8 — AI Copilot & Smart Monitoring

### Added
- AI Principal Copilot: `web/director/ai_copilot.php`
- Rule-based AI Executive Summary based on real prediction data.
- Director Q&A assistant for school analytics.
- Smart Risk Monitoring 2.0: `web/director/smart_monitoring.php`
- AI Intervention Plan: `web/director/intervention_plan.php`
- Risk Score based on confidence, growth, academic stability and temporal coverage.
- Activity log for AI Copilot questions.

### Preserved
- Build 0.1–0.7 modules remain compatible.
- AI/Python engine unchanged.


## Build 0.7 — AI Analytics Center

### Added
- AI Analytics Center 2.0 for Director.
- School AI Health Score.
- Confidence Distribution histogram.
- Direction Distribution chart.
- Prediction Trend monitoring.
- Class Ranking with confidence/stability/growth.
- Risk Students table.
- Teacher Effectiveness table.
- AI Early Warning System.
- Printable AI School Report: `web/director/ai_report.php`.

### Preserved
- Build 0.1–0.6.1 modules remain compatible.
- AI/Python engine unchanged.

## Build 0.6.1 — Hotfix

### Fixed
- PHP fatal error: `Cannot redeclare pack()` in `web/student/index.php`.
- Renamed custom helper `pack()` to `student_direction_plan()` because `pack()` is a built-in PHP function.


## Build 0.6 — Student Portal & Personal AI Passport

### Added
- Director Student Account Manager: `web/director/student_accounts.php`
- Student login generation/synchronization from imported students.
- Student Portal: `web/student/index.php`
- Student personal AI Career Passport.
- Student PDF/Print page: `web/student/passport.php`
- Student can only see own AI recommendation result.
- Login page updated with student-account note.

### Preserved
- Director, Teacher, Prediction and AI Core modules remain compatible.
- No database migration required.


## Build 0.5 — Teacher Intelligence Center

### Added
- Director Teacher Management:
  - `web/director/teachers.php`
  - `web/director/teacher_create.php`
  - `web/director/teacher_assign.php`
- Teacher Workspace:
  - `web/teacher/index.php`
  - `web/teacher/class_view.php`
  - `web/teacher/student_view.php`
- Teacher can view only assigned classes through `teacher_classes`.
- Class-level AI monitoring, direction distribution chart, student list, search.
- Teacher view of student AI recommendation profile.

### Preserved
- AI Engine unchanged.
- Build 0.1–0.4 features remain compatible.


## Build 0.4 — Student Intelligence Center

### Added
- AI Career Passport profile page redesigned.
- Student Digital Passport block.
- AI Recommendation Match Gauge.
- Prediction Reliability metrics.
- 11-year Academic Timeline chart based on `temporal_history`.
- Soft Skills DNA block.
- Explainable AI 2.0 natural-language explanation.
- Recommended Universities and Career Recommendation Engine.
- 12-month Development Plan.
- Printable AI Career Passport page: `web/director/student_passport.php`.

### Preserved
- Existing Prediction Engine and Python AI Core are unchanged.
- Existing Build 0.1–0.3 login, school registration, prediction history and dashboard remain compatible.

# EduDirectionAI Professional Enterprise — Changelog

## Build 0.3
+ Director Dashboard 2.0 qo‘shildi.
+ Chart.js asosida yo‘nalishlar va confidence trend grafiklari qo‘shildi.
+ Prediction detail sahifasi qidiruv, filter va pagination bilan yangilandi.
+ Student Profile sahifasi qo‘shildi.
+ Explainable AI bloklari qo‘shildi.
+ Analytics Center qo‘shildi.
+ Build 0.2 funksiyalari saqlab qolindi.

## Build 0.2
+ Prediction Batch Manager.
+ Python natijalarini MySQL bazaga import qilish.
+ Prediction History va Prediction Results.

## Build 0.1
+ Login, role-based auth, school registration.
