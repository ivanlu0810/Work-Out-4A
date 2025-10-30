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
        
        // 檢查是否已存在相同週數的計畫（僅檢查 user_id 和 week_number，配合資料庫唯一約束）
        $check_sql = "SELECT id FROM training_plans WHERE user_id = ? AND week_number = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$input['user_id'], $input['week_number']]);
        
        if ($check_stmt->rowCount() > 0) {
            // 更新現有計畫
            $plan_id = $check_stmt->fetchColumn();
            $update_sql = "UPDATE training_plans SET plan_name = ?, week_start_date = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
            $update_stmt = $pdo->prepare($update_sql);
            $update_stmt->execute([$input['plan_name'], $input['week_start_date'], $plan_id]);
            
            // 刪除現有的運動記錄（training_plan_exercises）
            $delete_exercises_sql = "DELETE FROM training_plan_exercises WHERE plan_id = ?";
            $delete_exercises_stmt = $pdo->prepare($delete_exercises_sql);
            $delete_exercises_stmt->execute([$plan_id]);
            
            // 注意：不刪除 training_plan_completion 記錄，因為這是由 syncWeeklyPlanToExerciseTable 管理的
            // syncWeeklyPlanToExerciseTable 會負責更新這些記錄
        } else {
            // 插入新計畫
            $insert_sql = "INSERT INTO training_plans (user_id, week_start_date, week_number, plan_name, is_active) VALUES (?, ?, ?, ?, 1)";
            $insert_stmt = $pdo->prepare($insert_sql);
            $insert_stmt->execute([$input['user_id'], $input['week_start_date'], $input['week_number'], $input['plan_name']]);
            $plan_id = $pdo->lastInsertId();
        }
        
        // 計算該週每天的具體日期
        // 先將 week_start_date 調整為該週的週一
        $weekStart = new DateTime($input['week_start_date']);
        $dayOfWeek = (int)$weekStart->format('w'); // 0=Sunday, 1=Monday, ..., 6=Saturday
        if ($dayOfWeek == 0) {
            // 如果是週日，往後加 1 天到週一（避免回推到上一週）
            $weekStart->modify('+1 day');
        } elseif ($dayOfWeek > 1) {
            // 如果不是週一，調整到週一
            $weekStart->modify('-' . ($dayOfWeek - 1) . ' days');
        }
        
        // 現在 weekStart 是該週的週一
        $dayToDateMapping = [];
        $dayOffsets = ['monday' => 0, 'tuesday' => 1, 'wednesday' => 2, 'thursday' => 3, 
                      'friday' => 4, 'saturday' => 5, 'sunday' => 6];
        
        foreach ($dayOffsets as $dayName => $offset) {
            $date = clone $weekStart;
            if ($offset > 0) {
                $date->modify("+$offset days");
            }
            $dayToDateMapping[$dayName] = $date->format('Y-m-d');
        }
        
        // 插入運動記錄（包含具體日期）
        $exercise_sql = "INSERT INTO training_plan_exercises 
                        (plan_id, day_of_week, exercise_date, exercise_id, exercise_name, muscle_group, sets, reps, weight, rest_time, notes, order_index) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
            
            // 獲取該天的具體日期
            $exercise_date = $dayToDateMapping[$day] ?? null;

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
                    $exercise_date,
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
        
        // ⚠️ 已移除自動建立空的 training_plan_completion 記錄
        // 改為只在使用者完成訓練時才寫入完成資料
        // 這樣可以避免產生大量空的 placeholder 記錄

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
        $week_number = isset($_GET['week_number']) ? (int)$_GET['week_number'] : null;
        
        if (!$user_id) {
            http_response_code(400);
            echo json_encode(['error' => '缺少必要參數: user_id']);
            exit;
        }
        
        try {
            // 載入計畫基本資料（若指定 week_number 則使用，否則載入最新一筆）
            if ($week_number !== null) {
                $sql = "SELECT * FROM training_plans WHERE user_id = ? AND week_number = ? ORDER BY week_start_date DESC";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $week_number]);
                $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } else {
                $plans = [];
            }
            
            // 如果指定週次沒有資料，改為載入最新的計畫
            if (empty($plans)) {
                $fallbackSql = "SELECT * FROM training_plans WHERE user_id = ? ORDER BY week_start_date DESC LIMIT 1";
                $fallbackStmt = $pdo->prepare($fallbackSql);
                $fallbackStmt->execute([$user_id]);
                $plans = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (!empty($plans)) {
                    error_log("week_number $week_number 沒有資料，改用最新計畫 week_number {$plans[0]['week_number']}");
                }
            }
            
            if (empty($plans)) {
                echo json_encode(['success' => true, 'plans' => []]);
                exit;
            }
            
            // 載入每個計畫的運動資料（優先使用 exercise_date 排序，如果沒有則用 day_of_week）
            foreach ($plans as &$plan) {
                $exercise_sql = "SELECT * FROM training_plan_exercises WHERE plan_id = ? ORDER BY COALESCE(exercise_date, '2000-01-01'), day_of_week, order_index";
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
                
                // 如果 exercise_date 存在，按日期分組；否則按 day_of_week 分組
                foreach ($exercises as $exercise) {
                    // 優先使用 exercise_date 判斷星期幾
                    if (!empty($exercise['exercise_date'])) {
                        // 根據具體日期判斷是星期幾
                        $dateObj = new DateTime($exercise['exercise_date']);
                        $dayNum = (int)$dateObj->format('w'); // 0=Sunday, 1=Monday, ...
                        $dayNames = ['sunday', 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
                        $day = $dayNames[$dayNum];
                    } else {
                        // 回退到使用 day_of_week
                        $day = $exercise['day_of_week'];
                    }
                    
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
                            'orderIndex' => $exercise['order_index'],
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
                
                // 週統計：以實際訓練日期計算，並做「跨計畫重複去重」
                // 總數：以 tpe 的 (exercise_date, exercise_id, exercise_name) 去重
                // 完成：以 (實際日期, exercise_id, exercise_name) 去重（由 tp.week_start_date + day_of_week 推算）
                $sql = "
                    WITH e AS (
                        SELECT 
                            CASE DAYOFWEEK(tpe.exercise_date)
                                WHEN 2 THEN 'monday'
                                WHEN 3 THEN 'tuesday'
                                WHEN 4 THEN 'wednesday'
                                WHEN 5 THEN 'thursday'
                                WHEN 6 THEN 'friday'
                                WHEN 7 THEN 'saturday'
                                WHEN 1 THEN 'sunday'
                            END AS day_of_week,
                            COUNT(DISTINCT CONCAT(DATE(tpe.exercise_date),'#',tpe.exercise_id,'#',tpe.exercise_name)) AS total_exercises
                        FROM training_plan_exercises tpe
                        JOIN training_plans tp ON tp.id = tpe.plan_id
                        WHERE tp.user_id = ?
                          AND tpe.exercise_id > 0
                          AND tpe.exercise_date BETWEEN ? AND ?
                        GROUP BY 1
                    ),
                    c AS (
                        SELECT 
                            CASE DAYOFWEEK(DATE_ADD(tp.week_start_date, INTERVAL 
                                CASE tpc.day_of_week
                                    WHEN 'monday' THEN 0
                                    WHEN 'tuesday' THEN 1
                                    WHEN 'wednesday' THEN 2
                                    WHEN 'thursday' THEN 3
                                    WHEN 'friday' THEN 4
                                    WHEN 'saturday' THEN 5
                                    WHEN 'sunday' THEN 6
                                END DAY))
                                WHEN 2 THEN 'monday'
                                WHEN 3 THEN 'tuesday'
                                WHEN 4 THEN 'wednesday'
                                WHEN 5 THEN 'thursday'
                                WHEN 6 THEN 'friday'
                                WHEN 7 THEN 'saturday'
                                WHEN 1 THEN 'sunday'
                            END AS day_of_week,
                            COUNT(DISTINCT CONCAT(
                                DATE(DATE_ADD(tp.week_start_date, INTERVAL 
                                    CASE tpc.day_of_week
                                        WHEN 'monday' THEN 0
                                        WHEN 'tuesday' THEN 1
                                        WHEN 'wednesday' THEN 2
                                        WHEN 'thursday' THEN 3
                                        WHEN 'friday' THEN 4
                                        WHEN 'saturday' THEN 5
                                        WHEN 'sunday' THEN 6
                                    END DAY
                                )), '#', tpc.exercise_id, '#', tpc.exercise_name
                            )) AS completed_exercises
                        FROM training_plan_completion tpc
                        JOIN training_plans tp ON tp.id = tpc.plan_id
                        WHERE tpc.user_id = ?
                          AND tpc.exercise_id IS NOT NULL
                          AND tpc.individual_completed = 1
                          AND DATE(DATE_ADD(tp.week_start_date, INTERVAL 
                                CASE tpc.day_of_week
                                    WHEN 'monday' THEN 0
                                    WHEN 'tuesday' THEN 1
                                    WHEN 'wednesday' THEN 2
                                    WHEN 'thursday' THEN 3
                                    WHEN 'friday' THEN 4
                                    WHEN 'saturday' THEN 5
                                    WHEN 'sunday' THEN 6
                                END DAY)) BETWEEN ? AND ?
                        GROUP BY 1
                    )
                    SELECT 
                        d.day_of_week,
                        COALESCE(e.total_exercises, 0) AS total_exercises,
                        COALESCE(c.completed_exercises, 0) AS completed_exercises
                    FROM (
                        SELECT 'monday' AS day_of_week UNION ALL
                        SELECT 'tuesday' UNION ALL
                        SELECT 'wednesday' UNION ALL
                        SELECT 'thursday' UNION ALL
                        SELECT 'friday' UNION ALL
                        SELECT 'saturday' UNION ALL
                        SELECT 'sunday'
                    ) d
                    LEFT JOIN e ON e.day_of_week = d.day_of_week
                    LEFT JOIN c ON c.day_of_week = d.day_of_week
                    ORDER BY FIELD(d.day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')
                ";
                $stmt = $pdo->prepare($sql);
                $stmt->execute([$user_id, $startDate, $endDate, $user_id, $startDate, $endDate]);
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
                // 月統計 - 使用 training_plan_exercises 的 exercise_date
                $sql = "SELECT 
                           tpe.exercise_date,
                           COUNT(DISTINCT CONCAT(tpe.exercise_date, '-', tpe.exercise_id, '-', tpe.exercise_name)) as total_exercises,
                           SUM(CASE WHEN tpc.individual_completed = 1 THEN 1 ELSE 0 END) as completed_exercises
                        FROM training_plan_exercises tpe
                        JOIN training_plans tp ON tpe.plan_id = tp.id AND tp.user_id = ?
                        LEFT JOIN training_plan_completion tpc 
                            ON tpe.plan_id = tpc.plan_id 
                            AND tpe.exercise_id = tpc.exercise_id 
                            AND tpe.day_of_week = tpc.day_of_week
                            AND tpc.user_id = tp.user_id
                        WHERE tpe.exercise_date IS NOT NULL
                        AND tpe.exercise_id > 0
                        AND YEAR(tpe.exercise_date) = ?
                        AND MONTH(tpe.exercise_date) = ?
                        GROUP BY tpe.exercise_date
                        ORDER BY tpe.exercise_date";
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
                // 年統計 - 使用 training_plan_exercises 的 exercise_date
                $sql = "SELECT 
                           MONTH(tpe.exercise_date) as month_num,
                           COUNT(DISTINCT CONCAT(tpe.exercise_date, '-', tpe.exercise_id, '-', tpe.exercise_name)) as total_exercises,
                           SUM(CASE WHEN tpc.individual_completed = 1 THEN 1 ELSE 0 END) as completed_exercises
                        FROM training_plan_exercises tpe
                        JOIN training_plans tp ON tpe.plan_id = tp.id AND tp.user_id = ?
                        LEFT JOIN training_plan_completion tpc 
                            ON tpe.plan_id = tpc.plan_id 
                            AND tpe.exercise_id = tpc.exercise_id 
                            AND tpe.day_of_week = tpc.day_of_week
                            AND tpc.user_id = tp.user_id
                        WHERE tpe.exercise_date IS NOT NULL
                        AND tpe.exercise_id > 0
                        AND YEAR(tpe.exercise_date) = ?
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