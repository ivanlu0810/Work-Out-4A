<?php
// 開啟錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

// 檢查是否為 POST 請求
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '只允許 POST 請求']);
    exit;
}

// 讀取 JSON 資料或 POST 資料
$data = null;

if (isset($_SERVER['CONTENT_TYPE']) && $_SERVER['CONTENT_TYPE'] === 'application/json') {
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
} else {
    $data = $_POST;
}

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '無效的資料']);
    exit;
}

// 驗證必要欄位
$required_fields = ['user_id', 'goal_weight', 'goal_fat_percentage', 'goal_muscle'];
foreach ($required_fields as $field) {
    if (!isset($data[$field]) || $data[$field] === '') {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => "缺少必要欄位: $field"]);
        exit;
    }
}

$user_id = $mysqli->real_escape_string($data['user_id']);
$goal_weight = floatval($data['goal_weight']);
$goal_fat_percentage = floatval($data['goal_fat_percentage']);
$goal_muscle = floatval($data['goal_muscle']);

// 驗證數值範圍
if ($goal_weight < 30 || $goal_weight > 200) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '目標體重必須在 30-200 kg 之間']);
    exit;
}

if ($goal_fat_percentage < 5 || $goal_fat_percentage > 50) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '目標體脂率必須在 5-50% 之間']);
    exit;
}

if ($goal_muscle < 10 || $goal_muscle > 100) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '目標肌肉量必須在 10-100 kg 之間']);
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
    
    // 檢查是否已存在該使用者的目標
    $check_sql = "SELECT id FROM health_goals WHERE user_id = '$user_id'";
    $check_result = $mysqli->query($check_sql);
    
    if (!$check_result) {
        echo json_encode([
            'success' => false,
            'error' => '查詢失敗: ' . $mysqli->error
        ]);
        exit;
    }

    if ($check_result->num_rows > 0) {
        // 更新現有目標
        $update_sql = "UPDATE health_goals SET 
                       goal_weight = $goal_weight, 
                       goal_fat_percentage = $goal_fat_percentage, 
                       goal_muscle = $goal_muscle, 
                       updated_at = CURRENT_TIMESTAMP 
                       WHERE user_id = '$user_id'";
        
        if ($mysqli->query($update_sql)) {
            echo json_encode([
                'success' => true, 
                'message' => '健康目標更新成功！',
                'action' => 'updated'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => '更新目標失敗: ' . $mysqli->error
            ]);
        }
    } else {
        // 建立新目標
        $goal_id = uniqid('goal_', true);
        $insert_sql = "INSERT INTO health_goals (id, user_id, goal_weight, goal_fat_percentage, goal_muscle) 
                       VALUES ('$goal_id', '$user_id', $goal_weight, $goal_fat_percentage, $goal_muscle)";
        
        if ($mysqli->query($insert_sql)) {
            echo json_encode([
                'success' => true, 
                'message' => '健康目標設定成功！',
                'action' => 'created'
            ]);
        } else {
            echo json_encode([
                'success' => false, 
                'error' => '建立目標失敗: ' . $mysqli->error
            ]);
        }
    }

} catch (Exception $e) {
    error_log("儲存健康目標失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '儲存目標時發生錯誤: ' . $e->getMessage()]);
} finally {
    $mysqli->close();
}
?>
