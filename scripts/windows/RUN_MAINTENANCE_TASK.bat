@echo off
setlocal
cd /d "%~dp0..\.."
where php >nul 2>nul
if errorlevel 1 (
  echo PHP was not found in PATH.
  echo Run this with your XAMPP PHP executable, for example:
  echo C:\xampp\php\php.exe scripts\maintenance-cron.php
  exit /b 1
)
php scripts\maintenance-cron.php
endlocal
