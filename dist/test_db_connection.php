<?php
header('Content-Type: application/json; charset=utf-8');

// 根據環境選擇資料庫配置
$isLocal = !isset($_SERVER['HTTP_HOST']) || 
           strpos($_SERVER['HTTP_HOST'], 'ngrok') === false ||
           strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
           strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;

if ($isLocal) {
    // 本地 XAMPP 配置 - 使用正確的 MySQL 設定
    $host = '127.0.0.1';
    $dbname = 'test';
    $username = 'root';
    $password = '';
    $port = 3306;
} else {
    // 遠端 ngrok 配置
    $host = '1.tcp.jp.ngrok.io';
    $dbname = 'test';
    $username = 'root';
    $password = '';
    $port = 20959;
}

try {
    // 測試 MySQLi 連接
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception("MySQLi 連接失敗: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // 測試查詢
    $result = $conn->query("SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = '$dbname'");
    $row = $result->fetch_assoc();
    $tableCount = $row['table_count'];
    
    // 檢查主要表是否存在
    $tables = ['user', 'inbody_records', 'exercises', 'training_plans', 'food'];
    $existingTables = [];
    
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->num_rows > 0) {
            $existingTables[] = $table;
        }
    }
    
    $conn->close();
    
    echo json_encode([
        'success' => true,
        'message' => "成功連接到資料庫 '$dbname'，共找到 $tableCount 個表",
        'environment' => $isLocal ? 'local' : 'remote',
        'host' => $host,
        'port' => $port,
        'database' => $dbname,
        'existing_tables' => $existingTables
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'environment' => $isLocal ? 'local' : 'remote',
        'host' => $host,
        'port' => $port,
        'database' => $dbname
    ]);
}
?>
