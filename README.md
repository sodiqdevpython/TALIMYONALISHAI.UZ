# Educational Pathway AI — PHP + Python

Ushbu loyiha 11 yillik akademik va soft-skills dataset asosida o‘quvchi profilini klasterlash, ta’lim yo‘nalishini klassifikatsiyalash va web dashboard orqali natijalarni ko‘rsatadi.

## 1. O‘rnatish

Windows CMD orqali loyiha papkasida:

```bat
install.bat
```

Yoki qo‘lda:

```bat
python -m pip install -r requirements.txt
```

## 2. One-click запуск

```bat
one_click_start.bat
```

Bu fayl kutubxonalarni o‘rnatadi, modelni ishga tushiradi va dashboardni ochadi.

## 3. Modelni alohida ishga tushirish

```bat
run_model.bat
```

Yoki:

```bat
python python\train_model.py --input data\dataset.xlsx --out outputs
```

## 4. XAMPP joylashuvi

Papka quyidagicha joyda bo‘lishi mumkin:

```text
C:\xampp\htdocs\edu_ikkinchi_bob
```

Brauzerda:

```text
http://localhost/edu_ikkinchi_bob/web/index.php
```

## 5. Admin panel

```text
http://localhost/edu_ikkinchi_bob/web/admin.php
```

Default parol:

```text
admin123
```

## 6. Grafikalar

Model ishga tushgandan so‘ng quyidagi grafikalar yaratiladi:

- `outputs/figures/pca_clusters.png`
- `outputs/figures/confusion_matrix.png`
- `outputs/figures/roc_auc.png`
- `outputs/figures/feature_importance.png`
- `outputs/figures/shap_summary.png` (agar `shap` to‘g‘ri o‘rnatilgan bo‘lsa)

Web sahifa:

```text
/web/charts.php
```

## 7. Muhim ilmiy eslatma

Datasetda real ta’lim yo‘nalishi labeli bo‘lmasa, dastur dominant profil indekslari asosida pseudo-label yaratadi. Real label qo‘shilsa, model supervised classification rejimida qayta o‘qitiladi.
