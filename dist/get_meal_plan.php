<?php
// 簡易版餐食計畫讀取 API（檔案制）
// 路徑：dist/get_meal_plan.php?date=YYYY-MM-DD&user_id=xxx

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

try {
    $userId = isset($_GET['user_id']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$_GET['user_id']) : 'guest';
    $date   = isset($_GET['date']) ? preg_replace('/[^0-9\-]/', '', (string)$_GET['date']) : null;
    if (!$date) throw new Exception('date is required');

    $file = __DIR__ . '/../logs/meal_plans/' . $userId . '/' . $date . '.json';
    if (!is_file($file)) {
        echo json_encode([ 'success' => true, 'data' => null ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $json = file_get_contents($file);
    $data = json_decode($json, true);
    echo json_encode([ 'success' => true, 'data' => $data ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([ 'success' => false, 'error' => $e->getMessage() ], JSON_UNESCAPED_UNICODE);
}
