<?php
session_start();

echo "<h2>Session 狀態檢查</h2>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "logged_in: " . ($_SESSION['logged_in'] ?? 'NOT SET') . "\n";
echo "user_id: " . ($_SESSION['user_id'] ?? 'NOT SET') . "\n";
echo "username: " . ($_SESSION['username'] ?? 'NOT SET') . "\n";
echo "email: " . ($_SESSION['email'] ?? 'NOT SET') . "\n";
echo "gender: " . ($_SESSION['gender'] ?? 'NOT SET') . "\n";
echo "role: " . ($_SESSION['role'] ?? 'NOT SET') . "\n";
echo "</pre>";

echo "<h3>所有 Session 變數:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookie 資訊:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    echo "<p style='color: green;'>✅ 使用者已登入</p>";
    echo "<a href='index.php'>前往首頁</a> | ";
    echo "<a href='logout.php'>登出</a>";
} else {
    echo "<p style='color: red;'>❌ 使用者未登入</p>";
    echo "<a href='auth-login.html'>前往登入頁面</a>";
}
?>
