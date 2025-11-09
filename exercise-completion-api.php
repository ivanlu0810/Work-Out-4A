<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// 檢測是否為本地環境
$isLocal = !isset($_SERVER['HTTP_X_FORWARDED_FOR']) && 
           !isset($_SERVER['HTTP_X_REAL_IP']) && 
           ($_SERVER['SERVER_NAME'] === 'localhost' || $_SERVER['SERVER_NAME'] === '127.0.0.1' || strpos($_SERVER['SERVER_NAME'], 'ngrok') === false);

// 資料庫連接
if ($isLocal) {
    // 本地 XAMPP 配置
    $host = 'localhost';
    $port = 3307;  // XAMPP MySQL 端口
    $dbname = 'test';  // 使用 test 資料庫
    $username = 'root';
    $password = '';
} else {
    // 遠端 ngrok 配置
    $host = '1.tcp.jp.ngrok.io';
    $port = 20959;
    $dbname = 'test';
    $username = 'root';
    $password = '';
}

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => '資料庫連接失敗: ' . $e->getMessage()]);
    exit();
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 如果是 POST 請求，嘗試從 JSON 中解析 action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($action)) {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
}

switch ($action) {
    case 'delete_week':
        deleteWeek($pdo);
        break;
    case 'save_exercise_completion':
        saveExerciseCompletion($pdo);
        break;
    case 'get_exercise_completion':
        getExerciseCompletion($pdo);
        break;
    case 'get_day_exercises':
        getDayExercises($pdo);
        break;
    case 'sync_weekly_plan_to_exercises':
        syncWeeklyPlanToExercises($pdo);
        break;
    case 'get_calendar_data':
        getCalendarData($pdo);
        break;
    case 'sync_calendar_exercise':
        syncCalendarExercise($pdo);
        break;
    case 'delete_calendar_exercise':
        deleteCalendarExercise($pdo);
        break;
    case 'get_plan_id':
        getPlanId($pdo);
        break;
    case 'get_plan_by_week_start':
        getPlanByWeekStart($pdo);
        break;
    case 'create_plan':
        createPlan($pdo);
        break;
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '無效的操作']);
        break;
}

// 儲存個別動作完成狀態（支援每個動作獨立記錄）
function saveExerciseCompletion($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '無效的JSON數據']);
        return;
    }
    
    $required_fields = ['plan_id', 'user_id', 'week_number', 'day_of_week', 'exercise_id', 'exercise_name'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "缺少必要欄位: $field"]);
            return;
        }
    }
    
    try {
        // 檢查是否已存在該動作的記錄
        $stmt = $pdo->prepare("SELECT id FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ? AND exercise_id = ?");
        $stmt->execute([$input['plan_id'], $input['user_id'], $input['week_number'], $input['day_of_week'], $input['exercise_id']]);
        $existing = $stmt->fetch();
        
        $individual_completed_at = $input['individual_completed'] ? date('Y-m-d H:i:s') : null;
        
        if ($existing) {
            // 更新現有記錄
            $stmt = $pdo->prepare("UPDATE training_plan_completion SET 
                exercise_name = ?, 
                muscle_group = ?, 
                sets = ?, 
                reps = ?, 
                weight = ?, 
                individual_completed = ?, 
                individual_completed_at = ?, 
                individual_notes = ?,
                updated_at = CURRENT_TIMESTAMP
                WHERE id = ?");
            
            $stmt->execute([
                $input['exercise_name'],
                $input['muscle_group'] ?? '',
                $input['sets'] ?? 0,
                $input['reps'] ?? 0,
                $input['weight'] ?? null,
                $input['individual_completed'] ? 1 : 0,
                $individual_completed_at,
                $input['individual_notes'] ?? null,
                $existing['id']
            ]);
        } else {
            // 插入新記錄（個別動作記錄）
            $stmt = $pdo->prepare("INSERT INTO training_plan_completion 
                (plan_id, user_id, week_number, day_of_week, exercise_id, exercise_name, muscle_group, sets, reps, weight, individual_completed, individual_completed_at, individual_notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            
            $stmt->execute([
                $input['plan_id'],
                $input['user_id'],
                $input['week_number'],
                $input['day_of_week'],
                $input['exercise_id'],
                $input['exercise_name'],
                $input['muscle_group'] ?? '',
                $input['sets'] ?? 0,
                $input['reps'] ?? 0,
                $input['weight'] ?? null,
                $input['individual_completed'] ? 1 : 0,
                $individual_completed_at,
                $input['individual_notes'] ?? null
            ]);
        }
        
        // 更新該天的整體完成統計
        updateDayCompletionStats($pdo, $input['plan_id'], $input['user_id'], $input['week_number'], $input['day_of_week']);
        
        echo json_encode(['success' => true, 'message' => '個別動作完成狀態已儲存']);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '儲存失敗: ' . $e->getMessage()]);
    }
}

// 更新該天的整體完成統計
function updateDayCompletionStats($pdo, $plan_id, $user_id, $week_number, $day_of_week) {
    try {
        // 獲取該天所有動作的統計
        $stmt = $pdo->prepare("SELECT 
            COUNT(*) as total_exercises,
            SUM(individual_completed) as completed_exercises,
            SUM(CASE WHEN individual_completed = 1 THEN 1 ELSE 0 END) as completed_count
            FROM training_plan_completion 
            WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ?");
        $stmt->execute([$plan_id, $user_id, $week_number, $day_of_week]);
        $stats = $stmt->fetch();
        
        $total_exercises = $stats['total_exercises'];
        $completed_exercises = $stats['completed_count'];
        $completion_percentage = $total_exercises > 0 ? round(($completed_exercises / $total_exercises) * 100) : 0;
        $is_completed = $completion_percentage === 100 ? 1 : 0;
        $completed_at = $is_completed ? date('Y-m-d H:i:s') : null;
        
        // 更新該天的整體記錄（如果存在）
        $stmt = $pdo->prepare("UPDATE training_plan_completion SET 
            is_completed = ?, 
            completed_at = ?, 
            completion_percentage = ?,
            total_exercises = ?,
            completed_exercises = ?,
            updated_at = CURRENT_TIMESTAMP
            WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ? AND exercise_id IS NULL");
        $stmt->execute([
            $is_completed,
            $completed_at,
            $completion_percentage,
            $total_exercises,
            $completed_exercises,
            $plan_id,
            $user_id,
            $week_number,
            $day_of_week
        ]);
        
    } catch (PDOException $e) {
        error_log("更新天完成統計失敗: " . $e->getMessage());
    }
}

// 獲取個別動作完成狀態
function getExerciseCompletion($pdo) {
    $plan_id = $_GET['plan_id'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    $week_number = $_GET['week_number'] ?? '';
    $day_of_week = $_GET['day_of_week'] ?? '';
    
    // 修正：week_number 可以是 0，所以檢查是否為空字串或 null
    if ($plan_id === '' || $plan_id === null || $user_id === '' || $user_id === null || 
        $week_number === '' || $week_number === null || $day_of_week === '' || $day_of_week === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ? ORDER BY exercise_id, id");
        $stmt->execute([$plan_id, $user_id, $week_number, $day_of_week]);
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $exercises]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '獲取失敗: ' . $e->getMessage()]);
    }
}

// 獲取某天的所有動作
function getDayExercises($pdo) {
    $plan_id = $_GET['plan_id'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    $week_number = $_GET['week_number'] ?? '';
    $day_of_week = $_GET['day_of_week'] ?? '';
    
    // 修正：week_number 可以是 0，所以檢查是否為空字串或 null
    if ($plan_id === '' || $plan_id === null || $user_id === '' || $user_id === null || 
        $week_number === '' || $week_number === null || $day_of_week === '' || $day_of_week === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ? ORDER BY exercise_id, id");
        $stmt->execute([$plan_id, $user_id, $week_number, $day_of_week]);
        $exercises = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // 計算完成統計
        $total_exercises = count($exercises);
        $completed_exercises = count(array_filter($exercises, function($ex) { return $ex['is_completed'] == 1; }));
        $completion_percentage = $total_exercises > 0 ? round(($completed_exercises / $total_exercises) * 100) : 0;
        
        echo json_encode([
            'success' => true, 
            'data' => [
                'exercises' => $exercises,
                'total_exercises' => $total_exercises,
                'completed_exercises' => $completed_exercises,
                'completion_percentage' => $completion_percentage
            ]
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '獲取失敗: ' . $e->getMessage()]);
    }
}

// 同步週計畫到個別動作表格（每個動作獨立記錄）
function syncWeeklyPlanToExercises($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '無效的JSON數據']);
        return;
    }
    
    $required_fields = ['plan_id', 'user_id', 'week_number', 'weekly_plan'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => "缺少必要欄位: $field"]);
            return;
        }
    }
    
    try {
        $pdo->beginTransaction();
        
        // 先清除該週的所有記錄
        $stmt = $pdo->prepare("DELETE FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND week_number = ?");
        $stmt->execute([$input['plan_id'], $input['user_id'], $input['week_number']]);
        
        // 為每個動作創建獨立記錄，使用 ON DUPLICATE KEY UPDATE 避免重複鍵值錯誤
        $stmt = $pdo->prepare("INSERT INTO training_plan_completion 
            (plan_id, user_id, week_number, day_of_week, exercise_id, exercise_name, muscle_group, sets, reps, weight, individual_completed, individual_completed_at, individual_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
            exercise_name = VALUES(exercise_name),
            muscle_group = VALUES(muscle_group),
            sets = VALUES(sets),
            reps = VALUES(reps),
            weight = VALUES(weight)");
        
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $total_exercises = 0;
        
        foreach ($days as $day) {
            if (isset($input['weekly_plan'][$day]) && is_array($input['weekly_plan'][$day])) {
                foreach ($input['weekly_plan'][$day] as $exercise) {
                    // 確保動作資料完整且不是休息日
                    if (isset($exercise['id']) && 
                        $exercise['id'] != 0 && 
                        isset($exercise['name']) && 
                        !empty($exercise['name'])) {
                        
                        $stmt->execute([
                            $input['plan_id'],
                            $input['user_id'],
                            $input['week_number'],
                            $day,
                            $exercise['id'],
                            $exercise['name'],
                            $exercise['muscleGroup'] ?? '',
                            $exercise['sets'] ?? 0,
                            $exercise['reps'] ?? 0,
                            $exercise['weight'] ?? null,
                            0, // 預設未完成
                            null, // 完成時間
                            null // 備註
                        ]);
                        $total_exercises++;
                    }
                }
            }
        }
        
        $pdo->commit();
        echo json_encode([
            'success' => true, 
            'message' => '週計畫已同步到個別動作表格',
            'total_exercises' => $total_exercises
        ]);
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '同步失敗: ' . $e->getMessage()]);
    }
}

// 獲取 plan_id
function getPlanId($pdo) {
    $week_number = $_GET['week_number'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    
    // 修正：week_number 可以是 0，所以檢查是否為空字串或 null
    if ($week_number === '' || $week_number === null || $user_id === '' || $user_id === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數: week_number=' . $week_number . ', user_id=' . $user_id]);
        return;
    }
    
    try {
        // 從 training_plans 表格獲取 plan_id
        $stmt = $pdo->prepare("SELECT id FROM training_plans WHERE week_number = ? AND user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$week_number, $user_id]);
        $result = $stmt->fetch();
        
        if ($result) {
            echo json_encode(['success' => true, 'plan_id' => $result['id']]);
        } else {
            echo json_encode(['success' => false, 'error' => '找不到對應的訓練計畫']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '獲取 plan_id 失敗: ' . $e->getMessage()]);
    }
}

// 根據 week_start_date 獲取 plan_id
function getPlanByWeekStart($pdo) {
    $week_start_date = $_GET['week_start_date'] ?? '';
    $user_id = $_GET['user_id'] ?? '';
    
    if ($week_start_date === '' || $week_start_date === null || $user_id === '' || $user_id === null) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數: week_start_date 或 user_id']);
        return;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT id, week_number, week_start_date FROM training_plans WHERE week_start_date = ? AND user_id = ? ORDER BY id DESC LIMIT 1");
        $stmt->execute([$week_start_date, $user_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'plan_id' => $result['id'],
                'week_number' => $result['week_number'],
                'week_start_date' => $result['week_start_date']
            ]);
        } else {
            echo json_encode(['success' => false, 'error' => '找不到對應的訓練計畫']);
        }
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '獲取 plan_id 失敗: ' . $e->getMessage()]);
    }
}

// 建立新的訓練計畫
function createPlan($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['user_id']) || !isset($input['week_start_date'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數: user_id 或 week_start_date']);
        return;
    }
    
    $user_id = (int)$input['user_id'];
    $week_start_date = $input['week_start_date'];
    $week_number = isset($input['week_number']) ? (int)$input['week_number'] : null;
    
    // 如果沒有提供 week_number，計算它
    if ($week_number === null) {
        try {
            $date_obj = new DateTime($week_start_date);
            $year_start = new DateTime($date_obj->format('Y') . '-01-01');
            $week_number = ceil(($date_obj->getTimestamp() - $year_start->getTimestamp()) / (7 * 24 * 60 * 60)) + 1;
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '無效的日期格式: ' . $e->getMessage()]);
            return;
        }
    }
    
    try {
        // 檢查是否已存在相同的計畫
        $stmt_check = $pdo->prepare("SELECT id FROM training_plans WHERE user_id = ? AND week_start_date = ? LIMIT 1");
        $stmt_check->execute([$user_id, $week_start_date]);
        $existing = $stmt_check->fetch();
        
        if ($existing) {
            echo json_encode([
                'success' => true,
                'plan_id' => $existing['id'],
                'week_number' => $week_number,
                'week_start_date' => $week_start_date,
                'message' => '計畫已存在'
            ]);
            return;
        }
        
        // 建立新計畫
        $sql_create_plan = "INSERT INTO training_plans (user_id, week_number, week_start_date, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
        $stmt_create_plan = $pdo->prepare($sql_create_plan);
        $stmt_create_plan->execute([$user_id, $week_number, $week_start_date]);
        $plan_id = $pdo->lastInsertId();
        
        error_log("createPlan: 成功建立新訓練計畫: plan_id=$plan_id, user_id=$user_id, week_number=$week_number, week_start_date=$week_start_date");
        
        echo json_encode([
            'success' => true,
            'plan_id' => $plan_id,
            'week_number' => $week_number,
            'week_start_date' => $week_start_date,
            'message' => '訓練計畫建立成功'
        ]);
        
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '建立計畫失敗: ' . $e->getMessage()]);
    }
}

// 獲取行事曆資料（從 training_plans 和 training_plan_exercises 讀取）
function getCalendarData($pdo) {
    try {
        // 從前端取得 user_id
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($user_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '缺少或無效的 user_id']);
            return;
        }
        
        // 從 training_plan_exercises 讀取基本訓練計畫資料
        // 優先使用 exercise_date，如果沒有則從 week_start_date + day_of_week 計算
        $sql_from_exercises = "SELECT 
                COALESCE(tpe.exercise_date, 
                    DATE_ADD(tp.week_start_date, INTERVAL 
                        CASE tpe.day_of_week 
                            WHEN 'monday' THEN 0
                            WHEN 'tuesday' THEN 1
                            WHEN 'wednesday' THEN 2
                            WHEN 'thursday' THEN 3
                            WHEN 'friday' THEN 4
                            WHEN 'saturday' THEN 5
                            WHEN 'sunday' THEN 6
                            ELSE 0
                        END DAY
                    )
                ) AS exercise_date,
                tpe.exercise_id,
                tpe.exercise_name,
                tpe.muscle_group,
                tpe.sets,
                tpe.reps,
                tpe.weight,
                tpe.order_index,
                tpe.id as exercise_record_id
            FROM training_plan_exercises tpe
            JOIN training_plans tp ON tp.id = tpe.plan_id AND tp.user_id = ?
            WHERE tpe.exercise_id IS NOT NULL
            ORDER BY exercise_date DESC, tpe.order_index ASC";

        $stmt_e = $pdo->prepare($sql_from_exercises);
        $stmt_e->execute([$user_id]);
        $exercises = $stmt_e->fetchAll(PDO::FETCH_ASSOC);
        
        // 調試：記錄讀取的動作數量
        error_log("getCalendarData: user_id = " . $user_id);
        error_log("getCalendarData: 從 training_plan_exercises 讀取了 " . count($exercises) . " 個動作記錄");
        if (count($exercises) > 0) {
            error_log("getCalendarData: 第一個動作範例: " . json_encode($exercises[0]));
            // 統計不同日期的動作數量
            $date_counts = [];
            foreach ($exercises as $ex) {
                $date = $ex['exercise_date'];
                if (is_object($date)) {
                    $date_key = $date->format('Y-m-d');
                } else {
                    $date_key = date('Y-m-d', strtotime($date));
                }
                if (!isset($date_counts[$date_key])) {
                    $date_counts[$date_key] = 0;
                }
                $date_counts[$date_key]++;
            }
            error_log("getCalendarData: 日期統計: " . json_encode($date_counts));
        } else {
            error_log("getCalendarData: 警告！沒有讀取到任何動作記錄！");
            // 檢查是否有該用戶的計畫
            $check_plan_sql = "SELECT COUNT(*) as plan_count FROM training_plans WHERE user_id = ?";
            $check_plan_stmt = $pdo->prepare($check_plan_sql);
            $check_plan_stmt->execute([$user_id]);
            $plan_count = $check_plan_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("getCalendarData: 該用戶的計畫數量: " . $plan_count['plan_count']);
            
            // 檢查是否有該用戶的動作記錄
            $check_ex_sql = "SELECT COUNT(*) as ex_count FROM training_plan_exercises tpe JOIN training_plans tp ON tp.id = tpe.plan_id WHERE tp.user_id = ?";
            $check_ex_stmt = $pdo->prepare($check_ex_sql);
            $check_ex_stmt->execute([$user_id]);
            $ex_count = $check_ex_stmt->fetch(PDO::FETCH_ASSOC);
            error_log("getCalendarData: 該用戶的動作記錄數量: " . $ex_count['ex_count']);
        }
        
        // 從 training_plan_completion 讀取完成狀態（已完成的部分）
        // 讀取所有完成狀態記錄，不只 individual_completed = 1，也包括其他可能的狀態值
        $sql_from_completion = "SELECT 
                DATE_ADD(tp.week_start_date, INTERVAL 
                    CASE tpc.day_of_week 
                        WHEN 'monday' THEN 0
                        WHEN 'tuesday' THEN 1
                        WHEN 'wednesday' THEN 2
                        WHEN 'thursday' THEN 3
                        WHEN 'friday' THEN 4
                        WHEN 'saturday' THEN 5
                        WHEN 'sunday' THEN 6
                        ELSE 0
                    END DAY
                ) AS exercise_date,
                tpc.exercise_id,
                tpc.exercise_name,
                tpc.individual_completed,
                tpc.individual_completed_at,
                tpc.week_number,
                tpc.day_of_week
            FROM training_plan_completion tpc
            JOIN training_plans tp ON tp.id = tpc.plan_id AND tp.user_id = ?
            WHERE tpc.exercise_id IS NOT NULL";

        $stmt_c = $pdo->prepare($sql_from_completion);
        $stmt_c->execute([$user_id]);
        $completions = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
        
        // 建立完成狀態的索引 (exercise_date + exercise_id 作為 key)
        // 同時也用 exercise_name 作為備用匹配
        $completion_map = [];
        $completion_map_by_name = [];
        foreach ($completions as $completion) {
            // 格式化日期為 YYYY-MM-DD
            $exercise_date = $completion['exercise_date'];
            if (is_object($exercise_date)) {
                $date_key = $exercise_date->format('Y-m-d');
            } else {
                $date_key = date('Y-m-d', strtotime($exercise_date));
            }
            $exercise_id = $completion['exercise_id'];
            $exercise_name = $completion['exercise_name'];
            
            // 檢查是否已完成（individual_completed = 1 或 true）
            $is_completed = ($completion['individual_completed'] == 1 || 
                           $completion['individual_completed'] === '1' || 
                           $completion['individual_completed'] === true);
            
            if ($is_completed) {
                // 主要匹配：使用日期 + exercise_id
                $key = $date_key . '_' . $exercise_id;
                $completion_map[$key] = [
                    'completed' => true,
                    'completed_at' => $completion['individual_completed_at'],
                    'exercise_name' => $exercise_name
                ];
                
                // 備用匹配1：使用日期 + exercise_name（避免 exercise_id 不匹配的情況）
                $key_by_name = $date_key . '_' . md5($exercise_name);
                $completion_map_by_name[$key_by_name] = [
                    'completed' => true,
                    'exercise_id' => $exercise_id,
                    'completed_at' => $completion['individual_completed_at']
                ];
                
                // 備用匹配2：使用日期 + exercise_name（直接字串匹配，更寬鬆）
                $key_by_name_direct = $date_key . '_' . trim($exercise_name);
                $completion_map_by_name[$key_by_name_direct] = [
                    'completed' => true,
                    'exercise_id' => $exercise_id,
                    'completed_at' => $completion['individual_completed_at']
                ];
            }
        }
        
        // 調試：記錄完成狀態映射
        error_log('完成狀態映射數量: ' . count($completion_map));
        error_log('備用映射數量: ' . count($completion_map_by_name));
        
        $calendar_data = [];
        
        foreach ($exercises as $exercise) {
            // 格式化日期為 YYYY-MM-DD
            $exercise_date = $exercise['exercise_date'];
            if (is_object($exercise_date)) {
                $date_key = $exercise_date->format('Y-m-d');
            } else {
                $date_key = date('Y-m-d', strtotime($exercise_date));
            }
            
            // 如果該日期還沒有記錄，初始化陣列
            if (!isset($calendar_data[$date_key])) {
                $calendar_data[$date_key] = [];
            }
            
            // 檢查完成狀態
            $exercise_id = $exercise['exercise_id'];
            $exercise_name = $exercise['exercise_name'];
            
            // 主要匹配：使用日期 + exercise_id
            $completion_key = $date_key . '_' . $exercise_id;
            $is_completed = isset($completion_map[$completion_key]) && $completion_map[$completion_key]['completed'];
            
            // 如果主要匹配失敗，使用備用匹配：日期 + exercise_name
            if (!$is_completed) {
                // 嘗試 MD5 匹配
                $completion_key_by_name = $date_key . '_' . md5($exercise_name);
                if (isset($completion_map_by_name[$completion_key_by_name])) {
                    $is_completed = $completion_map_by_name[$completion_key_by_name]['completed'];
                }
                
                // 如果還是失敗，嘗試直接字串匹配
                if (!$is_completed) {
                    $completion_key_by_name_direct = $date_key . '_' . trim($exercise_name);
                    if (isset($completion_map_by_name[$completion_key_by_name_direct])) {
                        $is_completed = $completion_map_by_name[$completion_key_by_name_direct]['completed'];
                    }
                }
                
                // 最後嘗試：遍歷所有完成記錄，比對日期和動作名稱（更寬鬆的匹配）
                if (!$is_completed) {
                    foreach ($completion_map as $key => $completion_info) {
                        // 從 key 中提取日期和 exercise_id
                        $parts = explode('_', $key);
                        if (count($parts) >= 2) {
                            $comp_date = $parts[0];
                            if ($comp_date === $date_key && 
                                isset($completion_info['exercise_name']) &&
                                trim($completion_info['exercise_name']) === trim($exercise_name)) {
                                $is_completed = $completion_info['completed'];
                                break;
                            }
                        }
                    }
                }
            }
            
            // 調試：記錄匹配結果
            if ($exercise_name === '相撲深蹲' || strpos($exercise_name, '相撲') !== false) {
                error_log("相撲深蹲匹配: date=$date_key, exercise_id=$exercise_id, exercise_name=$exercise_name, completed=" . ($is_completed ? 'true' : 'false'));
            }
            
            // 如果還是沒有匹配到，嘗試更寬鬆的匹配：只根據動作 ID 和名稱（不限日期）
            if (!$is_completed) {
                // 遍歷所有完成記錄，尋找匹配的動作（使用 exercise_id 或動作名稱）
                foreach ($completions as $comp) {
                    $comp_is_completed = ($comp['individual_completed'] == 1 || 
                                         $comp['individual_completed'] === '1' || 
                                         $comp['individual_completed'] === true);
                    
                    if (!$comp_is_completed) continue;
                    
                    // 匹配條件：exercise_id 相同 或 動作名稱相同
                    $id_match = ($comp['exercise_id'] == $exercise_id && $exercise_id > 0);
                    $name_match = (trim($comp['exercise_name']) === trim($exercise_name) && 
                                  trim($exercise_name) !== '');
                    
                    if ($id_match || $name_match) {
                        $comp_date = $comp['exercise_date'];
                        if (is_object($comp_date)) {
                            $comp_date_str = $comp_date->format('Y-m-d');
                        } else {
                            $comp_date_str = date('Y-m-d', strtotime($comp_date));
                        }
                        
                        // 如果日期相同或相差7天內（一週範圍），視為匹配
                        $date_diff = abs((strtotime($date_key) - strtotime($comp_date_str)) / 86400);
                        if ($date_diff <= 7) {
                            $is_completed = true;
                            error_log("寬鬆匹配成功: exercise=$exercise_name, exercise_id=$exercise_id, exercise_date=$date_key, completion_date=$comp_date_str, diff=$date_diff days");
                            break;
                        }
                    }
                }
            }
            
            // 檢查是否已存在相同的動作（避免重複）
            $exists = false;
            foreach ($calendar_data[$date_key] as $index => $existing_exercise) {
                if ($existing_exercise['id'] == $exercise['exercise_id'] && 
                    $existing_exercise['name'] == $exercise['exercise_name']) {
                    $exists = true;
                    // 如果已存在，更新完成狀態（以 completion 表的資料為準）
                    $calendar_data[$date_key][$index]['completed'] = $is_completed ? true : false;
                    $calendar_data[$date_key][$index]['individual_completed'] = $is_completed ? 1 : 0;
                    break;
                }
            }
            
            // 如果不存在重複，才加入
            if (!$exists) {
                $calendar_data[$date_key][] = [
                    'id' => $exercise['exercise_id'],
                    'exercise_id' => $exercise['exercise_id'],
                    'name' => $exercise['exercise_name'],
                    'exercise_name' => $exercise['exercise_name'],
                    'muscleGroup' => $exercise['muscle_group'],
                    'muscle_group' => $exercise['muscle_group'],
                    'sets' => (int)$exercise['sets'],
                    'reps' => (int)$exercise['reps'],
                    'weight' => $exercise['weight'] ? (float)$exercise['weight'] : null,
                    'completed' => $is_completed ? true : false,  // 明確設置為 boolean
                    'individual_completed' => $is_completed ? 1 : 0,  // 同時設置 individual_completed
                    'order_index' => (int)$exercise['order_index']
                ];
            }
        }
        
        // 調試：記錄最終的資料統計
        $total_exercises_in_calendar = 0;
        foreach ($calendar_data as $date_key => $day_exercises) {
            $total_exercises_in_calendar += count($day_exercises);
        }
        error_log("getCalendarData: 最終行事曆資料 - 有資料的日期數: " . count($calendar_data) . ", 總動作數: " . $total_exercises_in_calendar);
        
        echo json_encode([
            'success' => true,
            'data' => $calendar_data,
            'message' => '行事曆資料載入成功（從 training_plans 和 training_plan_exercises 讀取）',
            'count' => count($calendar_data),
            'total_exercises' => $total_exercises_in_calendar,
            'raw_exercises_count' => count($exercises)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '載入行事曆資料失敗: ' . $e->getMessage()]);
    }
}

// 同步行事曆動作到資料庫
function syncCalendarExercise($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['data']) || !isset($input['data']['date']) || !isset($input['data']['exercises'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數: data.date 或 data.exercises']);
        return;
    }
    
    try {
        $date = $input['data']['date'];
        $exercises = $input['data']['exercises'];
        // 取得使用者 ID（前端已傳入），避免硬編 9 造成跨用戶錯誤
        $user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
        if ($user_id <= 0) {
            throw new Exception('缺少或無效的 user_id');
        }
        
        // 調試：檢查資料格式
        error_log('syncCalendarExercise - 接收到的資料: ' . json_encode($input));
        error_log('syncCalendarExercise - exercises 數量: ' . count($exercises));
        
        // 計算星期幾與當週週一（作為 plan 的週起始日）
        $date_obj = new DateTime($date);
        $day_of_week_map = [
            0 => 'sunday',
            1 => 'monday', 
            2 => 'tuesday',
            3 => 'wednesday',
            4 => 'thursday',
            5 => 'friday',
            6 => 'saturday'
        ];
        $day_of_week = $day_of_week_map[$date_obj->format('w')];
        // 取本週週一（與前端顯示一致）
        $week_monday = (clone $date_obj)->modify('monday this week')->format('Y-m-d');
        
        // 開始事務（僅用於 completion 表寫入）
        $pdo->beginTransaction();
        
        // 1. 以『當週週一』為 key 檢查/取得訓練計畫
        $sql_get_plan = "SELECT id, week_number, week_start_date FROM training_plans WHERE user_id = ? AND week_start_date = ? LIMIT 1";
        $stmt_get_plan = $pdo->prepare($sql_get_plan);
        $stmt_get_plan->execute([$user_id, $week_monday]);
        $plan_result = $stmt_get_plan->fetch(PDO::FETCH_ASSOC);

        // 計算該日期所在年的週數（作為第二優先檢索條件，避免週起始日定義不同而重建）
        $year_start = new DateTime($date_obj->format('Y') . '-01-01');
        $week_number = ceil(($date_obj->getTimestamp() - $year_start->getTimestamp()) / (7 * 24 * 60 * 60)) + 1;

        if (!$plan_result) {
            // 二次嘗試：以 (user_id, week_number) 尋找既有計畫
            $sql_get_by_weeknum = "SELECT id, week_number, week_start_date FROM training_plans WHERE user_id = ? AND week_number = ? ORDER BY id DESC LIMIT 1";
            $stmt_get_by_weeknum = $pdo->prepare($sql_get_by_weeknum);
            $stmt_get_by_weeknum->execute([$user_id, $week_number]);
            $plan_by_week = $stmt_get_by_weeknum->fetch(PDO::FETCH_ASSOC);

            if ($plan_by_week) {
                $plan_id = $plan_by_week['id'];
                $week_number = $plan_by_week['week_number'];
            } else {
                // 如果找不到既有計畫，自動建立一個新的訓練計畫
                // 這樣用戶在行事曆上新增動作時，系統會自動為該週建立訓練計畫
                $sql_create_plan = "INSERT INTO training_plans (user_id, week_number, week_start_date, created_at, updated_at) VALUES (?, ?, ?, NOW(), NOW())";
                $stmt_create_plan = $pdo->prepare($sql_create_plan);
                $stmt_create_plan->execute([$user_id, $week_number, $week_monday]);
                $plan_id = $pdo->lastInsertId();
                error_log("自動建立新的訓練計畫: plan_id=$plan_id, user_id=$user_id, week_number=$week_number, week_start_date=$week_monday");
            }
        } else {
            $plan_id = $plan_result['id'];
            $week_number = (int)$plan_result['week_number'];
            // 如果 week_number 是 0，補齊
            if ($week_number == 0) {
                $sql_update_week = "UPDATE training_plans SET week_number = ? WHERE id = ?";
                $stmt_update_week = $pdo->prepare($sql_update_week);
                $stmt_update_week->execute([$week_number, $plan_id]);
            }
        }
        
        // 2. 若前端未帶有效動作，嘗試以當天 tpe 做為後援，避免前端過濾造成 0 筆
        $valid_count = 0;
        foreach ($exercises as $e) {
            $eid = isset($e['exercise_id']) && $e['exercise_id'] !== '' ? (int)$e['exercise_id'] : (isset($e['id']) ? (int)$e['id'] : 0);
            if ($eid > 0) { $valid_count++; }
        }
        if ($valid_count === 0) {
            $sql_tpe_day = "SELECT exercise_id AS id, exercise_name AS name, muscle_group AS muscleGroup, sets, reps, weight
                FROM training_plan_exercises WHERE plan_id = ? AND day_of_week = ? AND exercise_date = ? AND exercise_id > 0 ORDER BY order_index ASC";
            $stmt_tpe_day = $pdo->prepare($sql_tpe_day);
            $stmt_tpe_day->execute([$plan_id, $day_of_week, $date]);
            $fallback = $stmt_tpe_day->fetchAll(PDO::FETCH_ASSOC);
            $exercises = array_map(function($r){
                return [
                    'id' => (int)$r['id'],
                    'exercise_id' => (int)$r['id'],
                    'name' => $r['name'],
                    'muscleGroup' => $r['muscleGroup'],
                    'sets' => (int)$r['sets'],
                    'reps' => (int)$r['reps'],
                    'weight' => $r['weight'],
                    'completed' => false
                ];
            }, $fallback);
        }

        // 3. 處理 training_plan_exercises 表（新增動作時只寫入這個表）
        // 注意：training_plan_completion 表只在完成動作時才寫入，不在新增動作時寫入
        
        // 準備 training_plan_exercises 的 SQL
        $sql_check_existing_tpe = "SELECT id FROM training_plan_exercises WHERE plan_id = ? AND day_of_week = ? AND exercise_date = ? AND exercise_id = ?";
        $stmt_check_existing_tpe = $pdo->prepare($sql_check_existing_tpe);
        
        $sql_insert_tpe = "INSERT INTO training_plan_exercises (plan_id, day_of_week, exercise_date, exercise_id, exercise_name, muscle_group, sets, reps, weight, rest_time, notes, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_tpe = $pdo->prepare($sql_insert_tpe);
        
        $sql_update_tpe = "UPDATE training_plan_exercises SET exercise_name = ?, muscle_group = ?, sets = ?, reps = ?, weight = ? WHERE plan_id = ? AND day_of_week = ? AND exercise_date = ? AND exercise_id = ?";
        $stmt_update_tpe = $pdo->prepare($sql_update_tpe);
        
        $order_index = 0;
        $inserted_count = 0;
        $updated_count = 0;
        
        foreach ($exercises as $exercise) {
            $exercise_id_val = isset($exercise['exercise_id']) && $exercise['exercise_id'] !== ''
                ? (int)$exercise['exercise_id']
                : (isset($exercise['id']) ? (int)$exercise['id'] : null);
            
            if (!$exercise_id_val || $exercise_id_val <= 0) {
                continue; // 跳過無效的動作 ID
            }
            
            $exercise_name = $exercise['name'] ?? $exercise['exercise_name'] ?? '';
            $muscle_group = $exercise['muscleGroup'] ?? $exercise['muscle_group'] ?? '';
            $sets = isset($exercise['sets']) ? (int)$exercise['sets'] : 0;
            $reps = isset($exercise['reps']) ? (int)$exercise['reps'] : 0;
            $weight = isset($exercise['weight']) && $exercise['weight'] !== '' ? (float)$exercise['weight'] : null;
            $rest_time = isset($exercise['restTime']) ? $exercise['restTime'] : null;
            $notes = isset($exercise['notes']) ? $exercise['notes'] : null;
            $order_idx = isset($exercise['order_index']) ? (int)$exercise['order_index'] : $order_index;
            
            // 檢查是否已存在相同的動作記錄
            $stmt_check_existing_tpe->execute([$plan_id, $day_of_week, $date, $exercise_id_val]);
            $existing_tpe = $stmt_check_existing_tpe->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_tpe) {
                // 更新現有記錄
                $stmt_update_tpe->execute([
                    $exercise_name,
                    $muscle_group,
                    $sets,
                    $reps,
                    $weight,
                    $plan_id,
                    $day_of_week,
                    $date,
                    $exercise_id_val
                ]);
                $updated_count++;
                error_log("syncCalendarExercise: 更新 training_plan_exercises - plan_id=$plan_id, exercise_id=$exercise_id_val, date=$date");
            } else {
                // 插入新記錄
                $stmt_insert_tpe->execute([
                    $plan_id,
                    $day_of_week,
                    $date,
                    $exercise_id_val,
                    $exercise_name,
                    $muscle_group,
                    $sets,
                    $reps,
                    $weight,
                    $rest_time,
                    $notes,
                    $order_idx
                ]);
                $inserted_count++;
                error_log("syncCalendarExercise: 插入 training_plan_exercises - plan_id=$plan_id, exercise_id=$exercise_id_val, date=$date, name=$exercise_name");
            }
            
            $order_index++;
        }
        
        // 統計資訊
        $total_exercises = count($exercises);
        
        // 提交事務
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '行事曆動作同步成功（已寫入 training_plan_exercises 表）',
            'plan_id' => $plan_id,
            'exercises_count' => $total_exercises,
            'inserted_count' => $inserted_count,
            'updated_count' => $updated_count
        ]);
        
    } catch (Exception $e) {
        // 回滾事務
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '同步失敗: ' . $e->getMessage()]);
    }
}

// 刪除指定日期的單一動作（行事曆）
function deleteCalendarExercise($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!isset($input['date']) || (!isset($input['exercise_id']) && empty($input['exercise_name'])) || !isset($input['user_id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數: date, exercise_id/exercise_name, user_id']);
        return;
    }

    try {
        $date = $input['date'];
        $exercise_id = isset($input['exercise_id']) && $input['exercise_id'] !== '' ? (int)$input['exercise_id'] : null;
        $exercise_name = isset($input['exercise_name']) ? trim($input['exercise_name']) : '';
        $user_id = (int)$input['user_id'];

        if ($user_id <= 0) {
            throw new Exception('無效的 user_id');
        }

        // 決定 day_of_week 與該週週一以定位 plan
        $date_obj = new DateTime($date);
        $dow_map = [0=>'sunday',1=>'monday',2=>'tuesday',3=>'wednesday',4=>'thursday',5=>'friday',6=>'saturday'];
        $day_of_week = $dow_map[(int)$date_obj->format('w')];
        $week_monday = (clone $date_obj)->modify('monday this week')->format('Y-m-d');

        // 取得/確認 plan_id
        $sql_plan = "SELECT id FROM training_plans WHERE user_id = ? AND week_start_date = ? LIMIT 1";
        $stmt_plan = $pdo->prepare($sql_plan);
        $stmt_plan->execute([$user_id, $week_monday]);
        $plan = $stmt_plan->fetch(PDO::FETCH_ASSOC);

        if (!$plan) {
            // 沒有對應計畫，代表無需刪除
            echo json_encode(['success' => true, 'message' => '無對應訓練計畫，不需刪除', 'deleted' => 0]);
            return;
        }

        $plan_id = (int)$plan['id'];

        $pdo->beginTransaction();

        // 刪除 training_plan_exercises 中該日期該動作
        if ($exercise_id) {
            $sql_del_ex = "DELETE FROM training_plan_exercises WHERE plan_id = ? AND day_of_week = ? AND exercise_date = ? AND exercise_id = ?";
            $stmt_del_ex = $pdo->prepare($sql_del_ex);
            $stmt_del_ex->execute([$plan_id, $day_of_week, $date, $exercise_id]);
        } else {
            $sql_del_ex = "DELETE FROM training_plan_exercises WHERE plan_id = ? AND day_of_week = ? AND exercise_date = ? AND exercise_name = ?";
            $stmt_del_ex = $pdo->prepare($sql_del_ex);
            $stmt_del_ex->execute([$plan_id, $day_of_week, $date, $exercise_name]);
        }
        $deleted_exercises = $stmt_del_ex->rowCount();

        // 刪除 training_plan_completion 中對應的單筆記錄
        if ($exercise_id) {
            $sql_del_comp = "DELETE FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND day_of_week = ? AND exercise_id = ?";
            $stmt_del_comp = $pdo->prepare($sql_del_comp);
            $stmt_del_comp->execute([$plan_id, $user_id, $day_of_week, $exercise_id]);
        } else {
            $sql_del_comp = "DELETE FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND day_of_week = ? AND exercise_name = ?";
            $stmt_del_comp = $pdo->prepare($sql_del_comp);
            $stmt_del_comp->execute([$plan_id, $user_id, $day_of_week, $exercise_name]);
        }
        $deleted_logs = $stmt_del_comp->rowCount();

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => '單一動作刪除完成',
            'deleted_counts' => [
                'exercises' => $deleted_exercises,
                'logs' => $deleted_logs
            ]
        ]);

    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '刪除失敗: ' . $e->getMessage()]);
    }
}

// 刪除指定週的所有訓練資料
function deleteWeek($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['week_start'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'error' => '缺少必要參數: week_start']);
        return;
    }
    
    try {
        $week_start = $input['week_start'];
        $week_start_date = new DateTime($week_start);
        
        // 計算週的結束日期（週日）
        $week_end_date = clone $week_start_date;
        $week_end_date->modify('+6 days');
        
        $start_str = $week_start_date->format('Y-m-d');
        $end_str = $week_end_date->format('Y-m-d');
        
        // 開始事務
        $pdo->beginTransaction();
        
        // 1. 先獲取要刪除的 plan_id 列表
        $sql_get_plans = "SELECT id FROM training_plans WHERE week_start_date BETWEEN ? AND ?";
        $stmt_get_plans = $pdo->prepare($sql_get_plans);
        $stmt_get_plans->execute([$start_str, $end_str]);
        $plan_ids = $stmt_get_plans->fetchAll(PDO::FETCH_COLUMN);
        
        // 2. 刪除 training_plan_exercises 表中的資料
        $deleted_exercises = 0;
        if (!empty($plan_ids)) {
            $placeholders = str_repeat('?,', count($plan_ids) - 1) . '?';
            $sql1 = "DELETE FROM training_plan_exercises WHERE plan_id IN ($placeholders)";
            $stmt1 = $pdo->prepare($sql1);
            $stmt1->execute($plan_ids);
            $deleted_exercises = $stmt1->rowCount();
        }
        
        // 3. 刪除 training_plan_completion 表中的資料
        $deleted_logs = 0;
        if (!empty($plan_ids)) {
            $placeholders = str_repeat('?,', count($plan_ids) - 1) . '?';
            $sql3 = "DELETE FROM training_plan_completion WHERE plan_id IN ($placeholders)";
            $stmt3 = $pdo->prepare($sql3);
            $stmt3->execute($plan_ids);
            $deleted_logs = $stmt3->rowCount();
        }
        
        // 4. 刪除 training_plans 表中的資料
        $sql2 = "DELETE FROM training_plans WHERE week_start_date BETWEEN ? AND ?";
        $stmt2 = $pdo->prepare($sql2);
        $stmt2->execute([$start_str, $end_str]);
        $deleted_plans = $stmt2->rowCount();
        
        // 5. 如果 training_plans 表中沒有找到對應的記錄，但 training_plan_completion 中可能有孤兒記錄
        // 根據日期範圍刪除可能的孤兒記錄
        if ($deleted_plans == 0) {
            $sql_orphan = "DELETE FROM training_plan_completion WHERE DATE(created_at) BETWEEN ? AND ?";
            $stmt_orphan = $pdo->prepare($sql_orphan);
            $stmt_orphan->execute([$start_str, $end_str]);
            $deleted_logs += $stmt_orphan->rowCount();
        }
        
        // 提交事務
        $pdo->commit();
        
        echo json_encode([
            'success' => true, 
            'message' => '週資料刪除成功',
            'deleted_counts' => [
                'exercises' => $deleted_exercises,
                'plans' => $deleted_plans,
                'logs' => $deleted_logs
            ],
            'week_range' => $start_str . ' 到 ' . $end_str
        ]);
        
    } catch (Exception $e) {
        // 回滾事務
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        http_response_code(500);
        echo json_encode(['success' => false, 'error' => '刪除失敗: ' . $e->getMessage()]);
    }
}
?>
