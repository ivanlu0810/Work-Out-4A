<?php
session_start();

// 檢查是否已登入
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '未登入']);
    exit;
}

// 設定資料庫連線 - 使用與 get_user_info.php 相同的連線設定
$host = '1.tcp.jp.ngrok.io';
$dbname = 'test';
$username = 'root';
$password = '';
$port = 20959;

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '資料庫連線失敗']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? 'guest';
$date = $_GET['date'] ?? date('Y-m-d');

try {
    // 查詢餐食計畫
    $planSql = "SELECT * FROM meal_plans WHERE user_id = ? AND date = ?";
    $planStmt = $pdo->prepare($planSql);
    $planStmt->execute([$user_id, $date]);
    $plan = $planStmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$plan) {
        echo json_encode(['success' => false, 'message' => '沒有找到該日期的餐食計畫']);
        exit;
    }
    
    // 查詢餐食項目
    $itemsSql = "SELECT * FROM meal_items WHERE meal_plan_id = ? ORDER BY meal_type, id";
    $itemsStmt = $pdo->prepare($itemsSql);
    $itemsStmt->execute([$plan['id']]);
    $items = $itemsStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $plan['items'] = $items;
    
    echo json_encode([
        'success' => true,
        'data' => $plan
    ]);
    
} catch (Exception $e) {
    error_log("載入餐食計畫失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '載入失敗：' . $e->getMessage()]);
}
?>
