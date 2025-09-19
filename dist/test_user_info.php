<?php
session_start();

// 模擬登入狀態（僅用於測試）
if (!isset($_SESSION['logged_in'])) {
    $_SESSION['logged_in'] = true;
    $_SESSION['user_id'] = '1';
    $_SESSION['username'] = '測試使用者';
    $_SESSION['email'] = 'test@example.com';
    $_SESSION['gender'] = '男';
    $_SESSION['role'] = 'user';
}

// 直接回傳 session 資料進行測試
$user_info = [
    'userid' => $_SESSION['user_id'] ?? 'Unknown UserID',
    'username' => $_SESSION['username'] ?? 'Unknown User',
    'email' => $_SESSION['email'] ?? 'No email',
    'gender' => $_SESSION['gender'] ?? 'Unknown',
    'age' => null,
    'height' => null,
    'weight' => null,
    'role' => $_SESSION['role'] ?? 'user',
    'avatar' => $_SESSION['avatar'] ?? null,
    'has_inbody_data' => false
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($user_info, JSON_UNESCAPED_UNICODE);
?>
