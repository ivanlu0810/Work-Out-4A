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
$host      = $_ENV["DB_HOST"] ?? "localhost";
$port      = $_ENV["DB_PORT"] ?? "3306";
$dbname    = $_ENV["DB_NAME"] ?? "";
$username  = $_ENV["DB_USER"] ?? "";
$password  = $_ENV["DB_PASSWORD"] ?? "";
$apiKey    = $_ENV["OPENAI_API_KEY"] ?? "";
$projectId = $_ENV["OPENAI_PROJECT_ID"] ?? "";

// 1️⃣ 連線 MySQL
try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "資料庫連線失敗: " . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    exit;
}

// 2️⃣ 查詢最新 inbody 紀錄（先準備字串；只有在需要課表/動作時才會放進提示）
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
function saveMessage($pdo, $userId, $role, $message) {
    $stmt = $pdo->prepare("INSERT INTO chat_logs (user_id, role, message) VALUES (?, ?, ?)");
    $stmt->execute([$userId, $role, $message]);
}

function getChatHistory($pdo, $userId, $limit = 10) {
    $stmt = $pdo->prepare("SELECT role, message FROM chat_logs 
                           WHERE user_id = ? 
                           ORDER BY created_at ASC 
                           LIMIT ?");
    $stmt->bindValue(1, $userId);
    $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * 將 target_muscle 轉成「標準肌群陣列」
 * - 支援字串或陣列輸入
 * - 分隔符支援：| 、 逗號 , ， 斜線 / 空白 以及「和」「及」
 * - 別名：肩膀→肩部、腹部→核心
 * - 僅保留允許的大類：胸部/肩部/背部/腿部/手臂/核心
 */
function normalizeTargetMuscles($raw) {
    $allow = ['胸部','肩部','背部','腿部','手臂','核心'];
    $alias = ['肩膀' => '肩部', '腹部' => '核心'];

    if (is_array($raw)) {
        $candidates = $raw;
    } else {
        $s = (string)$raw;
        $s = str_replace(['和','及'], '|', $s);
        $parts = preg_split('/[\|\.,，、\/\s]+/u', $s, -1, PREG_SPLIT_NO_EMPTY);
        $candidates = $parts ?: [];
    }

    $res = [];
    foreach ($candidates as $m) {
        $m = trim($m);
        if ($m === '') continue;
        $m = $alias[$m] ?? $m;
        if (in_array($m, $allow, true)) $res[] = $m;
    }
    return array_values(array_unique($res));
}

// 5️⃣ 撈歷史紀錄
$history = getChatHistory($pdo, $userId, 10);
$isFirstChat = count($history) === 0;

// 6️⃣ 儲存使用者輸入
saveMessage($pdo, $userId, "user", $userMessage);

// 7️⃣ system prompt（預設正常聊天；需要課表/動作才整合資料）
$messages = [
    [
        "role" => "system",
        "content" =>
"你是一位健身教練型 AI 助理。請以自然、像教練對學員的口吻，專業但親切；務必使用繁體中文。
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
5) 內容請避免編造，盡量以提供的真實數據與資料庫資訊為依據。
6) 多肌群時，**請先完成同一部位的所有動作，再換下一個部位**（不可交錯）。"
    ]
];

// 8️⃣ 第一次 API call → 判斷意圖與目標肌群
$classificationPrompt = [
    [
        "role" => "system",
        "content" =>
"你是健身助手。請判斷使用者輸入並只輸出 JSON：
{ \"intent\": \"chat|plan|exercise_qa|other\",
  \"target_muscle\": \"胸部|肩部|背部|腿部|手臂|核心|null|多個以|分隔\" }
規則：
- 純寒暄/閒聊/一般問答 → intent=chat, target_muscle=null
- 要求課表、安排訓練、今天練什麼 → intent=plan
- 詢問某個動作/某部位訓練作法 → intent=exercise_qa（可為多肌群，以 | 分隔）
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
$intentRaw    = $classJson["intent"] ?? "chat";
$intent       = is_string($intentRaw) ? $intentRaw : "chat";
$targetRaw    = $classJson["target_muscle"] ?? null;

// 將 target_muscle 轉成「標準肌群陣列」
$targetMuscles = normalizeTargetMuscles($targetRaw);

// 9️⃣ 查 exercises 資料表（需要課表/動作且至少一個肌群時才查）
$exerciseText = "";
$ssql = ""; // 保證一定有值（聊天或無肌群時為空字串）

if (in_array($intent, ["plan", "exercise_qa"], true) && !empty($targetMuscles)) {
    // 大類→細分定義
    $map = [
        '胸部' => ['上胸', '中胸', '下胸'],
        '肩部' => ['肩膀前束', '肩膀中束', '肩膀後束', '肩膀'],
        '背部' => ['上背', '中背', '下背'],
        '腿部' => ['股四頭肌', '股二頭肌', '小腿', '臀肌', '臀中束', '臀前束', '臀後束'],
        '手臂' => ['二頭肌', '三頭肌', '前臂'],
        '核心' => ['上腹', '下腹', '側腹', '核心'],
    ];

    // 蒐集所有細分標籤（若大類沒細分，留大類當LIKE保底）
    $labels = [];
    foreach ($targetMuscles as $big) {
        if (isset($map[$big]) && !empty($map[$big])) {
            $labels = array_merge($labels, $map[$big]);
        } else {
            $labels[] = $big;
        }
    }
    $labels = array_values(array_unique(array_filter($labels)));

    if (!empty($labels)) {
        $placeholders = implode(',', array_fill(0, count($labels), '?'));
        $sql = "SELECT * FROM exercises 
                WHERE target_muscle IN ($placeholders) 
                  AND user_level = ?";
        $params = array_merge($labels, ["新手"]);
    } else {
        // 極端保底（理論上進不到這）
        $likeClauses = [];
        $params = [];
        foreach ($targetMuscles as $k) {
            $likeClauses[] = "target_muscle LIKE ?";
            $params[] = "%$k%";
        }
        $sql = "SELECT * FROM exercises WHERE (" . implode(' OR ', $likeClauses) . ") AND user_level = ?";
        $params[] = "新手";
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    // 紀錄 SQL + 參數（回傳前端觀察）
    $ssql = preg_replace("/\s+/", " ", $sql) . ' ｜參數：[' . implode(', ', $params) . ']';

    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        // A：動態排課規則（數量與順序）——供模型參考
        $targetCount = count($targetMuscles);
        $minMaxMap = [
            1 => [5, 6], // 一個部位：5–6 個
            2 => [3, 4], // 兩個部位：各 3–4 個
            'default' => [2, 3], // 三個以上：各 2–3 個
        ];
        list($minEach, $maxEach) = $minMaxMap[$targetCount] ?? $minMaxMap['default'];

        // 做一個穩定的日序隨機（同一用戶同一天固定，隔天換）
        $seed = crc32(date('Y-m-d') . '|' . $userId . '|' . implode('|', $targetMuscles));
        $eachCount = $minEach + ($seed % ($maxEach - $minEach + 1));

        $muscleOrderText = $targetCount ? implode(' → ', $targetMuscles) : '（未指定）';
        $selectionGuide = "排課規則：
- 本次共有 {$targetCount} 個部位：{$muscleOrderText}
- **請先完成同一部位的所有動作，再進到下一個部位**（不可來回交錯）。
- 每個部位請列出 **{$eachCount} 個動作**。
- 同一部位優先包含：1 個多關節/複合動作（如推/蹲/臀推/硬舉），搭配 1–2 個孤立/機械（如側平舉、腿屈伸、腿後彎）；避免連續出現同型態（例如連續 3 個前平舉）。
- 若使用者未提供 1RM，強度使用 **RPE 6–8 或保留 2–3 下**；每個動作寫清楚 **休息秒數**。";

        // B：資料候選分桶＋穩定隨機＋每部位精選 eachCount 個
        // 1) 反向映射：細分 -> 大類
        $bigOf = [];
        foreach ($map as $big => $subs) {
            $bigOf[$big] = $big;
            foreach ($subs as $sub) $bigOf[$sub] = $big;
        }

        // 2) 依大部位分桶
        $buckets = []; // e.g. $buckets['肩部'] = [row,row,...]
        foreach ($rows as $erow) {
            $big = $bigOf[$erow['target_muscle']] ?? $erow['target_muscle'];
            if (!in_array($big, $targetMuscles, true)) continue; // 僅保留本次目標大類
            $buckets[$big][] = $erow;
        }

        // 3) 穩定隨機排序
        $stableOrder = function(array &$arr) use ($seed) {
            usort($arr, function($a, $b) use ($seed) {
                $ka = crc32(($a['name'] ?? '') . '|' . $seed) % 100000;
                $kb = crc32(($b['name'] ?? '') . '|' . $seed) % 100000;
                return $ka <=> $kb;
            });
        };
        foreach ($buckets as $k => &$arr) { $stableOrder($arr); } unset($arr);

        // 4) 每個大部位只取 {$eachCount} 個，並分段輸出（避免交錯）
        $exerciseText .= "以下是資料庫查到的【" . implode('、', $targetMuscles) . "】新手訓練候選動作（已依部位分段與精選）：\n";
        foreach ($targetMuscles as $big) {
            if (empty($buckets[$big])) continue;
            $exerciseText .= "── 「{$big}」推薦動作（擇{$eachCount}）：\n";
            $pick = array_slice($buckets[$big], 0, $eachCount);
            foreach ($pick as $erow) {
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

        // 把 selectionGuide 存起來給後面做最終提示用
        $GLOBALS['_selectionGuide'] = $selectionGuide;
    }
}

// 🔟 組合送給 AI 的 user message（聊天不帶 InBody；課表/動作才帶規則與候選清單）
$userPrompt = "意圖(intent): {$intent}\n";
if ($isFirstChat && in_array($intent, ["plan","exercise_qa"], true)) {
    $userPrompt .= "以下是使用者的最新健康數據：\n" . $userDataText . "\n\n";
}
if (in_array($intent, ["plan","exercise_qa"], true) && isset($GLOBALS['_selectionGuide'])) {
    $userPrompt .= $GLOBALS['_selectionGuide'] . "\n\n"; // A：先餵規則，讓模型照規則排
}
if ($exerciseText) {
    $userPrompt .= $exerciseText . "\n\n"; // B：再餵分段精選候選
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

// 1️⃣3️⃣ 回傳給前端（聊天時 exercises_used 與 stmt 可能為空字串）
echo json_encode([
    "reply"          => $aiReply,
    "classified"     => $classJson ?? ["intent" => $intent, "target_muscle" => $targetRaw],
    "exercises_used" => $exerciseText,
    "user_data"      => $row,
    "stmt"           => $ssql
], JSON_UNESCAPED_UNICODE);
