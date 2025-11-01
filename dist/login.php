<?php
session_start();

// 根據環境選擇資料庫配置
$isLocal = !isset($_SERVER['HTTP_HOST']) || 
           strpos($_SERVER['HTTP_HOST'], 'ngrok') === false ||
           strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
           strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false;

if ($isLocal) {
    // 本地 XAMPP 配置
    $host = '1.tcp.jp.ngrok.io';
    $db   = 'test';
    $user = 'root';
    $pass = '';
    $port = 20959;
} else {
    // 遠端 ngrok 配置
    $host = '1.tcp.jp.ngrok.io';
    $db   = 'test';
    $user = 'root';
    $pass = '';
    $port = 20959;
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';

$conn = new mysqli($host, $user, $pass, $db, $port);
if ($conn->connect_error) {
    die('連線失敗: ' . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

// 查詢用戶信息，將 id 改為 user_id
$stmt = $conn->prepare("SELECT user_id, username, email, gender, role FROM user WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$stmt->store_result();

if ($stmt->num_rows === 0) {
    // 沒有這個使用者
    echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>健習生｜登入</title>
    <link rel='icon' href='images/fitness.png' type='image/png'>
    <link href='https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='assets/css/bootstrap.css'>
    <link rel='stylesheet' href='assets/vendors/bootstrap-icons/bootstrap-icons.css'>
    <link rel='stylesheet' href='assets/css/app.css'>
</head>
<body>
    <div class='modal fade show' id='errorModal' tabindex='-1' style='display: block; background-color: rgba(0,0,0,0.5);'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title'>提示！</h5>
                </div>
                <div class='modal-body'>
                    <p>尚未註冊</p>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-primary' onclick='window.location.href=\"auth-login.html\"'>確定</button>
                </div>
            </div>
        </div>
    </div>
    <script src='assets/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
    $stmt->close();
    $conn->close();
    exit;
}

$stmt->bind_result($user_id, $db_username, $email, $gender, $role);
$stmt->fetch();

// 重新查詢密碼進行驗證
$stmt2= $conn->prepare("SELECT password_hash FROM user WHERE username = ?");
$stmt2->bind_param("s", $username);
$stmt2->execute();
$stmt2->bind_result($password_hash);
$stmt2->fetch();

if (password_verify($password, $password_hash)) {
    // 登入成功，儲存用戶信息到 session
    $_SESSION['user_id'] = $user_id;
    $_SESSION['username'] = $db_username;
    $_SESSION['email'] = $email;
    $_SESSION['gender'] = $gender;
    $_SESSION['role'] = $role;
    $_SESSION['logged_in'] = true;
    
    echo "<script>window.location.href='index.html?login=success';</script>";
} else {
    // 密碼錯誤
    echo "<!DOCTYPE html>
<html lang='zh-Hant'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>健習生｜登入</title>
    <link rel='icon' href='images/fitness.png' type='image/png'>
    <link href='https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap' rel='stylesheet'>
    <link rel='stylesheet' href='assets/css/bootstrap.css'>
    <link rel='stylesheet' href='assets/vendors/bootstrap-icons/bootstrap-icons.css'>
    <link rel='stylesheet' href='assets/css/app.css'>
</head>
<body>
    <div class='modal fade show' id='errorModal' tabindex='-1' style='display: block; background-color: rgba(0,0,0,0.5);'>
        <div class='modal-dialog'>
            <div class='modal-content'>
                <div class='modal-header'>
                    <h5 class='modal-title'>提示！</h5>
                </div>
                <div class='modal-body'>
                    <p>密碼錯誤</p>
                </div>
                <div class='modal-footer'>
                    <button type='button' class='btn btn-primary' onclick='window.location.href=\"auth-login.html\"'>確定</button>
                </div>
            </div>
        </div>
    </div>
    <script src='assets/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
}

$stmt->close();
$stmt2->close();
$conn->close();
?>
