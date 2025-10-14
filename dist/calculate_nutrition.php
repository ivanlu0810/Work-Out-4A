<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// 抑制錯誤輸出
error_reporting(0);
ini_set('display_errors', 0);

// 資料庫連接
$host = 'localhost';
$dbname = 'test';
$username = 'root';
$password = '';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    $conn->set_charset("utf8mb4");
    
    if ($conn->connect_error) {
        throw new Exception("資料庫連接失敗: " . $conn->connect_error);
    }
    
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        switch ($action) {
            case 'calculate_bmr':
                $result = calculateBMR($input);
                break;
            case 'calculate_tdee':
                $result = calculateTDEE($input);
                break;
            case 'calculate_nutrition_goals':
                $result = calculateNutritionGoals($input);
                break;
            case 'get_meal_suggestions':
                $result = getMealSuggestions($input, $conn);
                break;
            default:
                throw new Exception("未知的操作: " . $action);
        }
        
        echo json_encode($result, JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception("不支援的請求方法");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

// 計算基礎代謝率 (BMR)
function calculateBMR($data) {
    $age = (int)$data['age'];
    $gender = $data['gender'];
    $height = (float)$data['height']; // cm
    $weight = (float)$data['weight']; // kg
    
    // Mifflin-St Jeor Equation
    if ($gender === '男') {
        $bmr = 10 * $weight + 6.25 * $height - 5 * $age + 5;
    } else {
        $bmr = 10 * $weight + 6.25 * $height - 5 * $age - 161;
    }
    
    return [
        'success' => true,
        'bmr' => round($bmr, 2),
        'formula' => 'Mifflin-St Jeor Equation'
    ];
}

// 計算每日總消耗 (TDEE)
function calculateTDEE($data) {
    $bmr = (float)$data['bmr'];
    $activity_level = $data['activity_level'];
    
    $activity_multipliers = [
        '久坐' => 1.2,
        '輕度活動' => 1.375,
        '中度活動' => 1.55,
        '高度活動' => 1.725,
        '極高度活動' => 1.9
    ];
    
    $multiplier = $activity_multipliers[$activity_level] ?? 1.2;
    $tdee = $bmr * $multiplier;
    
    return [
        'success' => true,
        'tdee' => round($tdee, 2),
        'activity_multiplier' => $multiplier
    ];
}

// 計算營養目標
function calculateNutritionGoals($data) {
    $tdee = (float)$data['tdee'];
    $goal = $data['goal'];
    
    // 根據目標調整熱量
    $calorie_adjustments = [
        '減脂' => -500, // 每日減少500卡路里
        '增肌' => 300,  // 每日增加300卡路里
        '維持' => 0     // 維持現狀
    ];
    
    $adjustment = $calorie_adjustments[$goal] ?? 0;
    $target_calories = $tdee + $adjustment;
    
    // 營養素比例 (根據目標調整)
    $macro_ratios = [
        '減脂' => ['protein' => 0.30, 'carbs' => 0.40, 'fat' => 0.30],
        '增肌' => ['protein' => 0.25, 'carbs' => 0.50, 'fat' => 0.25],
        '維持' => ['protein' => 0.25, 'carbs' => 0.45, 'fat' => 0.30]
    ];
    
    $ratios = $macro_ratios[$goal] ?? $macro_ratios['維持'];
    
    // 計算各營養素克數
    $protein_grams = round(($target_calories * $ratios['protein']) / 4, 1); // 1g蛋白質 = 4卡路里
    $carb_grams = round(($target_calories * $ratios['carbs']) / 4, 1); // 1g碳水化合物 = 4卡路里
    $fat_grams = round(($target_calories * $ratios['fat']) / 9, 1); // 1g脂肪 = 9卡路里
    
    return [
        'success' => true,
        'goal' => $goal,
        'target_calories' => round($target_calories, 0),
        'calorie_adjustment' => $adjustment,
        'macros' => [
            'protein' => [
                'grams' => $protein_grams,
                'percentage' => $ratios['protein'] * 100,
                'calories' => round($protein_grams * 4, 0)
            ],
            'carbs' => [
                'grams' => $carb_grams,
                'percentage' => $ratios['carbs'] * 100,
                'calories' => round($carb_grams * 4, 0)
            ],
            'fat' => [
                'grams' => $fat_grams,
                'percentage' => $ratios['fat'] * 100,
                'calories' => round($fat_grams * 9, 0)
            ]
        ],
        'meal_distribution' => [
            'breakfast' => round($target_calories * 0.25, 0),
            'lunch' => round($target_calories * 0.35, 0),
            'dinner' => round($target_calories * 0.30, 0),
            'snack' => round($target_calories * 0.10, 0)
        ]
    ];
}

// 獲取餐食建議
function getMealSuggestions($data, $conn) {
    $goal = $data['goal'];
    $meal_type = $data['meal_type'];
    $calories = (int)$data['calories'];
    
    // 根據目標和餐型選擇食物
    $category_filters = [
        '減脂' => [
            'breakfast' => ['蛋白質', '碳水化合物'],
            'lunch' => ['蛋白質', '蔬菜', '碳水化合物'],
            'dinner' => ['蛋白質', '蔬菜'],
            'snack' => ['蛋白質', '水果']
        ],
        '增肌' => [
            'breakfast' => ['蛋白質', '碳水化合物'],
            'lunch' => ['蛋白質', '碳水化合物', '脂肪'],
            'dinner' => ['蛋白質', '碳水化合物', '蔬菜'],
            'snack' => ['蛋白質', '碳水化合物']
        ],
        '維持' => [
            'breakfast' => ['蛋白質', '碳水化合物'],
            'lunch' => ['蛋白質', '碳水化合物', '蔬菜'],
            'dinner' => ['蛋白質', '蔬菜', '碳水化合物'],
            'snack' => ['蛋白質', '水果']
        ]
    ];
    
    $categories = $category_filters[$goal][$meal_type] ?? ['蛋白質', '碳水化合物', '蔬菜'];
    $category_list = "'" . implode("','", $categories) . "'";
    
    // 查詢符合條件的食物
    $sql = "SELECT * FROM food WHERE Category IN ($category_list) ORDER BY RAND() LIMIT 10";
    $result = $conn->query($sql);
    
    $foods = [];
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $foods[] = [
                'food_id' => $row['food_id'],
                'name' => $row['Food_Name'],
                'category' => $row['Category'],
                'calories_per_100g' => $row['Calories_(kcal/100g)'],
                'protein_per_100g' => $row['Protein_(g)'],
                'carbs_per_100g' => $row['Carbohydrates_(g)'],
                'fat_per_100g' => $row['Fat_(g)'],
                'notes' => $row['Notes']
            ];
        }
    }
    
    return [
        'success' => true,
        'meal_type' => $meal_type,
        'goal' => $goal,
        'target_calories' => $calories,
        'suggested_foods' => $foods,
        'tips' => getNutritionTips($goal, $meal_type)
    ];
}

// 獲取營養建議
function getNutritionTips($goal, $meal_type) {
    $tips = [
        '減脂' => [
            'breakfast' => [
                '選擇高蛋白早餐，如雞蛋、希臘優格',
                '避免高糖穀物，選擇燕麥片',
                '搭配蔬菜增加飽足感'
            ],
            'lunch' => [
                '控制碳水化合物攝取量',
                '增加蔬菜比例',
                '選擇瘦肉蛋白質'
            ],
            'dinner' => [
                '減少碳水化合物，增加蛋白質',
                '選擇蒸煮或烤製方式',
                '避免高熱量醬料'
            ],
            'snack' => [
                '選擇堅果、希臘優格',
                '避免高糖零食',
                '控制份量'
            ]
        ],
        '增肌' => [
            'breakfast' => [
                '攝取充足蛋白質和碳水化合物',
                '可選擇全麥麵包配雞蛋',
                '搭配水果補充維生素'
            ],
            'lunch' => [
                '均衡攝取三大營養素',
                '選擇複合碳水化合物',
                '搭配健康脂肪'
            ],
            'dinner' => [
                '訓練後補充蛋白質',
                '攝取足夠碳水化合物恢復',
                '搭配蔬菜補充纖維'
            ],
            'snack' => [
                '訓練前後補充碳水化合物',
                '選擇蛋白質飲品',
                '適量堅果補充健康脂肪'
            ]
        ]
    ];
    
    return $tips[$goal][$meal_type] ?? ['保持均衡飲食', '多喝水', '規律進食'];
}
?>
