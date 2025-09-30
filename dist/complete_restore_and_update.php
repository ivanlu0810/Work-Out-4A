<?php
// 完整恢復和更新動作資料的腳本
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
    
    echo "<h2>完整恢復和更新動作資料</h2>";
    
    // 1. 先檢查目前狀況
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>目前總動作數量: $total</h3>";
    
    // 2. 第一步：恢復所有動作到基本分類
    echo "<h3>第一步：恢復所有動作到基本分類</h3>";
    
    // 恢復胸部動作（包含所有可能的胸部動作）
    $sql = "UPDATE exercises SET target_muscle = '胸' WHERE 
            name LIKE '%胸%' OR 
            name LIKE '%臥推%' OR 
            name LIKE '%飛鳥%' OR 
            name LIKE '%伏地%' OR 
            name LIKE '%推%' OR
            name LIKE '%夾胸%' OR
            name LIKE '%蝴蝶%' OR
            name LIKE '%撐體%'";
    $result = $conn->query($sql);
    echo "恢復胸部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 恢復肩膀動作
    $sql = "UPDATE exercises SET target_muscle = '肩膀' WHERE 
            name LIKE '%肩%' OR 
            name LIKE '%推舉%' OR 
            name LIKE '%平舉%' OR 
            name LIKE '%飛鳥%' OR
            name LIKE '%划船%' OR
            name LIKE '%面拉%'";
    $result = $conn->query($sql);
    echo "恢復肩膀動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 恢復背部動作
    $sql = "UPDATE exercises SET target_muscle = '背' WHERE 
            name LIKE '%背%' OR 
            name LIKE '%划船%' OR 
            name LIKE '%下拉%' OR 
            name LIKE '%引體%' OR
            name LIKE '%硬舉%' OR
            name LIKE '%划%'";
    $result = $conn->query($sql);
    echo "恢復背部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 恢復腿部動作
    $sql = "UPDATE exercises SET target_muscle = '腿' WHERE 
            name LIKE '%腿%' OR 
            name LIKE '%深蹲%' OR 
            name LIKE '%弓箭步%' OR 
            name LIKE '%硬舉%' OR
            name LIKE '%蹲%' OR
            name LIKE '%推%' OR
            name LIKE '%彎舉%'";
    $result = $conn->query($sql);
    echo "恢復腿部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 恢復手臂動作
    $sql = "UPDATE exercises SET target_muscle = '手臂' WHERE 
            name LIKE '%二頭%' OR 
            name LIKE '%三頭%' OR 
            name LIKE '%彎舉%' OR 
            name LIKE '%撐體%' OR
            name LIKE '%下壓%' OR
            name LIKE '%伸展%'";
    $result = $conn->query($sql);
    echo "恢復手臂動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 恢復腹部動作
    $sql = "UPDATE exercises SET target_muscle = '腹部' WHERE 
            name LIKE '%腹%' OR 
            name LIKE '%核心%' OR 
            name LIKE '%平板%' OR 
            name LIKE '%捲腹%' OR
            name LIKE '%支撐%' OR
            name LIKE '%死蟲%' OR
            name LIKE '%V字%' OR
            name LIKE '%登山%'";
    $result = $conn->query($sql);
    echo "恢復腹部動作: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 3. 檢查恢復後的狀況
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>恢復後總動作數量: $total</h3>";
    
    // 4. 第二步：細分胸部動作
    echo "<h3>第二步：細分胸部動作</h3>";
    
    // 上胸部動作
    $sql = "UPDATE exercises SET target_muscle = '上胸' WHERE 
            (name LIKE '%上斜%' OR name LIKE '%上胸%') AND target_muscle = '胸'";
    $result = $conn->query($sql);
    echo "上胸動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 中胸部動作
    $sql = "UPDATE exercises SET target_muscle = '中胸' WHERE 
            (name LIKE '%平板%' OR name LIKE '%中胸%' OR 
             name IN ('胸推機', '蝴蝶機', '伏地挺身', '史密斯機臥推', '雙功能蝴蝶訓練器', '啞鈴地板臥推')) 
            AND target_muscle = '胸'";
    $result = $conn->query($sql);
    echo "中胸動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 下胸部動作
    $sql = "UPDATE exercises SET target_muscle = '下胸' WHERE 
            (name LIKE '%下斜%' OR name LIKE '%下胸%' OR name = '雙槓撐體') 
            AND target_muscle = '胸'";
    $result = $conn->query($sql);
    echo "下胸動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 5. 第三步：細分肩膀動作
    echo "<h3>第三步：細分肩膀動作</h3>";
    
    // 肩膀前束動作
    $sql = "UPDATE exercises SET target_muscle = '肩膀前束' WHERE 
            (name LIKE '%推%' OR name LIKE '%推舉%' OR name LIKE '%前平舉%' OR name = '直立划船') 
            AND target_muscle = '肩膀'";
    $result = $conn->query($sql);
    echo "肩膀前束動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 肩膀中束動作
    $sql = "UPDATE exercises SET target_muscle = '肩膀中束' WHERE 
            (name LIKE '%側平舉%' OR name LIKE '%側舉%') 
            AND target_muscle = '肩膀'";
    $result = $conn->query($sql);
    echo "肩膀中束動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 肩膀後束動作
    $sql = "UPDATE exercises SET target_muscle = '肩膀後束' WHERE 
            (name LIKE '%後平舉%' OR name LIKE '%後舉%' OR name LIKE '%反向%' OR name = '繩索面拉') 
            AND target_muscle = '肩膀'";
    $result = $conn->query($sql);
    echo "肩膀後束動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 6. 第四步：細分背部動作
    echo "<h3>第四步：細分背部動作</h3>";
    
    // 上背動作
    $sql = "UPDATE exercises SET target_muscle = '上背' WHERE 
            (name LIKE '%下拉%' OR name LIKE '%引體%') 
            AND target_muscle = '背'";
    $result = $conn->query($sql);
    echo "上背動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 中背動作
    $sql = "UPDATE exercises SET target_muscle = '中背' WHERE 
            (name LIKE '%划船%' OR name LIKE '%划%') 
            AND target_muscle = '背'";
    $result = $conn->query($sql);
    echo "中背動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 下背動作
    $sql = "UPDATE exercises SET target_muscle = '下背' WHERE 
            (name LIKE '%硬舉%' OR name = '反向划船') 
            AND target_muscle = '背'";
    $result = $conn->query($sql);
    echo "下背動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 7. 第五步：細分手臂動作
    echo "<h3>第五步：細分手臂動作</h3>";
    
    // 二頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '二頭肌' WHERE 
            (name LIKE '%二頭%' OR name LIKE '%彎舉%') 
            AND target_muscle = '手臂'";
    $result = $conn->query($sql);
    echo "二頭肌動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 三頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '三頭肌' WHERE 
            (name LIKE '%三頭%' OR name LIKE '%撐體%' OR name LIKE '%下壓%' OR name LIKE '%伸展%') 
            AND target_muscle = '手臂'";
    $result = $conn->query($sql);
    echo "三頭肌動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 8. 第六步：細分腿部動作
    echo "<h3>第六步：細分腿部動作</h3>";
    
    // 股四頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '股四頭肌' WHERE 
            (name LIKE '%深蹲%' OR name LIKE '%腿推%' OR name LIKE '%登階%' OR name = '側蹲') 
            AND target_muscle = '腿'";
    $result = $conn->query($sql);
    echo "股四頭肌動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 股二頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '股二頭肌' WHERE 
            (name LIKE '%腿彎舉%' OR name LIKE '%羅馬尼亞%') 
            AND target_muscle = '腿'";
    $result = $conn->query($sql);
    echo "股二頭肌動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 臀肌動作
    $sql = "UPDATE exercises SET target_muscle = '臀肌' WHERE 
            (name LIKE '%弓箭步%' OR name LIKE '%相撲%' OR name LIKE '%臀%') 
            AND target_muscle = '腿'";
    $result = $conn->query($sql);
    echo "臀肌動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 9. 第七步：細分腹部動作
    echo "<h3>第七步：細分腹部動作</h3>";
    
    // 上腹動作
    $sql = "UPDATE exercises SET target_muscle = '上腹' WHERE 
            (name LIKE '%仰臥起坐%' OR name LIKE '%捲腹%') 
            AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "上腹動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 下腹動作
    $sql = "UPDATE exercises SET target_muscle = '下腹' WHERE 
            (name LIKE '%抬腿%' OR name LIKE '%舉腿%' OR name LIKE '%反向%') 
            AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "下腹動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 側腹動作
    $sql = "UPDATE exercises SET target_muscle = '側腹' WHERE 
            (name LIKE '%轉體%' OR name LIKE '%側%') 
            AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "側腹動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 核心動作
    $sql = "UPDATE exercises SET target_muscle = '核心' WHERE 
            (name LIKE '%平板%' OR name LIKE '%支撐%' OR name LIKE '%死蟲%' OR name LIKE '%V字%' OR name LIKE '%登山%') 
            AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "核心動作細分: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 10. 顯示最終結果
    echo "<h3>最終結果統計</h3>";
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
    
    // 11. 檢查總數
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>最終總動作數量: $total</h3>";
    
    // 12. 檢查是否有遺漏的動作
    echo "<h3>檢查遺漏的動作</h3>";
    $sql = "SELECT id, name, target_muscle FROM exercises WHERE target_muscle IN ('胸', '肩膀', '背', '手臂', '腿', '腹部') ORDER BY target_muscle, name";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        echo "<p style='color: orange;'>以下動作仍使用基本分類，可以手動調整：</p>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>動作名稱</th><th>目前分類</th></tr>";
        while($row = $result->fetch_assoc()) {
            echo "<tr><td>" . $row['id'] . "</td><td>" . $row['name'] . "</td><td>" . $row['target_muscle'] . "</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: green;'>所有動作都已成功細分！</p>";
    }
    
    $conn->close();
    
    echo "<h3>完整恢復和更新完成！</h3>";
    echo "<p>現在可以重新載入訓練計畫頁面來查看恢復和細分後的動作分類。</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
}
?>
