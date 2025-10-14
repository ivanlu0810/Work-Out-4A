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
    echo json_encode(['success' => false, 'error' => '資料庫連線失敗，請檢查 XAMPP MySQL 服務是否啟動']);
    exit;
}

// 獲取 POST 資料
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => '無效的資料格式']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? $data['user_id'];
$date = $data['date'] ?? date('Y-m-d');
$target_calories = $data['target_calories'] ?? 0;
$target_protein = $data['target_protein'] ?? 0;
$target_carbs = $data['target_carbs'] ?? 0;
$target_fat = $data['target_fat'] ?? 0;
$actual_calories = $data['actual_calories'] ?? 0;
$actual_protein = $data['actual_protein'] ?? 0;
$actual_carbs = $data['actual_carbs'] ?? 0;
$actual_fat = $data['actual_fat'] ?? 0;
$meals = $data['meals'] ?? [];

try {
    // 檢查表格是否存在，如果不存在則創建
    $checkTables = $pdo->query("SHOW TABLES LIKE 'meal_plans'")->fetch();
    if (!$checkTables) {
        // 創建 meal_plans 表格
        $pdo->exec("CREATE TABLE IF NOT EXISTS meal_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id VARCHAR(50) NOT NULL,
            date DATE NOT NULL,
            target_calories INT DEFAULT 0,
            target_protein DECIMAL(8,2) DEFAULT 0,
            target_carbs DECIMAL(8,2) DEFAULT 0,
            target_fat DECIMAL(8,2) DEFAULT 0,
            actual_calories INT DEFAULT 0,
            actual_protein DECIMAL(8,2) DEFAULT 0,
            actual_carbs DECIMAL(8,2) DEFAULT 0,
            actual_fat DECIMAL(8,2) DEFAULT 0,
            meals_data JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_date (user_id, date),
            INDEX idx_user_id (user_id),
            INDEX idx_date (date)
        )");
    }
    
    $checkItems = $pdo->query("SHOW TABLES LIKE 'meal_items'")->fetch();
    if (!$checkItems) {
        // 創建 meal_items 表格
        $pdo->exec("CREATE TABLE IF NOT EXISTS meal_items (
            id INT AUTO_INCREMENT PRIMARY KEY,
            meal_plan_id INT NOT NULL,
            meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
            food_name VARCHAR(100) NOT NULL,
            quantity_grams DECIMAL(8,2) NOT NULL,
            calories_per_100g DECIMAL(8,2) NOT NULL,
            protein_per_100g DECIMAL(8,2) NOT NULL,
            carbs_per_100g DECIMAL(8,2) NOT NULL,
            fat_per_100g DECIMAL(8,2) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE CASCADE,
            INDEX idx_meal_plan_id (meal_plan_id),
            INDEX idx_meal_type (meal_type)
        )");
    }
    
    $pdo->beginTransaction();
    
    // 檢查是否已存在該日期的餐食計畫
    $checkSql = "SELECT id FROM meal_plans WHERE user_id = ? AND date = ?";
    $checkStmt = $pdo->prepare($checkSql);
    $checkStmt->execute([$user_id, $date]);
    $existingPlan = $checkStmt->fetch();
    
    if ($existingPlan) {
        // 更新現有計畫
        $updateSql = "UPDATE meal_plans SET 
                      target_calories = ?, target_protein = ?, target_carbs = ?, target_fat = ?,
                      actual_calories = ?, actual_protein = ?, actual_carbs = ?, actual_fat = ?,
                      meals_data = ?, updated_at = NOW()
                      WHERE id = ?";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute([
            $target_calories, $target_protein, $target_carbs, $target_fat,
            $actual_calories, $actual_protein, $actual_carbs, $actual_fat,
            json_encode($meals), $existingPlan['id']
        ]);
        
        $plan_id = $existingPlan['id'];
    } else {
        // 插入新計畫
        $insertSql = "INSERT INTO meal_plans 
                      (user_id, date, target_calories, target_protein, target_carbs, target_fat,
                       actual_calories, actual_protein, actual_carbs, actual_fat, meals_data, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $insertStmt = $pdo->prepare($insertSql);
        $insertStmt->execute([
            $user_id, $date, $target_calories, $target_protein, $target_carbs, $target_fat,
            $actual_calories, $actual_protein, $actual_carbs, $actual_fat, json_encode($meals)
        ]);
        
        $plan_id = $pdo->lastInsertId();
    }
    
    // 刪除現有的餐食項目
    $deleteItemsSql = "DELETE FROM meal_items WHERE meal_plan_id = ?";
    $deleteItemsStmt = $pdo->prepare($deleteItemsSql);
    $deleteItemsStmt->execute([$plan_id]);
    
    // 插入新的餐食項目
    $insertItemSql = "INSERT INTO meal_items 
                      (meal_plan_id, meal_type, food_name, quantity_grams, calories_per_100g, 
                       protein_per_100g, carbs_per_100g, fat_per_100g, created_at)
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    $insertItemStmt = $pdo->prepare($insertItemSql);
    
    foreach ($meals as $mealType => $items) {
        foreach ($items as $item) {
            $insertItemStmt->execute([
                $plan_id, $mealType, $item['name'], $item['grams'],
                $item['caloriesPer100'], $item['proteinPer100'], 
                $item['carbsPer100'], $item['fatPer100']
            ]);
        }
    }
    
    $pdo->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => '餐食計畫儲存成功',
        'plan_id' => $plan_id
    ]);
    
} catch (Exception $e) {
    $pdo->rollBack();
    error_log("儲存餐食計畫失敗: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '儲存失敗：' . $e->getMessage()]);
}
?>
