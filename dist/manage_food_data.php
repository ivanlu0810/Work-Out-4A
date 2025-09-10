<?php
header('Content-Type: text/html; charset=utf-8');

$host = 'localhost';
$username = 'root';
$password = '';
$dbname = '健習生';

try {
    $conn = new mysqli($host, $username, $password, $dbname);
    if ($conn->connect_error) {
        throw new Exception('資料庫連接失敗: ' . $conn->connect_error);
    }
    
    $conn->set_charset('utf8mb4');
    
    echo "<h2>食物資料管理系統</h2>";
    
    // 檢查現有資料
    echo "<h3>現有食物資料統計</h3>";
    $result = $conn->query("SELECT COUNT(*) as total FROM food");
    $count = $result->fetch_assoc()['total'];
    echo "<p>總共 <strong>$count</strong> 筆食物資料</p>";
    
    // 按類別統計
    $result = $conn->query("SELECT Category, COUNT(*) as count FROM food GROUP BY Category ORDER BY count DESC");
    echo "<h4>按類別統計：</h4><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['Category']}: {$row['count']} 筆</li>";
    }
    echo "</ul>";
    
    // 顯示部分食物資料
    echo "<h3>部分食物資料預覽</h3>";
    $result = $conn->query("SELECT Food_Name, Category, `Calories_(kcal/100g)` as Calories FROM food ORDER BY Category, Food_Name LIMIT 20");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>食物名稱</th><th>類別</th><th>熱量(卡/100g)</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Food_Name']}</td><td>{$row['Category']}</td><td>{$row['Calories']}</td></tr>";
    }
    echo "</table>";
    
    // 檢查是否需要新增資料
    if ($count < 50) {
        echo "<h3>建議新增更多食物資料</h3>";
        echo "<p>目前食物資料較少，建議執行 insert_food_data.sql 來新增豐富的食物資料。</p>";
        echo "<p>該檔案包含：</p>";
        echo "<ul>";
        echo "<li>蛋白質類：30種（各種肉類、蛋類、豆類、乳製品）</li>";
        echo "<li>碳水化合物類：25種（穀物、根莖類、豆類）</li>";
        echo "<li>脂肪類：15種（堅果、油脂、酪梨等）</li>";
        echo "<li>蔬菜類：20種（各種葉菜、根莖蔬菜）</li>";
        echo "<li>水果類：20種（常見水果）</li>";
        echo "<li>點心類：25種（健康點心、傳統點心、西式點心）</li>";
        echo "<li>飲品類：15種（茶類、咖啡、植物奶等）</li>";
        echo "<li>調味料類：10種（常用調味料）</li>";
        echo "</ul>";
        echo "<p><strong>總計：160種食物</strong></p>";
    } else {
        echo "<h3>食物資料充足</h3>";
        echo "<p>目前已有足夠的食物資料供飲食指南使用。</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
    echo "<p>請確認：</p>";
    echo "<ul>";
    echo "<li>XAMPP 服務已啟動</li>";
    echo "<li>MySQL 服務正在運行</li>";
    echo "<li>資料庫 '健習生' 存在</li>";
    echo "<li>food 表已建立</li>";
    echo "</ul>";
}
?>

<hr>
<h3>如何新增食物資料</h3>
<ol>
    <li>確保 XAMPP 和 MySQL 服務正在運行</li>
    <li>開啟 phpMyAdmin 或 MySQL 命令列</li>
    <li>選擇 '健習生' 資料庫</li>
    <li>執行 insert_food_data.sql 檔案中的 SQL 語句</li>
    <li>重新整理此頁面查看結果</li>
</ol>

<h3>SQL 檔案位置</h3>
<p>檔案位置：<code>dist/insert_food_data.sql</code></p>
<p>您可以直接複製檔案內容到 phpMyAdmin 的 SQL 標籤中執行。</p>
