<?php
header('Content-Type: text/plain; charset=utf-8');

try {
    $host = 'localhost';
    $port = 3307;
    $dbname = 'test';
    $username = 'root';
    $password = '';
    
    $conn = new mysqli($host, $port, $username, $password, $dbname);
    
    if ($conn->connect_error) {
        die("連接失敗: " . $conn->connect_error);
    }
    
    echo "=== 檢查 plan_id 48 ===\n\n";
    
    // 查詢計畫基本資料
    $result = $conn->query("SELECT * FROM training_plans WHERE id = 48");
    $plan = $result->fetch_assoc();
    
    if ($plan) {
        echo "計劃資料：\n";
        echo "  ID: {$plan['id']}\n";
        echo "  User ID: {$plan['user_id']}\n";
        echo "  Week Number: {$plan['week_number']}\n";
        echo "  Week Start Date: {$plan['week_start_date']}\n";
        echo "  Plan Name: {$plan['plan_name']}\n";
        echo "  Created: {$plan['created_at']}\n\n";
        
        // 查詢該計畫的動作
        $result2 = $conn->query("
            SELECT day_of_week, exercise_date, COUNT(*) as count
            FROM training_plan_exercises
            WHERE plan_id = 48
            GROUP BY day_of_week, exercise_date
            ORDER BY exercise_date, day_of_week
        ");
        
        echo "動作分布：\n";
        while ($row = $result2->fetch_assoc()) {
            echo "  {$row['day_of_week']} ({$row['exercise_date']}): {$row['count']} 個動作\n";
        }
        
    } else {
        echo "找不到 plan_id 48\n";
    }
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage();
}
?>
