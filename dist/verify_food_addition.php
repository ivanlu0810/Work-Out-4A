<?php
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
    
    echo "<h2>食物資料新增驗證</h2>";
    
    // 檢查總數
    $result = $conn->query("SELECT COUNT(*) as total FROM food");
    $count = $result->fetch_assoc()['total'];
    echo "<p><strong>總共 $count 筆食物資料</strong></p>";
    
    // 按類別統計
    $result = $conn->query("SELECT Category, COUNT(*) as count FROM food GROUP BY Category ORDER BY count DESC");
    echo "<h3>按類別統計：</h3><ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li><strong>{$row['Category']}</strong>: {$row['count']} 筆</li>";
    }
    echo "</ul>";
    
    // 檢查新增的食物
    echo "<h3>新增的食物預覽：</h3>";
    $result = $conn->query("SELECT Food_Name, Category, `Calories_(kcal/100g)` as Calories FROM food ORDER BY Category, Food_Name LIMIT 30");
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>食物名稱</th><th>類別</th><th>熱量(卡/100g)</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr><td>{$row['Food_Name']}</td><td>{$row['Category']}</td><td>{$row['Calories']}</td></tr>";
    }
    echo "</table>";
    
    // 檢查是否有重複
    $result = $conn->query("SELECT Food_Name, COUNT(*) as count FROM food GROUP BY Food_Name HAVING count > 1");
    if ($result->num_rows > 0) {
        echo "<h3 style='color: orange;'>⚠️ 發現重複食物：</h3><ul>";
        while ($row = $result->fetch_assoc()) {
            echo "<li>{$row['Food_Name']} (出現 {$row['count']} 次)</li>";
        }
        echo "</ul>";
    } else {
        echo "<h3 style='color: green;'>✅ 沒有發現重複食物</h3>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
    echo "<p>請確認：</p>";
    echo "<ul>";
    echo "<li>XAMPP 和 MySQL 服務正在運行</li>";
    echo "<li>資料庫 '健習生' 存在</li>";
    echo "<li>food 表已建立</li>";
    echo "<li>已執行 add_missing_foods.sql</li>";
    echo "</ul>";
}
?>
