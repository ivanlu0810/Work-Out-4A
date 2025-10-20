<?php
/**
 * 本地 XAMPP 資料庫配置檔案
 * 用於在本地環境中運行專案
 */

// 本地 XAMPP 資料庫配置
define('DB_HOST', 'localhost');
define('DB_PORT', '3306');
define('DB_NAME', 'workout_4a');
define('DB_USER', 'root');
define('DB_PASSWORD', '');

// 資料庫連接函數
function getDatabaseConnection() {
    try {
        $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $pdo = new PDO($dsn, DB_USER, DB_PASSWORD);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        die("資料庫連接失敗: " . $e->getMessage());
    }
}

// MySQLi 連接函數（用於需要 MySQLi 的檔案）
function getMySQLiConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        die("MySQLi 連接失敗: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
    return $conn;
}

// 檢查是否為本地環境
function isLocalEnvironment() {
    return !isset($_SERVER['HTTP_HOST']) || 
           strpos($_SERVER['HTTP_HOST'], 'ngrok') === false ||
           strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
           strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;
}

// 根據環境選擇資料庫配置
function getDatabaseConfig() {
    if (isLocalEnvironment()) {
        return [
            'host' => DB_HOST,
            'port' => DB_PORT,
            'dbname' => DB_NAME,
            'username' => DB_USER,
            'password' => DB_PASSWORD
        ];
    } else {
        // 遠端 ngrok 配置
        return [
            'host' => '1.tcp.jp.ngrok.io',
            'port' => '20959',
            'dbname' => 'test',
            'username' => 'root',
            'password' => ''
        ];
    }
}
?>
