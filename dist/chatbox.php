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
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
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

/**
 * 依名稱判斷「動作型態」與「去重鍵」
 */
function classifyExerciseType($name) {
    $n = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);

    // 肩/上肢常見
    if (preg_match('/(肩推|推舉|shoulder\s*press|press)/u', $n))            return ['type' => 'compound', 'key' => 'shoulder_press'];
    if (preg_match('/(側平舉|側肩上舉|lateral\s*raise)/u', $n))              return ['type' => 'isolation','key' => 'lateral_raise'];
    if (preg_match('/(前平舉|front\s*raise)/u', $n))                           return ['type' => 'isolation','key' => 'front_raise'];
    if (preg_match('/(反向飛鳥|後束飛鳥|rear\s*delt|reverse\s*fly)/u', $n))    return ['type' => 'isolation','key' => 'rear_delt_fly'];

    // 下肢常見
    if (preg_match('/(深蹲|squat)/u', $n))                                     return ['type' => 'compound', 'key' => 'squat'];
    if (preg_match('/(腿推|leg\s*press)/u', $n))                               return ['type' => 'compound', 'key' => 'leg_press'];
    if (preg_match('/(弓步|弓箭步|lunge)/u', $n))                               return ['type' => 'compound', 'key' => 'lunge'];
    if (preg_match('/(臀推|hip\s*thrust)/u', $n))                               return ['type' => 'compound', 'key' => 'hip_thrust'];
    if (preg_match('/(硬舉|deadlift)/u', $n))                                  return ['type' => 'compound', 'key' => 'deadlift'];
    if (preg_match('/(腿屈伸|leg\s*extension)/u', $n))                          return ['type' => 'isolation','key' => 'leg_extension'];
    if (preg_match('/(腿後彎|腿彎舉|leg\s*curl)/u', $n))                        return ['type' => 'isolation','key' => 'leg_curl'];
    if (preg_match('/(提踵|calf\s*raise)/u', $n))                               return ['type' => 'isolation','key' => 'calf_raise'];

    // 其他（保守預設）
    return ['type' => 'isolation', 'key' => preg_replace('/[\s（）\(\)]+/u', '', $n)];
}

/**
 * 在單一「部位桶」內，根據 eachCount 做多樣化挑選
 */
function pickDiverseExercises(array $rows, int $eachCount, int $seed): array {
    $compound = [];
    $isolation = [];
    foreach ($rows as $r) {
        $cls = classifyExerciseType($r['name'] ?? '');
        $r['_type'] = $cls['type'];
        $r['_key']  = $cls['key'];
        if ($r['_type'] === 'compound') $compound[] = $r; else $isolation[] = $r;
    }

    // 穩定隨機
    $stableSort = function(array &$arr) use ($seed) {
        usort($arr, function($a, $b) use ($seed) {
            $ka = crc32(($a['name'] ?? '') . '|' . $seed) % 100000;
            $kb = crc32(($b['name'] ?? '') . '|' . $seed) % 100000;
            return $ka <=> $kb;
        });
    };
    $stableSort($compound);
    $stableSort($isolation);

    $pick = [];
    $used = [];

    // 至少 1 個複合（若有）
    foreach ($compound as $c) {
        if (!isset($used[$c['_key']])) { $pick[] = $c; $used[$c['_key']] = true; break; }
    }
    // 再補不同 key 的孤立
    foreach ($isolation as $iso) {
        if (count($pick) >= $eachCount) break;
        if (isset($used[$iso['_key']])) continue;
        $pick[] = $iso; $used[$iso['_key']] = true;
    }
    // 不足再交替補
    $i=0; $j=0;
    while (count($pick) < $eachCount && ($i<count($compound) || $j<count($isolation))) {
        if ($i<count($compound)) $pick[] = $compound[$i++];
        if (count($pick) >= $eachCount) break;
        if ($j<count($isolation)) $pick[] = $isolation[$j++];
    }
    return array_slice($pick, 0, $eachCount);
}

/** === 器材：別名、顯示名、解析、關鍵字 Map（「獨立功能」使用） === */
function getEquipmentAliases(): array {
    return [
        'smith_machine' => ['史密斯','smith','史密斯機','史密斯槓'],
        'leg_press'     => ['腿推','腿推機','leg press'],
        'cable_machine' => ['cable','拉繩','滑輪','繩索','交叉拉繩'],
        'dumbbell'      => ['啞鈴','dumbbell','db'],
        'barbell'       => ['槓鈴','barbell','bb'],
        'leg_extension' => ['腿屈伸','leg extension'],
        'leg_curl'      => ['腿後彎','腿彎舉','leg curl'],
        'lat_pulldown'  => ['下拉','滑輪下拉','lat pulldown','pulldown'],
        'seated_row'    => ['划船','坐姿划船機','seated row','row'],
        'hip_thrust'    => ['臀推','臀橋','hip thrust'],
        'calf_raise'    => ['提踵','calf raise'],
    ];
}
function equipmentDisplayName(string $key): string {
    $map = [
        'smith_machine' => '史密斯機',
        'leg_press'     => '腿推機',
        'cable_machine' => 'Cable 拉繩機',
        'dumbbell'      => '啞鈴',
        'barbell'       => '槓鈴',
        'leg_extension' => '腿屈伸機',
        'leg_curl'      => '腿後彎機',
        'lat_pulldown'  => '滑輪下拉',
        'seated_row'    => '坐姿划船機',
        'hip_thrust'    => '臀推凳/裝置',
        'calf_raise'    => '提踵機/站姿提踵',
    ];
    return $map[$key] ?? $key;
}
function normalizeEquipmentKeysFromText(string $text): array {
    $aliases = getEquipmentAliases();
    $t = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
    $found = [];
    foreach ($aliases as $key => $names) {
        foreach ($names as $n) {
            $nn = function_exists('mb_strtolower') ? mb_strtolower($n, 'UTF-8') : strtolower($n);
            if ($nn !== '' && mb_strpos($t, $nn) !== false) { $found[]=$key; break; }
        }
    }
    return array_values(array_unique($found));
}
function normalizeEquipmentKeysFromClassified($raw): array {
    if (!$raw) return [];
    $aliases = getEquipmentAliases();
    $parts = is_array($raw) ? $raw : preg_split('/[\|\.,，、\/\s]+/u', (string)$raw, -1, PREG_SPLIT_NO_EMPTY);
    $res = [];
    foreach ($parts as $p) {
        $p = trim($p);
        if ($p === '') continue;
        if (isset($aliases[$p])) { $res[] = $p; continue; }
        foreach ($aliases as $key => $names) {
            foreach ($names as $n) {
                if ($p === $n) { $res[] = $key; continue 3; }
            }
        }
    }
    return array_values(array_unique($res));
}
/** 程式內關聯：器材 → 動作關鍵字（查 exercises.name LIKE） */
function equipmentKeywordMap(): array {
    return [
        'smith_machine' => ['史密斯','smith'],
        'leg_press'     => ['腿推','leg press'],
        'cable_machine' => ['cable','拉繩','繩索','滑輪','交叉拉繩'],
        'dumbbell'      => ['啞鈴','dumbbell','db'],
        'barbell'       => ['槓鈴','barbell','bb'],
        'leg_extension' => ['腿屈伸','leg extension'],
        'leg_curl'      => ['腿後彎','腿彎舉','leg curl'],
        'lat_pulldown'  => ['下拉','lat pulldown','pulldown'],
        'seated_row'    => ['划船','row','seated row'],
        'hip_thrust'    => ['臀推','hip thrust'],
        'calf_raise'    => ['提踵','calf raise'],
    ];
}
function buildNameLikeSql(array $keywords, string $col = 'name'): array {
    $likes = []; $params = [];
    foreach ($keywords as $kw) {
        $kw = trim($kw);
        if ($kw === '') continue;
        $likes[] = "$col LIKE ?";
        $params[] = "%{$kw}%";
    }
    return [ $likes ? '(' . implode(' OR ', $likes) . ')' : '1=0', $params ];
}

// 5️⃣ 撈歷史紀錄
$history = getChatHistory($pdo, $userId, 10);
$isFirstChat = count($history) === 0;

// 6️⃣ 儲存使用者輸入
saveMessage($pdo, $userId, "user", $userMessage);

// 7️⃣ system prompt（預設正常聊天；需要課表/動作才整合資料；器材解答為獨立路徑）
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
6) 多肌群時，**請先完成同一部位的所有動作，再換下一個部位**（不可交錯）。
7) 當意圖為器材解答（intent=equipment_qa）時，輸出『器材使用說明』：
   🧰 用途與主訓肌群
   🔧 基本調整（座椅/握距/安全鎖/限位）
   🏁 起始姿勢（對齊與預張力）
   ▶️ 動作路徑與呼吸（上/下、停頓、節奏）
   ❌ 常見錯誤與修正
   ⚠️ 安全重點
   🧱 可搭配的代表動作（若有提供）"
    ]
];

// 8️⃣ 第一次 API call → 判斷意圖與目標肌群 / 器材（器材為獨立路徑）
$classificationPrompt = [
    [
        "role" => "system",
        "content" =>
"你是健身助手。請判斷使用者輸入並只輸出 JSON（不得夾雜其他文字）：
{
  \"intent\": \"chat|plan|exercise_qa|equipment_qa|other\",
  \"target_muscle\": \"胸部|肩部|背部|腿部|手臂|核心|null|多個以|分隔\",
  \"equipment\": \"若有器材則以|分隔（可用：smith_machine|leg_press|cable_machine|dumbbell|barbell|leg_extension|leg_curl|lat_pulldown|seated_row|hip_thrust|calf_raise），否則 null\"
}
規則：
- 純寒暄/閒聊/一般問答 → intent=chat
- 要求課表、安排訓練 → intent=plan（target_muscle 如可辨識）
- 詢問某部位/動作作法 → intent=exercise_qa
- 詢問器材使用方式/調整/安全 → intent=equipment_qa
- 無法判斷 → intent=other"
    ],
    [ "role" => "user", "content" => $userMessage ]
];

$ch = curl_init("https://api.openai.com/v1/chat/completions");
$data = [ "model" => "gpt-4o-mini", "messages" => $classificationPrompt ];
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
$equipRaw     = $classJson["equipment"] ?? null;

$targetMuscles = normalizeTargetMuscles($targetRaw);
$equipmentKeys = array_values(array_unique(array_merge(
    normalizeEquipmentKeysFromClassified($equipRaw),
    normalizeEquipmentKeysFromText($userMessage) // 後備解析
)));

// === 回傳欄位初始值（維持你的命名與型別） ===
$exerciseText     = "";   // plan/exercise_qa 候選清單 或 器材代表動作段落
$ssql             = "";   // plan/exercise_qa 的 SQL（debug）
$equipmentStmt    = "";   // equipment_qa 的 FAQ 查詢 SQL（debug）
$exampleStmtList  = [];   // equipment_qa 的代表動作查詢 SQL（debug）

// 9️⃣（獨立功能）equipment_qa：器材用法卡 + 代表動作（不影響部位訓練）
if ($intent === "equipment_qa") {
    if (!empty($equipmentKeys)) {
        // 讀取 FAQ
        $placeholders = implode(',', array_fill(0, count($equipmentKeys), '?'));
        $sql = "SELECT * FROM equipment_faq WHERE equipment_key IN ($placeholders) ORDER BY FIELD(equipment_key, $placeholders)";
        $params = array_merge($equipmentKeys, $equipmentKeys);
        $stmt = $pdo->prepare($sql); $stmt->execute($params);
        $equipmentStmt = preg_replace("/\s+/", " ", $sql) . ' ｜參數：[' . implode(', ', $params) . ']';
        $faqs = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $equipmentText = "";
        if ($faqs) {
            foreach ($faqs as $faq) {
                $dn = $faq['display_name'] ?? equipmentDisplayName($faq['equipment_key']);
                $equipmentText .= "你問到「{$dn}」怎麼做，以下是重點：\n";
                if (!empty($faq['primary_muscles'])) $equipmentText .= "🧰 用途與主訓肌群：{$faq['primary_muscles']}\n";
                if (!empty($faq['setup_steps']))     $equipmentText .= "🔧 基本調整：{$faq['setup_steps']}\n";
                if (!empty($faq['form_cues']))       $equipmentText .= "🏁 起始姿勢：{$faq['form_cues']}\n";
                if (!empty($faq['movement']))        $equipmentText .= "▶️ 動作路徑與呼吸：{$faq['movement']}\n";
                if (!empty($faq['common_mistakes'])) $equipmentText .= "❌ 常見錯誤與修正：{$faq['common_mistakes']}\n";
                if (!empty($faq['safety_notes']))    $equipmentText .= "⚠️ 安全重點：{$faq['safety_notes']}\n";

                // 代表動作（程式內關聯 → 查 exercises）
                $kwMap = equipmentKeywordMap();
                $kw = $kwMap[$faq['equipment_key']] ?? [];
                if (!empty($kw)) {
                    list($likeClause, $likeParams) = buildNameLikeSql($kw, 'name');
                    $exSql = "SELECT * FROM exercises WHERE {$likeClause} AND user_level = ?";
                    $exParams = array_merge($likeParams, ["新手"]);
                    $exStmt = $pdo->prepare($exSql); $exStmt->execute($exParams);
                    $exampleStmtList[] = preg_replace("/\s+/", " ", $exSql) . ' ｜參數：[' . implode(', ', $exParams) . ']';
                    $rows = $exStmt->fetchAll(PDO::FETCH_ASSOC);

                    if ($rows) {
                        // 每次穩定隨機抓 1–3 個展示
                        $seed = crc32(date('Y-m-d') . '|' . $userId . '|' . $faq['equipment_key']);
                        usort($rows, function($a, $b) use ($seed) {
                            $ka = crc32(($a['name'] ?? '') . '|' . $seed) % 100000;
                            $kb = crc32(($b['name'] ?? '') . '|' . $seed) % 100000;
                            return $ka <=> $kb;
                        });
                        $pick = array_slice($rows, 0, min(3, count($rows)));
                        $exerciseText .= "── 「{$dn}」代表動作（擇" . count($pick) . "）：\n";
                        foreach ($pick as $erow) {
                            $exerciseText .= "- {$erow['name']}（{$erow['target_muscle']}）\n";
                            $exerciseText .= "  組數：{$erow['hypertrophy_sets_min']}–{$erow['hypertrophy_sets_max']}，次數：{$erow['hypertrophy_reps_min']}–{$erow['hypertrophy_reps_max']}\n";
                            if (!empty($erow['hypertrophy_load_min_pct']) && !empty($erow['hypertrophy_load_max_pct'])) {
                                $exerciseText .= "  建議負重：{$erow['hypertrophy_load_min_pct']}–{$erow['hypertrophy_load_max_pct']}% 1RM\n";
                            }
                            if (!empty($erow['instruction_short'])) $exerciseText .= "  動作重點：{$erow['instruction_short']}\n";
                            if (!empty($erow['instruction_cues']))   $exerciseText .= "  提示：{$erow['instruction_cues']}\n";
                            if (!empty($erow['difficulty']))         $exerciseText .= "  難度等級：{$erow['difficulty']}\n";
                            if (!empty($erow['notes']))              $exerciseText .= "  備註：{$erow['notes']}\n";
                            $exerciseText .= "\n";
                        }
                    }
                }
                $equipmentText .= "\n";
            }
        } else {
            $equipmentText = "⚠️ 資料庫目前沒有對應的器材說明，想問的是哪一種器材呢？（例：史密斯、腿推機、拉繩機、啞鈴、槓鈴…）";
        }
    } else {
        $equipmentText = "想了解哪種器材的使用方法呢？可以告訴我器材名稱，例如：史密斯、腿推機、Cable 拉繩、啞鈴、槓鈴…";
    }

    // === 組合「獨立」的器材提示給模型（不帶 InBody、不帶部位候選） ===
    $userPrompt = "意圖(intent): equipment_qa\n";
    $userPrompt .= $equipmentText . "\n";
    if ($exerciseText) {
        $userPrompt .= "以下為可搭配的代表動作（資料庫）：\n" . $exerciseText . "\n";
    }
    $userPrompt .= "使用者的問題：" . $userMessage;

    foreach ($history as $msg) { $messages[] = ["role"=>$msg["role"], "content"=>$msg["message"]]; }
    $messages[] = ["role"=>"user", "content"=>$userPrompt];

    // 呼叫 OpenAI
    $ch = curl_init("https://api.openai.com/v1/chat/completions");
    $data = [ "model" => "gpt-4o-mini", "messages" => $messages ];
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
    $aiReply = $decoded["choices"][0]["message"]["content"] ?? "這是器材使用解答模式。如果能告訴我器材名稱（例：史密斯、腿推機、Cable 拉繩），我可以提供更精準的用法與安全重點。";

    // 儲存與回傳（維持你的回傳欄位）
    saveMessage($pdo, $userId, "assistant", $aiReply);

    $stmtOut = $equipmentStmt;
    if (!empty($exampleStmtList)) {
        $stmtOut .= ($stmtOut ? " || " : "") . implode(" || ", $exampleStmtList);
    }

    echo json_encode([
        "reply"          => $aiReply,
        "classified"     => $classJson ?? ["intent" => $intent, "target_muscle" => $targetRaw, "equipment" => $equipRaw],
        "exercises_used" => trim(($equipmentText ?? '') . ($exerciseText ? ("\n代表動作：\n" . $exerciseText) : "")),
        "user_data"      => $row,
        "stmt"           => $stmtOut
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// 🔟（原功能）plan / exercise_qa：查 exercises（與器材路徑完全分開）
$exerciseText = "";
$ssql = ""; // 保證存在

if (in_array($intent, ["plan", "exercise_qa"], true) && !empty($targetMuscles)) {
    $map = [
        '胸部' => ['上胸', '中胸', '下胸'],
        '肩部' => ['肩膀前束', '肩膀中束', '肩膀後束', '肩膀'],
        '背部' => ['上背', '中背', '下背'],
        '腿部' => ['股四頭肌', '股二頭肌', '小腿', '臀肌', '臀中束', '臀前束', '臀後束'],
        '手臂' => ['二頭肌', '三頭肌', '前臂'],
        '核心' => ['上腹', '下腹', '側腹', '核心'],
    ];

    $labels = [];
    foreach ($targetMuscles as $big) {
        if (isset($map[$big]) && !empty($map[$big])) $labels = array_merge($labels, $map[$big]);
        else $labels[] = $big;
    }
    $labels = array_values(array_unique(array_filter($labels)));

    if (!empty($labels)) {
        $placeholders = implode(',', array_fill(0, count($labels), '?'));
        $sql = "SELECT * FROM exercises 
                WHERE target_muscle IN ($placeholders) 
                  AND user_level = ?";
        $params = array_merge($labels, ["新手"]);
    } else {
        $likeClauses = []; $params = [];
        foreach ($targetMuscles as $k) { $likeClauses[] = "target_muscle LIKE ?"; $params[] = "%$k%"; }
        $sql = "SELECT * FROM exercises WHERE (" . implode(' OR ', $likeClauses) . ") AND user_level = ?";
        $params[] = "新手";
    }

    $stmt = $pdo->prepare($sql); $stmt->execute($params);
    $ssql = preg_replace("/\s+/", " ", $sql) . ' ｜參數：[' . implode(', ', $params) . ']';
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
        $targetCount = count($targetMuscles);
        $minMaxMap = [ 1 => [5,6], 2 => [3,4], 'default' => [2,3] ];
        list($minEach, $maxEach) = $minMaxMap[$targetCount] ?? $minMaxMap['default'];
        $seed = crc32(date('Y-m-d') . '|' . $userId . '|' . implode('|', $targetMuscles));
        $eachCount = $minEach + ($seed % ($maxEach - $minEach + 1));
        $muscleOrderText = $targetCount ? implode(' → ', $targetMuscles) : '（未指定）';

        $selectionGuide = "排課規則：
- 本次共有 {$targetCount} 個部位：{$muscleOrderText}
- **請先完成同一部位的所有動作，再進到下一個部位**（不可來回交錯）。
- 每個部位請列出 **{$eachCount} 個動作**。
- 同一部位優先包含：1 個多關節/複合動作（如推/蹲/臀推/硬舉），搭配 1–2 個孤立/機械（如側平舉、腿屈伸、腿後彎）；避免連續出現同型態（例如連續 3 個前平舉）。
- 若使用者未提供 1RM，強度使用 **RPE 6–8 或保留 2–3 下**；每個動作寫清楚 **休息秒數**。";

        // 反向映射：細分 -> 大類
        $bigOf = [];
        foreach ($map as $big => $subs) { $bigOf[$big]=$big; foreach ($subs as $sub) $bigOf[$sub]=$big; }

        // 依大部位分桶
        $buckets = [];
        foreach ($rows as $erow) {
            $big = $bigOf[$erow['target_muscle']] ?? $erow['target_muscle'];
            if (!in_array($big, $targetMuscles, true)) continue;
            $buckets[$big][] = $erow;
        }

        // 桶內穩定隨機排序
        $stableOrder = function(array &$arr) use ($seed) {
            usort($arr, function($a, $b) use ($seed) {
                $ka = crc32(($a['name'] ?? '') . '|' . $seed) % 100000;
                $kb = crc32(($b['name'] ?? '') . '|' . $seed) % 100000;
                return $ka <=> $kb;
            });
        };
        foreach ($buckets as $k => &$arr) { $stableOrder($arr); } unset($arr);

        // 每部位精選 eachCount（同型態過濾 + 至少1複合）
        $exerciseText .= "以下是資料庫查到的【" . implode('、', $targetMuscles) . "】新手訓練候選動作（已依部位分段與精選）：\n";
        foreach ($targetMuscles as $big) {
            if (empty($buckets[$big])) continue;
            $picked = pickDiverseExercises($buckets[$big], $eachCount, $seed);
            $exerciseText .= "── 「{$big}」推薦動作（擇{$eachCount}）：\n";
            foreach ($picked as $erow) {
                $exerciseText .= "- {$erow['name']}（{$erow['target_muscle']}）\n";
                $exerciseText .= "  組數：{$erow['hypertrophy_sets_min']}–{$erow['hypertrophy_sets_max']}，次數：{$erow['hypertrophy_reps_min']}–{$erow['hypertrophy_reps_max']}\n";
                if (!empty($erow['hypertrophy_load_min_pct']) && !empty($erow['hypertrophy_load_max_pct'])) {
                    $exerciseText .= "  建議負重：{$erow['hypertrophy_load_min_pct']}–{$erow['hypertrophy_load_max_pct']}% 1RM\n";
                }
                if (!empty($erow['instruction_short'])) $exerciseText .= "  動作重點：{$erow['instruction_short']}\n";
                if (!empty($erow['instruction_cues']))   $exerciseText .= "  提示：{$erow['instruction_cues']}\n";
                if (!empty($erow['difficulty']))         $exerciseText .= "  難度等級：{$erow['difficulty']}\n";
                if (!empty($erow['notes']))              $exerciseText .= "  備註：{$erow['notes']}\n";
                $exerciseText .= "\n";
            }
        }

        $GLOBALS['_selectionGuide'] = $selectionGuide;
    }
}

// 🔟 組合送給 AI 的 user message（各意圖獨立）
$userPrompt = "意圖(intent): {$intent}\n";
if ($isFirstChat && in_array($intent, ["plan","exercise_qa"], true)) {
    $userPrompt .= "以下是使用者的最新健康數據：\n" . $userDataText . "\n\n";
}
if (in_array($intent, ["plan","exercise_qa"], true) && isset($GLOBALS['_selectionGuide'])) {
    $userPrompt .= $GLOBALS['_selectionGuide'] . "\n\n";
}
if ($exerciseText && in_array($intent, ["plan","exercise_qa"], true)) {
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
// 本輪
$messages[] = [ "role" => "user", "content" => $userPrompt ];

// 1️⃣1️⃣ 呼叫 OpenAI API → 最終回答（非 equipment_qa 才會走到這裡）
$ch = curl_init("https://api.openai.com/v1/chat/completions");
$data = [ "model" => "gpt-4o-mini", "messages" => $messages ];
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

// 1️⃣3️⃣ 回傳給前端（欄位維持原樣）
echo json_encode([
    "reply"          => $aiReply,
    "classified"     => $classJson ?? ["intent" => $intent, "target_muscle" => $targetRaw, "equipment" => $equipRaw ?? null],
    "exercises_used" => $exerciseText,
    "user_data"      => $row,
    "stmt"           => $ssql
], JSON_UNESCAPED_UNICODE);
