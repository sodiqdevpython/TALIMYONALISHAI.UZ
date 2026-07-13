# EduDirectionAI Enterprise Build 4.3 Test Plan

## Test 1 — Train Now
1. Super Admin -> Model Manager.
2. Prediction datasetdan birini tanlang.
3. `Train now` bosing.
4. Yangi model versiya paydo bo‘lishi kerak.

## Test 2 — Metrics
Yangi model uchun Accuracy, Confidence, F1 va ROC-AUC ko‘rinishi kerak.

## Test 3 — Activate
Yangi modelda `Active qilish` bosing.
Faqat bitta model Active bo‘lishi kerak.

## Test 4 — Rollback
Oldingi modelni qayta Active qiling.
Tizim predictionda shu modeldan foydalanishi kerak.
