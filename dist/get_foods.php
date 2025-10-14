<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 只記錄錯誤，不輸出 HTML（避免破壞 JSON）
error_reporting(E_ALL);
ini_set('display_errors', 0);
ob_start();

// 備用食物資料
function getBackupFoods() {
    return [
        ["food_id" => 1, "Food_Name" => "雞胸肉", "Category" => "蛋白質", "Calories" => 165, "Protein" => 31.0, "Carbs" => 0.0, "Fat" => 3.6, "Notes" => "高蛋白低脂"],
        ["food_id" => 2, "Food_Name" => "白米飯", "Category" => "碳水化合物", "Calories" => 116, "Protein" => 2.6, "Carbs" => 25.9, "Fat" => 0.3, "Notes" => "最常見主食"],
        ["food_id" => 3, "Food_Name" => "地瓜", "Category" => "碳水化合物", "Calories" => 86, "Protein" => 1.6, "Carbs" => 20.0, "Fat" => 0.1, "Notes" => "飽足感佳"],
        ["food_id" => 4, "Food_Name" => "燕麥片", "Category" => "碳水化合物", "Calories" => 379, "Protein" => 13.0, "Carbs" => 67.0, "Fat" => 6.5, "Notes" => "高纖早餐"],
        ["food_id" => 5, "Food_Name" => "雞蛋", "Category" => "蛋白質", "Calories" => 155, "Protein" => 13.0, "Carbs" => 1.1, "Fat" => 11.0, "Notes" => "完整蛋白質"],
        ["food_id" => 6, "Food_Name" => "鮭魚", "Category" => "蛋白質", "Calories" => 208, "Protein" => 25.0, "Carbs" => 0.0, "Fat" => 12.0, "Notes" => "富含Omega-3"],
        ["food_id" => 7, "Food_Name" => "花椰菜", "Category" => "蔬菜", "Calories" => 25, "Protein" => 3.0, "Carbs" => 5.0, "Fat" => 0.3, "Notes" => "高纖維蔬菜"],
        ["food_id" => 8, "Food_Name" => "酪梨", "Category" => "脂肪", "Calories" => 160, "Protein" => 2.0, "Carbs" => 9.0, "Fat" => 15.0, "Notes" => "健康脂肪來源"],
        ["food_id" => 9, "Food_Name" => "香蕉", "Category" => "水果", "Calories" => 89, "Protein" => 1.1, "Carbs" => 23.0, "Fat" => 0.3, "Notes" => "快速能量來源"],
        ["food_id" => 10, "Food_Name" => "希臘優格", "Category" => "蛋白質", "Calories" => 59, "Protein" => 10.0, "Carbs" => 3.6, "Fat" => 0.4, "Notes" => "高蛋白乳製品"],
        ["food_id" => 11, "Food_Name" => "吐司(白吐司)", "Category" => "碳水化合物", "Calories" => 265, "Protein" => 8.0, "Carbs" => 49.0, "Fat" => 3.2, "Notes" => "早餐主食"],
        ["food_id" => 12, "Food_Name" => "無糖豆漿", "Category" => "蛋白質", "Calories" => 33, "Protein" => 3.0, "Carbs" => 2.0, "Fat" => 1.8, "Notes" => "植物性蛋白質"],
        ["food_id" => 13, "Food_Name" => "牛奶", "Category" => "蛋白質", "Calories" => 42, "Protein" => 3.4, "Carbs" => 5.0, "Fat" => 1.0, "Notes" => "鈣質豐富"],
        ["food_id" => 14, "Food_Name" => "堅果", "Category" => "脂肪", "Calories" => 607, "Protein" => 20.0, "Carbs" => 21.0, "Fat" => 54.0, "Notes" => "健康脂肪來源"],
        ["food_id" => 15, "Food_Name" => "糙米飯", "Category" => "碳水化合物", "Calories" => 111, "Protein" => 2.6, "Carbs" => 23.0, "Fat" => 0.9, "Notes" => "高纖維"]
    ];
}

// 資料庫連接設定 - 使用與 get_user_info.php 相同的連線設定
$host = '1.tcp.jp.ngrok.io';
$dbname = 'test';
$username = 'root';
$password = '';
$port = 20959;

$useBackup = false;
$errorMessage = '';

try {
    // 建立資料庫連接
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
    
    // 設定連線超時
    $conn = new mysqli($host, $username, $password, '', $port);
    $conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 5);
    
    if ($conn->connect_error) {
        throw new Exception("MySQL 連線失敗: " . $conn->connect_error);
    }
    
    // 檢查資料庫是否存在
    $result = $conn->query("SHOW DATABASES LIKE 'test'");
    if ($result->num_rows == 0) {
        throw new Exception("資料庫 'test' 不存在");
    }
    
    // 選擇資料庫
    if (!$conn->select_db($dbname)) {
        throw new Exception("無法選擇資料庫 'test'");
    }
    
    // 檢查 food 表是否存在
    $result = $conn->query("SHOW TABLES LIKE 'food'");
    if ($result->num_rows == 0) {
        throw new Exception("資料表 'food' 不存在");
    }
    
    $conn->set_charset("utf8mb4");
    
    // 先嘗試簡單查詢
    $simpleSql = "SELECT COUNT(*) as count FROM food";
    $result = $conn->query($simpleSql);
    if ($result === false) {
        throw new Exception("無法查詢 food 表: " . $conn->error);
    }
    
    $countRow = $result->fetch_assoc();
    $totalCount = $countRow['count'];
    
    if ($totalCount == 0) {
        throw new Exception("food 表中沒有資料");
    }
    
    // 查詢食物資料，使用更簡單的 SQL
    $sql = "SELECT food_id, Food_Name, Category, 
            `Calories_(kcal/100g)` as Calories,
            `Protein_(g)` as Protein,
            `Carbohydrates_(g)` as Carbs,
            `Fat_(g)` as Fat,
            Notes
            FROM food 
            ORDER BY Category, Food_Name
            LIMIT 100";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        // 如果欄位名稱有問題，嘗試另一種查詢方式
        $sql2 = "SELECT food_id, Food_Name, Category, 
                Calories, Protein, Carbs, Fat, Notes
                FROM food 
                ORDER BY Category, Food_Name
                LIMIT 100";
        
        $result = $conn->query($sql2);
        if ($result === false) {
            throw new Exception("查詢失敗: " . $conn->error);
        }
    }
    
    $data = [];
    
    // 添加資料庫中的食物
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "food_id" => (int)$row['food_id'],
            "Food_Name" => $row['Food_Name'],
            "Category" => $row['Category'],
            "Calories" => (float)$row['Calories'],
            "Protein" => (float)$row['Protein'],
            "Carbs" => (float)$row['Carbs'],
            "Fat" => (float)$row['Fat'],
            "Notes" => $row['Notes']
        ];
    }
    
    $conn->close();
    
    ob_end_clean();
    echo json_encode([
        "success" => true, 
        "data" => $data,
        "count" => count($data),
        "total_count" => $totalCount,
        "message" => "成功從資料庫載入 " . count($data) . " 種食物"
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    $errorMessage = $e->getMessage();
    $useBackup = true;
}

// 如果資料庫連線失敗，使用備用資料
if ($useBackup) {
    ob_end_clean();
    $backupData = getBackupFoods();
    echo json_encode([
        "success" => true, 
        "data" => $backupData,
        "count" => count($backupData),
        "message" => "使用備用食物資料 (" . count($backupData) . " 種)",
        "warning" => "資料庫連線失敗，已使用備用資料。錯誤: " . $errorMessage,
        "debug" => [
            "host" => $host,
            "db" => $dbname,
            "port" => $port,
            "username" => $username,
            "error" => $errorMessage
        ]
    ], JSON_UNESCAPED_UNICODE);
}
?>
