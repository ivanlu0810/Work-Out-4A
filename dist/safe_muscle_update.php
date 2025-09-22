<?php
// 安全的肌肉群細分更新腳本（不會遺失動作）
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
    
    echo "<h2>安全更新肌肉群分類</h2>";
    
    // 1. 先檢查目前狀況
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>目前總動作數量: $total</h3>";
    
    if ($total < 10) {
        echo "<p style='color: red;'>動作數量太少，請先執行 restore_exercises.php 恢復資料！</p>";
        exit;
    }
    
    // 2. 安全的胸部動作更新
    echo "<h3>1. 更新胸部動作</h3>";
    
    // 上胸部動作（只更新明確的動作）
    $sql = "UPDATE exercises SET target_muscle = '上胸' WHERE name LIKE '%上斜%' AND target_muscle = '胸'";
    $result = $conn->query($sql);
    echo "上胸動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 中胸部動作
    $sql = "UPDATE exercises SET target_muscle = '中胸' WHERE (name LIKE '%平板%' OR name IN ('胸推機', '蝴蝶機', '伏地挺身', '史密斯機臥推', '雙功能蝴蝶訓練器')) AND target_muscle = '胸'";
    $result = $conn->query($sql);
    echo "中胸動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 下胸部動作
    $sql = "UPDATE exercises SET target_muscle = '下胸' WHERE (name LIKE '%下斜%' OR name = '雙槓撐體') AND target_muscle = '胸'";
    $result = $conn->query($sql);
    echo "下胸動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 3. 安全的肩膀動作更新
    echo "<h3>2. 更新肩膀動作</h3>";
    
    // 肩膀前束動作
    $sql = "UPDATE exercises SET target_muscle = '肩膀前束' WHERE (name LIKE '%推%' OR name LIKE '%推舉%' OR name LIKE '%前平舉%' OR name = '直立划船') AND target_muscle = '肩膀'";
    $result = $conn->query($sql);
    echo "肩膀前束動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 肩膀中束動作
    $sql = "UPDATE exercises SET target_muscle = '肩膀中束' WHERE (name LIKE '%側平舉%' OR name LIKE '%側舉%') AND target_muscle = '肩膀'";
    $result = $conn->query($sql);
    echo "肩膀中束動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 肩膀後束動作
    $sql = "UPDATE exercises SET target_muscle = '肩膀後束' WHERE (name LIKE '%後平舉%' OR name LIKE '%後舉%' OR name LIKE '%反向%' OR name = '繩索面拉') AND target_muscle = '肩膀'";
    $result = $conn->query($sql);
    echo "肩膀後束動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 4. 安全的背部動作更新
    echo "<h3>3. 更新背部動作</h3>";
    
    // 上背動作
    $sql = "UPDATE exercises SET target_muscle = '上背' WHERE (name LIKE '%下拉%' OR name LIKE '%引體%') AND target_muscle = '背'";
    $result = $conn->query($sql);
    echo "上背動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 中背動作
    $sql = "UPDATE exercises SET target_muscle = '中背' WHERE (name LIKE '%划船%' OR name LIKE '%划%') AND target_muscle = '背'";
    $result = $conn->query($sql);
    echo "中背動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 下背動作
    $sql = "UPDATE exercises SET target_muscle = '下背' WHERE (name LIKE '%硬舉%' OR name = '反向划船') AND target_muscle = '背'";
    $result = $conn->query($sql);
    echo "下背動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 5. 安全的手臂動作更新
    echo "<h3>4. 更新手臂動作</h3>";
    
    // 二頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '二頭肌' WHERE (name LIKE '%二頭%' OR name LIKE '%彎舉%') AND target_muscle = '手臂'";
    $result = $conn->query($sql);
    echo "二頭肌動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 三頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '三頭肌' WHERE (name LIKE '%三頭%' OR name LIKE '%撐體%' OR name LIKE '%下壓%' OR name LIKE '%伸展%') AND target_muscle = '手臂'";
    $result = $conn->query($sql);
    echo "三頭肌動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 6. 安全的腿部動作更新
    echo "<h3>5. 更新腿部動作</h3>";
    
    // 股四頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '股四頭肌' WHERE (name LIKE '%深蹲%' OR name LIKE '%腿推%' OR name LIKE '%登階%' OR name = '側蹲') AND target_muscle = '腿'";
    $result = $conn->query($sql);
    echo "股四頭肌動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 股二頭肌動作
    $sql = "UPDATE exercises SET target_muscle = '股二頭肌' WHERE (name LIKE '%腿彎舉%' OR name LIKE '%羅馬尼亞%') AND target_muscle = '腿'";
    $result = $conn->query($sql);
    echo "股二頭肌動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 臀肌動作
    $sql = "UPDATE exercises SET target_muscle = '臀肌' WHERE (name LIKE '%弓箭步%' OR name LIKE '%相撲%' OR name LIKE '%臀%') AND target_muscle = '腿'";
    $result = $conn->query($sql);
    echo "臀肌動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 7. 安全的腹部動作更新
    echo "<h3>6. 更新腹部動作</h3>";
    
    // 上腹動作
    $sql = "UPDATE exercises SET target_muscle = '上腹' WHERE (name LIKE '%仰臥起坐%' OR name LIKE '%捲腹%') AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "上腹動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 下腹動作
    $sql = "UPDATE exercises SET target_muscle = '下腹' WHERE (name LIKE '%抬腿%' OR name LIKE '%舉腿%' OR name LIKE '%反向%') AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "下腹動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 側腹動作
    $sql = "UPDATE exercises SET target_muscle = '側腹' WHERE (name LIKE '%轉體%' OR name LIKE '%側%') AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "側腹動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 核心動作
    $sql = "UPDATE exercises SET target_muscle = '核心' WHERE (name LIKE '%平板%' OR name LIKE '%支撐%' OR name LIKE '%死蟲%' OR name LIKE '%V字%' OR name LIKE '%登山%') AND target_muscle = '腹部'";
    $result = $conn->query($sql);
    echo "核心動作更新: " . ($result ? "成功" : "失敗") . " (影響 " . $conn->affected_rows . " 筆)<br>";
    
    // 8. 顯示更新結果
    echo "<h3>更新結果統計</h3>";
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
    
    // 9. 檢查總數
    $sql = "SELECT COUNT(*) as total FROM exercises";
    $result = $conn->query($sql);
    $total = $result->fetch_assoc()['total'];
    echo "<h3>更新後總動作數量: $total</h3>";
    
    $conn->close();
    
    echo "<h3>安全更新完成！</h3>";
    echo "<p>現在可以重新載入訓練計畫頁面來查看細分後的動作分類。</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>錯誤: " . $e->getMessage() . "</p>";
}
?>
