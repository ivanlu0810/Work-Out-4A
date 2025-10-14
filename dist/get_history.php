<?php
session_start();
header("Content-Type: application/json; charset=UTF-8");

// 開啟錯誤輸出（Debug 用）
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 暫時模擬登入狀態進行測試
if (!isset($_SESSION['user_id'])) {
    // 測試用：設定一個預設的 user_id
    $_SESSION['user_id'] = 9; // 根據您的資料庫圖片，用戶ID是9
    $_SESSION['logged_in'] = true;
}

$host = "1.tcp.jp.ngrok.io";
$port = 20959;
$user = "root";
$password = "";
$dbname = "test";

$conn = new mysqli($host, $user, $password, $dbname, $port);
$conn->set_charset("utf8mb4");

if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "資料庫連線失敗: " . $conn->connect_error]);
    exit;
}

// 只顯示當前用戶的資料
$userId = $_SESSION['user_id'];

$sql = "SELECT record_id, user_id, age, `height-cm` AS height_cm, `weight-kg` AS weight_kg,
               skeletal_muscle, body_fat, fat_percentage, basal_metabolism, bmi, `Date`
        FROM inbody_records
        WHERE user_id = ?
        ORDER BY `Date` ASC";

$stmt = $conn->prepare($sql);
if ($stmt === false) {
    echo json_encode(["success" => false, "error" => "SQL錯誤: " . $conn->error, "sql" => $sql]);
    exit;
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();

if (!$result) {
    echo json_encode(["success" => false, "error" => "查詢失敗: " . $conn->error]);
    exit;
}

$data = [];
while ($row = $result->fetch_assoc()) {
    // 計算 BMI 如果沒有儲存
    if (empty($row['bmi']) && !empty($row['height_cm']) && !empty($row['weight_kg'])) {
        $height_m = $row['height_cm'] / 100;
        $row['bmi'] = round($row['weight_kg'] / ($height_m * $height_m), 2);
    }

    // 格式化數據
    // 讓前端拿到純數字（或 null），不要 number_format
    $row['height_cm']        = isset($row['height_cm'])        ? (float)$row['height_cm']        : null;
    $row['weight_kg']        = isset($row['weight_kg'])        ? (float)$row['weight_kg']        : null;
    $row['skeletal_muscle']  = isset($row['skeletal_muscle'])  ? (float)$row['skeletal_muscle']  : null;
    $row['body_fat']         = isset($row['body_fat'])         ? (float)$row['body_fat']         : null;
    $row['fat_percentage']   = isset($row['fat_percentage'])   ? (float)$row['fat_percentage']   : null;
    $row['basal_metabolism'] = isset($row['basal_metabolism']) ? (float)$row['basal_metabolism'] : null;
    $row['bmi']              = isset($row['bmi'])              ? (float)$row['bmi']              : null;

    $data[] = $row;
}

// 獲取統計資訊
$totalRecords = count($data);

echo json_encode([
    "success" => true,
    "data" => $data,
    "stats" => [
        "total_records" => $totalRecords
    ]
], JSON_UNESCAPED_UNICODE);
