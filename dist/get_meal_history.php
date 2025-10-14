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
    error_log("資料庫連線失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '資料庫連線失敗']);
    exit;
}

$user_id = $_SESSION['user_id'];

try {
    // 查詢用戶的餐食紀錄，按日期降序排列
    $sql = "SELECT mp.*, 
                   COUNT(mi.id) as item_count
            FROM meal_plans mp
            LEFT JOIN meal_items mi ON mp.id = mi.meal_plan_id
            WHERE mp.user_id = ?
            GROUP BY mp.id
            ORDER BY mp.date DESC, mp.created_at DESC
            LIMIT 50";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 格式化日期和數值
    foreach ($records as &$record) {
        $record['target_calories'] = (int)$record['target_calories'];
        $record['target_protein'] = (float)$record['target_protein'];
        $record['target_carbs'] = (float)$record['target_carbs'];
        $record['target_fat'] = (float)$record['target_fat'];
        $record['actual_calories'] = (int)$record['actual_calories'];
        $record['actual_protein'] = (float)$record['actual_protein'];
        $record['actual_carbs'] = (float)$record['actual_carbs'];
        $record['actual_fat'] = (float)$record['actual_fat'];
        $record['item_count'] = (int)$record['item_count'];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $records,
        'count' => count($records),
        'message' => '餐食紀錄載入成功'
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log("查詢餐食紀錄失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '查詢失敗：' . $e->getMessage()]);
}
?>


