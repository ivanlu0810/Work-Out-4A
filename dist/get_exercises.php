<?php
// 抑制錯誤輸出
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 資料庫連接設定
$host = 'localhost';
$dbname = '健習生';
$username = 'root';
$password = '';

// 如果資料庫名稱有問題，嘗試其他可能的名稱
$possible_dbnames = ['健習生', 'jianxishang', 'fitness', 'exercise_db'];

try {
    // 嘗試連接資料庫
    $conn = null;
    $connected_db = '';
    
    foreach ($possible_dbnames as $db) {
        $conn = new mysqli($host, $username, $password, $db);
        if (!$conn->connect_error) {
            $connected_db = $db;
            break;
        }
    }
    
    if ($conn === null || $conn->connect_error) {
        throw new Exception("無法連接到任何資料庫。嘗試的資料庫: " . implode(', ', $possible_dbnames));
    }
    
    // 設定字符集
    $conn->set_charset("utf8mb4");
    
    // 查詢動作資料
    $sql = "SELECT id, name, target_muscle as muscle_group, 
            COALESCE(description, '無描述') as description,
            COALESCE(difficulty_level, '初級') as difficulty_level,
            COALESCE(equipment_needed, '無') as equipment_needed
            FROM exercises 
            ORDER BY target_muscle, name";
    
    $result = $conn->query($sql);
    
    if ($result === false) {
        throw new Exception("查詢失敗: " . $conn->error);
    }
    
    $data = [];
    
    // 添加休息選項
    $data[] = [
        "id" => 0,
        "name" => "休息",
        "muscle_group" => "休息",
        "description" => "今日無訓練，讓身體充分恢復",
        "difficulty_level" => "無",
        "equipment_needed" => "無"
    ];
    
    // 添加資料庫中的動作
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            "id" => (int)$row['id'],
            "name" => $row['name'],
            "muscle_group" => $row['muscle_group'],
            "description" => $row['description'],
            "difficulty_level" => $row['difficulty_level'],
            "equipment_needed" => $row['equipment_needed']
        ];
    }
    
    $conn->close();
    
    echo json_encode([
        "success" => true, 
        "data" => $data,
        "count" => count($data),
        "message" => "成功從資料庫載入 " . count($data) . " 個訓練動作"
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    // 如果資料庫查詢失敗，使用硬編碼資料作為備用方案
    $data = [
        // 休息選項
        ["id" => 0, "name" => "休息", "muscle_group" => "休息", "description" => "今日無訓練，讓身體充分恢復", "difficulty_level" => "無", "equipment_needed" => "無"],
        
        // 胸部動作 - 包含更多動作
        ["id" => 1, "name" => "伏地挺身", "muscle_group" => "胸", "description" => "經典的胸部訓練動作，可以鍛鍊胸肌、三頭肌和肩膀", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 2, "name" => "臥推", "muscle_group" => "胸", "description" => "胸部訓練的經典動作，可以負重訓練", "difficulty_level" => "中級", "equipment_needed" => "槓鈴和臥推椅"],
        ["id" => 3, "name" => "啞鈴飛鳥", "muscle_group" => "胸", "description" => "胸部訓練的孤立動作，專注於胸肌拉伸", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 4, "name" => "上斜臥推", "muscle_group" => "胸", "description" => "針對上胸部的訓練動作", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
        ["id" => 5, "name" => "下斜臥推", "muscle_group" => "胸", "description" => "針對下胸部的訓練動作", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
        ["id" => 6, "name" => "啞鈴臥推", "muscle_group" => "胸", "description" => "使用啞鈴的臥推動作，增加動作範圍", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 7, "name" => "窄握臥推", "muscle_group" => "胸", "description" => "窄握距的臥推，主要鍛鍊胸肌內側", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 8, "name" => "寬握臥推", "muscle_group" => "胸", "description" => "寬握距的臥推，主要鍛鍊胸肌外側", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 9, "name" => "啞鈴上斜飛鳥", "muscle_group" => "胸", "description" => "上斜角度的飛鳥動作，鍛鍊上胸部", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 10, "name" => "啞鈴下斜飛鳥", "muscle_group" => "胸", "description" => "下斜角度的飛鳥動作，鍛鍊下胸部", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 11, "name" => "纜繩夾胸", "muscle_group" => "胸", "description" => "使用纜繩機的夾胸動作", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 12, "name" => "纜繩上斜夾胸", "muscle_group" => "胸", "description" => "上斜角度的纜繩夾胸", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 13, "name" => "纜繩下斜夾胸", "muscle_group" => "胸", "description" => "下斜角度的纜繩夾胸", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 14, "name" => "雙槓撐體", "muscle_group" => "胸", "description" => "使用雙槓的撐體動作，鍛鍊下胸部", "difficulty_level" => "中級", "equipment_needed" => "雙槓"],
        ["id" => 15, "name" => "胸推機", "muscle_group" => "胸", "description" => "使用胸推機的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "胸推機"],
        ["id" => 16, "name" => "雙功能蝴蝶訓練器", "muscle_group" => "胸", "description" => "使用雙功能蝴蝶訓練器的胸部訓練", "difficulty_level" => "初級", "equipment_needed" => "蝴蝶訓練器"],
        ["id" => 17, "name" => "合式胸部推舉器", "muscle_group" => "胸", "description" => "使用合式胸部推舉器的訓練", "difficulty_level" => "初級", "equipment_needed" => "胸部推舉器"],
        ["id" => 18, "name" => "蝴蝶機", "muscle_group" => "胸", "description" => "使用蝴蝶機的胸部訓練", "difficulty_level" => "初級", "equipment_needed" => "蝴蝶機"],
        ["id" => 19, "name" => "坐式推胸訓練器", "muscle_group" => "胸", "description" => "使用坐式推胸訓練器的訓練", "difficulty_level" => "初級", "equipment_needed" => "推胸訓練器"],
        ["id" => 20, "name" => "分動式寬距部推舉器", "muscle_group" => "胸", "description" => "使用分動式寬距部推舉器的訓練", "difficulty_level" => "初級", "equipment_needed" => "寬距推舉器"],
        ["id" => 21, "name" => "奧林匹克下斜推舉台板", "muscle_group" => "胸", "description" => "使用奧林匹克下斜推舉台板的訓練", "difficulty_level" => "中級", "equipment_needed" => "下斜推舉台板"],
        ["id" => 22, "name" => "奧林匹克平躺台板", "muscle_group" => "胸", "description" => "使用奧林匹克平躺台板的訓練", "difficulty_level" => "中級", "equipment_needed" => "平躺台板"],
        ["id" => 23, "name" => "分動式胸部推舉器", "muscle_group" => "胸", "description" => "使用分動式胸部推舉器的訓練", "difficulty_level" => "初級", "equipment_needed" => "分動式推舉器"],
        
        // 背部動作 - 增加到15個
        ["id" => 24, "name" => "引體向上", "muscle_group" => "背", "description" => "背部訓練的黃金動作，主要鍛鍊背闊肌和肱二頭肌", "difficulty_level" => "中級", "equipment_needed" => "單槓"],
        ["id" => 25, "name" => "硬舉", "muscle_group" => "背", "description" => "全身性的複合動作，主要鍛鍊背部、臀部和腿部", "difficulty_level" => "高級", "equipment_needed" => "槓鈴"],
        ["id" => 26, "name" => "划船", "muscle_group" => "背", "description" => "背部訓練動作，鍛鍊背闊肌和菱形肌", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
        ["id" => 27, "name" => "反向划船", "muscle_group" => "背", "description" => "背部訓練動作，適合初學者", "difficulty_level" => "初級", "equipment_needed" => "槓鈴或TRX"],
        ["id" => 28, "name" => "單臂划船", "muscle_group" => "背", "description" => "單側背部訓練動作，改善肌肉不平衡", "difficulty_level" => "中級", "equipment_needed" => "啞鈴"],
        ["id" => 29, "name" => "纜繩划船", "muscle_group" => "背", "description" => "使用纜繩機的划船動作", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 30, "name" => "直臂下拉", "muscle_group" => "背", "description" => "背部訓練動作，鍛鍊背闊肌", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 31, "name" => "高位下拉", "muscle_group" => "背", "description" => "背部訓練動作，類似引體向上", "difficulty_level" => "初級", "equipment_needed" => "高位下拉機"],
        ["id" => 32, "name" => "T槓划船", "muscle_group" => "背", "description" => "使用T槓的划船動作", "difficulty_level" => "中級", "equipment_needed" => "T槓"],
        ["id" => 33, "name" => "槓鈴划船", "muscle_group" => "背", "description" => "使用槓鈴的划船動作", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 34, "name" => "啞鈴划船", "muscle_group" => "背", "description" => "使用啞鈴的划船動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 35, "name" => "坐姿划船", "muscle_group" => "背", "description" => "坐姿的划船動作", "difficulty_level" => "初級", "equipment_needed" => "划船機"],
        ["id" => 36, "name" => "寬握引體向上", "muscle_group" => "背", "description" => "寬握距的引體向上，主要鍛鍊背闊肌", "difficulty_level" => "中級", "equipment_needed" => "單槓"],
        ["id" => 37, "name" => "窄握引體向上", "muscle_group" => "背", "description" => "窄握距的引體向上，主要鍛鍊中下背", "difficulty_level" => "中級", "equipment_needed" => "單槓"],
        ["id" => 38, "name" => "反向飛鳥", "muscle_group" => "背", "description" => "針對後三角肌和菱形肌的訓練", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        
        // 腿部動作 - 增加到15個
        ["id" => 39, "name" => "深蹲", "muscle_group" => "腿", "description" => "腿部訓練的基礎動作，鍛鍊股四頭肌、臀肌和核心肌群", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 40, "name" => "弓箭步", "muscle_group" => "腿", "description" => "單腿訓練動作，鍛鍊股四頭肌、臀肌和平衡感", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 41, "name" => "保加利亞分腿蹲", "muscle_group" => "腿", "description" => "單腿訓練動作，鍛鍊股四頭肌和臀肌", "difficulty_level" => "中級", "equipment_needed" => "椅子或平台"],
        ["id" => 42, "name" => "腿舉", "muscle_group" => "腿", "description" => "腿部訓練的器械動作，可以負重訓練", "difficulty_level" => "初級", "equipment_needed" => "腿舉機"],
        ["id" => 43, "name" => "羅馬尼亞硬舉", "muscle_group" => "腿", "description" => "針對後腿肌群的訓練動作", "difficulty_level" => "中級", "equipment_needed" => "槓鈴或啞鈴"],
        ["id" => 44, "name" => "前蹲", "muscle_group" => "腿", "description" => "槓鈴前蹲，鍛鍊股四頭肌", "difficulty_level" => "高級", "equipment_needed" => "槓鈴"],
        ["id" => 45, "name" => "側蹲", "muscle_group" => "腿", "description" => "側向的深蹲動作，鍛鍊內收肌群", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 46, "name" => "登階", "muscle_group" => "腿", "description" => "登階動作，鍛鍊股四頭肌和臀肌", "difficulty_level" => "初級", "equipment_needed" => "階梯或平台"],
        ["id" => 47, "name" => "槓鈴深蹲", "muscle_group" => "腿", "description" => "使用槓鈴的深蹲動作", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 48, "name" => "啞鈴深蹲", "muscle_group" => "腿", "description" => "使用啞鈴的深蹲動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 49, "name" => "相撲深蹲", "muscle_group" => "腿", "description" => "寬站距的深蹲，主要鍛鍊內收肌群", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 50, "name" => "跳箱", "muscle_group" => "腿", "description" => "爆發力訓練動作，鍛鍊腿部肌群", "difficulty_level" => "中級", "equipment_needed" => "跳箱"],
        ["id" => 51, "name" => "單腿深蹲", "muscle_group" => "腿", "description" => "單腿的深蹲動作，挑戰平衡和力量", "difficulty_level" => "高級", "equipment_needed" => "無"],
        ["id" => 52, "name" => "腿推機", "muscle_group" => "腿", "description" => "使用腿推機的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "腿推機"],
        ["id" => 53, "name" => "腿彎舉", "muscle_group" => "腿", "description" => "針對後腿肌群的孤立訓練", "difficulty_level" => "初級", "equipment_needed" => "腿彎舉機"],
        
        // 手臂動作 - 增加到15個
        ["id" => 54, "name" => "二頭彎舉", "muscle_group" => "手臂", "description" => "針對肱二頭肌的孤立訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 55, "name" => "三頭撐體", "muscle_group" => "手臂", "description" => "鍛鍊肱三頭肌的經典動作", "difficulty_level" => "初級", "equipment_needed" => "椅子或板凳"],
        ["id" => 56, "name" => "槓鈴彎舉", "muscle_group" => "手臂", "description" => "二頭肌訓練的複合動作", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 57, "name" => "三頭下壓", "muscle_group" => "手臂", "description" => "三頭肌訓練的孤立動作", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 58, "name" => "錘式彎舉", "muscle_group" => "手臂", "description" => "針對前臂和二頭肌的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 59, "name" => "集中彎舉", "muscle_group" => "手臂", "description" => "坐姿的孤立二頭肌訓練", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 60, "name" => "過頭三頭伸展", "muscle_group" => "手臂", "description" => "過頭的三頭肌伸展動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 61, "name" => "纜繩彎舉", "muscle_group" => "手臂", "description" => "使用纜繩機的二頭肌訓練", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 62, "name" => "窄握臥推", "muscle_group" => "手臂", "description" => "窄握距的臥推，主要鍛鍊三頭肌", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 63, "name" => "啞鈴三頭伸展", "muscle_group" => "手臂", "description" => "使用啞鈴的三頭肌伸展", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 64, "name" => "槓鈴三頭伸展", "muscle_group" => "手臂", "description" => "使用槓鈴的三頭肌伸展", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 65, "name" => "纜繩三頭下壓", "muscle_group" => "手臂", "description" => "使用纜繩機的三頭肌下壓", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 66, "name" => "反向彎舉", "muscle_group" => "手臂", "description" => "針對前臂的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "槓鈴"],
        ["id" => 67, "name" => "前臂彎舉", "muscle_group" => "手臂", "description" => "針對前臂肌群的訓練", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 68, "name" => "三頭撐體變式", "muscle_group" => "手臂", "description" => "三頭撐體的變化動作", "difficulty_level" => "中級", "equipment_needed" => "椅子或板凳"],
        
        // 腹部動作 - 增加到15個
        ["id" => 69, "name" => "平板支撐", "muscle_group" => "腹部", "description" => "核心肌群訓練的基礎動作，鍛鍊腹肌和背部肌群", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 70, "name" => "仰臥起坐", "muscle_group" => "腹部", "description" => "傳統的腹部訓練動作，主要鍛鍊腹直肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 71, "name" => "捲腹", "muscle_group" => "腹部", "description" => "腹部訓練動作，主要鍛鍊腹直肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 72, "name" => "俄羅斯轉體", "muscle_group" => "腹部", "description" => "腹部訓練動作，鍛鍊腹斜肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 73, "name" => "側平板支撐", "muscle_group" => "腹部", "description" => "核心肌群訓練動作，鍛鍊腹斜肌", "difficulty_level" => "中級", "equipment_needed" => "無"],
        ["id" => 74, "name" => "登山者", "muscle_group" => "腹部", "description" => "全身性的有氧動作，鍛鍊核心肌群", "difficulty_level" => "中級", "equipment_needed" => "無"],
        ["id" => 75, "name" => "死蟲式", "muscle_group" => "腹部", "description" => "核心肌群訓練動作，改善穩定性", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 76, "name" => "自行車捲腹", "muscle_group" => "腹部", "description" => "腹部訓練動作，鍛鍊腹斜肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 77, "name" => "仰臥抬腿", "muscle_group" => "腹部", "description" => "針對下腹部的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 78, "name" => "側捲腹", "muscle_group" => "腹部", "description" => "針對腹斜肌的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "無"],
        ["id" => 79, "name" => "V字支撐", "muscle_group" => "腹部", "description" => "核心肌群訓練動作，鍛鍊腹直肌", "difficulty_level" => "中級", "equipment_needed" => "無"],
        ["id" => 80, "name" => "腹肌輪", "muscle_group" => "腹部", "description" => "使用腹肌輪的訓練動作", "difficulty_level" => "高級", "equipment_needed" => "腹肌輪"],
        ["id" => 81, "name" => "懸垂抬腿", "muscle_group" => "腹部", "description" => "懸垂狀態下的抬腿動作", "difficulty_level" => "中級", "equipment_needed" => "單槓"],
        ["id" => 82, "name" => "側平板轉體", "muscle_group" => "腹部", "description" => "側平板支撐的轉體變化", "difficulty_level" => "中級", "equipment_needed" => "無"],
        ["id" => 83, "name" => "腹肌撕裂者", "muscle_group" => "腹部", "description" => "高強度的腹部訓練動作組合", "difficulty_level" => "高級", "equipment_needed" => "無"],
        
        // 肩膀動作 - 增加到15個
        ["id" => 84, "name" => "肩推", "muscle_group" => "肩膀", "description" => "肩膀訓練的主要動作，鍛鍊三角肌", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
        ["id" => 85, "name" => "側平舉", "muscle_group" => "肩膀", "description" => "針對三角肌中束的孤立訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 86, "name" => "前平舉", "muscle_group" => "肩膀", "description" => "針對三角肌前束的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 87, "name" => "後平舉", "muscle_group" => "肩膀", "description" => "針對三角肌後束的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 88, "name" => "推舉", "muscle_group" => "肩膀", "description" => "肩膀訓練的複合動作", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
        ["id" => 89, "name" => "聳肩", "muscle_group" => "肩膀", "description" => "針對斜方肌的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴或槓鈴"],
        ["id" => 90, "name" => "阿諾推舉", "muscle_group" => "肩膀", "description" => "啞鈴肩推的變化動作", "difficulty_level" => "中級", "equipment_needed" => "啞鈴"],
        ["id" => 91, "name" => "纜繩側平舉", "muscle_group" => "肩膀", "description" => "使用纜繩機的側平舉", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 92, "name" => "槓鈴肩推", "muscle_group" => "肩膀", "description" => "使用槓鈴的肩推動作", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 93, "name" => "啞鈴肩推", "muscle_group" => "肩膀", "description" => "使用啞鈴的肩推動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 94, "name" => "直立划船", "muscle_group" => "肩膀", "description" => "針對三角肌前束和中束的訓練", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
        ["id" => 95, "name" => "啞鈴直立划船", "muscle_group" => "肩膀", "description" => "使用啞鈴的直立划船", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
        ["id" => 96, "name" => "纜繩前平舉", "muscle_group" => "肩膀", "description" => "使用纜繩機的前平舉", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 97, "name" => "纜繩後平舉", "muscle_group" => "肩膀", "description" => "使用纜繩機的後平舉", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
        ["id" => 98, "name" => "肩部推舉機", "muscle_group" => "肩膀", "description" => "使用肩部推舉機的訓練", "difficulty_level" => "初級", "equipment_needed" => "肩部推舉機"]
    ];
    
    echo json_encode([
        "success" => true, 
        "data" => $data,
        "count" => count($data),
        "message" => "使用備用資料載入 " . count($data) . " 個訓練動作 (資料庫連接失敗: " . $e->getMessage() . ")"
    ], JSON_UNESCAPED_UNICODE);
}
?>