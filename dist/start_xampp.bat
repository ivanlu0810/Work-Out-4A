@echo off
echo 啟動 XAMPP 服務 (使用端口 8080)...

REM 停止可能正在運行的服務
taskkill /f /im httpd.exe 2>nul
taskkill /f /im mysqld.exe 2>nul

REM 修改 Apache 配置使用端口 8080
cd /d C:\xampp\apache\conf
powershell -Command "(Get-Content httpd.conf) -replace 'Listen 80', 'Listen 8080' | Set-Content httpd.conf"
powershell -Command "(Get-Content httpd.conf) -replace 'ServerName localhost:80', 'ServerName localhost:8080' | Set-Content httpd.conf"

REM 啟動 MySQL
echo 啟動 MySQL...
cd /d C:\xampp\mysql\bin
start mysqld.exe --console

REM 等待 MySQL 啟動
timeout /t 3 /nobreak >nul

REM 啟動 Apache
echo 啟動 Apache (端口 8080)...
cd /d C:\xampp\apache\bin
start httpd.exe

echo.
echo XAMPP 服務已啟動！
echo.
echo 請在瀏覽器中訪問：
echo http://localhost:8080/Work-Out-4A-main/dist/test_local.php
echo.
echo 按任意鍵停止服務...
pause >nul

REM 停止服務
taskkill /f /im httpd.exe
taskkill /f /im mysqld.exe
echo 服務已停止
