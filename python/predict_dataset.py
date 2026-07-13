# -*- coding: utf-8 -*-
"""
Professional v4.0 Prediction Mode
Tayyor modelni qayta o‘qitmasdan yangi maktab datasetiga qo‘llaydi.
"""
import argparse, json
from pathlib import Path
import joblib
import pandas as pd
import numpy as np

from train_model import (
    load_and_engineer, PROFILE_NAMES, PROFILE_SCORE_COLS, FEATURE_COLS_31,
    map_clusters_to_profiles, direction_advice, make_reason_text
)

def make_student_password(student_id):
    s = ''.join(ch for ch in str(student_id) if ch.isalnum())
    return "edu" + s[-5:].lower().rjust(5, "0")

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument('--input', required=True)
    ap.add_argument('--out', default='outputs')
    ap.add_argument('--school_id', default='UNKNOWN')
    ap.add_argument('--school_name', default='Unknown school')
    ap.add_argument('--region', default='')
    ap.add_argument('--district', default='')
    ap.add_argument('--model_dir', default='outputs/models')
    args = ap.parse_args()

    out = Path(args.out); out.mkdir(parents=True, exist_ok=True)
    model_dir = Path(args.model_dir)
    clf_path = model_dir / 'classification_pipeline.joblib'
    cl_path = model_dir / 'clustering_pipeline.joblib'
    if not clf_path.exists() or not cl_path.exists():
        raise FileNotFoundError("Tayyor model topilmadi. Avval Research Mode orqali modelni o‘qiting.")

    print("Prediction Mode: loading school dataset...", flush=True)
    f = load_and_engineer(args.input)

    clustering = joblib.load(cl_path)
    clf = joblib.load(clf_path)
    scaler = clustering['scaler']; km = clustering['kmeans']; gmm = clustering['gmm']; pca = clustering['pca']

    Xprof = f[PROFILE_SCORE_COLS].values
    Xs = scaler.transform(Xprof)
    klabels = km.predict(Xs)
    centers = scaler.inverse_transform(km.cluster_centers_)
    mapping = map_clusters_to_profiles(centers)
    f['cluster_id'] = klabels
    f['kmeans_profile'] = f['cluster_id'].map(mapping)

    glabels = gmm.predict(Xs)
    gprobs = gmm.predict_proba(Xs)
    gmm_centers = scaler.inverse_transform(gmm.means_)
    gmap = map_clusters_to_profiles(gmm_centers)
    f['gmm_cluster_id'] = glabels
    f['gmm_profile'] = pd.Series(glabels).map(gmap).values
    f['gmm_max_probability'] = gprobs.max(axis=1)
    f['raw_model_confidence'] = f['gmm_max_probability']

    xy = pca.transform(Xs)
    f['pca_1'] = xy[:,0]; f['pca_2'] = xy[:,1]

    missing = [c for c in FEATURE_COLS_31 if c not in f.columns]
    if missing:
        raise ValueError(f"Prediction uchun feature yetishmayapti: {missing}")

    X = f[FEATURE_COLS_31].copy()
    rec = clf['final_recommender_model']
    proba = rec.predict_proba(X)
    classes = list(rec.classes_)
    pred_idx = np.argmax(proba, axis=1)
    f['recommended_direction'] = [classes[i] for i in pred_idx]
    raw_conf = proba.max(axis=1)
    f['raw_model_confidence'] = raw_conf
    coverage = f.get('temporal_coverage_ratio', pd.Series(1.0, index=f.index)).astype(float).clip(0,1)
    f['recommendation_confidence'] = (raw_conf * (0.85 + 0.15 * coverage)).clip(0,1)
    f['temporal_coverage_level'] = pd.cut(coverage, bins=[-0.01,0.35,0.65,0.90,1.01], labels=['Limited','Partial','Good','Excellent']).astype(str)

    for ci, cname in enumerate(classes):
        f[f'prob_{cname}'] = proba[:,ci]
    sorted_idx = np.argsort(-proba, axis=1)
    f['alternative_direction'] = [classes[idxs[1]] if len(idxs)>1 else '' for idxs in sorted_idx]
    f['alternative_confidence'] = [proba[i, sorted_idx[i][1]] if proba.shape[1] > 1 else 0 for i in range(len(f))]

    f['school_id'] = args.school_id
    f['school_name'] = args.school_name
    f['region'] = args.region
    f['district'] = args.district
    f['student_password'] = f['student_id'].apply(make_student_password)
    f['recommendation_reason'] = f.apply(make_reason_text, axis=1)
    f['selected_direction_advice'] = f['recommended_direction'].apply(direction_advice)

    prob_cols = [c for c in f.columns if c.startswith('prob_')]
    keep = [
        'school_id','school_name','region','district','student_id','FIO','Sinf','student_password',
        'cluster_id','kmeans_profile','gmm_cluster_id','gmm_profile','gmm_max_probability',
        'dominant_profile','target_direction','recommended_direction','recommendation_confidence','raw_model_confidence',
        'alternative_direction','alternative_confidence','recommendation_reason','selected_direction_advice',
        'temporal_years_count','temporal_coverage_ratio','temporal_coverage_level','temporal_history',
        'AnalyticalIndex','CreativeProfile','LeadershipScore','StabilityIndex','PracticalSkill',
        'IT_Index','Engineering_Index','Medicine_Index','Economics_Index','Pedagogy_Index',
        'academic_mean','academic_std','growth_trend','learning_dynamics','academic_stability',
        'temporal_consistency','skill_evolution','SoftSkillScore','Communication','Teamwork',
        'Leadership','Creativity','Critical_Thinking','Adaptability','certificates'
    ] + prob_cols
    result_cols = [c for c in keep if c in f.columns]

    f[result_cols].to_csv(out/'school_prediction_results.csv', index=False, encoding='utf-8-sig')
    f[result_cols].to_excel(out/'school_prediction_results.xlsx', index=False)
    f[['school_id','school_name','student_id','FIO','Sinf','student_password','recommended_direction','recommendation_confidence']].to_excel(out/'school_student_logins.xlsx', index=False)

    f['recommended_direction'].value_counts().rename_axis('direction').reset_index(name='students').to_csv(out/'school_direction_summary.csv', index=False, encoding='utf-8-sig')
    if 'Sinf' in f.columns:
        f.groupby(['Sinf','recommended_direction']).size().reset_index(name='students').to_csv(out/'school_class_direction_summary.csv', index=False, encoding='utf-8-sig')

    summary = {
        'status': 'ok',
        'mode': 'Prediction Mode',
        'school_id': args.school_id,
        'school_name': args.school_name,
        'students': int(f.shape[0]),
        'mean_confidence': round(float(f['recommendation_confidence'].mean()),4),
        'temporal_coverage_mean': round(float(f.get('temporal_coverage_ratio', pd.Series([1])).mean()),4),
        'directions': f['recommended_direction'].value_counts().to_dict(),
        'outputs': ['school_prediction_results.xlsx','school_direction_summary.csv','school_student_logins.xlsx']
    }
    (out/'school_prediction_summary.json').write_text(json.dumps(summary, ensure_ascii=False, indent=2), encoding='utf-8')
    print(json.dumps(summary, ensure_ascii=False), flush=True)

if __name__ == '__main__':
    main()
