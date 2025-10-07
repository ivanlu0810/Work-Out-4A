<?php
session_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// ✅ 載入 dotenv
require __DIR__ . '/vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(dirname(__DIR__));
$dotenv->load();

// 🔑 讀取設定
$host     = $_ENV["DB_HOST"];
$port     = $_ENV["DB_PORT"];
$dbname   = $_ENV["DB_NAME"];
$username = $_ENV["DB_USER"];
$password = $_ENV["DB_PASSWORD"];
$apiKey   = $_ENV["OPENAI_API_KEY"];
$projectId = $_ENV["OPENAI_PROJECT_ID"];

// 1️⃣ 連線 MySQL
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "資料庫連線失敗: " . $e->getMessage()]);
    exit;
}

// 2️⃣ 查詢最新 inbody 紀錄
$userDataText = "⚠️ 尚未查到身體數據，請先輸入健康數據。";
$row = null;
try {
    $stmt = $pdo->query("SELECT * FROM inbody_records ORDER BY `Date` DESC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($row) {
        $userDataText = "
目前您的最新身體數據如下：
- 年齡：{$row['age']}
- 身高：{$row['height-cm']} cm
- 體重：{$row['weight-kg']} kg
- 骨骼肌量：{$row['skeletal_muscle']} kg
- 體脂肪重量：{$row['body_fat']} kg
- 體脂率：{$row['fat_percentage']} %
- 基礎代謝：{$row['basal_metabolism']} kcal
- BMI：{$row['bmi']}
- 測量日期：{$row['Date']}
";
    }
} catch (Exception $e) {
    echo json_encode(["error" => "查詢 inbody 資料失敗: " . $e->getMessage()]);
    exit;
}

// 3️⃣ 讀取前端輸入
$input = json_decode(file_get_contents("php://input"), true);
$userMessage = $input["messages"][0]["content"] ?? "";
$userId = $input["user_id"] ?? "default_user";

// 4️⃣ 工具函數
function saveMessage($pdo, $userId, $role, $message)
{
    $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, role, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $role, $message]);
}

function getChatHistory($pdo, $userId, $limit = 10)
{
    $stmt = $pdo->prepare("SELECT role, message FROM chat_logs 
                           WHERE user_id = ? 
                           ORDER BY created_at ASC 
                           LIMIT ?");
    $stmt->bindValue(1, $userId);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// 5️⃣ 先撈歷史紀錄 → 判斷是不是第一次聊天
$history = getChatHistory($pdo, $userId, 10);
$isFirstChat = count($history) === 0;

// 6️⃣ 再儲存使用者輸入
saveMessage($pdo, $userId, "user", $userMessage);

// 7️⃣ system prompt
$messages = [
    [
        "role" => "system",
        "content" => "你是一位健身教練型 AI 助理。系統會自動提供使用者的最新身體數據與相關訓練動作資料（來源於資料庫）。請以這些真實數據為依據，避免額外編造。
        第一次對話時，請先用條列方式呈現身體數據（若某些欄位沒有資料，就直接省略即可）。後續回覆只需根據前面提供的數據即可。
        在回答時，請用自然、像教練對學員的口吻，專業但親切。例如：
        - 先簡短介紹該動作的主要肌群與特色
        - 再清楚列出建議的組數與次數
        - 讓使用者感覺建議是可操作、貼心的
        回覆務必使用繁體中文。"
    ]
];

// 8️⃣ 第一次 API call → 判斷目標肌群
$classificationPrompt = [
    [
        "role" => "system",
        "content" => "你是一個健身助手，請幫我分析使用者的問題，並輸出 JSON 格式：{ \"target_muscle\": \"胸部/肩部/背部/腿部/手臂/核心\" }。若判斷不出來就輸出 { \"target_muscle\": null }。"
    ],
    [
        "role" => "user",
        "content" => $userMessage
    ]
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
$data = [
    "model" => "gpt-4o-mini",
    "messages" => $classificationPrompt
];
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey",
        "OpenAI-Project: $projectId"
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_CAINFO => __DIR__ . "/../cacert.pem"
]);
$classResponse = curl_exec($ch);
curl_close($ch);

$classDecoded = json_decode($classResponse, true);
$classText = $classDecoded["choices"][0]["message"]["content"] ?? "{}";
$classJson = json_decode($classText, true);
$targetMuscle = $classJson["target_muscle"] ?? null;

// 9️⃣ 查 exercises 資料表
$exerciseText = "";
if ($targetMuscle) {
    $stmt = $pdo->prepare("SELECT * FROM exercises WHERE target_muscle LIKE ? AND user_level = ?");
    $stmt->execute(["%$targetMuscle%", "新手"]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        $exerciseText .= "以下是資料庫查到的【{$targetMuscle}】新手訓練動作：\n";
        foreach ($rows as $row) {
            $exerciseText .= "- {$row['name']}：建議次數 {$row['hypertrophy_reps_min']}–{$row['hypertrophy_reps_max']}，組數 {$row['hypertrophy_sets_min']}–{$row['hypertrophy_sets_max']}，備註：{$row['notes']}\n";
        }
    }
}

// 🔟 組合送給 AI 的 user message
$userPrompt = "";
if ($isFirstChat) {
    $userPrompt .= "以下是使用者的最新健康數據：\n" . $userDataText . "\n\n";
}
if ($exerciseText) {
    $userPrompt .= $exerciseText . "\n\n";
}
$userPrompt .= "使用者的問題：" . $userMessage;

// 加入歷史紀錄
foreach ($history as $msg) {
    $messages[] = [
        "role" => $msg["role"],
        "content" => $msg["message"]
    ];
}

// 加入這次的完整 prompt
$messages[] = [
    "role" => "user",
    "content" => $userPrompt
];

// 1️⃣1️⃣ 呼叫 OpenAI API → 最終回答
$ch = curl_init("https://api.openai.com/v1/chat/completions");
$data = [
    "model" => "gpt-4o-mini",
    "messages" => $messages
];
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        "Content-Type: application/json",
        "Authorization: Bearer $apiKey",
        "OpenAI-Project: $projectId"
    ],
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_CAINFO => __DIR__ . "/../cacert.pem"
]);
$response = curl_exec($ch);
curl_close($ch);

$decoded = json_decode($response, true);
$aiReply = $decoded["choices"][0]["message"]["content"] ?? "⚠️ AI 沒有回覆";

// 1️⃣2️⃣ 儲存 AI 回覆
saveMessage($pdo, $userId, "assistant", $aiReply);

// 1️⃣3️⃣ 回傳給前端
echo json_encode([
    "reply" => $aiReply,
    "classified" => $classJson,
    "exercises_used" => $exerciseText,
    "user_data" => $row, // 直接傳給前端用
    "stmt"=>"SELECT * FROM exercises WHERE target_muscle LIKE '%$targetMuscle%' AND user_level = '新手'",
    "exerciseText" => $exerciseText
], JSON_UNESCAPED_UNICODE);
