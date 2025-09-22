<?php
// 恢復動作資料的腳本
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
    
    echo "<h2>恢復動作資料</h2>";
    
    // 1. 先檢查目前狀況
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>目前總動作數量: $total</h3>";
    
    // 2. 恢復基本分類（如果動作太少）
    if ($total < 20) {
        echo "<p style='color: red;'>動作數量太少，開始恢復基本分類...</p>";
        
        // 恢復胸部動作
        $sql = "UPDATE exercises SET target_muscle = '胸' WHERE name LIKE '%胸%' OR name LIKE '%臥推%' OR name LIKE '%飛鳥%' OR name LIKE '%伏地%'";
        $result = $conn->query($sql);
        echo "恢復胸部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
        
        // 恢復肩膀動作
        $sql = "UPDATE exercises SET target_muscle = '肩膀' WHERE name LIKE '%肩%' OR name LIKE '%推%' OR name LIKE '%平舉%' OR name LIKE '%飛鳥%'";
        $result = $conn->query($sql);
        echo "恢復肩膀動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
        
        // 恢復背部動作
        $sql = "UPDATE exercises SET target_muscle = '背' WHERE name LIKE '%背%' OR name LIKE '%划船%' OR name LIKE '%下拉%' OR name LIKE '%引體%'";
        $result = $conn->query($sql);
        echo "恢復背部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
        
        // 恢復腿部動作
        $sql = "UPDATE exercises SET target_muscle = '腿' WHERE name LIKE '%腿%' OR name LIKE '%深蹲%' OR name LIKE '%弓箭步%' OR name LIKE '%硬舉%'";
        $result = $conn->query($sql);
        echo "恢復腿部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
        
        // 恢復手臂動作
        $sql = "UPDATE exercises SET target_muscle = '手臂' WHERE name LIKE '%二頭%' OR name LIKE '%三頭%' OR name LIKE '%彎舉%' OR name LIKE '%撐體%'";
        $result = $conn->query($sql);
        echo "恢復手臂動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
        
        // 恢復腹部動作
        $sql = "UPDATE exercises SET target_muscle = '腹部' WHERE name LIKE '%腹%' OR name LIKE '%核心%' OR name LIKE '%平板%' OR name LIKE '%捲腹%'";
        $result = $conn->query($sql);
        echo "恢復腹部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
        
    } else {
        echo "<p style='color: green;'>動作數量正常，不需要恢復。</p>";
    }
    
    // 3. 顯示恢復後的結果
    echo "<h3>恢復後的統計:</h3>";
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
    
    // 4. 檢查總數
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>恢復後總動作數量: $total</h3>";
    
    $conn->close();
    
    echo "<h3>恢復完成！</h3>";
    echo "<p>現在可以重新載入訓練計畫頁面來查看恢復的動作。</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
}
?>
