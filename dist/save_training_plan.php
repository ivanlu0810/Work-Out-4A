<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連線設定
$host = '1.tcp.jp.ngrok.io';
$port = '20959';
$username = 'root';
$password = '';
$dbname = 'test';

try {
    // 使用mysqli連線
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception("資料庫連線失敗: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");

    // 模擬登入狀態（實際使用時應該從session取得）
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $_SESSION['user_id'] = 9; // 測試用，請根據實際登入狀態調整
    }
    $user_id = $_SESSION['user_id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('無效的JSON資料');
        }

        $week_start_date = $input['week_start_date'] ?? null;
        $week_number = $input['week_number'] ?? 0;
        $plan_name = $input['plan_name'] ?? '訓練計畫';
        $exercises = $input['exercises'] ?? [];

        if (!$week_start_date) {
            throw new Exception('缺少週開始日期');
        }

        // 開始交易
        $conn->autocommit(false);

        try {
            // 1. 檢查是否已存在該週(以週起始日)的計畫
            $checkStmt = $conn->prepare("SELECT id FROM training_plans WHERE user_id = ? AND week_start_date = ?");
            $checkStmt->bind_param("is", $user_id, $week_start_date);
            $checkStmt->execute();
            $result = $checkStmt->get_result();
            $existingPlan = $result->fetch_assoc();

            if ($existingPlan) {
                // 更新現有計畫
                $plan_id = $existingPlan['id'];
                $updateStmt = $conn->prepare("UPDATE training_plans SET week_start_date = ?, plan_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
                $updateStmt->bind_param("ssi", $week_start_date, $plan_name, $plan_id);
                $updateStmt->execute();
                
                // 刪除舊的動作記錄
                $deleteStmt = $conn->prepare("DELETE FROM training_plan_exercises WHERE plan_id = ?");
                $deleteStmt->bind_param("i", $plan_id);
                $deleteStmt->execute();
                
                // 刪除舊的完成記錄（重要！）
                $deleteCompletionStmt = $conn->prepare("DELETE FROM training_plan_completion WHERE plan_id = ?");
                $deleteCompletionStmt->bind_param("i", $plan_id);
                $deleteCompletionStmt->execute();
            } else {
                // 建立新計畫
                $insertStmt = $conn->prepare("INSERT INTO training_plans (user_id, week_start_date, week_number, plan_name) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("isis", $user_id, $week_start_date, $week_number, $plan_name);
                $insertStmt->execute();
                $plan_id = $conn->insert_id;
            }

            // 2. 插入動作記錄（包含具體日期）
            $insertExerciseStmt = $conn->prepare("
                INSERT INTO training_plan_exercises 
                (plan_id, day_of_week, exercise_date, exercise_id, exercise_name, muscle_group, sets, reps, weight, rest_time, notes, order_index) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            // 計算該週每天的具體日期
            $dayToDateMapping = [];
            $dayMapping = ['monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 'thursday' => 3, 
                          'friday' => 4, 'saturday' => 5, 'sunday' => 6];
            
            foreach ($dayMapping as $dayName => $offset) {
                $date = new DateTime($week_start_date);
                $date->modify("+$offset days");
                $dayToDateMapping[$dayName] = $date->format('Y-m-d');
            }

            $insertedCount = 0;
            foreach ($exercises as $day => $dayExercises) {
                $orderIndex = 0; // 每重新開始一個新的天數，重置順序
                
                // 計算該天的具體日期
                $exercise_date = $dayToDateMapping[$day] ?? null;
                
                // 調試：輸出日期配對
                error_log("Day: $day, exercise_date: " . ($exercise_date ?? 'NULL'));
                
                foreach ($dayExercises as $exercise) {
                    $weight = $exercise['weight'] ?? null;
                    $restTime = $exercise['restTime'] ?? null;
                    $notes = $exercise['notes'] ?? null;
                    
                    // 使用 orderIndex 或 order_index（如果有的話），否則使用遞增索引
                    $order_index = $exercise['orderIndex'] ?? $exercise['order_index'] ?? $orderIndex;
                    
                    // 調試輸出
                    error_log("=== Inserting exercise ===");
                    error_log("day: $day");
                    error_log("exercise_date: " . ($exercise_date ?? 'NULL'));
                    error_log("exercise name: {$exercise['name']}");
                    error_log("plan_id: $plan_id");
                    
                    $insertExerciseStmt->bind_param("ississiisisi", 
                        $plan_id,
                        $day,
                        $exercise_date,
                        $exercise['id'],
                        $exercise['name'],
                        $exercise['muscleGroup'],
                        $exercise['sets'],
                        $exercise['reps'],
                        $weight,
                        $restTime,
                        $notes,
                        $order_index
                    );
                    $insertExerciseStmt->execute();
                    $insertedCount++;
                    $orderIndex++; // 遞增索引以確保順序
                }
            }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => '訓練計畫儲存成功',
                'plan_id' => $plan_id,
                'inserted_exercises' => $insertedCount
            ], JSON_UNESCAPED_UNICODE);

        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }

    } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // 取得訓練計畫
        $week_number = $_GET['week_number'] ?? 0;
        
        $planStmt = $conn->prepare("
            SELECT * FROM training_plans 
            WHERE user_id = ? AND week_number = ?
        ");
        $planStmt->bind_param("ii", $user_id, $week_number);
        $planStmt->execute();
        $result = $planStmt->get_result();
        $plan = $result->fetch_assoc();

        if (!$plan) {
            echo json_encode([
                'success' => true,
                'data' => null,
                'message' => '該週次沒有訓練計畫'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // 優先使用 exercise_date，如果為空則回退到 day_of_week 的計算方式
        $exercisesStmt = $conn->prepare("
            SELECT * FROM training_plan_exercises 
            WHERE plan_id = ? 
            ORDER BY 
                COALESCE(exercise_date, '2000-01-01'),
                CASE day_of_week 
                    WHEN 'monday' THEN 1
                    WHEN 'tuesday' THEN 2
                    WHEN 'wednesday' THEN 3
                    WHEN 'thursday' THEN 4
                    WHEN 'friday' THEN 5
                    WHEN 'saturday' THEN 6
                    WHEN 'sunday' THEN 7
                END,
                order_index
        ");
        $exercisesStmt->bind_param("i", $plan['id']);
        $exercisesStmt->execute();
        $result = $exercisesStmt->get_result();
        $exercises = $result->fetch_all(MYSQLI_ASSOC);

        // 重新組織資料結構（按日期分組，如果沒有日期則按 day_of_week）
        $weeklyPlan = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => []
        ];
        
        $exerciseDates = [];

        foreach ($exercises as $exercise) {
            // 如果有具體日期，按日期分組
            if (!empty($exercise['exercise_date'])) {
                // 將日期轉換為 day_of_week
                $dateObj = new DateTime($exercise['exercise_date']);
                $dayNum = (int)$dateObj->format('w'); // 0=Sunday, 1=Monday, ...
                $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                $dayName = $dayNames[$dayNum];
                
                if (isset($weeklyPlan[$dayName])) {
                    $weeklyPlan[$dayName][] = [
                        'id' => $exercise['exercise_id'],
                        'name' => $exercise['exercise_name'],
                        'muscleGroup' => $exercise['muscle_group'],
                        'sets' => $exercise['sets'],
                        'reps' => $exercise['reps'],
                        'weight' => $exercise['weight'],
                        'restTime' => $exercise['rest_time'],
                        'notes' => $exercise['notes']
                    ];
                    
                    // 記錄該日期的具體日期
                    $exerciseDates[$dayName] = $exercise['exercise_date'];
                }
            } else {
                // 回退到舊的按 day_of_week 分組
                $weeklyPlan[$exercise['day_of_week']][] = [
                    'id' => $exercise['exercise_id'],
                    'name' => $exercise['exercise_name'],
                    'muscleGroup' => $exercise['muscle_group'],
                    'sets' => $exercise['sets'],
                    'reps' => $exercise['reps'],
                    'weight' => $exercise['weight'],
                    'restTime' => $exercise['rest_time'],
                    'notes' => $exercise['notes']
                ];
            }
        }

        echo json_encode([
            'success' => true,
            'data' => [
                'plan' => $plan,
                'weeklyPlan' => $weeklyPlan
            ],
            'message' => '訓練計畫載入成功'
        ], JSON_UNESCAPED_UNICODE);

    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => '資料庫錯誤: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
?>
