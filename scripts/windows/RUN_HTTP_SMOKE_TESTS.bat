@echo off
setlocal
cd /d "%~dp0..\.."
set "BASE_URL=%~1"
if "%BASE_URL%"=="" set "BASE_URL=http://127.0.0.1:8000"
set "MODE=%~2"
where php >nul 2>nul
if errorlevel 1 (
  if exist "C:\xampp\php\php.exe" (
    set "PHP=C:\xampp\php\php.exe"
  ) else (
    echo PHP was not found in PATH and C:\xampp\php\php.exe was not found.
    exit /b 1
  )
) else (
  set "PHP=php"
)
%PHP% tests\http-smoke.php "%BASE_URL%" %MODE%
set "RC=%ERRORLEVEL%"
echo.
if "%RC%"=="2" echo Full application checks were blocked by the local PHP/database environment. Run scripts\windows\RUN_PREFLIGHT.bat for details.
pause
exit /b %RC%
