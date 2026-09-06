@echo off
setlocal EnableExtensions EnableDelayedExpansion
title DMMMSU USC - XAMPP Device Access

set "PROJECT=DMMMSU_USC"
set "XAMPP=C:\xampp"
set "LAN_IP="

cls
echo ============================================================
echo          DMMMSU USC - XAMPP DEVICE ACCESS
echo ============================================================
echo.

rem ------------------------------------------------------------
rem Validate XAMPP and project.
rem ------------------------------------------------------------
if not exist "%XAMPP%\apache_start.bat" (
    echo ERROR: XAMPP Apache launcher was not found:
    echo   %XAMPP%\apache_start.bat
    echo.
    pause
    exit /b 1
)

if not exist "%XAMPP%\htdocs\%PROJECT%" (
    echo ERROR: USC project folder was not found:
    echo   %XAMPP%\htdocs\%PROJECT%
    echo.
    echo Place this project at:
    echo   C:\xampp\htdocs\DMMMSU_USC
    echo.
    pause
    exit /b 1
)

rem ------------------------------------------------------------
rem Start MySQL if port 3306 is not listening.
rem ------------------------------------------------------------
netstat -ano -p tcp | findstr /R /C:":3306 .*LISTENING" >nul 2>&1
if errorlevel 1 (
    echo Starting MySQL...
    start "XAMPP MySQL" /min "%XAMPP%\mysql_start.bat"
    timeout /t 3 /nobreak >nul
) else (
    echo MySQL is already running.
)

rem ------------------------------------------------------------
rem Start Apache if port 80 is not listening.
rem ------------------------------------------------------------
netstat -ano -p tcp | findstr /R /C:":80 .*LISTENING" >nul 2>&1
if errorlevel 1 (
    echo Starting Apache...
    start "XAMPP Apache" /min "%XAMPP%\apache_start.bat"
    timeout /t 3 /nobreak >nul
) else (
    echo Port 80 is already listening.
)

rem ------------------------------------------------------------
rem Detect LAN IP from the active IPv4 default route.
rem This avoids VPN/VirtualBox host-only addresses.
rem ------------------------------------------------------------
for /f "tokens=4" %%I in ('route print -4 0.0.0.0 ^| findstr /R /C:"^[ ]*0\.0\.0\.0[ ]*0\.0\.0\.0"') do (
    if not defined LAN_IP set "LAN_IP=%%I"
)

if not defined LAN_IP (
    for /f "usebackq delims=" %%I in (`powershell -NoProfile -Command "$c=Get-NetIPConfiguration -ErrorAction SilentlyContinue ^| Where-Object {$_.NetAdapter.Status -eq 'Up' -and $_.IPv4DefaultGateway -and $_.IPv4Address} ^| Select-Object -First 1; if($c){$c.IPv4Address.IPAddress}"`) do (
        if not defined LAN_IP set "LAN_IP=%%I"
    )
)

rem ------------------------------------------------------------
rem Open Apache HTTP port through Windows Firewall.
rem ------------------------------------------------------------
netsh advfirewall firewall show rule name="DMMMSU USC XAMPP HTTP" >nul 2>&1
if errorlevel 1 (
    echo.
    echo Windows may ask for Administrator permission once.
    echo Allowing incoming HTTP traffic on port 80...
    powershell -NoProfile -Command "Start-Process netsh -Verb RunAs -ArgumentList 'advfirewall firewall add rule name=\"DMMMSU USC XAMPP HTTP\" dir=in action=allow protocol=TCP localport=80 profile=any' -Wait" >nul 2>&1
)

rem ------------------------------------------------------------
rem Wait briefly and verify Apache.
rem ------------------------------------------------------------
timeout /t 2 /nobreak >nul

cls
echo ============================================================
echo          DMMMSU USC - XAMPP DEVICE ACCESS
echo ============================================================
echo.
echo PC URL:
echo   http://localhost/%PROJECT%/
echo.

if defined LAN_IP (
    echo PHONE / TABLET URL:
    echo   http://%LAN_IP%/%PROJECT%/
    echo.
    echo ADMIN LOGIN:
    echo   http://%LAN_IP%/%PROJECT%/admin/login.php
    echo.
) else (
    echo WARNING: LAN IP could not be detected automatically.
    echo.
    echo Run IPCONFIG and find the IPv4 address of the adapter
    echo that has a Default Gateway.
    echo Then open:
    echo   http://YOUR-IP/%PROJECT%/
    echo.
)

echo IMPORTANT:
echo   1. Keep Apache and MySQL running.
echo   2. PC and phone/tablet must be on the SAME Wi-Fi/LAN.
echo   3. Use HTTP, not HTTPS.
echo   4. Do not use localhost on the phone/tablet.
echo.
echo ============================================================

start "" "http://localhost/%PROJECT%/"

echo.
echo This window may now stay open or be minimized.
echo To stop the server later, stop Apache/MySQL from XAMPP.
echo.
pause
