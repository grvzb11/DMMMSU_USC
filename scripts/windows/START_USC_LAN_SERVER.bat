@echo off
setlocal EnableExtensions EnableDelayedExpansion
for %%I in ("%~dp0..\..") do set "PROJECT_ROOT=%%~fI"
cd /d "%PROJECT_ROOT%"
title DMMMSU USC LAN Server

set "PORT=8000"
set "PHP_EXE="
set "LAN_IP="
set "MYSQL_OK="

rem ------------------------------------------------------------
rem Find PHP (PATH, XAMPP, then common Laragon locations)
rem ------------------------------------------------------------
where php >nul 2>&1
if not errorlevel 1 set "PHP_EXE=php"
if not defined PHP_EXE if exist "C:\xampp\php\php.exe" set "PHP_EXE=C:\xampp\php\php.exe"
if not defined PHP_EXE if exist "C:\laragon\bin\php\php.exe" set "PHP_EXE=C:\laragon\bin\php\php.exe"

if not defined PHP_EXE (
  for /f "delims=" %%P in ('dir /b /ad /o-n "C:\laragon\bin\php\php-*" 2^>nul') do (
    if exist "C:\laragon\bin\php\%%P\php.exe" (
      set "PHP_EXE=C:\laragon\bin\php\%%P\php.exe"
      goto :phpfound
    )
  )
)
:phpfound

if not defined PHP_EXE (
  echo.
  echo ERROR: PHP was not found.
  echo.
  echo Install/start XAMPP or Laragon, or add PHP to Windows PATH.
  echo Expected XAMPP path: C:\xampp\php\php.exe
  echo.
  pause
  exit /b 1
)

rem ------------------------------------------------------------
rem Confirm PDO MySQL is available
rem ------------------------------------------------------------
"%PHP_EXE%" -m | findstr /I /X "pdo_mysql" >nul 2>&1
if errorlevel 1 (
  echo.
  echo ERROR: PHP PDO MySQL extension is not enabled.
  echo Enable pdo_mysql in php.ini before running the USC system.
  echo.
  pause
  exit /b 1
)

rem ------------------------------------------------------------
rem Check whether MySQL/MariaDB is listening locally
rem ------------------------------------------------------------
for /f "usebackq delims=" %%M in (`powershell -NoProfile -Command "if(Test-NetConnection -ComputerName 127.0.0.1 -Port 3306 -InformationLevel Quiet -WarningAction SilentlyContinue){'YES'}else{'NO'}"`) do set "MYSQL_OK=%%M"

rem ------------------------------------------------------------
rem Find the most useful LAN IPv4 address
rem ------------------------------------------------------------
for /f "usebackq delims=" %%I in (`powershell -NoProfile -Command "$ip=Get-NetIPAddress -AddressFamily IPv4 -ErrorAction SilentlyContinue ^| Where-Object {$_.IPAddress -notlike '169.254*' -and $_.IPAddress -ne '127.0.0.1' -and $_.PrefixOrigin -ne 'WellKnown'} ^| Sort-Object InterfaceMetric ^| Select-Object -First 1 -ExpandProperty IPAddress; if($ip){$ip}"`) do set "LAN_IP=%%I"

if not defined LAN_IP (
  for /f "tokens=2 delims=:" %%I in ('ipconfig ^| findstr /C:"IPv4 Address"') do (
    set "LAN_IP=%%I"
    set "LAN_IP=!LAN_IP: =!"
    goto :ipfound
  )
)
:ipfound

rem ------------------------------------------------------------
rem Avoid starting a second server on the same port
rem ------------------------------------------------------------
for /f "usebackq delims=" %%P in (`powershell -NoProfile -Command "if(Get-NetTCPConnection -State Listen -LocalPort %PORT% -ErrorAction SilentlyContinue){'INUSE'}else{'FREE'}"`) do set "PORT_STATE=%%P"
if /I "%PORT_STATE%"=="INUSE" (
  echo.
  echo ERROR: Port %PORT% is already in use.
  echo Close the other server or change PORT in this file.
  echo.
  pause
  exit /b 1
)

rem ------------------------------------------------------------
rem Allow private-network access through Windows Firewall
rem ------------------------------------------------------------
netsh advfirewall firewall show rule name="DMMMSU USC LAN Port %PORT%" >nul 2>&1
if errorlevel 1 (
  echo Requesting Windows Firewall access for private networks on port %PORT%...
  powershell -NoProfile -Command "Start-Process netsh -Verb RunAs -ArgumentList 'advfirewall firewall add rule name=\"DMMMSU USC LAN Port %PORT%\" dir=in action=allow protocol=TCP localport=%PORT% profile=private' -Wait" >nul 2>&1
)

cls
echo ============================================================
echo             DMMMSU USC - LOCAL LAN SERVER
echo ============================================================
echo.
echo Public website:
echo   Computer: http://localhost:%PORT%/
if defined LAN_IP echo   Phone/Tablet: http://%LAN_IP%:%PORT%/
echo.
echo Administration:
echo   Computer: http://localhost:%PORT%/admin/login.php
if defined LAN_IP echo   Phone/Tablet: http://%LAN_IP%:%PORT%/admin/login.php
echo.
if /I not "%MYSQL_OK%"=="YES" (
  echo WARNING: MySQL/MariaDB does not appear to be running on port 3306.
  echo Start MySQL in XAMPP/Laragon before using the system.
  echo.
)
if not defined LAN_IP (
  echo LAN IP could not be detected automatically.
  echo Run ipconfig and use your Wi-Fi/Ethernet IPv4 address with port %PORT%.
  echo.
)
echo Large media upload profile:
echo   Video file: up to 250 MB
echo   Server POST: 300 MB per request
echo.
echo Use this only on a trusted PRIVATE Wi-Fi/LAN network.
echo Keep this window open while using the system.
echo Press Ctrl+C to stop the server.
echo ============================================================
echo.

start "" "http://localhost:%PORT%/"
"%PHP_EXE%" -d upload_max_filesize=256M -d post_max_size=300M -d max_file_uploads=120 -d max_input_time=600 -d display_errors=0 -S 0.0.0.0:%PORT% -t "%PROJECT_ROOT%" "%PROJECT_ROOT%\config\dev-router.php"

pause
