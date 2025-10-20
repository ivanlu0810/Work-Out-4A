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
            echo json_encode(['success' => false, 'error' => '缺少必要欄位'], JSON_UNESCAPED_UNICODE); exit;
        }

        // 查詢當天總動作數
        $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM training_plan_exercises WHERE plan_id = ? AND day_of_week = ?");
        $stmt->execute([$planId, $day]);
        $total = (int)($stmt->fetch()['cnt'] ?? 0);
        $completed = $isDone ? $total : 0;

        // 若沒有 UNIQUE KEY 也能運作：先查有無，無則 INSERT，有則 UPDATE
        $chk = $pdo->prepare("SELECT id FROM training_plan_completion WHERE plan_id = ? AND day_of_week = ? LIMIT 1");
        $chk->execute([$planId, $day]);
        $row = $chk->fetch();
        if ($row) {
            $upd = $pdo->prepare("UPDATE training_plan_completion
                                  SET user_id = :user_id,
                                      week_number = :week_number,
                                      is_completed = :is_completed,
                                      completed_at = CASE WHEN :is_completed=1 THEN NOW() ELSE NULL END,
                                      completion_percentage = :pct,
                                      total_exercises = :total,
                                      completed_exercises = :completed,
                                      updated_at = NOW()
                                  WHERE id = :id");
            $upd->execute([
                ':user_id' => $userId,
                ':week_number' => $weekNum,
                ':is_completed' => $isDone,
                ':pct' => $pct,
                ':total' => $total,
                ':completed' => $completed,
                ':id' => $row['id']
            ]);
        } else {
            $ins = $pdo->prepare("INSERT INTO training_plan_completion
                                  (plan_id, user_id, week_number, day_of_week, is_completed, completed_at, completion_percentage, total_exercises, completed_exercises)
                                  VALUES (:plan_id, :user_id, :week_number, :day, :is_completed, CASE WHEN :is_completed=1 THEN NOW() ELSE NULL END, :pct, :total, :completed)
                                 ");
            $ins->execute([
                ':plan_id' => $planId,
                ':user_id' => $userId,
                ':week_number' => $weekNum,
                ':day' => $day,
                ':is_completed' => $isDone,
                ':pct' => $pct,
                ':total' => $total,
                ':completed' => $completed,
            ]);
        }

        echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE); exit;
    }

    // 2) 讀取某計畫的完成細節
    if ($action === 'get_plan_completion_detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $planId = (int)($_GET['plan_id'] ?? 0);
        if (!$planId) { echo json_encode(['success' => false, 'error' => '缺少 plan_id']); exit; }
        $stmt = $pdo->prepare("SELECT * FROM training_plan_completion WHERE plan_id = ? ORDER BY FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday')");
        $stmt->execute([$planId]);
        $rows = $stmt->fetchAll();
        echo json_encode(['success' => true, 'rows' => $rows], JSON_UNESCAPED_UNICODE); exit;
    }

    // 3) 讀取計畫（供前端載入 weeklyPlan）
    if ($action === 'load_training_plan' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = (int)($_GET['user_id'] ?? 0);
        $weekNum = isset($_GET['week_number']) ? (int)$_GET['week_number'] : null;
        if (!$userId) { echo json_encode(['success' => false, 'error' => '缺少 user_id']); exit; }

        $params = [$userId];
        $where = 'WHERE user_id = ?';
        if ($weekNum !== null) { $where .= ' AND week_number = ?'; $params[] = $weekNum; }

        // plans
        $plans = $pdo->prepare("SELECT id, user_id, week_start_date, week_number, plan_name FROM training_plans $where ORDER BY id DESC");
        $plans->execute($params);
        $planRows = $plans->fetchAll();

        $resultPlans = [];
        foreach ($planRows as $p) {
            $exStmt = $pdo->prepare("SELECT day_of_week, exercise_id, exercise_name, muscle_group, sets, reps, weight, rest_time FROM training_plan_exercises WHERE plan_id = ? ORDER BY FIELD(day_of_week,'monday','tuesday','wednesday','thursday','friday','saturday','sunday'), id");
            $exStmt->execute([$p['id']]);
            $exRows = $exStmt->fetchAll();
            $weekly = [
                'monday'=>[], 'tuesday'=>[], 'wednesday'=>[], 'thursday'=>[], 'friday'=>[], 'saturday'=>[], 'sunday'=>[]
            ];
            foreach ($exRows as $ex) {
                $weekly[$ex['day_of_week']][] = [
                    'id' => (int)$ex['exercise_id'],
                    'name' => $ex['exercise_name'],
                    'muscleGroup' => $ex['muscle_group'],
                    'sets' => (int)$ex['sets'],
                    'reps' => (int)$ex['reps'],
                    'weight' => $ex['weight'] === null ? null : (float)$ex['weight'],
                    'restTime' => $ex['rest_time'] === null ? null : (int)$ex['rest_time'],
                ];
            }
            $resultPlans[] = [
                'id' => (int)$p['id'],
                'user_id' => (int)$p['user_id'],
                'week_start_date' => $p['week_start_date'],
                'week_number' => (int)$p['week_number'],
                'plan_name' => $p['plan_name'],
                'exercises' => $weekly,
            ];
        }
        echo json_encode(['success' => true, 'plans' => $resultPlans], JSON_UNESCAPED_UNICODE); exit;
    }

    // 3.5) 獲取有資料的週次列表
    if ($action === 'get_available_weeks' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = (int)($_GET['user_id'] ?? 0);
        if (!$userId) { echo json_encode(['success' => false, 'error' => '缺少 user_id']); exit; }

        // 查詢該用戶所有有資料的週次
        $stmt = $pdo->prepare("SELECT DISTINCT week_number, week_start_date, plan_name FROM training_plans WHERE user_id = ? AND is_active = 1 ORDER BY week_number ASC");
        $stmt->execute([$userId]);
        $weeks = $stmt->fetchAll();
        
        echo json_encode(['success' => true, 'weeks' => $weeks], JSON_UNESCAPED_UNICODE); exit;
    }

    echo json_encode(['success' => false, 'error' => '未知的 action'], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>

<?php
// 健習生系統 - 完成狀態記錄 PHP API
// 用於處理前端完成狀態的 AJAX 請求
// 建立時間：2024年12月

ini_set('display_errors', 0);
error_reporting(0);
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

// 資料庫連線設定
$host = '1.tcp.jp.ngrok.io';
$db   = 'test';
$user = 'root';
$pass = '';
$port = 20959;

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => '資料庫連線失敗: ' . $e->getMessage()]);
    exit;
}

// 取得請求方法和動作
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

switch($action) {
    case 'save_training_plan':
        if ($method === 'POST') {
            saveTrainingPlan($pdo);
        }
        break;
    case 'delete_training_plan':
        if ($method === 'POST') {
            deleteTrainingPlan($pdo);
        }
        break;
    case 'delete_training_plan_by_week':
        if ($method === 'POST') {
            deleteTrainingPlanByWeek($pdo);
        }
        break;
        
    case 'load_training_plan':
        if ($method === 'GET') {
            loadTrainingPlan($pdo);
        }
        break;
        
    case 'save_exercise_completion':
        if ($method === 'POST') {
            saveExerciseCompletion($pdo);
        }
        break;
        
    case 'save_group_completion':
        if ($method === 'POST') {
            saveGroupCompletion($pdo);
        }
        break;
        
    case 'save_training_plan_completion':
        if ($method === 'POST') {
            saveTrainingPlanCompletion($pdo);
        }
        break;
        
    case 'get_completion_stats':
        if ($method === 'GET') {
            getCompletionStats($pdo);
        }
        break;
        
    case 'get_user_progress':
        if ($method === 'GET') {
            getUserProgress($pdo);
        }
        break;
        
    case 'get_training_history':
        if ($method === 'GET') {
            getTrainingHistory($pdo);
        }
        break;
        
    case 'get_plan_completion':
        if ($method === 'GET') {
            getPlanCompletion($pdo);
        }
        break;
    case 'get_plan_completion_detail':
        if ($method === 'GET') {
            getPlanCompletionDetail($pdo);
        }
        break;
        
    case 'get_plan_completion':
        if ($method === 'GET') {
            getPlanCompletion($pdo);
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => '無效的動作']);
        break;
}

// 儲存單個運動完成狀態
function saveExerciseCompletion($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['session_id', 'exercise_id', 'user_id', 'completion_status'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "缺少必要欄位: $field"]);
            return;
        }
    }
    
    try {
        $sql = "INSERT INTO exercise_completion_logs 
                (session_id, exercise_id, user_id, completion_status, actual_sets_completed, 
                 actual_reps_completed, actual_weight_used, notes) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['session_id'],
            $input['exercise_id'],
            $input['user_id'],
            $input['completion_status'],
            $input['actual_sets_completed'] ?? 0,
            $input['actual_reps_completed'] ?? 0,
            $input['actual_weight_used'] ?? null,
            $input['notes'] ?? null
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '運動完成狀態已儲存',
            'id' => $pdo->lastInsertId()
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '儲存失敗: ' . $e->getMessage()]);
    }
}

// 儲存訓練組別完成狀態
function saveGroupCompletion($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['session_id', 'user_id', 'group_name'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "缺少必要欄位: $field"]);
            return;
        }
    }
    
    try {
        // 檢查是否已存在記錄
        $check_sql = "SELECT id FROM workout_group_completion 
                      WHERE session_id = ? AND user_id = ? AND group_name = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$input['session_id'], $input['user_id'], $input['group_name']]);
        
        if ($check_stmt->rowCount() > 0) {
            // 更新現有記錄
            $sql = "UPDATE workout_group_completion 
                    SET is_completed = ?, completed_at = ?, completion_percentage = ?, 
                        total_exercises = ?, completed_exercises = ?, skipped_exercises = ?, 
                        notes = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE session_id = ? AND user_id = ? AND group_name = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['is_completed'] ? 1 : 0,
                $input['is_completed'] ? date('Y-m-d H:i:s') : null,
                $input['completion_percentage'] ?? 0,
                $input['total_exercises'] ?? 0,
                $input['completed_exercises'] ?? 0,
                $input['skipped_exercises'] ?? 0,
                $input['notes'] ?? null,
                $input['session_id'],
                $input['user_id'],
                $input['group_name']
            ]);
        } else {
            // 插入新記錄
            $sql = "INSERT INTO workout_group_completion 
                    (session_id, user_id, group_name, group_order, is_completed, completed_at, 
                     completion_percentage, total_exercises, completed_exercises, skipped_exercises, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['session_id'],
                $input['user_id'],
                $input['group_name'],
                $input['group_order'] ?? 0,
                $input['is_completed'] ? 1 : 0,
                $input['is_completed'] ? date('Y-m-d H:i:s') : null,
                $input['completion_percentage'] ?? 0,
                $input['total_exercises'] ?? 0,
                $input['completed_exercises'] ?? 0,
                $input['skipped_exercises'] ?? 0,
                $input['notes'] ?? null
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => '訓練組別完成狀態已儲存'
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '儲存失敗: ' . $e->getMessage()]);
    }
}

// 儲存訓練課程完成狀態
function saveSessionCompletion($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['session_id', 'user_id'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "缺少必要欄位: $field"]);
            return;
        }
    }
    
    try {
        $sql = "UPDATE workout_sessions 
                SET is_completed = ?, completed_at = ?, completion_percentage = ?, 
                    total_duration = ?, notes = ?
                WHERE id = ? AND user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $input['is_completed'] ? 1 : 0,
            $input['is_completed'] ? date('Y-m-d H:i:s') : null,
            $input['completion_percentage'] ?? 0,
            $input['total_duration'] ?? null,
            $input['notes'] ?? null,
            $input['session_id'],
            $input['user_id']
        ]);
        
        echo json_encode([
            'success' => true,
            'message' => '訓練課程完成狀態已儲存'
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '儲存失敗: ' . $e->getMessage()]);
    }
}

// 取得完成統計資料
function getCompletionStats($pdo) {
    $user_id = $_GET['user_id'] ?? 1; // 預設使用者 ID
    
    try {
        $sql = "SELECT 
                    COUNT(DISTINCT ws.id) as total_sessions,
                    COUNT(DISTINCT CASE WHEN ws.is_completed = 1 THEN ws.id END) as completed_sessions,
                    COUNT(DISTINCT ecl.id) as total_exercise_logs,
                    COUNT(DISTINCT CASE WHEN ecl.completion_status = 'completed' THEN ecl.id END) as completed_exercises,
                    COALESCE(AVG(ws.completion_percentage), 0) as avg_session_completion,
                    MAX(ws.completed_at) as last_workout_date,
                    COALESCE(SUM(ws.total_duration), 0) as total_workout_time
                FROM users u
                LEFT JOIN workout_sessions ws ON u.user_id = ws.user_id
                LEFT JOIN exercise_completion_logs ecl ON ws.id = ecl.session_id
                WHERE u.user_id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'stats' => $stats
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
    }
}

// 取得使用者進度
function getUserProgress($pdo) {
    $user_id = $_GET['user_id'] ?? 1; // 預設使用者 ID
    
    try {
        $sql = "SELECT 
                    tp.id as plan_id,
                    tp.plan_name,
                    tp.week_number,
                    tp.week_start_date,
                    COUNT(DISTINCT tpc.day_of_week) as total_days,
                    COUNT(DISTINCT CASE WHEN tpc.is_completed = 1 THEN tpc.day_of_week END) as completed_days,
                    COALESCE(AVG(tpc.completion_percentage), 0) as avg_completion_percentage,
                    COALESCE(SUM(tpc.total_exercises), 0) as total_exercises,
                    COALESCE(SUM(tpc.completed_exercises), 0) as completed_exercises,
                    COALESCE(SUM(tpc.session_duration), 0) as total_duration
                FROM training_plans tp
                LEFT JOIN training_plan_completion tpc ON tp.id = tpc.plan_id
                WHERE tp.user_id = ?
                GROUP BY tp.id, tp.plan_name, tp.week_number, tp.week_start_date
                ORDER BY tp.week_start_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $progress = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'progress' => $progress
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
    }
}

// 儲存訓練計畫
function saveTrainingPlan($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['user_id', 'week_start_date', 'week_number', 'plan_name', 'weekly_plan'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "缺少必要欄位: $field"]);
            return;
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
            
            // 刪除現有的完成記錄（重要！）
            $delete_completion_sql = "DELETE FROM training_plan_completion WHERE plan_id = ?";
            $delete_completion_stmt = $pdo->prepare($delete_completion_sql);
            $delete_completion_stmt->execute([$plan_id]);
            
            // 移除這行 echo，因為它會破壞 JSON 響應
            // echo "已清除 plan_id $plan_id 的舊運動記錄和完成記錄\n";
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
            // 安全取得 day_of_week 值，避免不在映射表時發生錯誤
            $dayOfWeek = isset($day_mapping[$day]) ? $day_mapping[$day] : 'monday';

            foreach ($exercises as $index => $exercise) {
                // 前端送來的是物件陣列，這裡以陣列方式安全讀取
                $exerciseId = isset($exercise['id']) ? (int)$exercise['id'] : 0;
                if ($exerciseId === 0) {
                    // 跳過休息日或無效資料
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
        
    } catch(PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => '儲存失敗: ' . $e->getMessage()]);
    }
}

// 刪除本週計畫（含明細與完成紀錄）
function deleteTrainingPlan($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $required = ['plan_id','user_id'];
    foreach ($required as $f) {
        if (!isset($input[$f])) {
            http_response_code(400);
            echo json_encode(['error' => "缺少必要欄位: $f"]);
            return;
        }
    }
    try {
        $pdo->beginTransaction();
        $del1 = $pdo->prepare("DELETE FROM training_plan_completion WHERE plan_id=? AND user_id=?");
        $del1->execute([$input['plan_id'], $input['user_id']]);
        $del2 = $pdo->prepare("DELETE FROM training_plan_exercises WHERE plan_id=?");
        $del2->execute([$input['plan_id']]);
        $del3 = $pdo->prepare("DELETE FROM training_plans WHERE id=? AND user_id=?");
        $del3->execute([$input['plan_id'], $input['user_id']]);
        $pdo->commit();
        echo json_encode(['success'=>true]);
    } catch(PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error'=>'刪除失敗: '.$e->getMessage()]);
    }
}

// 按週次刪除訓練計畫
function deleteTrainingPlanByWeek($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    $required = ['week_number','week_start_date','user_id'];
    foreach ($required as $f) {
        if (!isset($input[$f])) {
            http_response_code(400);
            echo json_encode(['error' => "缺少必要欄位: $f"]);
            return;
        }
    }
    try {
        $pdo->beginTransaction();
        
        // 先找出該週的所有計畫
        $find_sql = "SELECT id FROM training_plans WHERE user_id = ? AND week_number = ? AND week_start_date = ?";
        $find_stmt = $pdo->prepare($find_sql);
        $find_stmt->execute([$input['user_id'], $input['week_number'], $input['week_start_date']]);
        $plan_ids = $find_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($plan_ids)) {
            echo json_encode(['success' => true, 'message' => '沒有找到該週的計畫']);
            $pdo->commit();
            return;
        }
        
        // 刪除所有相關資料
        foreach ($plan_ids as $plan_id) {
            $del1 = $pdo->prepare("DELETE FROM training_plan_completion WHERE plan_id=? AND user_id=?");
            $del1->execute([$plan_id, $input['user_id']]);
            $del2 = $pdo->prepare("DELETE FROM training_plan_exercises WHERE plan_id=?");
            $del2->execute([$plan_id]);
            $del3 = $pdo->prepare("DELETE FROM training_plans WHERE id=? AND user_id=?");
            $del3->execute([$plan_id, $input['user_id']]);
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'deleted_count' => count($plan_ids)]);
    } catch(PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error'=>'刪除失敗: '.$e->getMessage()]);
    }
}

// 載入訓練計畫
function loadTrainingPlan($pdo) {
    $user_id = $_GET['user_id'] ?? 1;
    $week_number = $_GET['week_number'] ?? null;
    $week_start_date = $_GET['week_start_date'] ?? null;
    
    // 添加調試日誌
    error_log("loadTrainingPlan: user_id=$user_id, week_number=$week_number, week_start_date=$week_start_date");
    
    try {
        $sql = "SELECT tp.*, tpe.* FROM training_plans tp
                LEFT JOIN training_plan_exercises tpe ON tp.id = tpe.plan_id
                WHERE tp.user_id = ? AND tp.is_active = 1";
        $params = [$user_id];
        
        if ($week_number) {
            $sql .= " AND tp.week_number = ?";
            $params[] = $week_number;
        }
        
        if ($week_start_date) {
            $sql .= " AND tp.week_start_date = ?";
            $params[] = $week_start_date;
        }
        
        $sql .= " ORDER BY tp.week_start_date DESC, tpe.day_of_week, tpe.order_index";
        
        error_log("SQL: $sql");
        error_log("Params: " . json_encode($params));
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("查詢結果數量: " . count($results));
        
        // 組織資料結構
        $plans = [];
        foreach ($results as $row) {
            $plan_id = $row['plan_id'];
            if (!isset($plans[$plan_id])) {
                $plans[$plan_id] = [
                    'id' => $row['id'],
                    'user_id' => $row['user_id'],
                    'week_start_date' => $row['week_start_date'],
                    'week_number' => $row['week_number'],
                    'plan_name' => $row['plan_name'],
                    'is_active' => $row['is_active'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'exercises' => []
                ];
            }
            
            if ($row['exercise_id']) {
                error_log("處理動作: plan_id={$plan_id}, day_of_week={$row['day_of_week']}, exercise_name={$row['exercise_name']}");
                $plans[$plan_id]['exercises'][$row['day_of_week']][] = [
                    'id' => $row['exercise_id'],
                    'name' => $row['exercise_name'],
                    'muscleGroup' => $row['muscle_group'],
                    'sets' => $row['sets'],
                    'reps' => $row['reps'],
                    'weight' => $row['weight'],
                    'restTime' => $row['rest_time'],
                    'notes' => $row['notes'],
                    'orderIndex' => $row['order_index']
                ];
            }
        }
        
        error_log("最終 plans 結構: " . json_encode($plans));
        
        echo json_encode([
            'success' => true,
            'plans' => array_values($plans)
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
    }
}

// 取得訓練歷史記錄
function getTrainingHistory($pdo) {
    $user_id = $_GET['user_id'] ?? 1;
    
    try {
        $sql = "SELECT 
                    tp.id as plan_id,
                    tp.plan_name,
                    tp.week_number,
                    tp.week_start_date,
                    tp.created_at,
                    COUNT(DISTINCT tpe.day_of_week) as total_days,
                    COUNT(DISTINCT CASE WHEN tpc.is_completed = 1 THEN tpc.day_of_week END) as completed_days,
                    COALESCE(AVG(tpc.completion_percentage), 0) as avg_completion_percentage,
                    COUNT(DISTINCT tpe.id) as total_exercises,
                    SUM(CASE WHEN tpc.is_completed = 1 THEN 1 ELSE 0 END) as completed_exercises
                FROM training_plans tp
                LEFT JOIN training_plan_exercises tpe ON tp.id = tpe.plan_id
                LEFT JOIN training_plan_completion tpc ON tp.id = tpc.plan_id
                WHERE tp.user_id = ?
                GROUP BY tp.id, tp.plan_name, tp.week_number, tp.week_start_date, tp.created_at
                ORDER BY tp.week_start_date DESC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$user_id]);
        $history = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'history' => $history
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
    }
}

// 取得特定計畫的每日完成狀態（給前端初始化用）
function getPlanCompletion($pdo) {
    $plan_id = $_GET['plan_id'] ?? null;
    if (!$plan_id) {
        http_response_code(400);
        echo json_encode(['error' => '缺少必要參數: plan_id']);
        return;
    }
    try {
        $sql = "SELECT day_of_week, is_completed, completion_percentage, total_exercises, completed_exercises, skipped_exercises, session_duration, completed_at
                FROM training_plan_completion
                WHERE plan_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$plan_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'rows' => $rows]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '查詢失敗: ' . $e->getMessage()]);
    }
}

// 取得計畫完成詳情（含未完成日）
function getPlanCompletionDetail($pdo) {
    $plan_id = $_GET['plan_id'] ?? null;
    if (!$plan_id) {
        http_response_code(400);
        echo json_encode(['error' => '缺少必要參數: plan_id']);
        return;
    }
    try {
        $days = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
        $sql = "SELECT day_of_week, is_completed, completion_percentage, total_exercises, completed_exercises, skipped_exercises, session_duration, completed_at
                FROM training_plan_completion WHERE plan_id=?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$plan_id]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $map = [];
        foreach ($rows as $r) { $map[$r['day_of_week']] = $r; }
        $ret = [];
        foreach ($days as $d) {
            if (isset($map[$d])) $ret[] = $map[$d];
            else $ret[] = ['day_of_week'=>$d,'is_completed'=>0,'completion_percentage'=>0,'total_exercises'=>0,'completed_exercises'=>0,'skipped_exercises'=>0,'session_duration'=>null,'completed_at'=>null];
        }
        echo json_encode(['success'=>true,'rows'=>$ret]);
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error'=>'查詢失敗: '.$e->getMessage()]);
    }
}

// 儲存訓練計畫完成狀態
function saveTrainingPlanCompletion($pdo) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $required_fields = ['plan_id', 'user_id', 'week_number', 'day_of_week'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "缺少必要欄位: $field"]);
            return;
        }
    }
    
    try {
        // 檢查是否已存在記錄
        $check_sql = "SELECT id FROM training_plan_completion 
                      WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ?";
        $check_stmt = $pdo->prepare($check_sql);
        $check_stmt->execute([$input['plan_id'], $input['user_id'], $input['week_number'], $input['day_of_week']]);
        
        if ($check_stmt->rowCount() > 0) {
            // 更新現有記錄
            $sql = "UPDATE training_plan_completion 
                    SET is_completed = ?, completed_at = ?, completion_percentage = ?, 
                        total_exercises = ?, completed_exercises = ?, skipped_exercises = ?, 
                        session_duration = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
                    WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['is_completed'] ? 1 : 0,
                $input['is_completed'] ? date('Y-m-d H:i:s') : null,
                $input['completion_percentage'] ?? 0,
                $input['total_exercises'] ?? 0,
                $input['completed_exercises'] ?? 0,
                $input['skipped_exercises'] ?? 0,
                $input['session_duration'] ?? null,
                $input['notes'] ?? null,
                $input['plan_id'],
                $input['user_id'],
                $input['week_number'],
                $input['day_of_week']
            ]);
        } else {
            // 插入新記錄
            $sql = "INSERT INTO training_plan_completion 
                    (plan_id, user_id, week_number, day_of_week, is_completed, completed_at, 
                     completion_percentage, total_exercises, completed_exercises, skipped_exercises, 
                     session_duration, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $input['plan_id'],
                $input['user_id'],
                $input['week_number'],
                $input['day_of_week'],
                $input['is_completed'] ? 1 : 0,
                $input['is_completed'] ? date('Y-m-d H:i:s') : null,
                $input['completion_percentage'] ?? 0,
                $input['total_exercises'] ?? 0,
                $input['completed_exercises'] ?? 0,
                $input['skipped_exercises'] ?? 0,
                $input['session_duration'] ?? null,
                $input['notes'] ?? null
            ]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => '訓練計畫完成狀態已儲存'
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(['error' => '儲存失敗: ' . $e->getMessage()]);
    }
}
?>
