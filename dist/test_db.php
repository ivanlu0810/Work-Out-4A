<?php
header('Content-Type: application/json');

// 資料庫連線設定
$host = 'localhost';
$dbname = 'test';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 檢查 health_goals 表格是否存在
    $stmt = $pdo->query("SHOW TABLES LIKE 'health_goals'");
    $table_exists = $stmt->fetch();
    
    if ($table_exists) {
        // 檢查表格結構
        $stmt = $pdo->query("DESCRIBE health_goals");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'message' => '資料庫連線成功',
            'table_exists' => true,
            'columns' => $columns
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'health_goals 表格不存在',
            'table_exists' => false
        ]);
    }
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => '資料庫連線失敗: ' . $e->getMessage()
    ]);
}
?>
