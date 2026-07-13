# EduDirectionAI Professional v3.0

Professional v3.0 — maktab ma’muriyati, o‘quvchi va tadqiqotchi uchun AI Educational Decision Support System.

## v3.0 da qo‘shilgan modullar

1. **Maktab direktori dashboardi**
   - jami o‘quvchilar soni;
   - yo‘nalishlar bo‘yicha taqsimot;
   - past ishonchlilikdagi o‘quvchilar;
   - yo‘nalishi noaniq o‘quvchilar;
   - TOP-20 yuqori salohiyatli o‘quvchilar.

2. **Sinf kesimidagi tahlil**
   - 1–11 yoki 11-A/11-B kabi sinflar bo‘yicha saralash;
   - har bir sinfda IT, Muhandislik, Tibbiyot, Iqtisodiyot, Pedagogika taqsimoti.

3. **O‘quvchi rivojlanish trayektoriyasi**
   - 1-sinfdan 11-sinfgacha taxminiy temporal yo‘nalish;
   - yil/sinf bo‘yicha rivojlanish signallari.

4. **O‘quvchi kabineti**
   - login/parol orqali kirish;
   - yakuniy tavsiya;
   - 5 ta yo‘nalish bo‘yicha moslik foizlari;
   - tavsiya sababi;
   - boshqa yo‘nalishni tanlasa individual tavsiyalar.

5. **Maktab hisobot generatori**
   - `school_full_report.xlsx`;
   - `school_recommendations.xlsx`;
   - `student_logins.xlsx`;
   - chop etiladigan HTML/PDF hisobot.

6. **Amaliy tushuntirish moduli**
   - SHAP o‘rniga maktab uchun tushunarli sabablar;
   - kuchli profil indekslari;
   - kuchli soft-skills ko‘rsatkichlari.

## Ishga tushirish

1. `install.bat`
2. `one_click_start.bat`
3. Brauzerda oching:
   - `http://localhost:8000/web/index.php`

## Asosiy sahifalar

- Bosh sahifa: `web/index.php`
- Direktor dashboardi: `web/director_dashboard.php`
- Sinf tahlili: `web/class_analysis.php`
- Dataset jadvali: `web/dataset.php`
- Model dashboard: `web/model_dashboard.php`
- O‘quvchi kabineti: `web/student_login.php`
- Chop etiladigan hisobot: `web/print_school_report.php`

## Eslatma

Datasetda haqiqiy target bo‘lmasa, tizim profil indekslari asosida pseudo-label yaratadi.
Yakuniy tavsiya Logistic Regression ehtimolliklari orqali shakllantiriladi:

`Recommendation = argmax_j P(Y=j|X)`
