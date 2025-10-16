<?php
// 關閉所有錯誤顯示，確保只輸出 JSON
ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(0);

// 開始輸出緩衝，防止任何意外輸出
ob_start();

session_start();

// 清除任何可能的輸出
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 如果沒有登入狀態，嘗試使用預設的測試用戶資料
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    // 設定預設的測試用戶資料（根據您的資料庫圖片，用戶ID是9）
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = '9';
    $_SESSION['username'] = '測試用戶';
    $_SESSION['email'] = 'test@example.com';
    $_SESSION['gender'] = '男';
    $_SESSION['role'] = 'user';
}

try {
    // 資料庫設定
    $host = '1.tcp.jp.ngrok.io';
    $db   = 'test';
    $user = 'root';
    $pass = '';
    $port = 20959;

    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        throw new Exception('連線失敗: ' . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");

    $user_id = $_SESSION['user_id'];

    // 查詢用戶基本資料
    $user_stmt = $conn->prepare("SELECT username, email, gender, role FROM user WHERE user_id = ?");
    $user_stmt->bind_param('s', $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user_data = $user_result->fetch_assoc();
    
    // 調試：記錄抓取到的性別資料
    error_log("User data from database: " . print_r($user_data, true));

    // 查詢最新的 InBody 資料
    $inbody_stmt = $conn->prepare("SELECT age, `height-cm` as height, `weight-kg` as weight, skeletal_muscle, body_fat, fat_percentage, basal_metabolism, bmi, Date as last_inbody_date FROM inbody_records WHERE user_id = ? ORDER BY Date DESC LIMIT 1");
    $inbody_stmt->bind_param('s', $user_id);
    $inbody_stmt->execute();
    $inbody_result = $inbody_stmt->get_result();
    $inbody_data = $inbody_result->fetch_assoc();

    // 組合用戶資料
    $user_info = [
        'userid' => $user_id,
        'username' => $user_data['username'] ?? $_SESSION['username'] ?? 'Unknown User',
        'email' => $user_data['email'] ?? $_SESSION['email'] ?? 'No email',
        'gender' => $user_data['gender'] ?? $_SESSION['gender'] ?? 'Unknown',
        'role' => $user_data['role'] ?? $_SESSION['role'] ?? 'user',
        'avatar' => $_SESSION['avatar'] ?? null,
        'has_inbody_data' => !empty($inbody_data)
    ];

    // 如果有 InBody 資料，加入身體資料
    if ($inbody_data) {
        $user_info = array_merge($user_info, [
            'age' => $inbody_data['age'],
            'height' => $inbody_data['height'],
            'weight' => $inbody_data['weight'],
            'skeletal_muscle' => $inbody_data['skeletal_muscle'],
            'body_fat' => $inbody_data['body_fat'],
            'fat_percentage' => $inbody_data['fat_percentage'],
            'basal_metabolism' => $inbody_data['basal_metabolism'],
            'bmi' => $inbody_data['bmi'],
            'last_inbody_date' => $inbody_data['last_inbody_date']
        ]);
    }

    $user_stmt->close();
    $inbody_stmt->close();
    $conn->close();

    // 確保回傳正確的 JSON 格式
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($user_info, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // 記錄錯誤
    error_log("Database error: " . $e->getMessage());
    
    // 如果資料庫查詢失敗，回傳 session 資料
    $user_info = [
        'userid' => $_SESSION['user_id'] ?? 'Unknown UserID',
        'username' => $_SESSION['username'] ?? 'Unknown User',
        'email' => $_SESSION['email'] ?? 'No email',
        'gender' => $_SESSION['gender'] ?? 'Unknown',
        'role' => $_SESSION['role'] ?? 'user',
        'avatar' => $_SESSION['avatar'] ?? null,
        'has_inbody_data' => false
    ];
    
    // 確保回傳正確的 JSON 格式
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($user_info, JSON_UNESCAPED_UNICODE);
}
?>