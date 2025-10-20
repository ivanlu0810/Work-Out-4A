@echo off
echo 啟動健習生專案...
echo.

REM 檢查 XAMPP 控制面板是否運行
tasklist /fi "imagename eq xampp-control.exe" 2>nul | find /i "xampp-control.exe" >nul
if %errorlevel% neq 0 (
    echo 啟動 XAMPP 控制面板...
    start "" "C:\xampp\xampp-control.exe"
    echo 請在 XAMPP 控制面板中啟動 Apache 和 MySQL 服務
    echo 然後按任意鍵繼續...
    pause >nul
)

REM 等待服務啟動
echo 等待服務啟動...
timeout /t 3 /nobreak >nul

REM 啟動 PHP 內建服務器
echo 啟動 PHP 服務器...
echo.
echo 專案已啟動！
echo 請在瀏覽器中訪問：
echo http://localhost:8000/index.php
echo.
echo 或者訪問測試頁面：
echo http://localhost:8000/test_local.php
echo.
echo 按 Ctrl+C 停止服務器
echo.

cd /d C:\xampp\htdocs\Work-Out-4A-main\dist
C:\xampp\php\php.exe -S localhost:8000
