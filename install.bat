@echo off
chcp 65001 >nul
cd /d %~dp0
echo ==========================================
echo  Python kutubxonalarini o'rnatish boshlandi
echo ==========================================
where py >nul 2>nul
if %errorlevel%==0 (
    py -m pip install --upgrade pip
    py -m pip install -r requirements.txt
) else (
    python -m pip install --upgrade pip
    python -m pip install -r requirements.txt
)
echo.
echo O'rnatish tugadi.
pause
