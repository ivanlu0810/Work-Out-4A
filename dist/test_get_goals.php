<?php
echo "Testing get_goals API...\n";

// 模擬 GET 請求
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['user_id'] = '9';

// 包含 API 檔案
ob_start();
include 'get_goals_mysqli.php';
$output = ob_get_clean();

echo "API Response:\n";
echo $output . "\n";
?>
