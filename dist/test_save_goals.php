<?php
// 開啟錯誤報告
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

// 測試儲存功能
$test_data = [
    'user_id' => '9',
    'goal_weight' => '65.0',
    'goal_fat_percentage' => '18.0',
    'goal_muscle' => '30.0'
];

echo json_encode([
    'message' => '測試儲存健康目標',
    'test_data' => $test_data
], JSON_PRETTY_PRINT);

// 模擬 POST 請求
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// 設定輸入資料
$input = json_encode($test_data);
file_put_contents('php://temp', $input);

// 包含 save_goals.php 並執行
ob_start();
include 'save_goals.php';
$output = ob_get_clean();

echo "\n\n儲存結果:\n";
echo $output;
?>
