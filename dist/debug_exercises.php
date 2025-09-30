<?php
header('Content-Type: application/json; charset=utf-8');

// 資料庫連接設定
$host = '1.tcp.jp.ngrok.io';
$port = '20959';
$dbname = 'test';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception("資料庫連接失敗: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // 查詢所有動作的肌群分佈
    $sql = "SELECT target_muscle as muscle_group, COUNT(*) as count 
            FROM exercises 
            GROUP BY target_muscle 
            ORDER BY count DESC";
    
    $result = $conn->query($sql);
    $muscleGroups = [];
    
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $muscleGroups[] = $row;
        }
    }
    
    // 查詢特定肌群的動作
    $specificMuscles = ['上胸', '中胸', '下胸', '上背', '中背', '下背', '肩膀前束', '肩膀中束', '肩膀後束', '三頭肌', '二頭肌', '股四頭肌', '股二頭肌', '臀肌', '上腹', '下腹', '側腹'];
    
    $specificExercises = [];
    foreach ($specificMuscles as $muscle) {
        $sql = "SELECT name, target_muscle, difficulty_level 
                FROM exercises 
                WHERE target_muscle = ? 
                LIMIT 5";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $muscle);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $exercises = [];
        while($row = $result->fetch_assoc()) {
            $exercises[] = $row;
        }
        
        $specificExercises[$muscle] = $exercises;
    }
    
    echo json_encode([
        'success' => true,
        'total_exercises' => $conn->query("SELECT COUNT(*) as count FROM exercises")->fetch_assoc()['count'],
        'muscle_groups' => $muscleGroups,
        'specific_exercises' => $specificExercises
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
