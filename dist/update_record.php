<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// TODO: 檢查使用者是否登入，範例 assume $_SESSION['user_id']
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => '未登入']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['record_id'])) {
    echo json_encode(['success' => false, 'error' => '參數不完整']);
    exit;
}

$record_id = $input['record_id'];
// sanitize / validate as needed...

try {
    // 請改成你自己的 DB 連線方式
    $pdo = new PDO('mysql:host=localhost;dbname=your_db;charset=utf8mb4', 'dbuser', 'dbpass', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    // 請根據你 table 名稱修改，例如 inbody_records
    $sql = "UPDATE inbody_records SET 
              Date = :Date,
              age = :age,
              height_cm = :height_cm,
              weight_kg = :weight_kg,
              skeletal_muscle = :skeletal_muscle,
              body_fat = :body_fat,
              fat_percentage = :fat_percentage,
              basal_metabolism = :basal_metabolism,
              bmi = :bmi
            WHERE record_id = :record_id
              AND user_id = :user_id"; // 確保只能改自己的

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':Date' => $input['Date'] ?? null,
        ':age' => $input['age'] ?? null,
        ':height_cm' => $input['height_cm'] ?? null,
        ':weight_kg' => $input['weight_kg'] ?? null,
        ':skeletal_muscle' => $input['skeletal_muscle'] ?? null,
        ':body_fat' => $input['body_fat'] ?? null,
        ':fat_percentage' => $input['fat_percentage'] ?? null,
        ':basal_metabolism' => $input['basal_metabolism'] ?? null,
        ':bmi' => $input['bmi'] ?? null,
        ':record_id' => $record_id,
        ':user_id' => $_SESSION['user_id']
    ]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => '資料庫錯誤']);
}
