<?php
// 關閉所有錯誤顯示
ini_set('display_errors', 0);
ini_set('log_errors', 0);
error_reporting(0);

// 開始輸出緩衝
ob_start();

session_start();

// 清除任何可能的輸出
ob_clean();

header('Content-Type: application/json; charset=utf-8');

// 檢查登入狀態
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['error' => 'Not logged in']);
    exit;
}

// 直接回傳 session 資料
$user_info = [
    'userid' => $_SESSION['user_id'] ?? 'Unknown',
    'username' => $_SESSION['username'] ?? 'Unknown User',
    'email' => $_SESSION['email'] ?? 'No email',
    'gender' => $_SESSION['gender'] ?? 'Unknown',
    'age' => $_SESSION['age'] ?? null,
    'height' => $_SESSION['height'] ?? null,
    'weight' => $_SESSION['weight'] ?? null,
    'role' => $_SESSION['role'] ?? 'user',
    'avatar' => $_SESSION['avatar'] ?? null,
    'has_inbody_data' => false
];

echo json_encode($user_info, JSON_UNESCAPED_UNICODE);
?>
