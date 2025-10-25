<?php
// 開啟錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// 測試不同的資料庫連線設定
$configs = [
    ['host' => 'localhost', 'dbname' => 'test', 'username' => 'root', 'password' => ''],
    ['host' => '127.0.0.1', 'dbname' => 'test', 'username' => 'root', 'password' => ''],
    ['host' => 'localhost', 'dbname' => 'fitness_tracker', 'username' => 'root', 'password' => ''],
    ['host' => 'localhost', 'dbname' => 'test', 'username' => 'root', 'password' => 'root'],
];

$results = [];

foreach ($configs as $i => $config) {
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
            $config['username'], 
            $config['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // 測試查詢
        $stmt = $pdo->query("SELECT 1 as test");
        $result = $stmt->fetch();
        
        $results[] = [
            'config' => $i + 1,
            'settings' => $config,
            'status' => 'success',
            'test_query' => $result['test']
        ];
        
    } catch (PDOException $e) {
        $results[] = [
            'config' => $i + 1,
            'settings' => $config,
            'status' => 'failed',
            'error' => $e->getMessage()
        ];
    }
}

echo json_encode([
    'message' => '資料庫連線測試結果',
    'results' => $results
], JSON_PRETTY_PRINT);
?>