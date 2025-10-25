<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { exit(0); }

// 簡單的錯誤輸出控制
ini_set('display_errors', 0);

// 資料庫連線（依你的本機環境）
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
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'DB 連線失敗: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

// 讀取原始 JSON
function read_json() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

try {
    // 1) 儲存每天完成狀態
    if ($action === 'save_training_plan_completion' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $data = read_json();
        $planId  = (int)($data['plan_id'] ?? 0);
        $userId  = (int)($data['user_id'] ?? 0);
        $weekNum = (int)($data['week_number'] ?? 0);
        $day     = $data['day_of_week'] ?? '';
        $isDone  = (int)($data['is_completed'] ?? 0);
        $pct     = (int)($data['completion_percentage'] ?? 0);

        if (!$planId || !$userId || !$day) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '缺少必要參數']); 
            exit; 
        }

        $pdo->beginTransaction();
        try {
            // 檢查是否已存在記錄
            $check = $pdo->prepare("SELECT id FROM training_plan_completion WHERE plan_id=? AND user_id=? AND week_number=? AND day_of_week=?");
            $check->execute([$planId, $userId, $weekNum, $day]);
            
            if ($check->rowCount() > 0) {
                // 更新現有記錄
                $update = $pdo->prepare("UPDATE training_plan_completion SET is_completed=?, completion_percentage=?, updated_at=NOW() WHERE plan_id=? AND user_id=? AND week_number=? AND day_of_week=?");
                $update->execute([$isDone, $pct, $planId, $userId, $weekNum, $day]);
                    } else {
                // 插入新記錄
                $insert = $pdo->prepare("INSERT INTO training_plan_completion (plan_id, user_id, week_number, day_of_week, is_completed, completion_percentage, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
                $insert->execute([$planId, $userId, $weekNum, $day, $isDone, $pct]);
            }
            
            $pdo->commit();
            echo json_encode(['success' => true]);
            exit;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    // 2) 載入完成狀態
    if ($action === 'load_completion_status' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $planId = (int)($_GET['plan_id'] ?? 0);
        $userId = (int)($_GET['user_id'] ?? 0);
        $weekNum = (int)($_GET['week_number'] ?? 0);

        if (!$planId || !$userId) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '缺少必要參數']);
            exit;
        }
        
        $sql = "SELECT day_of_week, is_completed, completion_percentage FROM training_plan_completion WHERE plan_id=? AND user_id=? AND week_number=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$planId, $userId, $weekNum]);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'data' => $results]);
        exit;
    }

    // 3) 儲存訓練計畫
    if ($action === 'save_training_plan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
        if (!$input) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '無效的 JSON 資料']);
            exit;
        }
    
    $required_fields = ['user_id', 'week_start_date', 'week_number', 'plan_name', 'weekly_plan'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
                echo json_encode(['success' => false, 'error' => "缺少必要欄位: $field"]);
                exit;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // 檢查是否已存在相同週數的計畫
        $check_sql = "SELECT id FROM training_plans WHERE user_id = ? AND week_number = ? AND week_start_date = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$input['user_id'], $input['week_number'], $input['week_start_date']]);
        
        if ($check_stmt->rowCount() > 0) {
            // 更新現有計畫
            $plan_id = $check_stmt->fetchColumn();
            $update_sql = "UPDATE training_plans SET plan_name = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$input['plan_name'], $plan_id]);
            
            // 刪除現有的運動記錄
            $delete_exercises_sql = "DELETE FROM training_plan_exercises WHERE plan_id = ?";
            $delete_exercises_stmt = $pdo->prepare($delete_exercises_sql);
            $delete_exercises_stmt->execute([$plan_id]);
            
                // 刪除現有的完成記錄
            $delete_completion_sql = "DELETE FROM training_plan_completion WHERE plan_id = ?";
            $delete_completion_stmt = $pdo->prepare($delete_completion_sql);
            $delete_completion_stmt->execute([$plan_id]);
        } else {
            // 插入新計畫
            $insert_sql = "INSERT INTO training_plans (user_id, week_start_date, week_number, plan_name, is_active) VALUES (?, ?, ?, ?, 1)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([$input['user_id'], $input['week_start_date'], $input['week_number'], $input['plan_name']]);
            $plan_id = $pdo->lastInsertId();
        }
        
        // 插入運動記錄
        $exercise_sql = "INSERT INTO training_plan_exercises 
                        (plan_id, day_of_week, exercise_id, exercise_name, muscle_group, sets, reps, weight, rest_time, notes, order_index) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $exercise_stmt = $pdo->prepare($exercise_sql);
        
        $day_mapping = [
            'monday' => 'monday',
            'tuesday' => 'tuesday', 
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'sunday' => 'sunday'
        ];
        
        foreach ($input['weekly_plan'] as $day => $exercises) {
            $dayOfWeek = isset($day_mapping[$day]) ? $day_mapping[$day] : 'monday';

            foreach ($exercises as $index => $exercise) {
                $exerciseId = isset($exercise['id']) ? (int)$exercise['id'] : 0;
                if ($exerciseId === 0) {
                    continue;
                }

                $exerciseName = $exercise['name'] ?? '';
                $muscleGroup = $exercise['muscleGroup'] ?? '';
                $sets        = isset($exercise['sets']) ? (int)$exercise['sets'] : 0;
                $reps        = isset($exercise['reps']) ? (int)$exercise['reps'] : 0;
                $weight      = isset($exercise['weight']) && $exercise['weight'] !== '' ? $exercise['weight'] : null;
                $restTime    = isset($exercise['restTime']) ? (int)$exercise['restTime'] : 60;
                $notes       = $exercise['notes'] ?? null;

                $exercise_stmt->execute([
                    $plan_id,
                    $dayOfWeek,
                    $exerciseId,
                    $exerciseName,
                    $muscleGroup,
                    $sets,
                    $reps,
                    $weight,
                    $restTime,
                    $notes,
                    (int)$index,
                ]);
            }
        }
        
        // 確保本週7天都建立完成記錄（預設未完成）
        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        foreach ($days as $d) {
            $check = $pdo->prepare("SELECT id FROM training_plan_completion WHERE plan_id=? AND user_id=? AND week_number=? AND day_of_week=?");
            $check->execute([$plan_id, $input['user_id'], $input['week_number'], $d]);
            if ($check->rowCount() === 0) {
                $ins = $pdo->prepare("INSERT INTO training_plan_completion (plan_id,user_id,week_number,day_of_week,is_completed,completion_percentage,total_exercises,completed_exercises,skipped_exercises,session_duration,notes,created_at,updated_at) VALUES (?,?,?,?,0,0,0,0,0,NULL,NULL,NOW(),NOW())");
                $ins->execute([$plan_id, $input['user_id'], $input['week_number'], $d]);
            }
        }

        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '訓練計畫已儲存',
            'plan_id' => $plan_id
        ]);
            exit;
        
    } catch(PDOException $e) {
            if ($pdo->inTransaction()) {
        $pdo->rollBack();
            }
        http_response_code(500);
            echo json_encode(['success' => false, 'error' => '資料庫操作失敗: ' . $e->getMessage()]);
            exit;
        } catch(Exception $e) {
            if ($pdo->inTransaction()) {
        $pdo->rollBack();
            }
        http_response_code(500);
            echo json_encode(['success' => false, 'error' => '儲存失敗: ' . $e->getMessage()]);
            exit;
        }
    }

    // 4) 載入訓練計畫
    if ($action === 'get_training_plan' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $user_id = $_GET['user_id'] ?? 0;
        $week_number = $_GET['week_number'] ?? 0;
        
        if (!$user_id || $week_number === '' || $week_number === null) {
            http_response_code(400);
            echo json_encode(['error' => '缺少必要參數: user_id, week_number']);
            exit;
        }
        
        try {
            $sql = "SELECT * FROM training_plans WHERE user_id = ? AND week_number = ? ORDER BY week_start_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $week_number]);
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'plans' => $plans]);
            exit;
            
    } catch(PDOException $e) {
        http_response_code(500);
            echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
            exit;
        }
    }

    // 4.1) 載入訓練計畫（別名，相容性）
    if ($action === 'load_training_plan' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $user_id = $_GET['user_id'] ?? 0;
        $week_number = $_GET['week_number'] ?? 0;
        
        if (!$user_id || $week_number === '' || $week_number === null) {
            http_response_code(400);
            echo json_encode(['error' => '缺少必要參數: user_id, week_number']);
            exit;
        }
        
        try {
            // 載入計畫基本資料
            $sql = "SELECT * FROM training_plans WHERE user_id = ? AND week_number = ? ORDER BY week_start_date DESC";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([$user_id, $week_number]);
            $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($plans)) {
                echo json_encode(['success' => true, 'plans' => []]);
                exit;
            }
            
            // 載入每個計畫的運動資料
            foreach ($plans as &$plan) {
                $exercise_sql = "SELECT * FROM training_plan_exercises WHERE plan_id = ? ORDER BY day_of_week, order_index";
                $exercise_stmt = $pdo->prepare($exercise_sql);
                $exercise_stmt->execute([$plan['id']]);
                $exercises = $exercise_stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 按星期組織運動資料
                $plan['exercises'] = [
                    'monday' => [],
                    'tuesday' => [],
                    'wednesday' => [],
                    'thursday' => [],
                    'friday' => [],
                    'saturday' => [],
                    'sunday' => []
                ];
                
                foreach ($exercises as $exercise) {
                    $day = $exercise['day_of_week'];
                    if (isset($plan['exercises'][$day])) {
                        // 查詢該動作的完成狀態
                        $completion_sql = "SELECT individual_completed, individual_completed_at, individual_notes 
                                          FROM training_plan_completion 
                                          WHERE plan_id = ? AND user_id = ? AND week_number = ? 
                                          AND day_of_week = ? AND exercise_id = ?";
                        $completion_stmt = $pdo->prepare($completion_sql);
                        $completion_stmt->execute([$plan['id'], $user_id, $week_number, $day, $exercise['exercise_id']]);
                        $completion_data = $completion_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        $plan['exercises'][$day][] = [
                            'id' => $exercise['exercise_id'],
                            'name' => $exercise['exercise_name'],
                            'muscleGroup' => $exercise['muscle_group'],
                            'sets' => $exercise['sets'],
                            'reps' => $exercise['reps'],
                            'weight' => $exercise['weight'],
                            'restTime' => $exercise['rest_time'],
                            'notes' => $exercise['notes'],
                            'completed' => $completion_data ? (bool)$completion_data['individual_completed'] : false,
                            'completedAt' => $completion_data['individual_completed_at'] ?? null,
                            'completionNotes' => $completion_data['individual_notes'] ?? null
                        ];
                    }
                }
            }
            
            echo json_encode(['success' => true, 'plans' => $plans]);
            exit;
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
            exit;
        }
    }

    // 5) 刪除訓練計畫
    if ($action === 'delete_training_plan' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['plan_id'])) {
            http_response_code(400);
            echo json_encode(['error' => '缺少必要欄位: plan_id']);
            exit;
        }
        
    try {
        $pdo->beginTransaction();
            
            // 刪除相關的完成記錄
            $delete_completion_sql = "DELETE FROM training_plan_completion WHERE plan_id = ?";
            $delete_completion_stmt = $pdo->prepare($delete_completion_sql);
            $delete_completion_stmt->execute([$input['plan_id']]);
            
            // 刪除相關的運動記錄
            $delete_exercises_sql = "DELETE FROM training_plan_exercises WHERE plan_id = ?";
            $delete_exercises_stmt = $pdo->prepare($delete_exercises_sql);
            $delete_exercises_stmt->execute([$input['plan_id']]);
            
            // 刪除主計畫記錄
            $delete_plan_sql = "DELETE FROM training_plans WHERE id = ?";
            $delete_plan_stmt = $pdo->prepare($delete_plan_sql);
            $delete_plan_stmt->execute([$input['plan_id']]);
            
        $pdo->commit();
        echo json_encode(['success'=>true]);
            exit;
        
    } catch(PDOException $e) {
            if ($pdo->inTransaction()) {
        $pdo->rollBack();
            }
        http_response_code(500);
        echo json_encode(['error'=>'刪除失敗: '.$e->getMessage()]);
            exit;
    }
}

    // 6) 按週刪除訓練計畫
    if ($action === 'delete_training_plan_by_week' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
        
        if (!isset($input['user_id']) || !isset($input['week_number'])) {
            http_response_code(400);
            echo json_encode(['error' => '缺少必要欄位: user_id, week_number']);
            exit;
        }
        
    try {
        $pdo->beginTransaction();
        
            // 先找到該週的所有計畫 ID
            $find_plans_sql = "SELECT id FROM training_plans WHERE user_id = ? AND week_number = ?";
            $find_plans_stmt = $pdo->prepare($find_plans_sql);
            $find_plans_stmt->execute([$input['user_id'], $input['week_number']]);
            $plan_ids = $find_plans_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // 無論是否有 plan_ids，都要刪除該週的所有相關資料
        
        // 1. 刪除該週的所有完成記錄（包括個別動作記錄）
        $delete_completion_sql = "DELETE FROM training_plan_completion WHERE user_id = ? AND week_number = ?";
        $delete_completion_stmt = $pdo->prepare($delete_completion_sql);
        $delete_completion_stmt->execute([$input['user_id'], $input['week_number']]);
        $deleted_completion_count = $delete_completion_stmt->rowCount();
        
        // 2. 刪除該週的所有運動記錄（通過 plan_id）
        $delete_exercises_sql = "DELETE FROM training_plan_exercises WHERE plan_id IN (" . implode(',', array_fill(0, count($plan_ids), '?')) . ")";
        $delete_exercises_stmt = $pdo->prepare($delete_exercises_sql);
        $delete_exercises_stmt->execute($plan_ids);
        $deleted_exercises_count = $delete_exercises_stmt->rowCount();
        
        // 3. 刪除該週的主計畫記錄
        $delete_plan_sql = "DELETE FROM training_plans WHERE user_id = ? AND week_number = ?";
        $delete_plan_stmt = $pdo->prepare($delete_plan_sql);
        $delete_plan_stmt->execute([$input['user_id'], $input['week_number']]);
        $deleted_plan_count = $delete_plan_stmt->rowCount();
        
        $total_deleted = $deleted_completion_count + $deleted_exercises_count + $deleted_plan_count;
        
        $pdo->commit();
        echo json_encode([
            'success' => true,
            'deleted_count' => $total_deleted,
            'details' => [
                'completion_records' => $deleted_completion_count,
                'exercise_records' => $deleted_exercises_count,
                'plan_records' => $deleted_plan_count
            ]
        ]);
            exit;
        
    } catch(PDOException $e) {
            if ($pdo->inTransaction()) {
        $pdo->rollBack();
            }
        http_response_code(500);
        echo json_encode(['error'=>'刪除失敗: '.$e->getMessage()]);
            exit;
        }
    }

    // 7) 取得完成度資料（用於統計圖表）
    if ($action === 'get_completion_data' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $user_id = $_GET['user_id'] ?? 0;
        $year = $_GET['year'] ?? 0;
        $month = $_GET['month'] ?? 0;
        $week = $_GET['week'] ?? 0;
        
        if (!$user_id || !$year) {
        http_response_code(400);
            echo json_encode(['error' => '缺少必要參數: user_id, year']);
            exit;
        }
        
        try {
            $completionData = [];
            
            if ($week && $month) {
                // 週統計 - 根據日期範圍查詢，不依賴 week_number
                // 計算該週的日期範圍
                $firstDayOfMonth = new DateTime("$year-$month-01");
                $firstMonday = clone $firstDayOfMonth;
                $firstMonday->modify('monday this week');
                if ($firstMonday > $firstDayOfMonth) {
                    $firstMonday->modify('-1 week');
                }
                
                $targetWeekStart = clone $firstMonday;
                $targetWeekStart->modify('+' . ($week - 1) . ' weeks');
                $targetWeekEnd = clone $targetWeekStart;
                $targetWeekEnd->modify('+6 days');
                
                $startDate = $targetWeekStart->format('Y-m-d');
                $endDate = $targetWeekEnd->format('Y-m-d');
                
                $sql = "SELECT day_of_week, 
                               COUNT(*) as total_exercises,
                               SUM(CASE WHEN individual_completed = 1 THEN 1 ELSE 0 END) as completed_exercises
                FROM training_plan_completion
                        WHERE user_id = ? AND DATE(created_at) BETWEEN ? AND ?
                        AND exercise_id IS NOT NULL
                        GROUP BY day_of_week
                        ORDER BY day_of_week";
        $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $startDate, $endDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
                foreach ($days as $day) {
                    $dayData = array_filter($rows, function($row) use ($day) {
                        return $row['day_of_week'] === $day;
                    });
                    
                    if (!empty($dayData)) {
                        $dayRow = array_values($dayData)[0];
                        $totalExercises = (int)$dayRow['total_exercises'];
                        $completedExercises = (int)$dayRow['completed_exercises'];
                        $completionPercentage = $totalExercises > 0 ? round(($completedExercises / $totalExercises) * 100, 2) : 0;
                        
                        $completionData[$day] = [
                            'total_exercises' => $totalExercises,
                            'completed_exercises' => $completedExercises,
                            'completion_percentage' => $completionPercentage
                        ];
                    } else {
                        $completionData[$day] = [
                            'total_exercises' => 0,
                            'completed_exercises' => 0,
                            'completion_percentage' => 0
                        ];
                    }
                }
            } elseif ($month) {
                // 月統計 - 直接使用 exercise-completion-api 的邏輯
                $sql = "SELECT 
                           DATE_ADD(tp.week_start_date, INTERVAL CASE tpc.day_of_week 
                               WHEN 'monday' THEN 0
                               WHEN 'tuesday' THEN 1
                               WHEN 'wednesday' THEN 2
                               WHEN 'thursday' THEN 3
                               WHEN 'friday' THEN 4
                               WHEN 'saturday' THEN 5
                               WHEN 'sunday' THEN 6
                           END DAY) as exercise_date,
                           COUNT(*) as total_exercises,
                           SUM(CASE WHEN tpc.individual_completed = 1 THEN 1 ELSE 0 END) as completed_exercises
                        FROM training_plan_completion tpc
                        JOIN training_plans tp ON tpc.plan_id = tp.id
                        WHERE tpc.user_id = ? 
                        AND YEAR(DATE_ADD(tp.week_start_date, INTERVAL CASE tpc.day_of_week 
                            WHEN 'monday' THEN 0
                            WHEN 'tuesday' THEN 1
                            WHEN 'wednesday' THEN 2
                            WHEN 'thursday' THEN 3
                            WHEN 'friday' THEN 4
                            WHEN 'saturday' THEN 5
                            WHEN 'sunday' THEN 6
                        END DAY)) = ?
                        AND MONTH(DATE_ADD(tp.week_start_date, INTERVAL CASE tpc.day_of_week 
                            WHEN 'monday' THEN 0
                            WHEN 'tuesday' THEN 1
                            WHEN 'wednesday' THEN 2
                            WHEN 'thursday' THEN 3
                            WHEN 'friday' THEN 4
                            WHEN 'saturday' THEN 5
                            WHEN 'sunday' THEN 6
                        END DAY)) = ?
                        AND tpc.exercise_id IS NOT NULL
                        GROUP BY exercise_date
                        ORDER BY exercise_date";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $year, $month]);
                $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 將按日期分組的數據轉換為按週分組
                $weekData = [];
                foreach ($rows as $row) {
                    $exerciseDate = new DateTime($row['exercise_date']);
                    $weekNum = ceil($exerciseDate->format('j') / 7); // 計算該日期是該月的第幾週
                    
                    if (!isset($weekData[$weekNum])) {
                        $weekData[$weekNum] = [
                            'total_exercises' => 0,
                            'completed_exercises' => 0
                        ];
                    }
                    
                    $weekData[$weekNum]['total_exercises'] += (int)$row['total_exercises'];
                    $weekData[$weekNum]['completed_exercises'] += (int)$row['completed_exercises'];
                }
                
                for ($i = 1; $i <= 4; $i++) {
                    if (isset($weekData[$i])) {
                        $completionData["week{$i}"] = $weekData[$i];
                    } else {
                        $completionData["week{$i}"] = [
                            'total_exercises' => 0,
                            'completed_exercises' => 0
                        ];
                    }
                }
            } else {
                // 年統計 - 使用實際訓練日期
                $sql = "SELECT 
                           MONTH(DATE_ADD(tp.week_start_date, INTERVAL CASE tpc.day_of_week 
                               WHEN 'monday' THEN 0
                               WHEN 'tuesday' THEN 1
                               WHEN 'wednesday' THEN 2
                               WHEN 'thursday' THEN 3
                               WHEN 'friday' THEN 4
                               WHEN 'saturday' THEN 5
                               WHEN 'sunday' THEN 6
                           END DAY)) as month_num,
                           COUNT(*) as total_exercises,
                           SUM(CASE WHEN tpc.individual_completed = 1 THEN 1 ELSE 0 END) as completed_exercises
                        FROM training_plan_completion tpc
                        JOIN training_plans tp ON tpc.plan_id = tp.id
                        WHERE tpc.user_id = ? 
                        AND YEAR(DATE_ADD(tp.week_start_date, INTERVAL CASE tpc.day_of_week 
                            WHEN 'monday' THEN 0
                            WHEN 'tuesday' THEN 1
                            WHEN 'wednesday' THEN 2
                            WHEN 'thursday' THEN 3
                            WHEN 'friday' THEN 4
                            WHEN 'saturday' THEN 5
                            WHEN 'sunday' THEN 6
                        END DAY)) = ?
                        AND tpc.exercise_id IS NOT NULL
                        GROUP BY month_num
                        ORDER BY month_num";
        $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $year]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                for ($i = 1; $i <= 12; $i++) {
                    $monthData = array_filter($rows, function($row) use ($i) {
                        return $row['month_num'] == $i;
                    });
                    
                    if (!empty($monthData)) {
                        $monthRow = array_values($monthData)[0];
                        $completionData["month_{$i}"] = [
                            'total_exercises' => (int)$monthRow['total_exercises'],
                            'completed_exercises' => (int)$monthRow['completed_exercises']
                        ];
        } else {
                        $completionData["month_{$i}"] = [
                            'total_exercises' => 0,
                            'completed_exercises' => 0
                        ];
                    }
                }
            }
            
            echo json_encode(['success' => true, 'data' => $completionData]);
            exit;
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
            exit;
        }
    }

    // 8) 取得訓練歷史（用於歷史選單）
    if ($action === 'get_training_history' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $user_id = $_GET['user_id'] ?? 9;
        
        try {
            $sql = "SELECT id as plan_id, plan_name, week_number, week_start_date, created_at 
                    FROM training_plans 
                    WHERE user_id = ? 
                    ORDER BY week_start_date DESC, created_at DESC";
            $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
            echo json_encode(['success' => true, 'history' => $history]);
            exit;
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
            exit;
        }
    }

    // 預設回應
    echo json_encode(['success' => false, 'error' => '未知的 action'], JSON_UNESCAPED_UNICODE);
    exit;

} catch (Throwable $e) {
        http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}
?>