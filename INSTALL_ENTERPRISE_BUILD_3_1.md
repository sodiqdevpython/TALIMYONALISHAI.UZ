# EduDirectionAI Professional Enterprise — Build 3.1 Installation

## Maqsad
Build 3.1 yangi prediction qilingan sinflar va o‘quvchilarni eski prediction natijalariga qo‘shib yubormaslik uchun `Prediction Isolation Architecture` ni joriy qiladi.

## O‘rnatish
1. Hozirgi `EduDirectionAI_Professional_Enterprise_3_0` papkasini backup qiling.
2. ZIP ichidagi fayllarni mavjud loyiha papkasi ustiga extract qiling.
3. Brauzerda direktor login bilan kiring.
4. `Prediction tarixi` sahifasini oching.
5. Kerakli prediction uchun `Active qilish` tugmasini bosing.
6. `O‘qituvchilar -> Sinflar` sahifasida prediction tanlab, o‘qituvchini sinfga biriktiring.

## Database
Build 3.1 avtomatik ravishda quyidagi ustunlarni tekshiradi va kerak bo‘lsa qo‘shadi:
- `classes.batch_id`
- `students.batch_id`
- `students.external_student_code`
- `teacher_classes.batch_id`
- `prediction_batches.is_active`

Qo‘lda migration qilish kerak bo‘lsa:
`database/migrations/003_prediction_isolation.sql`

## Muhim
Eski predictionlar saqlanib qoladi. Yangi predictionlar esa endi mustaqil snapshot sifatida yoziladi.
