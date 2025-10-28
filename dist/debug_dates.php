<?php
// 調試日期計算

header('Content-Type: text/html; charset=utf-8');

echo "<h2>日期計算調試</h2>";

// 模擬 week_start_date
$week_start_date = '2025-10-26'; // 週日

echo "<p>Week Start Date: $week_start_date</p>";

// 計算日期
$dayMapping = ['monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 'thursday' => 3, 
              'friday' => 4, 'saturday' => 5, 'sunday' => 6];

echo "<h3>計算的日期對應：</h3>";
echo "<ul>";

foreach ($dayMapping as $dayName => $offset) {
    $date = new DateTime($week_start_date);
    $date->modify("+$offset days");
    $dateStr = $date->format('Y-m-d');
    
    $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $actualDay = $dayNames[(int)$date->format('w')];
    
    echo "<li style='padding: 5px; background: #f0f0f0; margin-bottom: 3px;'>";
    echo "<strong>$dayName</strong> (offset +$offset days) = <strong style='color: blue;'>$dateStr</strong> ";
    echo "<small>($actualDay)</small>";
    echo "</li>";
}

echo "</ul>";

// 檢查實際數據
echo "<h3>檢查實際資料庫中的資料：</h3>";

try {
    $host = 'localhost';
    $port = 3307;
    $dbname = 'test';
    $username = 'root';
    $password = '';
    
    $conn = new mysqli($host, $port, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
    
    // 查詢最新一筆資料
    $result = $conn->query("
        SELECT plan_id, day_of_week, exercise_date, exercise_name 
        FROM training_plan_exercises 
        WHERE plan_id = (SELECT MAX(plan_id) FROM training_plan_exercises)
        ORDER BY day_of_week, order_index
        LIMIT 10
    ");
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Plan ID</th><th>Day</th><th>Exercise Date</th><th>Exercise Name</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $dateClass = $row['exercise_date'] ? 'green' : 'red';
        echo "<tr>";
        echo "<td>{$row['plan_id']}</td>";
        echo "<td>{$row['day_of_week']}</td>";
        echo "<td style='color: $dateClass; font-weight: bold;'>" . ($row['exercise_date'] ?? 'NULL') . "</td>";
        echo "<td>{$row['exercise_name']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><strong>結論：</strong>如果 exercise_date 顯示為 NULL（紅色），表示日期沒有寫入。</p>";
echo "<p>請檢查 save_training_plan.php 的調試輸出。</p>";
?>
