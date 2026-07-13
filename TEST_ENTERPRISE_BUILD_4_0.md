# EduDirectionAI Enterprise Build 4.0 Test Plan

## Test 1 — Master Registry
1. Datasetga `pinfl` yoki `national_student_id` ustunini qo‘shing.
2. Prediction bajaring.
3. Direktor -> Master Registry sahifasini oching.
4. O‘quvchi Master ID bilan ko‘rinishi kerak.

## Test 2 — Transfer scenario
1. Bir xil `pinfl` bilan 13-maktab datasetini import qiling.
2. Keyin boshqa maktabda shu `pinfl` bilan 20-maktab datasetini import qiling.
3. `master_students` jadvalida bitta Master Student yozuvi bo‘lishi kerak.
4. `student_school_history` jadvalida ikkala maktab tarixi ko‘rinishi kerak.

## Test 3 — Compatibility
Prediction, Analytics, Digital Twin, Teacher Assign va Student Profile avvalgidek ishlashi kerak.
