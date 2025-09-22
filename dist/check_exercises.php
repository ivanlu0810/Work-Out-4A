<?php
// 檢查資料庫中動作資料的狀況
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 資料庫連接設定
$host = '1.tcp.jp.ngrok.io';
$port = '20959';
$dbname = 'test';
$username = 'root';
$password = '';

try {
    // 連接資料庫
    $conn = new mysqli($host, $username, $password, $dbname, $port);
    
    if ($conn->connect_error) {
        throw new Exception("資料庫連接失敗: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    echo "<h2>檢查資料庫中的動作資料</h2>";
    
    // 1. 檢查總動作數量
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>總動作數量: $total</h3>";
    
    // 2. 檢查各肌群分類的動作數量
    echo "<h3>各肌群分類統計:</h3>";
    $sql = "SELECT target_muscle, COUNT(*) as count FROM exercises GROUP BY target_muscle ORDER BY count DESC";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>肌群分類</th><th>動作數量</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['target_muscle'] . "</td><td>" . $row['count'] . "</td></tr>";
        }
        echo "</table>";
    }
    
    // 3. 檢查是否有動作被錯誤分類或遺漏
    echo "<h3>檢查可能遺漏的動作:</h3>";
    $sql = "SELECT id, name, target_muscle FROM exercises WHERE target_muscle IN ('胸', '肩膀', '背', '手臂', '腿', '腹部') ORDER BY target_muscle, name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<p style='color: red;'>以下動作仍使用舊的分類，需要重新分類：</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>動作名稱</th><th>目前分類</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td><td>" . $row['target_muscle'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>所有動作都已成功細分！</p>";
    }
    
    // 4. 檢查肩膀動作
    echo "<h3>肩膀動作檢查:</h3>";
    $sql = "SELECT id, name, target_muscle FROM exercises WHERE target_muscle LIKE '%肩膀%' OR target_muscle LIKE '%束%' ORDER BY target_muscle, name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>動作名稱</th><th>分類</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td><td>" . $row['target_muscle'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>沒有找到肩膀相關動作！</p>";
    }
    
    // 5. 檢查胸部動作
    echo "<h3>胸部動作檢查:</h3>";
    $sql = "SELECT id, name, target_muscle FROM exercises WHERE target_muscle LIKE '%胸%' ORDER BY target_muscle, name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>動作名稱</th><th>分類</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td><td>" . $row['target_muscle'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>沒有找到胸部相關動作！</p>";
    }
    
    $conn->close();
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
}
?>
