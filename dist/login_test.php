<?php
session_start();

// 檢查是否已登入
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in']) {
    echo "<h2>✅ 登入成功！</h2>";
    echo "<p>使用者: " . ($_SESSION['username'] ?? 'Unknown') . "</p>";
    echo "<p>User ID: " . ($_SESSION['user_id'] ?? 'Unknown') . "</p>";
    echo "<p>Email: " . ($_SESSION['email'] ?? 'Unknown') . "</p>";
    echo "<p>Session ID: " . session_id() . "</p>";
    echo "<br>";
    echo "<a href='index.php'>前往首頁</a> | ";
    echo "<a href='logout.php'>登出</a> | ";
    echo "<a href='test_session.php'>檢查 Session</a>";
} else {
    echo "<h2>❌ 未登入</h2>";
    echo "<p>請先登入</p>";
    echo "<a href='auth-login.html'>前往登入頁面</a>";
}
?>
