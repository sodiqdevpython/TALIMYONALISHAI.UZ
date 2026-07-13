# Educational Pathway AI — Professional v2.0

Ushbu versiyada dissertatsiyadagi 4 ta asosiy algoritmik talabga mos tuzatishlar kiritildi.

## Kiritilgan asosiy yangiliklar

1. **K-Means + GMM yashirin profil algoritmi**
   - `KMeans(n_clusters=5)`
   - `GaussianMixture(n_components=5)`
   - GMM ehtimolliklari va `gmm_max_probability` saqlanadi.

2. **31 ta xususiyatdan iborat Feature Engineering**
   - Akademik ko'rsatkichlar
   - Soft-skills indikatorlari
   - Temporal indikatorlar: `growth_trend`, `learning_dynamics`, `academic_stability`, `temporal_consistency`, `skill_evolution`
   - Profil indekslari: `IT_Index`, `Engineering_Index`, `Medicine_Index`, `Economics_Index`, `Pedagogy_Index`

3. **5 ta klassifikatsiya algoritmi**
   - Logistic Regression
   - Random Forest
   - XGBoost
   - SVM
   - Neural Network

4. **Logistic Regression asosidagi yakuniy tavsiya mexanizmi**
   - `Recommendation = argmax_j P(Y=j|X)`
   - Har bir yo'nalish bo'yicha ehtimollik chiqariladi.
   - Alternativ yo'nalish va confidence ko'rsatiladi.

5. **Professional Dashboard**
   - Accuracy, Precision, Recall, F1-score, ROC-AUC
   - K-Means/GMM metrikalari
   - Model comparison
   - Student probability view
   - Recommendation reason

## Ishga tushirish

1. `install.bat`
2. `one_click_start.bat`
3. Brauzerda `http://127.0.0.1:8000/web/index.php`

## Admin panel

Default parol: `admin123`

Xavfsizlik uchun production rejimda `EDUAI_ADMIN_HASH` environment orqali hash parol o'rnatish tavsiya etiladi.
