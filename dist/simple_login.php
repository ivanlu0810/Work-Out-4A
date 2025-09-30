<?php
session_start();

// 簡單的登入測試
if ($_POST['username'] ?? false) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // 簡單驗證（測試用）
    if ($username === 'test' && $password === 'test') {
        $_SESSION['logged_in'] = true;
        $_SESSION['username'] = $username;
        $_SESSION['user_id'] = '1';
        $_SESSION['email'] = 'test@example.com';
        $_SESSION['gender'] = '男';
        $_SESSION['role'] = 'user';
        
        echo "<h2>登入成功！</h2>";
        echo "<p>使用者: " . $_SESSION['username'] . "</p>";
        echo "<p>Session ID: " . session_id() . "</p>";
        echo "<a href='index.php'>前往首頁</a> | ";
        echo "<a href='test_session.php'>檢查 Session</a>";
        exit;
    } else {
        echo "<h2>登入失敗</h2>";
        echo "<p>請使用 test / test 登入</p>";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>簡單登入測試</title>
</head>
<body>
    <h2>簡單登入測試</h2>
    <form method="POST">
        <p>使用者名稱: <input type="text" name="username" value="test"></p>
        <p>密碼: <input type="password" name="password" value="test"></p>
        <p><input type="submit" value="登入"></p>
    </form>
    <p><a href="test_session.php">檢查當前 Session 狀態</a></p>
</body>
</html>
