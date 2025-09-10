<?php
echo "<h2>食物資料新增指南</h2>";

echo "<h3>📊 建議新增的食物資料</h3>";
echo "<p>為了讓飲食指南功能更完整，建議新增以下類別的食物資料：</p>";

$foodCategories = [
    '蛋白質類' => [
        'count' => 30,
        'examples' => ['雞胸肉', '雞蛋', '鮭魚', '瘦牛肉', '豆腐', '希臘優格', '乳清蛋白粉'],
        'description' => '各種肉類、蛋類、豆類、乳製品，提供完整蛋白質'
    ],
    '碳水化合物類' => [
        'count' => 25,
        'examples' => ['白米飯', '糙米飯', '燕麥片', '地瓜', '馬鈴薯', '全麥麵包', '義大利麵'],
        'description' => '穀物、根莖類、豆類，提供主要能量來源'
    ],
    '脂肪類' => [
        'count' => 15,
        'examples' => ['杏仁', '核桃', '橄欖油', '酪梨', '椰子油', '亞麻籽油'],
        'description' => '堅果、油脂、健康脂肪來源'
    ],
    '蔬菜類' => [
        'count' => 20,
        'examples' => ['花椰菜', '菠菜', '胡蘿蔔', '番茄', '青椒', '蘆筍', '高麗菜'],
        'description' => '各種葉菜、根莖蔬菜，提供維生素和礦物質'
    ],
    '水果類' => [
        'count' => 20,
        'examples' => ['香蕉', '蘋果', '橘子', '草莓', '藍莓', '奇異果', '芒果'],
        'description' => '常見水果，提供維生素C和天然糖分'
    ],
    '點心類' => [
        'count' => 25,
        'examples' => ['希臘優格', '燕麥餅乾', '黑巧克力', '堅果混合', '鳳梨酥', '馬卡龍'],
        'description' => '健康點心、傳統點心、西式點心'
    ],
    '飲品類' => [
        'count' => 15,
        'examples' => ['綠茶', '咖啡', '豆漿', '杏仁奶', '蜂蜜檸檬', '椰子水'],
        'description' => '茶類、咖啡、植物奶、天然飲品'
    ],
    '調味料類' => [
        'count' => 10,
        'examples' => ['醬油', '醋', '蜂蜜', '黑糖', '蒜頭', '薑'],
        'description' => '常用調味料，增加食物風味'
    ]
];

$totalFoods = 0;
foreach ($foodCategories as $category => $data) {
    $totalFoods += $data['count'];
    echo "<div style='border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 8px;'>";
    echo "<h4 style='color: #5A628C; margin-top: 0;'>{$category} ({$data['count']}種)</h4>";
    echo "<p><strong>描述：</strong>{$data['description']}</p>";
    echo "<p><strong>範例：</strong>" . implode('、', $data['examples']) . "</p>";
    echo "</div>";
}

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin: 20px 0;'>";
echo "<h3 style='color: #5A628C;'>📈 總計：{$totalFoods}種食物</h3>";
echo "<p>這些食物資料將大大豐富飲食指南的功能，讓用戶有更多選擇來制定個人化的飲食計畫。</p>";
echo "</div>";

echo "<h3>🔧 如何新增資料</h3>";
echo "<ol>";
echo "<li><strong>檢查資料庫連接：</strong>確保 XAMPP 和 MySQL 服務正在運行</li>";
echo "<li><strong>開啟 phpMyAdmin：</strong>訪問 <code>http://localhost/phpmyadmin</code></li>";
echo "<li><strong>選擇資料庫：</strong>選擇 '健習生' 資料庫</li>";
echo "<li><strong>執行 SQL：</strong>複製 <code>insert_food_data.sql</code> 的內容到 SQL 標籤中執行</li>";
echo "<li><strong>驗證結果：</strong>檢查 food 表是否成功新增了所有資料</li>";
echo "</ol>";

echo "<h3>📁 檔案位置</h3>";
echo "<p>SQL 檔案：<code>dist/insert_food_data.sql</code></p>";
echo "<p>管理腳本：<code>dist/manage_food_data.php</code></p>";

echo "<h3>💡 使用建議</h3>";
echo "<ul>";
echo "<li>新增資料後，飲食指南的食物搜尋功能將更加豐富</li>";
echo "<li>用戶可以根據不同類別篩選食物</li>";
echo "<li>營養計算將有更多食物選項可供參考</li>";
echo "<li>餐食建議將更加多樣化和實用</li>";
echo "</ul>";
?>
