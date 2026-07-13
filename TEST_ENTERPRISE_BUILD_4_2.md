# EduDirectionAI Enterprise Build 4.2 Test Plan

## Test 1 — Academic Year
Prediction upload sahifasida o‘quv yilini kiritmasdan prediction boshlang.
Kutiladi: xatolik chiqadi.

## Test 2 — Active / Inactive
Direktor -> Prediction tarixi.
Bitta predictionni Active qiling, keyin Noaktiv qiling.
Kutiladi: holat o‘zgaradi.

## Test 3 — Admin Prediction Manager
Super Admin -> Prediction Manager.
Predictionni Archive qiling, Restore qiling, Soft Delete qiling.
Kutiladi: lifecycle_status mos ravishda o‘zgaradi.

## Test 4 — Dataset Registry
Predictiondan keyin `dataset_registry` jadvalida dataset hash va academic_year yozilishi kerak.

## Test 5 — Model Manager
Super Admin -> Model Manager.
Prediction dataset uchun Retrain navbatga qo‘yish tugmasini bosing.
Kutiladi: `model_training_jobs` jadvalida queued job paydo bo‘ladi.
