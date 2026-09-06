@echo off
setlocal EnableExtensions EnableDelayedExpansion
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

echo === PHP syntax ===
set "LINTFAIL=0"
for /r %%F in (*.php) do (
  %PHP% -l "%%F" >nul
  if errorlevel 1 (
    echo [FAIL] %%F
    set "LINTFAIL=1"
  )
)
if "!LINTFAIL!"=="1" exit /b 1
echo [PASS] All PHP files passed syntax checking.

echo.
echo === Automated checks ===
%PHP% tests\run.php
if errorlevel 1 exit /b 1

echo.
echo === Environment preflight ===
%PHP% tests\preflight.php
set "RC=%ERRORLEVEL%"
if not "%RC%"=="0" (
  echo.
  echo Automated code checks passed, but the local PHP/server environment still needs attention.
)
pause
exit /b %RC%
