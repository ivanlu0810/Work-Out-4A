<?php
header('Content-Type: application/json; charset=utf-8');
session_start();

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

try {
    $pdo = new PDO('mysql:host=localhost;dbname=your_db;charset=utf8mb4', 'dbuser', 'dbpass', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
    ]);

    $sql = "DELETE FROM inbody_records WHERE record_id = :record_id AND user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([':record_id' => $record_id, ':user_id' => $_SESSION['user_id']]);

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log($e->getMessage());
    echo json_encode(['success' => false, 'error' => '資料庫錯誤']);
}
