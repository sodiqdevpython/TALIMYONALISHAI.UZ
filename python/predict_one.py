# -*- coding: utf-8 -*-
import argparse
import json
from pathlib import Path
import joblib
import pandas as pd


def main():
    p = argparse.ArgumentParser()
    p.add_argument('--student_id', required=True)
    p.add_argument('--results', default='../outputs/student_results.csv')
    args = p.parse_args()
    df = pd.read_csv(args.results)
    row = df[df['student_id'].astype(str) == str(args.student_id)]
    if row.empty:
        print(json.dumps({'status':'not_found','message':'Oquvchi topilmadi'}, ensure_ascii=False))
        return
    r = row.iloc[0].to_dict()
    print(json.dumps({'status':'ok','student':r}, ensure_ascii=False, default=str))

if __name__ == '__main__':
    main()
