<?php
// 清空訓練計畫資料表
header('Content-Type: application/json; charset=utf-8');

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
    
    echo "=== 開始清空訓練計畫資料表 ===\n";
    
    // 開始事務
    $pdo->beginTransaction();
    
    // 1. 清空 training_plan_completion 表
    $stmt1 = $pdo->prepare("DELETE FROM training_plan_completion");
    $stmt1->execute();
    $deleted1 = $stmt1->rowCount();
    echo "已刪除 training_plan_completion 表記錄: $deleted1 筆\n";
    
    // 2. 清空 training_plan_exercises 表
    $stmt2 = $pdo->prepare("DELETE FROM training_plan_exercises");
    $stmt2->execute();
    $deleted2 = $stmt2->rowCount();
    echo "已刪除 training_plan_exercises 表記錄: $deleted2 筆\n";
    
    // 3. 清空 training_plans 表
    $stmt3 = $pdo->prepare("DELETE FROM training_plans");
    $stmt3->execute();
    $deleted3 = $stmt3->rowCount();
    echo "已刪除 training_plans 表記錄: $deleted3 筆\n";
    
    // 重置自動遞增ID
    $pdo->exec("ALTER TABLE training_plan_completion AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE training_plan_exercises AUTO_INCREMENT = 1");
    $pdo->exec("ALTER TABLE training_plans AUTO_INCREMENT = 1");
    echo "已重置自動遞增ID\n";
    
    // 提交事務
    $pdo->commit();
    
    // 驗證清空結果
    echo "\n=== 驗證清空結果 ===\n";
    $tables = ['training_plan_completion', 'training_plan_exercises', 'training_plans'];
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM $table");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "$table 剩餘記錄: {$result['count']} 筆\n";
    }
    
    echo "\n=== 清空完成 ===\n";
    echo "總共刪除記錄: " . ($deleted1 + $deleted2 + $deleted3) . " 筆\n";
    
} catch (Exception $e) {
    // 回滾事務
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    echo "錯誤: " . $e->getMessage() . "\n";
    http_response_code(500);
}
?>

