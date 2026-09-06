@echo off
setlocal
cd /d "%~dp0..\.."
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
%PHP% tests\preflight.php
set "RC=%ERRORLEVEL%"
echo.
if not "%RC%"=="0" echo Preflight found required items that need attention.
pause
exit /b %RC%
