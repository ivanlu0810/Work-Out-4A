<?php
echo "Testing MySQL connection...\n";

$mysqli = new mysqli('localhost:3307', 'root', '', 'test');

if ($mysqli->connect_error) {
    echo "Connection failed: " . $mysqli->connect_error . "\n";
} else {
    echo "Connection successful!\n";
    
    // 測試查詢
    $result = $mysqli->query("SHOW TABLES LIKE 'health_goals'");
    if ($result && $result->num_rows > 0) {
        echo "health_goals table exists!\n";
    } else {
        echo "health_goals table does not exist!\n";
    }
}

$mysqli->close();
?>
