<?php
// 測試儲存時是否寫入 exercise_date

require_once 'assets/include/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode(['success' => false, 'error' => '無效的JSON']);
        exit;
    }
    
    // 獲取參數
    $week_start_date = $input['week_start_date'] ?? '2025-10-26';
    $exercises = $input['exercises'] ?? [];
    
    echo "<h2>測試日期計算</h2>";
    echo "<p>week_start_date: $week_start_date</p>";
    
    // 計算日期
    $dayMapping = ['monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 'thursday' => 3, 
                  'friday' => 4, 'saturday' => 5, 'sunday' => 6];
    
    echo "<h3>日期對應：</h3><ul>";
    foreach ($dayMapping as $dayName => $offset) {
        $date = new DateTime($week_start_date);
        $date->modify("+$offset days");
        $dateStr = $date->format('Y-m-d');
        echo "<li>$dayName ($offset days): $dateStr</li>";
    }
    echo "</ul>";
    
    echo "<h3>Exercises 數據：</h3>";
    echo "<pre>";
    print_r($exercises);
    echo "</pre>";
} else {
    echo "<form method='POST'>
        <input type='text' name='week_start_date' value='2025-10-26' placeholder='Week Start Date'>
        <button type='submit'>Test</button>
    </form>";
}
?>
