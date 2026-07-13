# EduDirectionAI Professional v2.1

Maktab ma’muriyati uchun amaliy dashboard va o‘quvchi kabineti qo‘shildi.

## Yangi imkoniyatlar

1. Dataset yuklangandan keyin jadval ko‘rinishida ma’lumotlarni ko‘rish.
2. Sinflar bo‘yicha saralash.
3. F.I.O yoki ID bo‘yicha qidirish.
4. Har bir o‘quvchi uchun:
   - F.I.O
   - ID
   - Sinf
   - yakuniy tavsiya etilgan yo‘nalish
   - ishonchlilik darajasi
   - 5 ta yo‘nalish bo‘yicha moslik foizlari
   - temporal ko‘rsatkichlar
   - tavsiya sababi
   - alternativ yo‘nalish
5. O‘quvchi login/parol bilan o‘z kabinetiga kirib natijalarini ko‘radi.
6. O‘quvchi boshqa yo‘nalishni tanlasa, shu yo‘nalish bo‘yicha tavsiyalar chiqadi.
7. Maktab ma’muriyati uchun alohida Excel hisobot:
   - `outputs/school_recommendations.xlsx`
   - `outputs/student_logins.xlsx`
8. Model natijalari uchun alohida dashboard:
   - `web/model_dashboard.php`

## Ishga tushirish

1. `install.bat`
2. `one_click_start.bat`
3. Brauzerda: `http://localhost:8000/web/index.php`

## O‘quvchi kabineti

`http://localhost:8000/web/student_login.php`

Login/parollar model o‘qitilgandan keyin `student_logins.xlsx` faylida shakllanadi.
