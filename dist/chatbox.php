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
$host       = $_ENV["DB_HOST"];
$port       = $_ENV["DB_PORT"];
$dbname     = $_ENV["DB_NAME"];
$username   = $_ENV["DB_USER"];
$password   = $_ENV["DB_PASSWORD"];
$apiKey     = $_ENV["OPENAI_API_KEY"];
$projectId  = $_ENV["OPENAI_PROJECT_ID"];

// 1️⃣ 連線 MySQL
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "資料庫連線失敗: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2️⃣ 查詢最新 inbody 紀錄（僅在需要課表/動作時才會用到，但先準備字串）
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
    echo json_encode(["error" => "查詢 inbody 資料失敗: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
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

// 7️⃣ system prompt（**預設正常聊天**；只有需要課表/動作才整合資料）
$messages = [
    [
        "role" => "system",
        "content" => "你是一位健身教練型 AI 助理。請以自然、像教練對學員的口吻，專業但親切，務必使用繁體中文。
行為規則：
1) 若本輪意圖為一般聊天（intent=chat）或無明確健身請求，就以正常聊天與建議回答，**不要**主動產生課表。
2) 只有當意圖為課表/動作（intent=plan 或 intent=exercise_qa）時，才根據『使用者最新身體數據』與『資料庫提供的相關訓練動作』給建議。
3) 若缺少明確肌群，請先用一句話追問：「今天想練哪個部位？胸/背/腿/肩/手臂/核心」。
4) 輸出課表時請遵守固定結構（新手友善）：
   - 🎯 今日設定（目標/時間/器材若有）
   - 🔥 暖身（2–3 分鐘）
   - 🧱 主訓（3–5 動作；每個含：組×次、休息秒數；無1RM時請用RPE 6–8或保留2–3下）
   - 🔄 替代動作（做不到或沒器材時）
   - 🧊 收操（2–3個）
   - ⚠️ 安全提醒（非醫療建議；疼痛請停止並就醫）
5) 內容請避免編造，盡量以提供的真實數據與資料庫資訊為依據。"
    ]
];

// 8️⃣ 第一次 API call → 判斷意圖與目標肌群
$classificationPrompt = [
    [
        "role" => "system",
        "content" => "你是健身助手。請判斷使用者輸入並只輸出 JSON：
{ \"intent\": \"chat|plan|exercise_qa|other\",
  \"target_muscle\": \"胸部|肩部|背部|腿部|手臂|核心|null\" }
規則：
- 純寒暄/閒聊/一般問答 → intent=chat, target_muscle=null
- 要求課表、安排訓練、今天練什麼 → intent=plan
- 詢問某個動作/某部位訓練作法 → intent=exercise_qa（若未指明肌群可為 null）
- 無法判斷 → intent=other, target_muscle=null"
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

$classDecoded = json_decode($classResponse ?? "{}", true);
$classText    = $classDecoded["choices"][0]["message"]["content"] ?? "{}";
$classJson    = json_decode($classText, true);
$intent       = $classJson["intent"] ?? "chat";
$targetMuscle = $classJson["target_muscle"] ?? null;

// 9️⃣ 查 exercises 資料表（只有在需要課表/動作且有肌群時才查）
$exerciseText = "";
$ssql = ""; // 保證一定有值，聊天或無肌群時就回空字串

if (in_array($intent, ["plan", "exercise_qa"]) && $targetMuscle) {
    // 大類→細分（解法A）
    $map = [
        '胸部' => ['上胸', '中胸', '下胸'],
        '肩部' => ['肩膀前束', '肩膀中束', '肩膀後束', '肩膀'],
        '背部' => ['上背', '中背', '下背'],
        '腿部' => ['股四頭肌', '股二頭肌', '小腿', '臀肌', '臀中束', '臀前束', '臀後束'],
        '手臂' => ['二頭肌', '三頭肌', '前臂'],
        '核心' => ['上腹', '下腹', '側腹', '核心'],
    ];
    $aliases = ['肩膀' => '肩部', '腹部' => '核心'];
    $key = $aliases[$targetMuscle] ?? $targetMuscle;

    if (isset($map[$key]) && count($map[$key]) > 0) {
        // 用 IN 精準查多個細分
        $labels = array_values(array_unique(array_filter($map[$key])));
        $placeholders = implode(',', array_fill(0, count($labels), '?'));
        $sql = "SELECT * FROM exercises 
                WHERE target_muscle IN ($placeholders) 
                  AND user_level = ?";
        $stmt = $pdo->prepare($sql);
        $params = array_merge($labels, ["新手"]);
        $stmt->execute($params);
    } else {
        // 回退到 LIKE（細分或未知值）
        $sql = "SELECT * FROM exercises WHERE target_muscle LIKE ? AND user_level = ?";
        $stmt = $pdo->prepare($sql);
        $params = ["%$targetMuscle%", "新手"];
        $stmt->execute($params);
    }

    // 記錄實際 SQL + 參數（回傳給前端觀察用）
    $ssql = preg_replace("/\s+/", " ", $sql) . ' ｜參數：[' . implode(', ', $params) . ']';

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        $exerciseText .= "以下是資料庫查到的【{$targetMuscle}】新手訓練動作：\n";
        foreach ($rows as $erow) {
            $exerciseText .= "- {$erow['name']}（{$erow['target_muscle']}）\n";
            $exerciseText .= "  組數：{$erow['hypertrophy_sets_min']}–{$erow['hypertrophy_sets_max']}，次數：{$erow['hypertrophy_reps_min']}–{$erow['hypertrophy_reps_max']}\n";

            if (!empty($erow['hypertrophy_load_min_pct']) && !empty($erow['hypertrophy_load_max_pct'])) {
                $exerciseText .= "  建議負重：{$erow['hypertrophy_load_min_pct']}–{$erow['hypertrophy_load_max_pct']}% 1RM\n";
            }

            if (!empty($erow['instruction_short'])) {
                $exerciseText .= "  動作重點：{$erow['instruction_short']}\n";
            }

            if (!empty($erow['instruction_cues'])) {
                $exerciseText .= "  提示：{$erow['instruction_cues']}\n";
            }

            if (!empty($erow['difficulty'])) {
                $exerciseText .= "  難度等級：{$erow['difficulty']}\n";
            }

            if (!empty($erow['notes'])) {
                $exerciseText .= "  備註：{$erow['notes']}\n";
            }

            $exerciseText .= "\n";
        }
    }
}

// 🔟 組合送給 AI 的 user message（聊天不帶 InBody；課表/動作才帶）
$userPrompt = "意圖(intent): {$intent}\n";
if ($isFirstChat && in_array($intent, ["plan","exercise_qa"])) {
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

$decoded = json_decode($response ?? "{}", true);
$aiReply = $decoded["choices"][0]["message"]["content"] ?? "⚠️ 目前為一般聊天模式；若需要課表或動作建議，請告訴我今天想練哪個部位（胸/背/腿/肩/手臂/核心）與可用時間/器材。";

// 1️⃣2️⃣ 儲存 AI 回覆
saveMessage($pdo, $userId, "assistant", $aiReply);

// 1️⃣3️⃣ 回傳給前端（聊天時 stmt 與 exercises_used 會是空字串）
echo json_encode([
    "reply"          => $aiReply,
    "classified"     => $classJson,
    "exercises_used" => $exerciseText,
    "user_data"      => $row,       // 直接傳給前端用（如需隱私管控可改為摘要）
    "stmt"           => $ssql
], JSON_UNESCAPED_UNICODE);
