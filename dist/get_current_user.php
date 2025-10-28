<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

// 從 session 取得 user_id
$user_id = isset($_SESSION['user_id']) ? (int)$_SESSION['user_id'] : null;

if (!$user_id) {
    echo json_encode(['success' => false, 'error' => '未登入']);
    exit;
}

echo json_encode(['success' => true, 'user_id' => $user_id]);
?>
