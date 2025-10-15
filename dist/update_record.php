<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

// 暫時模擬登入狀態進行測試
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 9; // 根據您的資料庫圖片，用戶ID是9
    $_SESSION['logged_in'] = true;
}

// 接收 POST 資料（application/x-www-form-urlencoded）
$record_id = $_POST['record_id'] ?? '';
if (empty($record_id)) {
    echo json_encode(['success' => false, 'error' => '缺少記錄ID']);
    exit;
}

try {
    // 使用與 get_history.php 相同的資料庫連線設定
    $host = "1.tcp.jp.ngrok.io";
    $port = 20959;
    $user = "root";
    $password = "";
    $dbname = "test";
    
    $conn = new mysqli($host, $user, $password, $dbname, $port);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        echo json_encode(['success' => false, 'error' => '資料庫連線失敗: ' . $conn->connect_error]);
        exit;
    }

    // 更新健康記錄（使用與 get_history.php 相同的資料表名稱）
    $sql = "UPDATE inbody_records SET 
              `Date` = ?,
              age = ?,
              `height-cm` = ?,
              `weight-kg` = ?,
              skeletal_muscle = ?,
              body_fat = ?,
              fat_percentage = ?,
              basal_metabolism = ?,
              bmi = ?
            WHERE record_id = ?
              AND user_id = ?";

    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'error' => 'SQL錯誤: ' . $conn->error]);
        exit;
    }

    // 準備變數以避免引用問題
    $date = $_POST['Date'] ?? null;
    $age = $_POST['age'] ?? null;
    $height_cm = $_POST['height_cm'] ?? null;
    $weight_kg = $_POST['weight_kg'] ?? null;
    $skeletal_muscle = $_POST['skeletal_muscle'] ?? null;
    $body_fat = $_POST['body_fat'] ?? null;
    $fat_percentage = $_POST['fat_percentage'] ?? null;
    $basal_metabolism = $_POST['basal_metabolism'] ?? null;
    $bmi = $_POST['bmi'] ?? null;

    $stmt->bind_param("ssdddddddsi", 
        $date,
        $age,
        $height_cm,
        $weight_kg,
        $skeletal_muscle,
        $body_fat,
        $fat_percentage,
        $basal_metabolism,
        $bmi,
        $record_id,
        $_SESSION['user_id']
    );

    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => '更新成功']);
    } else {
        echo json_encode(['success' => false, 'error' => '沒有找到要更新的記錄或沒有變更']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log('更新記錄錯誤: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '資料庫錯誤: ' . $e->getMessage()]);
}
?>
