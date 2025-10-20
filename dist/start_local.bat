@echo off
echo 啟動健習生本地測試環境...
echo.

REM 檢查 PHP 是否可用
php --version >nul 2>&1
if %errorlevel% neq 0 (
    echo 錯誤：找不到 PHP。請確保 XAMPP 已安裝且 PHP 在 PATH 中。
    echo 或者請手動啟動 XAMPP 控制面板。
    pause
    exit /b 1
)

echo PHP 版本：
php --version
echo.

REM 檢查資料庫連接
echo 檢查資料庫連接...
php -r "
try {
    \$conn = new mysqli('localhost', 'root', '', 'test', 3306);
    if (\$conn->connect_error) {
        throw new Exception('MySQL 連接失敗: ' . \$conn->connect_error);
    }
    echo '資料庫連接成功！' . PHP_EOL;
    \$conn->close();
} catch (Exception \$e) {
    echo '資料庫連接失敗: ' . \$e->getMessage() . PHP_EOL;
    echo '請確保 MySQL 服務正在運行。' . PHP_EOL;
}
"
echo.

REM 啟動 PHP 內建服務器
echo 啟動 PHP 內建服務器...
echo 請在瀏覽器中訪問：http://localhost:8000
echo 按 Ctrl+C 停止服務器
echo.

cd /d C:\xampp\htdocs\Work-Out-4A-main\dist
php -S localhost:8000
