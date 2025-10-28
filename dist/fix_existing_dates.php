<?php
// 修正現有資料的日期

header('Content-Type: text/plain; charset=utf-8');

try {
    $host = 'localhost';
    $port = 3307;
    $dbname = 'test';
    $username = 'root';
    $password = '';
    
    $conn = new mysqli($host, $port, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
    
    if ($conn->connect_error) {
        die("連接失敗: " . $conn->connect_error);
    }
    
    echo "=== 修正舊資料的日期 ===\n\n";
    
    // 獲取所有計畫
    $plans = $conn->query("SELECT id, week_start_date FROM training_plans")->fetch_all(MYSQLI_ASSOC);
    
    echo "找到 " . count($plans) . " 個計畫\n\n";
    
    foreach ($plans as $plan) {
        $plan_id = $plan['id'];
        $week_start_date = $plan['week_start_date'];
        
        echo "處理 plan_id: $plan_id, week_start_date: $week_start_date\n";
        
        // 先調整到該週的週一
        $weekStart = new DateTime($week_start_date);
        $dayOfWeek = (int)$weekStart->format('w');
        if ($dayOfWeek == 0) {
            $weekStart->modify('-6 days');
        } elseif ($dayOfWeek > 1) {
            $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        
        echo "  調整後的週一: " . $weekStart->format('Y-m-d') . "\n";
        
        // 計算各天的日期
        $dateMapping = [
            'monday' => 0,
            'tuesday' => 1,
            'wednesday' => 2,
            'thursday' => 3,
            'friday' => 4,
            'saturday' => 5,
            'sunday' => 6
        ];
        
        $updatedCount = 0;
        foreach ($dateMapping as $dayName => $offset) {
            $dateObj = clone $weekStart;
            if ($offset > 0) {
                $dateObj->modify("+$offset days");
            }
            $exercise_date = $dateObj->format('Y-m-d');
            
            // 更新該天所有的紀錄
            $updateStmt = $conn->prepare("
                UPDATE training_plan_exercises 
                SET exercise_date = ? 
                WHERE plan_id = ? AND day_of_week = ? AND (exercise_date IS NULL OR exercise_date != ?)
            ");
            
            $updateStmt->bind_param("sis", $exercise_date, $plan_id, $dayName, $exercise_date);
            $updateStmt->execute();
            
            if ($updateStmt->affected_rows > 0) {
                $updatedCount += $updateStmt->affected_rows;
                echo "  $dayName ($exercise_date): " . $updateStmt->affected_rows . " 筆\n";
            }
        }
        
        echo "  計劃 $plan_id 總共更新 $updatedCount 筆記錄\n\n";
    }
    
    echo "=== 修正完成 ===\n";
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage();
}
?>
