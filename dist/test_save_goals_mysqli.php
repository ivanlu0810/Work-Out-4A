<?php
echo "Testing save_goals API...\n";

// 模擬 POST 請求
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/x-www-form-urlencoded'; // 改為表單格式

// 測試資料
$test_data = [
    'user_id' => '9',
    'goal_weight' => '65.0',
    'goal_fat_percentage' => '18.0',
    'goal_muscle' => '30.0'
];

// 直接設定 $_POST 資料來模擬
$_POST = $test_data;

echo "POST data: " . print_r($_POST, true) . "\n";

// 包含 API 檔案
ob_start();
include 'save_goals_mysqli.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";
?>
