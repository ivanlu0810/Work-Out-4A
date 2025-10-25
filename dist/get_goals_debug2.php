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

// 資料庫連線設定
$host = 'localhost';
$dbname = 'test';
$username = 'root';
$password = '';

echo json_encode(['debug' => '開始連線資料庫...']);

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo json_encode(['debug' => '資料庫連線成功']);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => '資料庫連線失敗: ' . $e->getMessage(),
        'debug' => '連線參數: host=' . $host . ', dbname=' . $dbname . ', username=' . $username
    ]);
    exit;
}

// 檢查是否為 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => '只允許 GET 請求']);
    exit;
}

// 取得 user_id 參數
$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (empty($user_id)) {
    echo json_encode(['success' => false, 'error' => '缺少 user_id 參數']);
    exit;
}

echo json_encode(['debug' => 'user_id: ' . $user_id]);

try {
    // 先檢查表格是否存在
    $check_table = $pdo->query("SHOW TABLES LIKE 'health_goals'");
    $table_exists = $check_table->fetch();
    
    if (!$table_exists) {
        echo json_encode([
            'success' => false,
            'error' => 'health_goals 表格不存在',
            'debug' => '表格檢查失敗'
        ]);
        exit;
    }
    
    echo json_encode(['debug' => '表格存在，開始查詢...']);
    
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
            ],
            'debug' => '找到目標資料'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'data' => null,
            'message' => '尚未設定健康目標',
            'debug' => '未找到目標資料'
        ]);
    }

} catch (PDOException $e) {
    echo json_encode([
        'success' => false, 
        'error' => '資料庫查詢失敗: ' . $e->getMessage(),
        'debug' => 'SQL 錯誤'
    ]);
}
?>
