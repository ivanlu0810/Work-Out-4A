<?php
// setup_video_url.php - 設定影片路徑
header('Content-Type: application/json; charset=utf-8');

try {
    // 資料庫連接設定（與 get_exercises.php 相同）
    $host = '1.tcp.jp.ngrok.io';
    $port = '20959';
    $dbname = 'test';
    $username = 'root';
    $password = '';

    // 建立 PDO 連接
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // 1. 檢查 video_url 欄位是否存在
    $stmt = $pdo->query("SHOW COLUMNS FROM exercises LIKE 'video_url'");
    $columnExists = $stmt->rowCount() > 0;

    if (!$columnExists) {
        // 新增 video_url 欄位
        $pdo->exec("ALTER TABLE exercises ADD COLUMN video_url VARCHAR(500) DEFAULT NULL COMMENT '影片檔案路徑'");
        echo json_encode(['success' => true, 'message' => '已新增 video_url 欄位'], JSON_UNESCAPED_UNICODE);
    }

    // 2. 更新 cable上斜臥推 的影片路徑
    $stmt = $pdo->prepare("UPDATE exercises SET video_url = ? WHERE name = ?");
    $result = $stmt->execute(['/健習生/dist/assets/videos/cable上斜臥推.mp4', 'cable上斜臥推']);

    if ($result) {
        $affectedRows = $stmt->rowCount();
        echo json_encode([
            'success' => true, 
            'message' => "已更新 cable上斜臥推 的影片路徑",
            'affected_rows' => $affectedRows
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('更新失敗');
    }

    // 3. 檢查更新結果
    $stmt = $pdo->prepare("SELECT id, name, video_url FROM exercises WHERE name = ?");
    $stmt->execute(['cable上斜臥推']);
    $exercise = $stmt->fetch();

    echo json_encode([
        'success' => true,
        'message' => '設定完成',
        'exercise' => $exercise
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '資料庫錯誤',
        'details' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
