# EduDirectionAI Enterprise Build 4.3 Installation

## Yangilik
Admin uchun AI Model Lifecycle Manager qo‘shildi.

## O‘rnatish
1. Build 4.2 papkangizni backup qiling.
2. ZIP faylni loyiha papkasi ustiga extract qiling.
3. Super Admin login qiling.
4. `Model Manager` sahifasini oching.
5. Prediction dataset yonidagi `Train now` tugmasini bosing.
6. Yangi model versiya hosil bo‘ladi.
7. Natija yaxshi bo‘lsa, `Active qilish` tugmasini bosing.

## Database
Avtomatik schema tekshiruv mavjud.
Qo‘lda migration kerak bo‘lsa:
`database/migrations/006_model_lifecycle_build_4_3.sql`
