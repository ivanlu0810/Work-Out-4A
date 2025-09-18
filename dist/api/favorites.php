<?php
// 收藏 API：GET 讀取、POST 新增/覆寫、DELETE 刪除
// 依現有登入流程，沿用 ngrok test 資料庫與 session 驗證

session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '未登入']);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => '缺少 user_id']);
    exit;
}

// 資料庫設定：依環境動態切換（本機 vs ngrok）
$isNgrok = isset($_SERVER['HTTP_HOST']) && strpos($_SERVER['HTTP_HOST'], 'ngrok') !== false;
if ($isNgrok) {
    // 遠端 ngrok 轉發到你本機 MySQL，沿用既有設定
    $host = '1.tcp.jp.ngrok.io';
    $db   = 'test';
    $user = 'root';
    $pass = '';
    $port = 20959;
} else {
    // 本機開發（修正為你的 port 3307）
    $host = '127.0.0.1';
    $db   = 'test';
    $user = 'root';
    $pass = '';
    $port = 3307;
}

$mysqli = new mysqli($host, $user, $pass, $db, $port);
if ($mysqli->connect_error) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '資料庫連線失敗', 'details' => $mysqli->connect_error, 'host' => $host, 'port' => $port, 'db' => $db]);
    exit;
}
$mysqli->set_charset('utf8mb4');

$createResult = $mysqli->query("CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    exercise_json LONGTEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_name (user_id, name),
    KEY idx_user (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (!$createResult) {
    error_log("CREATE TABLE failed: " . $mysqli->error);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        // 讀取使用者收藏
        $stmt = $mysqli->prepare('SELECT id, name, exercise_json, created_at, updated_at FROM favorites WHERE user_id = ? ORDER BY updated_at DESC');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $rows = [];
        while ($row = $result->fetch_assoc()) {
            $item = json_decode($row['exercise_json'], true);
            if (!is_array($item)) { $item = []; }
            $item['name'] = $row['name'];
            $item['favorite_id'] = (int)$row['id'];
            $item['created_at'] = $row['created_at'];
            $item['updated_at'] = $row['updated_at'];
            $rows[] = $item;
        }
        echo json_encode(['success' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($method === 'POST') {
        // 新增或覆寫（同一 user_id + name 唯一）
        $payload = json_decode(file_get_contents('php://input'), true);
        if (!$payload) { $payload = $_POST; }
        $name = $payload['name'] ?? '';
        $exercise = $payload['exercise'] ?? null; // 完整物件
        if (!$name || !$exercise) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '缺少 name 或 exercise']);
            exit;
        }
        // 儲存完整 JSON
        $exerciseJson = json_encode($exercise, JSON_UNESCAPED_UNICODE);

        $stmt = $mysqli->prepare('INSERT INTO favorites (user_id, name, exercise_json) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE exercise_json = VALUES(exercise_json), updated_at = CURRENT_TIMESTAMP');
        $stmt->bind_param('iss', $userId, $name, $exerciseJson);
        if (!$stmt->execute()) {
            throw new Exception('儲存失敗: ' . $stmt->error);
        }

        echo json_encode(['success' => true, 'action' => 'saved']);
        exit;
    }

    if ($method === 'DELETE') {
        // 刪除
        $input = file_get_contents('php://input');
        parse_str($input, $params);
        $name = $params['name'] ?? ($_GET['name'] ?? '');
        if (!$name) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '缺少 name']);
            exit;
        }

        $stmt = $mysqli->prepare('DELETE FROM favorites WHERE user_id = ? AND name = ?');
        $stmt->bind_param('is', $userId, $name);
        if (!$stmt->execute()) {
            throw new Exception('刪除失敗: ' . $stmt->error);
        }

        echo json_encode(['success' => true, 'action' => 'deleted']);
        exit;
    }

    http_response_code(405);
    echo json_encode(['success' => false, 'error' => '方法不被允許']);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
} finally {
    $mysqli->close();
}

?>


