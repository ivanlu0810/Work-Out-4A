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
    $port = 3307;  // 您的 XAMPP MySQL 端口
    $dbname = '健習生';
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

switch ($action) {
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
    case 'get_plan_id':
        getPlanId($pdo);
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
        $stmt = $pdo->prepare("SELECT * FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ? ORDER BY id");
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
        $stmt = $pdo->prepare("SELECT * FROM training_plan_completion WHERE plan_id = ? AND user_id = ? AND week_number = ? AND day_of_week = ? ORDER BY id");
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
        
        // 為每個動作創建獨立記錄
        $stmt = $pdo->prepare("INSERT INTO training_plan_completion 
            (plan_id, user_id, week_number, day_of_week, exercise_id, exercise_name, muscle_group, sets, reps, weight, individual_completed, individual_completed_at, individual_notes) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        foreach ($days as $day) {
            if (isset($input['weekly_plan'][$day]) && is_array($input['weekly_plan'][$day])) {
                foreach ($input['weekly_plan'][$day] as $exercise) {
                    if (isset($exercise['id']) && $exercise['id'] != 0) { // 排除休息日
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
                    }
                }
            }
        }
        
        $pdo->commit();
        echo json_encode(['success' => true, 'message' => '週計畫已同步到個別動作表格']);
        
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
?>
