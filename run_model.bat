@echo off
chcp 65001 >nul
cd /d %~dp0
echo ==========================================
echo  Model ishga tushmoqda...
echo ==========================================
if not exist outputs mkdir outputs
where py >nul 2>nul
if %errorlevel%==0 (
    py python\train_model.py --input data\dataset.xlsx --out outputs
) else (
    python python\train_model.py --input data\dataset.xlsx --out outputs
)
echo.
echo Tayyor. Brauzerda oching:
echo http://localhost/edu_pathway_app/web/index.php
echo yoki agar papka nomi edu_ikkinchi_bob bo'lsa:
echo http://localhost/edu_ikkinchi_bob/web/index.php
pause
