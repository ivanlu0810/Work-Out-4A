<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 直接使用豐富的範例資料，包含更多胸部動作
$data = [
    // 休息選項
    ["id" => 0, "name" => "休息", "muscle_group" => "休息", "description" => "今日無訓練，讓身體充分恢復", "difficulty_level" => "無", "equipment_needed" => "無"],
    
    // 胸部動作 - 增加到15個
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
    
    // 背部動作
    ["id" => 16, "name" => "引體向上", "muscle_group" => "背", "description" => "背部訓練的黃金動作，主要鍛鍊背闊肌和肱二頭肌", "difficulty_level" => "中級", "equipment_needed" => "單槓"],
    ["id" => 17, "name" => "硬舉", "muscle_group" => "背", "description" => "全身性的複合動作，主要鍛鍊背部、臀部和腿部", "difficulty_level" => "高級", "equipment_needed" => "槓鈴"],
    ["id" => 18, "name" => "划船", "muscle_group" => "背", "description" => "背部訓練動作，鍛鍊背闊肌和菱形肌", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
    ["id" => 19, "name" => "反向划船", "muscle_group" => "背", "description" => "背部訓練動作，適合初學者", "difficulty_level" => "初級", "equipment_needed" => "槓鈴或TRX"],
    ["id" => 20, "name" => "單臂划船", "muscle_group" => "背", "description" => "單側背部訓練動作，改善肌肉不平衡", "difficulty_level" => "中級", "equipment_needed" => "啞鈴"],
    ["id" => 21, "name" => "纜繩划船", "muscle_group" => "背", "description" => "使用纜繩機的划船動作", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
    ["id" => 22, "name" => "直臂下拉", "muscle_group" => "背", "description" => "背部訓練動作，鍛鍊背闊肌", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
    ["id" => 23, "name" => "高位下拉", "muscle_group" => "背", "description" => "背部訓練動作，類似引體向上", "difficulty_level" => "初級", "equipment_needed" => "高位下拉機"],
    
    // 腿部動作
    ["id" => 24, "name" => "深蹲", "muscle_group" => "腿", "description" => "腿部訓練的基礎動作，鍛鍊股四頭肌、臀肌和核心肌群", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 25, "name" => "弓箭步", "muscle_group" => "腿", "description" => "單腿訓練動作，鍛鍊股四頭肌、臀肌和平衡感", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 26, "name" => "保加利亞分腿蹲", "muscle_group" => "腿", "description" => "單腿訓練動作，鍛鍊股四頭肌和臀肌", "difficulty_level" => "中級", "equipment_needed" => "椅子或平台"],
    ["id" => 27, "name" => "腿舉", "muscle_group" => "腿", "description" => "腿部訓練的器械動作，可以負重訓練", "difficulty_level" => "初級", "equipment_needed" => "腿舉機"],
    ["id" => 28, "name" => "羅馬尼亞硬舉", "muscle_group" => "腿", "description" => "針對後腿肌群的訓練動作", "difficulty_level" => "中級", "equipment_needed" => "槓鈴或啞鈴"],
    ["id" => 29, "name" => "前蹲", "muscle_group" => "腿", "description" => "槓鈴前蹲，鍛鍊股四頭肌", "difficulty_level" => "高級", "equipment_needed" => "槓鈴"],
    ["id" => 30, "name" => "側蹲", "muscle_group" => "腿", "description" => "側向的深蹲動作，鍛鍊內收肌群", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 31, "name" => "登階", "muscle_group" => "腿", "description" => "登階動作，鍛鍊股四頭肌和臀肌", "difficulty_level" => "初級", "equipment_needed" => "階梯或平台"],
    
    // 手臂動作
    ["id" => 32, "name" => "二頭彎舉", "muscle_group" => "手臂", "description" => "針對肱二頭肌的孤立訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
    ["id" => 33, "name" => "三頭撐體", "muscle_group" => "手臂", "description" => "鍛鍊肱三頭肌的經典動作", "difficulty_level" => "初級", "equipment_needed" => "椅子或板凳"],
    ["id" => 34, "name" => "槓鈴彎舉", "muscle_group" => "手臂", "description" => "二頭肌訓練的複合動作", "difficulty_level" => "中級", "equipment_needed" => "槓鈴"],
    ["id" => 35, "name" => "三頭下壓", "muscle_group" => "手臂", "description" => "三頭肌訓練的孤立動作", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
    ["id" => 36, "name" => "錘式彎舉", "muscle_group" => "手臂", "description" => "針對前臂和二頭肌的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
    ["id" => 37, "name" => "集中彎舉", "muscle_group" => "手臂", "description" => "坐姿的孤立二頭肌訓練", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
    ["id" => 38, "name" => "過頭三頭伸展", "muscle_group" => "手臂", "description" => "過頭的三頭肌伸展動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
    ["id" => 39, "name" => "纜繩彎舉", "muscle_group" => "手臂", "description" => "使用纜繩機的二頭肌訓練", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"],
    
    // 腹部動作
    ["id" => 40, "name" => "平板支撐", "muscle_group" => "腹部", "description" => "核心肌群訓練的基礎動作，鍛鍊腹肌和背部肌群", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 41, "name" => "仰臥起坐", "muscle_group" => "腹部", "description" => "傳統的腹部訓練動作，主要鍛鍊腹直肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 42, "name" => "捲腹", "muscle_group" => "腹部", "description" => "腹部訓練動作，主要鍛鍊腹直肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 43, "name" => "俄羅斯轉體", "muscle_group" => "腹部", "description" => "腹部訓練動作，鍛鍊腹斜肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 44, "name" => "側平板支撐", "muscle_group" => "腹部", "description" => "核心肌群訓練動作，鍛鍊腹斜肌", "difficulty_level" => "中級", "equipment_needed" => "無"],
    ["id" => 45, "name" => "登山者", "muscle_group" => "腹部", "description" => "全身性的有氧動作，鍛鍊核心肌群", "difficulty_level" => "中級", "equipment_needed" => "無"],
    ["id" => 46, "name" => "死蟲式", "muscle_group" => "腹部", "description" => "核心肌群訓練動作，改善穩定性", "difficulty_level" => "初級", "equipment_needed" => "無"],
    ["id" => 47, "name" => "自行車捲腹", "muscle_group" => "腹部", "description" => "腹部訓練動作，鍛鍊腹斜肌", "difficulty_level" => "初級", "equipment_needed" => "無"],
    
    // 肩膀動作
    ["id" => 48, "name" => "肩推", "muscle_group" => "肩膀", "description" => "肩膀訓練的主要動作，鍛鍊三角肌", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
    ["id" => 49, "name" => "側平舉", "muscle_group" => "肩膀", "description" => "針對三角肌中束的孤立訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
    ["id" => 50, "name" => "前平舉", "muscle_group" => "肩膀", "description" => "針對三角肌前束的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
    ["id" => 51, "name" => "後平舉", "muscle_group" => "肩膀", "description" => "針對三角肌後束的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴"],
    ["id" => 52, "name" => "推舉", "muscle_group" => "肩膀", "description" => "肩膀訓練的複合動作", "difficulty_level" => "中級", "equipment_needed" => "啞鈴或槓鈴"],
    ["id" => 53, "name" => "聳肩", "muscle_group" => "肩膀", "description" => "針對斜方肌的訓練動作", "difficulty_level" => "初級", "equipment_needed" => "啞鈴或槓鈴"],
    ["id" => 54, "name" => "阿諾推舉", "muscle_group" => "肩膀", "description" => "啞鈴肩推的變化動作", "difficulty_level" => "中級", "equipment_needed" => "啞鈴"],
    ["id" => 55, "name" => "纜繩側平舉", "muscle_group" => "肩膀", "description" => "使用纜繩機的側平舉", "difficulty_level" => "初級", "equipment_needed" => "纜繩機"]
];

echo json_encode([
    "success" => true, 
    "data" => $data,
    "count" => count($data),
    "message" => "成功載入 " . count($data) . " 個訓練動作"
], JSON_UNESCAPED_UNICODE);
?>