<?php
// 清空訓練計畫資料表 API
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 檢測是否為本地環境
$isLocal = !isset($_SERVER['HTTP_X_FORWARDED_FOR']) && 
           !isset($_SERVER['HTTP_X_REAL_IP']) && 
           ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1' || strpos($_SERVER['SERVER_NAME'], 'ngrok') === false);

// 資料庫連接
if ($isLocal) {
    // 本地 XAMPP 配置
    $host = 'localhost';
    $port = 3307;  // XAMPP MySQL 端口
    $dbname = 'test';  // 使用 test 資料庫
    $username = 'root';
    $password = '';
} else {
    // 遠端 ngrok 配置
    $host = '1.tcp.jp.ngrok.io';
    $port = 20959;
    $dbname = 'test';
    $username = 'root';
    $password = '';
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $results = [];
    
    // 1. 清空 training_plan_completion 表
    $stmt1 = $pdo->prepare("DELETE FROM training_plan_completion");
    $stmt1->execute();
    $results['training_plan_completion'] = $stmt1->rowCount();
    
    // 2. 清空 training_plan_exercises 表
    $stmt2 = $pdo->prepare("DELETE FROM training_plan_exercises");
    $stmt2->execute();
    $results['training_plan_exercises'] = $stmt2->rowCount();
    
    // 3. 清空 training_plans 表
    $stmt3 = $pdo->prepare("DELETE FROM training_plans");
    $stmt3->execute();
    $results['training_plans'] = $stmt3->rowCount();
    
    // 重置自動遞增ID
    $pdo->exec("ALTER TABLE training_plan_completion AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE training_plan_exercises AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE training_plans AUTO_INCREMENT = 1");
    
    // 計算總數
    $totalDeleted = array_sum($results);
    
    echo json_encode([
        'success' => true,
        'message' => '所有訓練計畫資料已清空',
        'deleted_records' => $results,
        'total_deleted' => $totalDeleted
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '清空資料失敗: ' . $e->getMessage()
    ]);
}
?>
