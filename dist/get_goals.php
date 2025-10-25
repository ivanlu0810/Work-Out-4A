<?php
// 開啟錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 處理 OPTIONS 請求
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連線設定 - 嘗試多種配置
$configs = [
    ['host' => 'localhost', 'dbname' => 'test', 'username' => 'root', 'password' => ''],
    ['host' => '127.0.0.1', 'dbname' => 'test', 'username' => 'root', 'password' => ''],
    ['host' => 'localhost', 'dbname' => 'fitness_tracker', 'username' => 'root', 'password' => ''],
];

$pdo = null;
$connection_error = '';

foreach ($configs as $config) {
    try {
        $pdo = new PDO(
            "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4", 
            $config['username'], 
            $config['password']
        );
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        break; // 連線成功，跳出迴圈
    } catch (PDOException $e) {
        $connection_error = $e->getMessage();
        continue; // 嘗試下一個配置
    }
}

if (!$pdo) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => '所有資料庫連線配置都失敗',
        'debug' => $connection_error
    ]);
    exit;
}

// 檢查是否為 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '只允許 GET 請求']);
    exit;
}

// 取得 user_id 參數
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '缺少 user_id 參數']);
    exit;
}

try {
    // 先檢查表格是否存在
    $check_table = $pdo->query("SHOW TABLES LIKE 'health_goals'");
    if (!$check_table->fetch()) {
        echo json_encode([
            'success' => false,
            'error' => 'health_goals 表格不存在，請先建立表格'
        ]);
        exit;
    }
    
    // 查詢使用者的健康目標
    $sql = "SELECT goal_weight, goal_fat_percentage, goal_muscle, created_at, updated_at 
            FROM health_goals 
            WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$user_id]);
    $goal = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($goal) {
        echo json_encode([
            'success' => true,
            'data' => [
                'goal_weight' => floatval($goal['goal_weight']),
                'goal_fat_percentage' => floatval($goal['goal_fat_percentage']),
                'goal_muscle' => floatval($goal['goal_muscle']),
                'created_at' => $goal['created_at'],
                'updated_at' => $goal['updated_at']
            ]
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => '尚未設定健康目標'
        ]);
    }

} catch (PDOException $e) {
    error_log("取得健康目標失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '取得目標時發生錯誤: ' . $e->getMessage()]);
}
?>
