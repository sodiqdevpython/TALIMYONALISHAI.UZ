# EduDirectionAI Enterprise Build 4.2 Installation

## Yangiliklar
Build 4.2 AI Governance Platform funksiyalarini qo‘shadi:
- Academic Year majburiy maydoni
- Prediction Active/Inactive
- Super Admin Archive / Restore / Soft Delete
- Dataset Registry
- AI Model Manager
- Retrain Job queue

## O‘rnatish
1. Build 4.1 papkangizni backup qiling.
2. ZIP faylni loyiha papkasi ustiga extract qiling.
3. Direktor login qiling.
4. `+ Prediction` sahifasida o‘quv yilini `2025-2026` formatida kiriting.
5. Super Admin login qiling.
6. `Prediction Manager` va `Model Manager` sahifalarini tekshiring.

## Database
Avtomatik schema tekshiruv mavjud.
Qo‘lda migration kerak bo‘lsa:
`database/migrations/005_ai_governance_build_4_2.sql`
