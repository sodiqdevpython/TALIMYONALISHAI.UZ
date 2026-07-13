# Professional v4.1 dastur natijalari

Datasetdagi jami o'quvchilar soni: 20000.
Featurelar soni: 35.

## Feature Engineering
Akademik ko'rsatkichlar, soft-skills indikatorlari va temporal dinamikalar asosida 31 ta xususiyat shakllantirildi.

## Klasterlash
K-Means: WCSS = 24655.2257, Silhouette = 0.2565, Davies-Bouldin = 1.2131.
GMM: AIC = 194364.8008, BIC = 194791.5891, Silhouette = 0.2249, Davies-Bouldin = 1.293.
GMM o'rtacha ishonchlilik = 0.8973.

## Klassifikatsiya
5 ta model o'qitildi: Logistic Regression, Random Forest, XGBoost, SVM, Neural Network.
Eng yaxshi model: Logistic Regression.
Accuracy = 0.9785, Precision = 0.9654, Recall = 0.986, F1-score = 0.9753.

## Tavsiya
Yakuniy tavsiya Logistic Regression ehtimolliklari asosida shakllantirildi: Recommendation = argmax_j P(Y=j|X).
O'rtacha confidence = 0.9534.

Eslatma: datasetda haqiqiy ta'lim yo'nalishi labeli mavjud bo'lmasa, yo'nalishlar profil indekslari asosida pseudo-label sifatida shakllantiriladi.
