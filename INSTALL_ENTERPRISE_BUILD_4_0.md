# EduDirectionAI Enterprise Build 4.0 Installation

## Maqsad
Build 4.0 Student Master Registry arxitekturasini joriy qiladi. Bu o‘quvchi maktab almashtirganda uning 1–11-sinf akademik va soft-skills tarixini yagona Master ID orqali bog‘lash uchun poydevor hisoblanadi.

## O‘rnatish
1. Oldingi loyiha papkangizni backup qiling.
2. ZIP faylni mavjud loyiha papkasi ustiga extract qiling.
3. Direktor login bilan kiring.
4. Yangi prediction bajaring.
5. `Master Registry` sahifasini oching.

## Dataset uchun tavsiya etilgan identifikator ustunlari
- `national_student_id`
- `pinfl`
- `JSHSHIR`
- `birth_date`
- `FIO`

Agar `pinfl` yoki `national_student_id` bo‘lsa, tizim o‘quvchini turli maktablar kesimida aniqroq bog‘laydi.

## Database
Avtomatik schema tekshiruv mavjud. Qo‘lda migration kerak bo‘lsa:
`database/migrations/004_student_master_registry.sql`
