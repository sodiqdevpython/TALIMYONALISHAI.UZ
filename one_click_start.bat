@echo off
chcp 65001 >nul
cd /d %~dp0
echo ==========================================
echo  ONE-CLICK START
 echo  1) Kutubxonalar tekshiriladi/o'rnatiladi
 echo  2) Model ishga tushadi
 echo  3) Web dashboard ochiladi
 echo ==========================================
where py >nul 2>nul
if %errorlevel%==0 (
    py -m pip install -r requirements.txt
    py python\train_model.py --input data\dataset.xlsx --out outputs
) else (
    python -m pip install -r requirements.txt
    python python\train_model.py --input data\dataset.xlsx --out outputs
)
start http://localhost/edu_pathway_app/web/index.php
start http://localhost/edu_ikkinchi_bob/web/index.php
pause
