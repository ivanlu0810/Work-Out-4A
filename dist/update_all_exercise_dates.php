<?php
// 更新所有舊資料的 exercise_date
// 根據 training_plans 的 week_start_date 和 day_of_week 自動配對日期

header('Content-Type: text/plain; charset=utf-8');

// 資料庫連接
$host = 'localhost';
$port = 3307;
$dbname = 'test';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $port, $username, $password, $dbname);
    $conn->set_charset('utf8mb4');
    
    if ($conn->connect_error) {
        die("連接失敗: " . $conn->connect_error);
    }
    
    echo "=== 開始更新所有計畫的 exercise_date ===\n\n";
    
    // 1. 獲取所有計畫
    $plans = $conn->query("SELECT id, week_start_date, user_id FROM training_plans")->fetch_all(MYSQLI_ASSOC);
    
    echo "找到 " . count($plans) . " 個計畫\n\n";
    
    foreach ($plans as $plan) {
        $plan_id = $plan['id'];
        $week_start_date = $plan['week_start_date'];
        
        echo "處理 plan_id: $plan_id, week_start_date: $week_start_date\n";
        
        // 計算該週每天的具體日期
        $weekStart = new DateTime($week_start_date);
        
        // 確保 week_start_date 是週一
        $dayOfWeek = (int)$weekStart->format('w'); // 0=Sunday, 1=Monday, ...
        if ($dayOfWeek != 1) {
            // 不是週一，調整到週一
            $daysToMonday = $dayOfWeek == 0 ? -6 : 1 - $dayOfWeek;
            $weekStart->modify("$daysToMonday days");
            echo "  調整 week_start_date 到週一: " . $weekStart->format('Y-m-d') . "\n";
        }
        
        // 建立日期對應表
        $dayOffsets = [
            'monday' => 0,
            'tuesday' => 1,
            'wednesday' => 2,
            'thursday' => 3,
            'friday' => 4,
            'saturday' => 5,
            'sunday' => 6
        ];
        
        $dateMapping = [];
        foreach ($dayOffsets as $day => $offset) {
            $dateObj = clone $weekStart;
            if ($offset > 0) {
                $dateObj->modify("+$offset days");
            }
            $dateMapping[$day] = $dateObj->format('Y-m-d');
        }
        
        // 輸出日期對應表
        echo "  日期對應：\n";
        foreach ($dateMapping as $day => $date) {
            echo "    $day -> $date\n";
        }
        
        // 更新該計畫的所有動作日期
        $updateStmt = $conn->prepare("
            UPDATE training_plan_exercises 
            SET exercise_date = ? 
            WHERE plan_id = ? AND day_of_week = ? AND (exercise_date IS NULL OR exercise_date = '')
        ");
        
        $updatedCount = 0;
        foreach ($dateMapping as $dayOfWeek => $date) {
            $updateStmt->bind_param("sis", $date, $plan_id, $dayOfWeek);
            $updateStmt->execute();
            
            if ($updateStmt->affected_rows > 0) {
                $updatedCount += $updateStmt->affected_rows;
                echo "  已更新 $dayOfWeek: $date (" . $updateStmt->affected_rows . " 筆)\n";
            }
        }
        
        echo "  計劃 $plan_id 總共更新 $updatedCount 筆記錄\n\n";
    }
    
    echo "=== 更新完成 ===\n\n";
    
    // 驗證結果
    echo "驗證結果：\n";
    $result = $conn->query("
        SELECT 
            plan_id,
            COUNT(*) as total,
            SUM(CASE WHEN exercise_date IS NOT NULL AND exercise_date != '' THEN 1 ELSE 0 END) as with_date,
            SUM(CASE WHEN exercise_date IS NULL OR exercise_date = '' THEN 1 ELSE 0 END) as without_date
        FROM training_plan_exercises
        GROUP BY plan_id
        ORDER BY plan_id
    ");
    
    while ($row = $result->fetch_assoc()) {
        echo "Plan ID {$row['plan_id']}: 總計 {$row['total']} 筆，";
        echo "有日期: {$row['with_date']} 筆，";
        echo "無日期: {$row['without_date']} 筆\n";
    }
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage();
}
?>
