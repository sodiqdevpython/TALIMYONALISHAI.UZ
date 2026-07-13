# EduDirectionAI Enterprise Build 3.1 Test Plan

## Test 1 — Yangi prediction
1. Direktor login qiling.
2. `+ Prediction` orqali yangi `.xlsx` dataset yuklang.
3. Prediction History da yangi batch paydo bo‘lishi kerak.

## Test 2 — Sinf qo‘shilib ketmasligi
1. Avvalgi predictionda `11-sinf` 99 o‘quvchi bo‘lsin.
2. Yangi predictionda `11-sinf` 20 o‘quvchi bo‘lsin.
3. Teacher Assign sahifasida prediction tanlang.
4. Prediction #1 tanlansa: `11-sinf — 99 o‘quvchi`.
5. Prediction #2 tanlansa: `11-sinf — 20 o‘quvchi`.

## Test 3 — O‘qituvchi biriktirish
1. Mavjud o‘qituvchini Prediction #2 dagi 11-sinfga biriktiring.
2. Teacher login qiling.
3. O‘qituvchi faqat biriktirilgan prediction snapshotini ko‘rishi kerak.

## Test 4 — Active prediction
1. Prediction History sahifasida boshqa batchni Active qiling.
2. History da `ACTIVE` badge chiqishi kerak.

## Test 5 — Oldingi modullar
Prediction Results, Analytics, Student Profile va Teacher View ishlashini tekshiring.
