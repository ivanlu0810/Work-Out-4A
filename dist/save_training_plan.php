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
            } else {
                // 建立新計畫
                $insertStmt = $conn->prepare("INSERT INTO training_plans (user_id, week_start_date, week_number, plan_name) VALUES (?, ?, ?, ?)");
                $insertStmt->bind_param("isis", $user_id, $week_start_date, $week_number, $plan_name);
                $insertStmt->execute();
                $plan_id = $conn->insert_id;
            }

            // 2. 插入動作記錄
            $insertExerciseStmt = $conn->prepare("
                INSERT INTO training_plan_exercises 
                (plan_id, day_of_week, exercise_id, exercise_name, muscle_group, sets, reps, weight, rest_time, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $insertedCount = 0;
            foreach ($exercises as $day => $dayExercises) {
                foreach ($dayExercises as $exercise) {
                    $weight = $exercise['weight'] ?? null;
                    $restTime = $exercise['restTime'] ?? null;
                    $notes = $exercise['notes'] ?? null;
                    
                    $insertExerciseStmt->bind_param("isisssssss", 
                        $plan_id,
                        $day,
                        $exercise['id'],
                        $exercise['name'],
                        $exercise['muscleGroup'],
                        $exercise['sets'],
                        $exercise['reps'],
                        $weight,
                        $restTime,
                        $notes
                    );
                    $insertExerciseStmt->execute();
                    $insertedCount++;
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

        $exercisesStmt = $conn->prepare("
            SELECT * FROM training_plan_exercises 
            WHERE plan_id = ? 
            ORDER BY 
                CASE day_of_week 
                    WHEN 'monday' THEN 1
                    WHEN 'tuesday' THEN 2
                    WHEN 'wednesday' THEN 3
                    WHEN 'thursday' THEN 4
                    WHEN 'friday' THEN 5
                    WHEN 'saturday' THEN 6
                    WHEN 'sunday' THEN 7
                END
        ");
        $exercisesStmt->bind_param("i", $plan['id']);
        $exercisesStmt->execute();
        $result = $exercisesStmt->get_result();
        $exercises = $result->fetch_all(MYSQLI_ASSOC);

        // 重新組織資料結構
        $weeklyPlan = [
            'monday' => [],
            'tuesday' => [],
            'wednesday' => [],
            'thursday' => [],
            'friday' => [],
            'saturday' => [],
            'sunday' => []
        ];

        foreach ($exercises as $exercise) {
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
