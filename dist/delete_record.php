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

    $sql = "DELETE FROM inbody_records WHERE record_id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        echo json_encode(['success' => false, 'error' => 'SQL錯誤: ' . $conn->error]);
        exit;
    }

    $stmt->bind_param("si", $record_id, $_SESSION['user_id']);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo json_encode(['success' => true, 'message' => '刪除成功']);
    } else {
        echo json_encode(['success' => false, 'error' => '沒有找到要刪除的記錄']);
    }
    
    $stmt->close();
    $conn->close();
} catch (Exception $e) {
    error_log('刪除記錄錯誤: ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => '資料庫錯誤: ' . $e->getMessage()]);
}
?>
