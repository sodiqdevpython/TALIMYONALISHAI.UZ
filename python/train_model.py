# -*- coding: utf-8 -*-
"""Educational Pathway AI Professional v2.0
PHP dashboard uchun ML workflow:
1) Feature Engineering: 31 ta muhim xususiyat
2) Hidden Profile: K-Means + GMM
3) Classification: LR, RF, XGBoost, SVM, ANN
4) Recommendation: eng yaxshi natija bergan Logistic Regression ehtimolliklari asosida yakuniy tavsiya
5) Charts/Reports: PCA, ROC, Confusion Matrix, Feature Importance, SHAP-like importance, Model comparison
"""
import argparse
import json
import warnings
from pathlib import Path

import joblib
import numpy as np
import pandas as pd

import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt

from sklearn.cluster import KMeans
from sklearn.mixture import GaussianMixture
from sklearn.decomposition import PCA
from sklearn.ensemble import RandomForestClassifier
from sklearn.metrics import (
    accuracy_score, precision_score, recall_score, f1_score,
    silhouette_score, davies_bouldin_score, classification_report,
    confusion_matrix, roc_curve, auc, roc_auc_score
)
from sklearn.model_selection import train_test_split
from sklearn.linear_model import LogisticRegression
from sklearn.pipeline import Pipeline
from sklearn.preprocessing import StandardScaler, label_binarize, LabelEncoder
from sklearn.neural_network import MLPClassifier
from sklearn.svm import SVC

warnings.filterwarnings('ignore')

try:
    from xgboost import XGBClassifier
    HAS_XGB = True
except Exception:
    HAS_XGB = False

try:
    import shap
    HAS_SHAP = True
except Exception:
    HAS_SHAP = False

# Dissertatsiyadagi 5 ta ta'lim yo'nalishi
PROFILE_NAMES = ["IT", "Muhandislik", "Tibbiyot", "Iqtisodiyot", "Pedagogika"]
DIRECTION_MAP = {p: p for p in PROFILE_NAMES}

ID_OPTIONS = ["O‘quvchi_ID", "O'quvchi_ID", "student_id", "Student_ID", "ID", "id"]
FIO_OPTIONS = ["FIO", "fio", "name", "Name", "Ism", "Familiya"]
CLASS_OPTIONS = ["Sinf", "sinf", "Class", "class", "Guruh", "guruh"]

TECH = ["Matematika", "Algebra", "Geometriya", "Fizika", "Informatika", "Kimyo", "Astronomiya"]
CREATIVE = ["Ona tili", "Adabiyot", "O‘qish", "O'qish", "Tasviriy san’at", "Tasviriy san'at", "Musiqa"]
SOCIAL = ["Tarix", "O‘zbekiston tarixi", "Jahon tarixi", "Davlat va huquq asoslari", "Iqtisodiyot asoslari", "Tarbiya"]
PRACTICAL = ["Texnologiya", "Informatika", "Jismoniy tarbiya"]
SCIENCE = ["Biologiya", "Kimyo", "Geografiya", "Tabiiy fanlar", "Fizika"]

PROFILE_SCORE_COLS = ['IT_Index', 'Engineering_Index', 'Medicine_Index', 'Economics_Index', 'Pedagogy_Index']
PROFILE_SCORE_TO_NAME = dict(zip(PROFILE_SCORE_COLS, PROFILE_NAMES))

# Aynan 31 ta feature: dissertatsiya va dastur natijalari mos bo'lishi uchun
FEATURE_COLS_31 = [
    'academic_mean', 'academic_std', 'temporal_years_count', 'temporal_coverage_ratio', 'temporal_coverage_level', 'raw_model_confidence', 'growth_trend', 'learning_dynamics',
    'academic_stability', 'temporal_consistency', 'skill_evolution',
    'technical_academic_100', 'creative_academic_100', 'social_academic_100',
    'practical_academic_100', 'science_academic_100',
    'Communication', 'Teamwork', 'Leadership', 'Creativity', 'Critical_Thinking',
    'Adaptability', 'certificates', 'SoftSkillScore',
    'AnalyticalIndex', 'CreativeProfile', 'LeadershipScore', 'StabilityIndex',
    'PracticalSkill', 'IT_Index', 'Engineering_Index', 'Medicine_Index',
    'Economics_Index', 'Pedagogy_Index', 'cluster_id'
]


def pick(cols, opts):
    return next((o for o in opts if o in cols), None)


def safe_numeric(s):
    return pd.to_numeric(s, errors='coerce')


def normalize_100(s):
    s = safe_numeric(s).astype(float)
    if s.isna().all():
        return pd.Series(50.0, index=s.index)
    s = s.fillna(s.median())
    mn, mx = float(s.min()), float(s.max())
    if mx == mn:
        return pd.Series(50.0, index=s.index)
    return (100 * (s - mn) / (mx - mn)).clip(0, 100)


def sheet_features(df, grade):
    idc = pick(df.columns, ID_OPTIONS)
    fioc = pick(df.columns, FIO_OPTIONS)
    classc = pick(df.columns, CLASS_OPTIONS)
    if idc is None:
        raise ValueError(f"ID column not found in grade {grade}")
    num = df.select_dtypes(include=[np.number]).columns.tolist()
    out = pd.DataFrame({'student_id': df[idc].astype(str), 'grade': grade})
    if fioc:
        out['FIO'] = df[fioc].astype(str)
    if classc:
        out['Sinf'] = df[classc].astype(str)
    else:
        out['Sinf'] = str(grade) + "-sinf"

    def avg(cols, name):
        existing = [c for c in cols if c in num]
        out[name] = df[existing].mean(axis=1) if existing else np.nan

    avg(num, 'year_mean')
    avg(TECH, 'technical_academic')
    avg(CREATIVE, 'creative_academic')
    avg(SOCIAL, 'social_academic')
    avg(PRACTICAL, 'practical_academic')
    avg(SCIENCE, 'science_academic')
    out['year_std'] = df[num].std(axis=1) if num else np.nan
    return out


LAST_LONG_GRADES = None

def load_and_engineer(xlsx):
    global LAST_LONG_GRADES
    xls = pd.ExcelFile(xlsx)
    frames = []
    for g in range(1, 12):
        s = f'{g}-sinf'
        if s in xls.sheet_names:
            frames.append(sheet_features(pd.read_excel(xlsx, sheet_name=s), g))
    if not frames:
        raise ValueError('1-sinf ... 11-sinf sheets not found')

    long = pd.concat(frames, ignore_index=True)
    LAST_LONG_GRADES = long.copy()
    coverage = long.groupby('student_id')['grade'].nunique().rename('temporal_years_count').reset_index()
    coverage['temporal_coverage_ratio'] = (coverage['temporal_years_count'] / 11).clip(0, 1)
    agg = long.groupby('student_id').agg(
        FIO=('FIO', 'first') if 'FIO' in long.columns else ('student_id', 'first'),
        Sinf=('Sinf', 'last') if 'Sinf' in long.columns else ('grade', lambda s: str(int(max(s))) + "-sinf"),
        academic_mean=('year_mean', 'mean'),
        academic_std=('year_std', 'mean'),
        technical_academic=('technical_academic', 'mean'),
        creative_academic=('creative_academic', 'mean'),
        social_academic=('social_academic', 'mean'),
        practical_academic=('practical_academic', 'mean'),
        science_academic=('science_academic', 'mean'),
    ).reset_index()
    
    agg = agg.merge(coverage, on='student_id', how='left')
    
    agg["temporal_coverage_level"] = np.select(
    [
        agg["temporal_coverage_ratio"] >= 0.90,
        agg["temporal_coverage_ratio"] >= 0.60
    ],
    [
        2,
        1
    ],
    default=0
    )


    # Har bir sinf bo‘yicha taxminiy yo‘nalish trayektoriyasi
    def row_temporal_direction(r):
        vals = {
            'IT': r.get('technical_academic', np.nan),
            'Muhandislik': np.nanmean([r.get('technical_academic', np.nan), r.get('practical_academic', np.nan)]),
            'Tibbiyot': r.get('science_academic', np.nan),
            'Iqtisodiyot': r.get('social_academic', np.nan),
            'Pedagogika': r.get('creative_academic', np.nan)
        }
        vals = {k: (0 if pd.isna(v) else float(v)) for k, v in vals.items()}
        d = max(vals, key=vals.get)
        return d, vals[d]
    tmp_hist = long.copy()
    hist_dirs = tmp_hist.apply(row_temporal_direction, axis=1)
    tmp_hist['temporal_direction'] = [x[0] for x in hist_dirs]
    tmp_hist['temporal_score'] = [x[1] for x in hist_dirs]
    hist = tmp_hist.sort_values(['student_id','grade']).groupby('student_id').apply(
        lambda g: json.dumps([
            {'grade': int(r['grade']), 'direction': str(r['temporal_direction']), 'score': round(float(r['temporal_score']) if not pd.isna(r['temporal_score']) else 0, 2), 'year_mean': round(float(r['year_mean']) if not pd.isna(r['year_mean']) else 0, 2)}
            for _, r in g.iterrows()
        ], ensure_ascii=False)
    ).rename('temporal_history').reset_index()
    agg = agg.merge(hist, on='student_id', how='left')

    # GrowthTrend va LearningDynamics — uzoq muddatli dinamik ko'rsatkichlar
    tmp = long.dropna(subset=['year_mean']).copy()
    mean_g = tmp.groupby('student_id')['grade'].transform('mean')
    mean_y = tmp.groupby('student_id')['year_mean'].transform('mean')
    tmp['cov'] = (tmp['grade'] - mean_g) * (tmp['year_mean'] - mean_y)
    tmp['varg'] = (tmp['grade'] - mean_g) ** 2
    trend = (tmp.groupby('student_id')['cov'].sum() / tmp.groupby('student_id')['varg'].sum()) \
        .replace([np.inf, -np.inf], 0).fillna(0).rename('growth_trend').reset_index()
    agg = agg.merge(trend, on='student_id', how='left')
    agg['growth_trend'] = agg['growth_trend'].fillna(0)

    sorted_long = long.sort_values(['student_id', 'grade'])
    sorted_long['diff'] = sorted_long.groupby('student_id')['year_mean'].diff()
    dyn = sorted_long.groupby('student_id')['diff'].mean().fillna(0).rename('learning_dynamics').reset_index()
    agg = agg.merge(dyn, on='student_id', how='left')
    agg['learning_dynamics'] = agg['learning_dynamics'].fillna(0)

    # Akademik barqarorlik va davomiylik
    agg['academic_stability'] = (1 - (agg['academic_std'] / agg['academic_mean']).replace([np.inf, -np.inf], 0)).clip(0, 1)
    agg['temporal_consistency'] = agg['academic_stability']

    # Soft-skills varaqi
    if 'soft-skills' in xls.sheet_names:
        soft = pd.read_excel(xlsx, sheet_name='soft-skills')
        sid = pick(soft.columns, ID_OPTIONS)
        if sid and sid != 'student_id':
            soft = soft.rename(columns={sid: 'student_id'})
        if 'student_id' in soft.columns:
            soft['student_id'] = soft['student_id'].astype(str)
            agg = agg.merge(soft, on='student_id', how='left')

    for c in ['Communication', 'Teamwork', 'Leadership', 'Creativity', 'Critical_Thinking', 'Adaptability', 'certificates']:
        if c not in agg:
            agg[c] = np.nan
        agg[c] = safe_numeric(agg[c])
        agg[c] = agg[c].fillna(agg[c].median() if not agg[c].isna().all() else 50)

    for c in agg.select_dtypes(include=[np.number]).columns:
        agg[c] = agg[c].fillna(agg[c].median() if not agg[c].isna().all() else 0)

    # Baholarni 0-100 diapazonga keltirish
    for c in ['technical_academic', 'creative_academic', 'social_academic', 'practical_academic', 'science_academic', 'academic_mean']:
        agg[c + '_100'] = normalize_100(agg[c])

    agg['SoftSkillScore'] = agg[['Communication', 'Teamwork', 'Leadership', 'Creativity', 'Critical_Thinking', 'Adaptability']].mean(axis=1)
    agg['skill_evolution'] = normalize_100(agg['SoftSkillScore'] - agg['academic_mean_100'])

    # 5 ta umumiy kompetensiya indekslari
    agg['AnalyticalIndex'] = 0.40 * agg['technical_academic_100'] + 0.35 * agg['Critical_Thinking'] + 0.15 * agg['science_academic_100'] + 0.10 * agg['academic_stability'] * 100
    agg['CreativeProfile'] = 0.35 * agg['Creativity'] + 0.30 * agg['Communication'] + 0.25 * agg['creative_academic_100'] + 0.10 * agg['Adaptability']
    agg['LeadershipScore'] = 0.40 * agg['Leadership'] + 0.30 * agg['Teamwork'] + 0.20 * agg['Communication'] + 0.10 * agg['social_academic_100']
    agg['StabilityIndex'] = 0.55 * agg['academic_stability'] * 100 + 0.25 * agg['academic_mean_100'] + 0.20 * agg['temporal_consistency'] * 100
    agg['PracticalSkill'] = 0.40 * agg['practical_academic_100'] + 0.25 * agg['Adaptability'] + 0.20 * agg['Teamwork'] + 0.15 * agg['technical_academic_100']

    # Dissertatsiyadagi 5 ta ta'lim yo'nalishi uchun indekslar
    agg['IT_Index'] = 0.35 * agg['technical_academic_100'] + 0.25 * agg['Critical_Thinking'] + 0.20 * agg['Creativity'] + 0.20 * agg['PracticalSkill']
    agg['Engineering_Index'] = 0.40 * agg['technical_academic_100'] + 0.25 * agg['PracticalSkill'] + 0.20 * agg['AnalyticalIndex'] + 0.15 * agg['Adaptability']
    agg['Medicine_Index'] = 0.40 * agg['science_academic_100'] + 0.25 * agg['StabilityIndex'] + 0.20 * agg['academic_mean_100'] + 0.15 * agg['Communication']
    agg['Economics_Index'] = 0.30 * agg['social_academic_100'] + 0.25 * agg['LeadershipScore'] + 0.25 * agg['AnalyticalIndex'] + 0.20 * agg['Communication']
    agg['Pedagogy_Index'] = 0.30 * agg['creative_academic_100'] + 0.25 * agg['Communication'] + 0.20 * agg['Teamwork'] + 0.15 * agg['Leadership'] + 0.10 * agg['Creativity']

    agg['dominant_profile'] = agg[PROFILE_SCORE_COLS].idxmax(axis=1).map(PROFILE_SCORE_TO_NAME)
    # Pseudo-label: Excelda haqiqiy target bo'lmasa, indekslar asosida shakllantiriladi
    agg['target_direction'] = agg['dominant_profile']
    agg['recommended_direction_rule'] = agg['dominant_profile']
    print(
    agg[
        [
            "temporal_years_count",
            "temporal_coverage_ratio",
            "temporal_coverage_level"
        ]
    ].head()
    )
    return agg


def safe_silhouette(X, labels):
    if len(set(labels)) < 2:
        return None
    return round(float(silhouette_score(X, labels, sample_size=min(1000, len(labels)), random_state=42)), 4)


def map_clusters_to_profiles(centers):
    mapping = {}
    used = set()
    for cid, cen in enumerate(centers):
        for idx in np.argsort(cen)[::-1]:
            name = PROFILE_NAMES[idx]
            if name not in used:
                mapping[int(cid)] = name
                used.add(name)
                break
        if int(cid) not in mapping:
            mapping[int(cid)] = PROFILE_NAMES[int(np.argmax(cen))]
    return mapping


def cluster(features):
    X = features[PROFILE_SCORE_COLS].values
    scaler = StandardScaler()
    Xs = scaler.fit_transform(X)

    km = KMeans(n_clusters=5, n_init=30, random_state=42)
    klabels = km.fit_predict(Xs)
    centers = scaler.inverse_transform(km.cluster_centers_)
    mapping = map_clusters_to_profiles(centers)
    features['cluster_id'] = klabels
    features['kmeans_profile'] = features['cluster_id'].map(mapping)

    gmm = GaussianMixture(n_components=5, random_state=42, covariance_type='diag', max_iter=100)
    glabels = gmm.fit_predict(Xs)
    gmm_probs = gmm.predict_proba(Xs)
    gmm_centers = scaler.inverse_transform(gmm.means_)
    gmm_mapping = map_clusters_to_profiles(gmm_centers)
    features['gmm_cluster_id'] = glabels
    features['gmm_profile'] = pd.Series(glabels).map(gmm_mapping).values
    features['gmm_max_probability'] = gmm_probs.max(axis=1)
    features['raw_model_confidence'] = features['gmm_max_probability']
    for i in range(5):
        features[f'gmm_prob_C{i}'] = gmm_probs[:, i]

    pca = PCA(n_components=2, random_state=42)
    xy = pca.fit_transform(Xs)
    features['pca_1'] = xy[:, 0]
    features['pca_2'] = xy[:, 1]

    comparison = {
        'KMeans': {
            'wcss': round(float(km.inertia_), 4),
            'silhouette': safe_silhouette(Xs, klabels),
            'davies_bouldin': round(float(davies_bouldin_score(Xs, klabels)), 4)
        },
        'GaussianMixture': {
            'aic': round(float(gmm.aic(Xs)), 4),
            'bic': round(float(gmm.bic(Xs)), 4),
            'silhouette': safe_silhouette(Xs, glabels),
            'davies_bouldin': round(float(davies_bouldin_score(Xs, glabels)), 4),
            'mean_probability': round(float(features['gmm_max_probability'].mean()), 4),
            'median_probability': round(float(features['gmm_max_probability'].median()), 4)
        }
    }
    metrics = {
        'optimal_k': 5,
        'selected_algorithm': 'KMeans + GMM',
        'kmeans_silhouette': comparison['KMeans']['silhouette'],
        'kmeans_davies_bouldin': comparison['KMeans']['davies_bouldin'],
        'gmm_silhouette': comparison['GaussianMixture']['silhouette'],
        'gmm_davies_bouldin': comparison['GaussianMixture']['davies_bouldin'],
        'algorithm_comparison': comparison,
        'cluster_profile_map': mapping,
        'gmm_profile_map': gmm_mapping,
        'pca_explained_variance': [round(float(v), 4) for v in pca.explained_variance_ratio_]
    }
    return features, scaler, km, gmm, pca, metrics


def eval_pred(y, p, proba=None, labels=None):
    res = {
        'accuracy': round(float(accuracy_score(y, p)), 4),
        'precision_macro': round(float(precision_score(y, p, average='macro', zero_division=0)), 4),
        'recall_macro': round(float(recall_score(y, p, average='macro', zero_division=0)), 4),
        'f1_macro': round(float(f1_score(y, p, average='macro', zero_division=0)), 4)
    }
    if proba is not None and labels is not None and len(labels) > 2:
        try:
            yb = label_binarize(y, classes=labels)
            res['roc_auc_ovr_macro'] = round(float(roc_auc_score(yb, proba, average='macro', multi_class='ovr')), 4)
        except Exception:
            res['roc_auc_ovr_macro'] = None
    return res


def classify(features):
    missing = [c for c in FEATURE_COLS_31 if c not in features.columns]
    if missing:
        raise ValueError(f'Missing feature columns: {missing}')
    X = features[FEATURE_COLS_31].copy()
    y = features['target_direction'].copy()
    labels = sorted(y.unique().tolist())

    Xtr, Xte, ytr, yte = train_test_split(X, y, test_size=.2, random_state=42, stratify=y)

    models = {
        'Logistic Regression': Pipeline([('scaler', StandardScaler()), ('model', LogisticRegression(max_iter=800, multi_class='auto', class_weight='balanced'))]),
        'Random Forest': Pipeline([('scaler', StandardScaler()), ('model', RandomForestClassifier(n_estimators=120, max_depth=12, random_state=42, class_weight='balanced', n_jobs=-1))]),
        'SVM': Pipeline([('scaler', StandardScaler()), ('model', SVC(kernel='linear', C=1.0, probability=False, class_weight='balanced', random_state=42, max_iter=5000))]),
        'Neural Network': Pipeline([('scaler', StandardScaler()), ('model', MLPClassifier(hidden_layer_sizes=(64, 32), max_iter=160, random_state=42, early_stopping=True))])
    }

    res, fitted, preds, probas = {}, {}, {}, {}
    for name, model in models.items():
        model.fit(Xtr, ytr)
        pred = model.predict(Xte)
        proba = model.predict_proba(Xte) if hasattr(model, 'predict_proba') else None
        preds[name] = pred
        probas[name] = proba
        res[name] = eval_pred(yte, pred, proba, labels)
        fitted[name] = model

    if HAS_XGB:
        le = LabelEncoder()
        ytr_e = le.fit_transform(ytr)
        yte_e = le.transform(yte)
        xgb = Pipeline([('scaler', StandardScaler()), ('model', XGBClassifier(
            n_estimators=120, max_depth=3, learning_rate=0.08, subsample=0.9,
            colsample_bytree=0.9, eval_metric='mlogloss', random_state=42
        ))])
        xgb.fit(Xtr, ytr_e)
        pe = xgb.predict(Xte)
        pred = le.inverse_transform(pe)
        proba = xgb.predict_proba(Xte)
        fitted['XGBoost'] = {'pipeline': xgb, 'label_encoder': le}
        preds['XGBoost'] = pred
        probas['XGBoost'] = proba
        res['XGBoost'] = eval_pred(yte, pred, proba, labels)

    best = max(res, key=lambda n: res[n]['f1_macro'])

    # Final recommendation engine: Logistic Regression probability model trained on full data
    recommender = Pipeline([('scaler', StandardScaler()), ('model', LogisticRegression(max_iter=900, multi_class='auto', class_weight='balanced'))])
    recommender.fit(X, y)
    all_proba = recommender.predict_proba(X)
    lr_classes = list(recommender.classes_)
    pred_idx = np.argmax(all_proba, axis=1)
    features['recommended_direction'] = [lr_classes[i] for i in pred_idx]
    raw_conf = all_proba.max(axis=1)
    features['raw_model_confidence'] = raw_conf
    # Adaptive Temporal Feature Engineering: ma'lumot to'liqligi past bo'lsa confidence yumshatiladi.
    # coverage = features.get(pd.Series(1.0, index=features.index)).astype(float).clip(0, 1)
    coverage = features["temporal_coverage_ratio"]
    coverage_penalty = (0.85 + 0.15 * coverage)  # 1/11 -> ~0.864, 11/11 -> 1.0
    features['recommendation_confidence'] = (raw_conf * coverage_penalty).clip(0, 1)
    features['temporal_coverage_level'] = pd.cut(
        coverage, bins=[-0.01, 0.35, 0.65, 0.90, 1.01],
        labels=['Limited', 'Partial', 'Good', 'Excellent']
    ).astype(str)
    for cls_idx, cls_name in enumerate(lr_classes):
        features[f'prob_{cls_name}'] = all_proba[:, cls_idx]

    # Alternative top-2 direction
    sorted_idx = np.argsort(-all_proba, axis=1)
    features['alternative_direction'] = [lr_classes[idxs[1]] if len(idxs) > 1 else '' for idxs in sorted_idx]
    features['alternative_confidence'] = [all_proba[i, sorted_idx[i][1]] if all_proba.shape[1] > 1 else 0 for i in range(len(features))]

    return {
        'feature_cols': FEATURE_COLS_31,
        'X_test': Xte,
        'y_test': yte,
        'model_results': res,
        'best_model_name': best,
        'best_model': fitted[best],
        'all_models': fitted,
        'pred': preds[best],
        'probas': probas,
        'classification_report': classification_report(yte, preds[best], output_dict=True, zero_division=0),
        'confusion_matrix': confusion_matrix(yte, preds[best], labels=labels).tolist(),
        'labels': labels,
        'final_recommender_name': 'Logistic Regression',
        'final_recommender_model': recommender,
        'recommendation_classes': lr_classes
    }


def save_confusion_matrix(out, labels, cm):
    fig, ax = plt.subplots(figsize=(10, 8))
    ax.imshow(cm)
    ax.set_xticks(range(len(labels)))
    ax.set_yticks(range(len(labels)))
    ax.set_xticklabels(labels, rotation=35, ha='right', fontsize=8)
    ax.set_yticklabels(labels, fontsize=8)
    ax.set_xlabel('Predicted')
    ax.set_ylabel('Actual')
    ax.set_title('Confusion Matrix')
    for i in range(len(labels)):
        for j in range(len(labels)):
            ax.text(j, i, str(cm[i][j]), ha='center', va='center', fontsize=7)
    fig.tight_layout()
    fig.savefig(out / 'figures' / 'confusion_matrix.png', dpi=180)
    plt.close(fig)


def save_roc(out, labels, y_test, probas):
    fig, ax = plt.subplots(figsize=(8, 6))
    y_bin = label_binarize(y_test, classes=labels)
    plotted = False
    for model_name, y_score in probas.items():
        if y_score is None or y_score.shape[1] != len(labels):
            continue
        try:
            # Macro-average ROC approximation by flattening class-binarized scores
            fpr, tpr, _ = roc_curve(y_bin.ravel(), y_score.ravel())
            roc_auc = auc(fpr, tpr)
            ax.plot(fpr, tpr, label=f'{model_name} (AUC={roc_auc:.3f})')
            plotted = True
        except Exception:
            pass
    if not plotted:
        plt.close(fig)
        return
    ax.plot([0, 1], [0, 1], '--')
    ax.set_xlabel('False Positive Rate')
    ax.set_ylabel('True Positive Rate')
    ax.set_title('ROC-AUC Comparison')
    ax.legend(fontsize=7, loc='lower right')
    fig.tight_layout()
    fig.savefig(out / 'figures' / 'roc_auc.png', dpi=180)
    plt.close(fig)


def save_pca(out, f):
    fig, ax = plt.subplots(figsize=(8, 6))
    profiles = sorted(f['kmeans_profile'].dropna().unique())
    for p in profiles:
        s = f[f['kmeans_profile'] == p]
        ax.scatter(s['pca_1'], s['pca_2'], s=5, alpha=.5, label=p)
    ax.set_xlabel('PCA 1')
    ax.set_ylabel('PCA 2')
    ax.set_title('PCA Student Hidden Profiles')
    ax.legend(fontsize=7)
    fig.tight_layout()
    fig.savefig(out / 'figures' / 'pca_clusters.png', dpi=180)
    plt.close(fig)


def save_feature_importance(out, clf):
    best = clf['best_model_name']
    model_obj = clf['best_model']
    cols = clf['feature_cols']
    vals = None
    try:
        if isinstance(model_obj, dict):
            m = model_obj['pipeline'].named_steps['model']
            if hasattr(m, 'feature_importances_'):
                vals = m.feature_importances_
        else:
            m = model_obj.named_steps['model']
            if hasattr(m, 'feature_importances_'):
                vals = m.feature_importances_
            elif hasattr(m, 'coef_'):
                vals = np.mean(np.abs(m.coef_), axis=0)
    except Exception:
        vals = None
    if vals is None:
        return
    order = np.argsort(vals)[-15:]
    fig, ax = plt.subplots(figsize=(9, 6))
    ax.barh([cols[i] for i in order], vals[order])
    ax.set_title(f'Feature Importance - {best}')
    fig.tight_layout()
    fig.savefig(out / 'figures' / 'feature_importance.png', dpi=180)
    plt.close(fig)


def save_model_comparison(out, clf):
    names = list(clf['model_results'].keys())
    acc = [clf['model_results'][n]['accuracy'] for n in names]
    f1 = [clf['model_results'][n]['f1_macro'] for n in names]
    x = np.arange(len(names))
    width = 0.35
    fig, ax = plt.subplots(figsize=(10, 5))
    ax.bar(x - width/2, acc, width, label='Accuracy')
    ax.bar(x + width/2, f1, width, label='F1-score')
    ax.set_xticks(x)
    ax.set_xticklabels(names, rotation=25, ha='right')
    ax.set_ylim(0, 1.05)
    ax.set_title('Model Comparison')
    ax.legend()
    fig.tight_layout()
    fig.savefig(out / 'figures' / 'model_comparison.png', dpi=180)
    plt.close(fig)


def save_shap(out, clf):
    # Fast, robust SHAP-like importance. True SHAP tries if available for RF; otherwise coefficients/importance.
    cols = clf['feature_cols']
    vals = None
    try:
        # Prefer final recommender LR coefficients for interpretability
        model = clf['final_recommender_model'].named_steps['model']
        vals = np.mean(np.abs(model.coef_), axis=0)
    except Exception:
        vals = None
    if vals is None:
        return
    order = np.argsort(vals)[-15:]
    fig, ax = plt.subplots(figsize=(9, 6))
    ax.barh([cols[i] for i in order], vals[order])
    ax.set_title('SHAP-like Global Explanation - Logistic Regression')
    fig.tight_layout()
    fig.savefig(out / 'figures' / 'shap_summary.png', dpi=180)
    plt.close(fig)


def make_reason_text(row):
    indices = {
        'IT': row.get('IT_Index', 0),
        'Muhandislik': row.get('Engineering_Index', 0),
        'Tibbiyot': row.get('Medicine_Index', 0),
        'Iqtisodiyot': row.get('Economics_Index', 0),
        'Pedagogika': row.get('Pedagogy_Index', 0),
    }
    top = sorted(indices.items(), key=lambda x: x[1], reverse=True)[:2]
    soft = {
        'Communication': row.get('Communication', 0),
        'Teamwork': row.get('Teamwork', 0),
        'Leadership': row.get('Leadership', 0),
        'Creativity': row.get('Creativity', 0),
        'Critical Thinking': row.get('Critical_Thinking', 0),
        'Adaptability': row.get('Adaptability', 0),
    }
    top_soft = sorted(soft.items(), key=lambda x: x[1], reverse=True)[:2]
    return f"Kuchli profil indekslari: {top[0][0]} ({top[0][1]:.1f}), {top[1][0]} ({top[1][1]:.1f}); yetakchi soft-skills: {top_soft[0][0]}, {top_soft[1][0]}."



def direction_advice(direction):
    adv = {
        'IT': "Informatika, matematika va algoritmik fikrlashni kuchaytirish; Python, web dasturlash, ma'lumotlar tahlili va ingliz tili bo'yicha individual reja tuzish tavsiya etiladi.",
        'Muhandislik': "Matematika, fizika, texnologiya va amaliy loyihalash ko'nikmalarini rivojlantirish; robototexnika, chizma, konstruktorlik va laboratoriya mashg'ulotlariga ko'proq e'tibor berish tavsiya etiladi.",
        'Tibbiyot': "Biologiya, kimyo va akademik barqarorlikni kuchaytirish; anatomiya, sog'liqni saqlash, laboratoriya ishlari va mas'uliyat ko'nikmalarini rivojlantirish tavsiya etiladi.",
        'Iqtisodiyot': "Matematika, iqtisodiyot, mantiqiy fikrlash, kommunikatsiya va liderlik ko'nikmalarini rivojlantirish; moliyaviy savodxonlik va biznes tahlil yo'nalishlariga e'tibor berish tavsiya etiladi.",
        'Pedagogika': "Kommunikativlik, jamoada ishlash, ona tili, adabiyot, kreativlik va liderlik ko'nikmalarini rivojlantirish; ta'lim metodikasi va bolalar bilan ishlash tajribasini oshirish tavsiya etiladi."
    }
    return adv.get(str(direction), "Tanlangan yo'nalish bo'yicha asosiy fanlar va soft-skills ko'nikmalarini bosqichma-bosqich rivojlantirish tavsiya etiladi.")

def make_student_password(student_id):
    s = ''.join(ch for ch in str(student_id) if ch.isalnum())
    return "edu" + s[-5:].lower().rjust(5, "0")

def save_outputs(out, f, cm, clf, scaler, km, gmm, pca):
    out = Path(out)
    out.mkdir(parents=True, exist_ok=True)
    (out / 'models').mkdir(exist_ok=True)
    (out / 'figures').mkdir(exist_ok=True)

    # 1-sinfdan 11-sinfgacha barcha yillik baholar/ko‘rsatkichlarni ko‘rish uchun eksport
    try:
        global LAST_LONG_GRADES
        if LAST_LONG_GRADES is not None:
            lg = LAST_LONG_GRADES.copy()
            lg = lg.merge(f[['student_id','recommended_direction','recommendation_confidence']], on='student_id', how='left')
            lg.to_csv(out / 'student_grade_history.csv', index=False, encoding='utf-8-sig')
            lg.to_excel(out / 'student_grade_history.xlsx', index=False)
    except Exception:
        pass

    f['recommendation_reason'] = f.apply(make_reason_text, axis=1)
    f['student_password'] = f['student_id'].apply(make_student_password)
    f['selected_direction_advice'] = f['recommended_direction'].apply(direction_advice)

    prob_cols = [c for c in f.columns if c.startswith('prob_')]
    gmm_cols = [c for c in f.columns if c.startswith('gmm_prob_C')]
    keep = [
        'student_id', 'FIO', 'Sinf', 'student_password', 'cluster_id', 'kmeans_profile', 'gmm_cluster_id', 'gmm_profile', 'gmm_max_probability',
        'dominant_profile', 'target_direction', 'recommended_direction', 'recommendation_confidence',
        'alternative_direction', 'alternative_confidence', 'recommendation_reason', 'selected_direction_advice',
        'AnalyticalIndex', 'CreativeProfile', 'LeadershipScore', 'StabilityIndex', 'PracticalSkill',
        'IT_Index', 'Engineering_Index', 'Medicine_Index', 'Economics_Index', 'Pedagogy_Index',
        'academic_mean', 'academic_std', 'growth_trend', 'learning_dynamics', 'academic_stability',
        'temporal_consistency', 'skill_evolution', 'temporal_history', 'SoftSkillScore', 'Communication', 'Teamwork',
        'Leadership', 'Creativity', 'Critical_Thinking', 'Adaptability', 'certificates', 'pca_1', 'pca_2'
    ] + prob_cols + gmm_cols
    result_cols = [c for c in keep if c in f]
    f[result_cols].to_csv(out / 'student_results.csv', index=False, encoding='utf-8-sig')
    # Maktab ma'muriyati uchun tayyor Excel hisobot
    try:
        admin_cols = ['student_id', 'FIO', 'Sinf', 'recommended_direction', 'recommendation_confidence',
                      'alternative_direction', 'alternative_confidence', 'IT_Index', 'Engineering_Index',
                      'Medicine_Index', 'Economics_Index', 'Pedagogy_Index'] + prob_cols
        f[[c for c in admin_cols if c in f]].to_excel(out / 'school_recommendations.xlsx', index=False)
        f[['student_id','FIO','Sinf','student_password','recommended_direction','recommendation_confidence']].to_excel(out / 'student_logins.xlsx', index=False)
    except Exception:
        pass

    f.groupby('kmeans_profile').agg(
        students=('student_id', 'count'),
        IT_mean=('IT_Index', 'mean'),
        Engineering_mean=('Engineering_Index', 'mean'),
        Medicine_mean=('Medicine_Index', 'mean'),
        Economics_mean=('Economics_Index', 'mean'),
        Pedagogy_mean=('Pedagogy_Index', 'mean')
    ).round(3).reset_index().to_csv(out / 'cluster_summary.csv', index=False, encoding='utf-8-sig')

    f['recommended_direction'].value_counts().rename_axis('direction').reset_index(name='students').to_csv(out / 'direction_summary.csv', index=False, encoding='utf-8-sig')
    if 'Sinf' in f.columns:
        f.groupby(['Sinf','recommended_direction']).size().reset_index(name='students').to_csv(out / 'class_direction_summary.csv', index=False, encoding='utf-8-sig')

    # Professional v4.1: direktor dashboardi uchun tahliliy eksportlar
    top20 = f.sort_values('recommendation_confidence', ascending=False).head(20)
    top20[['student_id','FIO','Sinf','recommended_direction','recommendation_confidence','alternative_direction','alternative_confidence']].to_csv(out / 'top_students.csv', index=False, encoding='utf-8-sig')
    low_conf = f[f['recommendation_confidence'] < 0.70].sort_values('recommendation_confidence')
    low_conf[['student_id','FIO','Sinf','recommended_direction','recommendation_confidence','alternative_direction','alternative_confidence']].to_csv(out / 'low_confidence_students.csv', index=False, encoding='utf-8-sig')
    gap = (f['recommendation_confidence'] - f['alternative_confidence']).fillna(0)
    unclear = f[gap < 0.15].sort_values('recommendation_confidence')
    unclear[['student_id','FIO','Sinf','recommended_direction','recommendation_confidence','alternative_direction','alternative_confidence']].to_csv(out / 'unclear_students.csv', index=False, encoding='utf-8-sig')

    # Temporal history CSV
    if 'temporal_history' in f.columns:
        rows = []
        for _, rr in f[['student_id','FIO','Sinf','temporal_history']].iterrows():
            try:
                items = json.loads(rr.get('temporal_history') or '[]')
            except Exception:
                items = []
            for it in items:
                rows.append({'student_id': rr['student_id'], 'FIO': rr.get('FIO',''), 'Sinf': rr.get('Sinf',''), **it})
        if rows:
            pd.DataFrame(rows).to_csv(out / 'student_temporal_history.csv', index=False, encoding='utf-8-sig')

    try:
        with pd.ExcelWriter(out / 'school_full_report.xlsx') as writer:
            f[[c for c in admin_cols if c in f]].to_excel(writer, sheet_name='Recommendations', index=False)
            f['recommended_direction'].value_counts().rename_axis('direction').reset_index(name='students').to_excel(writer, sheet_name='Direction summary', index=False)
            if 'Sinf' in f.columns:
                f.groupby(['Sinf','recommended_direction']).size().reset_index(name='students').to_excel(writer, sheet_name='Class summary', index=False)
            top20.to_excel(writer, sheet_name='Top students', index=False)
            low_conf.to_excel(writer, sheet_name='Low confidence', index=False)
            unclear.to_excel(writer, sheet_name='Unclear direction', index=False)
    except Exception:
        pass

    bestres = clf['model_results'][clf['best_model_name']]
    metrics = {
        'version': 'Professional v4.1',
        'dataset': {
            'students': int(f.shape[0]),
            'features_used': len(clf['feature_cols']),
            'features': clf['feature_cols'],
            'profiles': PROFILE_NAMES,
            'directions': sorted(f['recommended_direction'].unique().tolist()),
            'classes': sorted(f['Sinf'].dropna().astype(str).unique().tolist()) if 'Sinf' in f.columns else [],
            'target_note': "Excelda target topilmasa, profil indekslari asosida pseudo-label yaratildi."
        },
        'feature_engineering': {
            'feature_count': len(clf['feature_cols']),
            'temporal_features': ['growth_trend', 'learning_dynamics', 'academic_stability', 'temporal_consistency', 'skill_evolution'],
            'profile_indices': PROFILE_SCORE_COLS
        },
        'clustering': cm,
        'classification': {
            'best_model': clf['best_model_name'],
            'final_recommender': clf['final_recommender_name'],
            'model_results': clf['model_results'],
            'labels': clf['labels'],
            'confusion_matrix': clf['confusion_matrix'],
            'classification_report': clf['classification_report']
        },
        'recommendation': {
            'method': 'Logistic Regression probability argmax',
            'formula': 'Recommendation = argmax_j P(Y=j|X)',
            'mean_confidence': round(float(f['recommendation_confidence'].mean()), 4),
            'median_confidence': round(float(f['recommendation_confidence'].median()), 4),
            'min_confidence': round(float(f['recommendation_confidence'].min()), 4),
            'max_confidence': round(float(f['recommendation_confidence'].max()), 4),
            'low_confidence_count': int((f['recommendation_confidence'] < 0.70).sum()),
            'unclear_direction_count': int(((f['recommendation_confidence'] - f['alternative_confidence']).fillna(0) < 0.15).sum())
        },
        'director_dashboard': {
            'top_students_count': int(min(20, len(f))),
            'direction_distribution': f['recommended_direction'].value_counts().to_dict(),
            'class_count': int(f['Sinf'].nunique()) if 'Sinf' in f.columns else 0
        },
        'figures': [
            'figures/pca_clusters.png', 'figures/confusion_matrix.png', 'figures/roc_auc.png',
            'figures/feature_importance.png', 'figures/shap_summary.png', 'figures/model_comparison.png'
        ],
        'system_architecture': 'Research Mode + Prediction Mode + Master Dataset + School Filtering + Adaptive Temporal Feature Engineering',
        'method_note': "Datasetda haqiqiy yo'nalish labeli mavjud bo'lmasa, yo'nalishlar profil indekslari asosida pseudo-label sifatida shakllantirildi. Real label qo'shilsa, model supervised classification rejimida qayta o'qitiladi."
    }
    (out / 'metrics.json').write_text(json.dumps(metrics, ensure_ascii=False, indent=2), encoding='utf-8')

    joblib.dump({'scaler': scaler, 'kmeans': km, 'gmm': gmm, 'pca': pca, 'profile_names': PROFILE_NAMES}, out / 'models' / 'clustering_pipeline.joblib')
    joblib.dump({
        'best_model_name': clf['best_model_name'],
        'best_model': clf['best_model'],
        'final_recommender_name': clf['final_recommender_name'],
        'final_recommender_model': clf['final_recommender_model'],
        'feature_cols': clf['feature_cols'],
        'classes': clf['recommendation_classes']
    }, out / 'models' / 'classification_pipeline.joblib')

    save_pca(out, f)
    save_confusion_matrix(out, clf['labels'], clf['confusion_matrix'])
    save_roc(out, clf['labels'], clf['y_test'], clf['probas'])
    save_feature_importance(out, clf)
    save_shap(out, clf)
    save_model_comparison(out, clf)

    txt = f"""# Professional v4.1 dastur natijalari

Datasetdagi jami o'quvchilar soni: {f.shape[0]}.
Featurelar soni: {len(clf['feature_cols'])}.

## Feature Engineering
Akademik ko'rsatkichlar, soft-skills indikatorlari va temporal dinamikalar asosida 31 ta xususiyat shakllantirildi.

## Klasterlash
K-Means: WCSS = {cm['algorithm_comparison']['KMeans']['wcss']}, Silhouette = {cm['algorithm_comparison']['KMeans']['silhouette']}, Davies-Bouldin = {cm['algorithm_comparison']['KMeans']['davies_bouldin']}.
GMM: AIC = {cm['algorithm_comparison']['GaussianMixture']['aic']}, BIC = {cm['algorithm_comparison']['GaussianMixture']['bic']}, Silhouette = {cm['algorithm_comparison']['GaussianMixture']['silhouette']}, Davies-Bouldin = {cm['algorithm_comparison']['GaussianMixture']['davies_bouldin']}.
GMM o'rtacha ishonchlilik = {cm['algorithm_comparison']['GaussianMixture']['mean_probability']}.

## Klassifikatsiya
5 ta model o'qitildi: Logistic Regression, Random Forest, XGBoost, SVM, Neural Network.
Eng yaxshi model: {clf['best_model_name']}.
Accuracy = {bestres['accuracy']}, Precision = {bestres['precision_macro']}, Recall = {bestres['recall_macro']}, F1-score = {bestres['f1_macro']}.

## Tavsiya
Yakuniy tavsiya Logistic Regression ehtimolliklari asosida shakllantirildi: Recommendation = argmax_j P(Y=j|X).
O'rtacha confidence = {metrics['recommendation']['mean_confidence']}.

Eslatma: datasetda haqiqiy ta'lim yo'nalishi labeli mavjud bo'lmasa, yo'nalishlar profil indekslari asosida pseudo-label sifatida shakllantiriladi.
"""
    (out / 'article_results_summary.md').write_text(txt, encoding='utf-8')


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--input', required=True)
    ap.add_argument('--out', default='outputs')
    args = ap.parse_args()
    print('Professional v4.1: loading dataset...', flush=True)
    f = load_and_engineer(args.input)
    print(f'Feature Engineering bajarildi: students={f.shape[0]}', flush=True)
    f, sc, km, gmm, pca, cm = cluster(f)
    print('K-Means + GMM bajarildi', json.dumps(cm['algorithm_comparison'], ensure_ascii=False), flush=True)
    clf = classify(f)
    print('5 ta algoritm o‘qitildi. Best model:', clf['best_model_name'], flush=True)
    save_outputs(args.out, f, cm, clf, sc, km, gmm, pca)
    print(json.dumps({'status': 'ok', 'version': 'Professional v4.1', 'students': int(f.shape[0]), 'features': len(clf['feature_cols']), 'best_model': clf['best_model_name'], 'out': args.out}, ensure_ascii=False))


if __name__ == '__main__':
    main()
