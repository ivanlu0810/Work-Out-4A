<?php
// 簡易版餐食計畫儲存 API（檔案制），避免資料庫依賴
// 路徑：dist/save_meal_plan.php
// 前端以 JSON POST：{ user_id, date, meals, target_*, actual_* }

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

try {
    $raw = file_get_contents('php://input');
    if (!$raw) throw new Exception('Empty body');
    $data = json_decode($raw, true);
    if (!is_array($data)) throw new Exception('Invalid JSON');

    $userId = isset($data['user_id']) ? preg_replace('/[^a-zA-Z0-9_\-]/', '', (string)$data['user_id']) : 'guest';
    $date   = isset($data['date']) ? preg_replace('/[^0-9\-]/', '', (string)$data['date']) : null;
    if (!$date) throw new Exception('date is required');

    $baseDir = __DIR__ . '/../logs/meal_plans';
    if (!is_dir($baseDir)) @mkdir($baseDir, 0777, true);
    $userDir = $baseDir . '/' . $userId;
    if (!is_dir($userDir)) @mkdir($userDir, 0777, true);

    $file = $userDir . '/' . $date . '.json';
    $payload = [
        'user_id' => $userId,
        'date' => $date,
        'meals' => isset($data['meals']) ? $data['meals'] : [ 'breakfast'=>[], 'lunch'=>[], 'dinner'=>[] ],
        'target_calories' => $data['target_calories'] ?? null,
        'target_protein' => $data['target_protein'] ?? null,
        'target_carbs' => $data['target_carbs'] ?? null,
        'target_fat' => $data['target_fat'] ?? null,
        'actual_calories' => $data['actual_calories'] ?? null,
        'actual_protein' => $data['actual_protein'] ?? null,
        'actual_carbs' => $data['actual_carbs'] ?? null,
        'actual_fat' => $data['actual_fat'] ?? null,
        'updated_at' => date('c'),
    ];

    if (false === file_put_contents($file, json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT))) {
        throw new Exception('Failed to write file');
    }

    echo json_encode([ 'success' => true, 'plan_id' => $date, 'file' => basename($file) ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(200);
    echo json_encode([ 'success' => false, 'error' => $e->getMessage() ], JSON_UNESCAPED_UNICODE);
}
