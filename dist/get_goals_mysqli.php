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

// 資料庫連線設定 - 使用 mysqli
$host = 'localhost:3307';
$dbname = 'test';
$username = 'root';
$password = '';

// 嘗試連線
$mysqli = new mysqli($host, $username, $password, $dbname);

// 檢查連線
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'error' => '資料庫連線失敗: ' . $mysqli->connect_error
    ]);
    exit;
}

// 設定字符集
$mysqli->set_charset("utf8mb4");

// 檢查是否為 GET 請求
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '只允許 GET 請求']);
    exit;
}

// 取得 user_id 參數
$user_id = isset($_GET['user_id']) ? $mysqli->real_escape_string($_GET['user_id']) : '';

if (empty($user_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '缺少 user_id 參數']);
    exit;
}

try {
    // 先檢查表格是否存在
    $check_table = $mysqli->query("SHOW TABLES LIKE 'health_goals'");
    if (!$check_table || $check_table->num_rows == 0) {
        echo json_encode([
            'success' => false,
            'error' => 'health_goals 表格不存在，請先建立表格'
        ]);
        exit;
    }
    
    // 查詢使用者的健康目標
    $sql = "SELECT goal_weight, goal_fat_percentage, goal_muscle, created_at, updated_at 
            FROM health_goals 
            WHERE user_id = '$user_id'";
    
    $result = $mysqli->query($sql);
    
    if (!$result) {
        echo json_encode([
            'success' => false,
            'error' => '查詢失敗: ' . $mysqli->error
        ]);
        exit;
    }

    if ($result->num_rows > 0) {
        $goal = $result->fetch_assoc();
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

} catch (Exception $e) {
    error_log("取得健康目標失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '取得目標時發生錯誤: ' . $e->getMessage()]);
} finally {
    $mysqli->close();
}
?>
