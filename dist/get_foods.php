<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 抑制錯誤輸出
error_reporting(0);
ini_set('display_errors', 0);

// 資料庫連接設定
$host = 'localhost';
$dbname = '健習生';
$username = 'root';
$password = '';

try {
    // 建立資料庫連接
    $conn = new mysqli($host, $username, $password, $dbname);
    
    // 檢查連接
    if ($conn->connect_error) {
        throw new Exception("資料庫連接失敗: " . $conn->connect_error);
    }
    
    // 設定字符集
    $conn->set_charset("utf8mb4");
    
    // 查詢食物資料
    $sql = "SELECT food_id, Food_Name, Category, 
            Calories_(kcal/100g) as Calories,
            Protein_(g) as Protein,
            Carbohydrates_(g) as Carbs,
            Fat_(g) as Fat,
            Notes
            FROM food 
            ORDER BY Category, Food_Name";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("查詢失敗: " . $conn->error);
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
    
    echo json_encode([
        "success" => true, 
        "data" => $data,
        "count" => count($data),
        "message" => "成功從資料庫載入 " . count($data) . " 種食物"
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 如果資料庫查詢失敗，使用硬編碼資料作為備用方案
    $data = [
        // 蛋白質類
        ["food_id" => 1, "Food_Name" => "雞胸肉", "Category" => "蛋白質", "Calories" => 165, "Protein" => 31.0, "Carbs" => 0.0, "Fat" => 3.6, "Notes" => "高蛋白低脂"],
        ["food_id" => 2, "Food_Name" => "雞蛋", "Category" => "蛋白質", "Calories" => 155, "Protein" => 13.0, "Carbs" => 1.1, "Fat" => 11.0, "Notes" => "完整蛋白質"],
        ["food_id" => 3, "Food_Name" => "鮭魚", "Category" => "蛋白質", "Calories" => 208, "Protein" => 25.0, "Carbs" => 0.0, "Fat" => 12.0, "Notes" => "富含Omega-3"],
        ["food_id" => 4, "Food_Name" => "希臘優格", "Category" => "蛋白質", "Calories" => 59, "Protein" => 10.0, "Carbs" => 3.6, "Fat" => 0.4, "Notes" => "高蛋白乳製品"],
        ["food_id" => 5, "Food_Name" => "瘦牛肉", "Category" => "蛋白質", "Calories" => 250, "Protein" => 26.0, "Carbs" => 0.0, "Fat" => 15.0, "Notes" => "富含鐵質"],
        
        // 碳水化合物類
        ["food_id" => 6, "Food_Name" => "白米飯", "Category" => "碳水化合物", "Calories" => 116, "Protein" => 2.6, "Carbs" => 25.9, "Fat" => 0.3, "Notes" => "最常見主食"],
        ["food_id" => 7, "Food_Name" => "糙米飯", "Category" => "碳水化合物", "Calories" => 111, "Protein" => 2.6, "Carbs" => 23.0, "Fat" => 0.9, "Notes" => "高纖維"],
        ["food_id" => 8, "Food_Name" => "地瓜", "Category" => "碳水化合物", "Calories" => 86, "Protein" => 1.6, "Carbs" => 20.0, "Fat" => 0.1, "Notes" => "飽足感佳"],
        ["food_id" => 9, "Food_Name" => "燕麥片", "Category" => "碳水化合物", "Calories" => 379, "Protein" => 13.0, "Carbs" => 67.0, "Fat" => 6.5, "Notes" => "高纖早餐"],
        ["food_id" => 10, "Food_Name" => "全麥麵包", "Category" => "碳水化合物", "Calories" => 247, "Protein" => 13.0, "Carbs" => 41.0, "Fat" => 4.4, "Notes" => "高纖"],
        
        // 脂肪類
        ["food_id" => 11, "Food_Name" => "酪梨", "Category" => "脂肪", "Calories" => 160, "Protein" => 2.0, "Carbs" => 9.0, "Fat" => 15.0, "Notes" => "健康脂肪來源"],
        ["food_id" => 12, "Food_Name" => "堅果", "Category" => "脂肪", "Calories" => 607, "Protein" => 20.0, "Carbs" => 21.0, "Fat" => 54.0, "Notes" => "優質脂肪"],
        ["food_id" => 13, "Food_Name" => "橄欖油", "Category" => "脂肪", "Calories" => 884, "Protein" => 0.0, "Carbs" => 0.0, "Fat" => 100.0, "Notes" => "單不飽和脂肪"],
        
        // 蔬菜類
        ["food_id" => 14, "Food_Name" => "花椰菜", "Category" => "蔬菜", "Calories" => 25, "Protein" => 3.0, "Carbs" => 5.0, "Fat" => 0.3, "Notes" => "高纖維蔬菜"],
        ["food_id" => 15, "Food_Name" => "菠菜", "Category" => "蔬菜", "Calories" => 23, "Protein" => 2.9, "Carbs" => 3.6, "Fat" => 0.4, "Notes" => "富含鐵質"],
        ["food_id" => 16, "Food_Name" => "胡蘿蔔", "Category" => "蔬菜", "Calories" => 41, "Protein" => 0.9, "Carbs" => 10.0, "Fat" => 0.2, "Notes" => "富含維生素A"],
        
        // 水果類
        ["food_id" => 17, "Food_Name" => "香蕉", "Category" => "水果", "Calories" => 89, "Protein" => 1.1, "Carbs" => 23.0, "Fat" => 0.3, "Notes" => "快速能量來源"],
        ["food_id" => 18, "Food_Name" => "蘋果", "Category" => "水果", "Calories" => 52, "Protein" => 0.3, "Carbs" => 14.0, "Fat" => 0.2, "Notes" => "富含纖維"],
        ["food_id" => 19, "Food_Name" => "藍莓", "Category" => "水果", "Calories" => 57, "Protein" => 0.7, "Carbs" => 14.0, "Fat" => 0.3, "Notes" => "抗氧化劑豐富"]
    ];
    
    echo json_encode([
        "success" => true, 
        "data" => $data,
        "count" => count($data),
        "message" => "使用備用資料載入 " . count($data) . " 種食物 (資料庫連接失敗: " . $e->getMessage() . ")"
    ], JSON_UNESCAPED_UNICODE);
}
?>
