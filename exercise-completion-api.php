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

// 獲取行事曆資料
function getCalendarData($pdo) {
    try {
        // 從前端取得 user_id
        $user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
        if ($user_id <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => '缺少或無效的 user_id']);
            return;
        }
        
        // 以 training_plan_completion 為主，保證畫面依據資料庫真實紀錄
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
                tpc.muscle_group,
                tpc.sets,
                tpc.reps,
                tpc.weight,
                0 as order_index,
                tpc.individual_completed,
                tpc.individual_completed_at
            FROM training_plan_completion tpc
            JOIN training_plans tp ON tp.id = tpc.plan_id AND tp.user_id = ?
            WHERE tpc.exercise_id IS NOT NULL
            ORDER BY exercise_date DESC";

        $stmt_c = $pdo->prepare($sql_from_completion);
        $stmt_c->execute([$user_id]);
        $exercises = $stmt_c->fetchAll(PDO::FETCH_ASSOC);
        
        $calendar_data = [];
        
        foreach ($exercises as $exercise) {
            $date_key = $exercise['exercise_date'];
            
            // 如果該日期還沒有記錄，初始化陣列
            if (!isset($calendar_data[$date_key])) {
                $calendar_data[$date_key] = [];
            }
            
            // 檢查是否已存在相同的動作（避免重複）
            $exists = false;
            foreach ($calendar_data[$date_key] as $existing_exercise) {
                if ($existing_exercise['id'] == $exercise['exercise_id'] && 
                    $existing_exercise['name'] == $exercise['exercise_name']) {
                    $exists = true;
                    break;
                }
            }
            
            // 如果不存在重複，才加入
            if (!$exists) {
                $calendar_data[$date_key][] = [
                    'id' => $exercise['exercise_id'],
                    'name' => $exercise['exercise_name'],
                    'muscleGroup' => $exercise['muscle_group'],
                    'sets' => (int)$exercise['sets'],
                    'reps' => (int)$exercise['reps'],
                    'weight' => $exercise['weight'] ? (float)$exercise['weight'] : null,
                    'completed' => $exercise['individual_completed'] == 1 || $exercise['individual_completed'] === '1' || $exercise['individual_completed'] === true
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'data' => $calendar_data,
            'message' => '行事曆資料載入成功',
            'count' => count($calendar_data)
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
                // 不建立新計畫：僅回傳成功訊息，不做任何寫入
                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message' => '本週沒有既有訓練計畫，已略過同步（不新增 training_plans）',
                    'plan_id' => null,
                    'exercises_count' => 0,
                    'completed_count' => 0
                ]);
                return;
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

        // 3. 僅處理 completion 表（符合你的需求）
        
        // 4. 插入個別動作的完成記錄（避免重複）
        $sql_check_existing = "SELECT id FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND exercise_id = ? AND day_of_week = ?";
        $stmt_check_existing = $pdo->prepare($sql_check_existing);
        
        $sql_insert_completion = "INSERT INTO training_plan_completion (plan_id, user_id, exercise_id, exercise_name, muscle_group, sets, reps, weight, individual_completed, individual_completed_at, week_number, day_of_week) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert_completion = $pdo->prepare($sql_insert_completion);
        
        $sql_update_completion = "UPDATE training_plan_completion SET exercise_name = ?, muscle_group = ?, sets = ?, reps = ?, weight = ?, individual_completed = ?, individual_completed_at = ?, updated_at = CURRENT_TIMESTAMP WHERE plan_id = ? AND user_id = ? AND exercise_id = ? AND day_of_week = ?";
        $stmt_update_completion = $pdo->prepare($sql_update_completion);
        
        foreach ($exercises as $exercise) {
            $exercise_id_val = isset($exercise['exercise_id']) && $exercise['exercise_id'] !== ''
                ? (int)$exercise['exercise_id']
                : (isset($exercise['id']) ? (int)$exercise['id'] : null);
            // 檢查是否已存在相同的動作記錄
            $stmt_check_existing->execute([$plan_id, $user_id, $exercise_id_val, $day_of_week]);
            $existing_record = $stmt_check_existing->fetch(PDO::FETCH_ASSOC);
            
            if ($existing_record && $exercise_id_val) {
                // 更新現有記錄
                $stmt_update_completion->execute([
                    $exercise['name'],
                    $exercise['muscleGroup'],
                    $exercise['sets'],
                    $exercise['reps'],
                    isset($exercise['weight']) ? $exercise['weight'] : null,
                    isset($exercise['completed']) && $exercise['completed'] ? 1 : 0,
                    isset($exercise['completed']) && $exercise['completed'] ? date('Y-m-d H:i:s') : null,
                    $plan_id,
                    $user_id,
                    $exercise_id_val,
                    $day_of_week
                ]);
            } else if ($exercise_id_val) {
                // 插入新記錄
                $stmt_insert_completion->execute([
                    $plan_id,
                    $user_id,
                    $exercise_id_val,
                    $exercise['name'],
                    $exercise['muscleGroup'],
                    $exercise['sets'],
                    $exercise['reps'],
                    isset($exercise['weight']) ? $exercise['weight'] : null,
                    isset($exercise['completed']) && $exercise['completed'] ? 1 : 0,
                    isset($exercise['completed']) && $exercise['completed'] ? date('Y-m-d H:i:s') : null,
                    $week_number,
                    $day_of_week
                ]);
            }
        }
        
        // 4. 更新整體完成統計
        $total_exercises = count($exercises);
        $completed_exercises = count(array_filter($exercises, function($ex) { return isset($ex['completed']) && $ex['completed']; }));
        $completion_percentage = $total_exercises > 0 ? ($completed_exercises / $total_exercises) * 100 : 0;
        
        // 更新或插入整體完成記錄
        $sql_upsert_overall = "INSERT INTO training_plan_completion (plan_id, user_id, exercise_id, exercise_name, muscle_group, sets, reps, weight, individual_completed, individual_completed_at, week_number, day_of_week, is_completed, completed_at, completion_percentage, total_exercises, completed_exercises) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE is_completed = VALUES(is_completed), completed_at = VALUES(completed_at), completion_percentage = VALUES(completion_percentage), total_exercises = VALUES(total_exercises), completed_exercises = VALUES(completed_exercises)";
        $stmt_upsert_overall = $pdo->prepare($sql_upsert_overall);
        
        // 插入一個特殊的整體完成記錄（exercise_id = 0 表示整體記錄）
        $stmt_upsert_overall->execute([
            $plan_id,
            $user_id,
            0, // exercise_id = 0 表示整體記錄
            '整體完成記錄',
            '整體',
            0, // sets
            0, // reps
            null, // weight
            0, // individual_completed
            null, // individual_completed_at
            $week_number,
            $day_of_week,
            $completed_exercises > 0 ? 1 : 0, // is_completed
            $completed_exercises > 0 ? date('Y-m-d H:i:s') : null, // completed_at
            $completion_percentage,
            $total_exercises,
            $completed_exercises
        ]);
        
        // 提交事務
        $pdo->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '行事曆動作同步成功',
            'plan_id' => $plan_id,
            'exercises_count' => $total_exercises,
            'completed_count' => $completed_exercises
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
