<?php
// 檢查資料庫中的訓練資料
$dbHost = '127.0.0.1';
$dbPort = '3307';
$dbName = 'test';
$dbUser = 'root';
$dbPass = '';

try {
    $pdo = new PDO("mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    
    echo "=== 檢查訓練計畫資料 ===\n";
    
    // 檢查 training_plans 表
    $stmt = $pdo->query("SELECT * FROM training_plans WHERE user_id = 9 ORDER BY created_at DESC LIMIT 3");
    $plans = $stmt->fetchAll();
    
    foreach ($plans as $plan) {
        echo "計畫ID: {$plan['id']}, 週數: {$plan['week_number']}, 開始日期: {$plan['week_start_date']}\n";
        
        // 檢查該計畫的動作資料
        $stmt2 = $pdo->prepare("SELECT day_of_week, COUNT(*) as count FROM training_plan_exercises WHERE plan_id = ? GROUP BY day_of_week");
        $stmt2->execute([$plan['id']]);
        $exercises = $stmt2->fetchAll();
        
        foreach ($exercises as $exercise) {
            echo "  {$exercise['day_of_week']}: {$exercise['count']} 個動作\n";
        }
        echo "\n";
    }
    
    // 檢查 training_plan_completion 表
    echo "=== 檢查完成記錄 ===\n";
    $stmt = $pdo->query("SELECT day_of_week, COUNT(*) as count FROM training_plan_completion WHERE user_id = 9 AND exercise_id > 0 GROUP BY day_of_week ORDER BY day_of_week");
    $completions = $stmt->fetchAll();
    
    foreach ($completions as $completion) {
        echo "{$completion['day_of_week']}: {$completion['count']} 個動作\n";
    }
    
} catch (Exception $e) {
    echo "錯誤: " . $e->getMessage() . "\n";
}
?>

