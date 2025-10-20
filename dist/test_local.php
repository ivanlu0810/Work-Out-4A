<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>健習生 - 本地測試頁面</title>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/bootstrap.css">
    <link rel="stylesheet" href="assets/vendors/bootstrap-icons/bootstrap-icons.css">
    <link rel="stylesheet" href="assets/css/app.css">
    <style>
        .test-card {
            margin-bottom: 20px;
        }
        .status-success {
            color: #28a745;
        }
        .status-error {
            color: #dc3545;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-12">
                <h1 class="text-center mb-4">健習生 - 本地環境測試</h1>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card test-card">
                            <div class="card-header">
                                <h5><i class="bi bi-database"></i> 資料庫連接測試</h5>
                            </div>
                            <div class="card-body">
                                <div id="db-status">
                                    <i class="bi bi-hourglass-split"></i> 測試中...
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card test-card">
                            <div class="card-header">
                                <h5><i class="bi bi-person-check"></i> 用戶登入測試</h5>
                            </div>
                            <div class="card-body">
                                <form id="loginForm">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">用戶名</label>
                                        <input type="text" class="form-control" id="username" name="username" value="test_user">
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">密碼</label>
                                        <input type="password" class="form-control" id="password" name="password" value="password">
                                    </div>
                                    <button type="submit" class="btn btn-primary">測試登入</button>
                                </form>
                                <div id="login-result" class="mt-3"></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <div class="card test-card">
                            <div class="card-header">
                                <h5><i class="bi bi-link-45deg"></i> 快速連結</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <a href="index.php" class="btn btn-outline-primary w-100 mb-2">
                                            <i class="bi bi-house"></i> 主頁
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="auth-login.html" class="btn btn-outline-secondary w-100 mb-2">
                                            <i class="bi bi-box-arrow-in-right"></i> 登入頁面
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="training-plan.html" class="btn btn-outline-success w-100 mb-2">
                                            <i class="bi bi-activity"></i> 訓練計畫
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="nutrition-guide.html" class="btn btn-outline-warning w-100 mb-2">
                                            <i class="bi bi-apple"></i> 營養指南
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // 測試資料庫連接
        function testDatabaseConnection() {
            fetch('test_db_connection.php')
                .then(response => response.json())
                .then(data => {
                    const statusDiv = document.getElementById('db-status');
                    if (data.success) {
                        statusDiv.innerHTML = '<i class="bi bi-check-circle status-success"></i> 資料庫連接成功！';
                        statusDiv.innerHTML += '<br><small class="text-muted">' + data.message + '</small>';
                    } else {
                        statusDiv.innerHTML = '<i class="bi bi-x-circle status-error"></i> 資料庫連接失敗：' + data.error;
                    }
                })
                .catch(error => {
                    document.getElementById('db-status').innerHTML = '<i class="bi bi-x-circle status-error"></i> 測試失敗：' + error.message;
                });
        }

        // 測試登入
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const resultDiv = document.getElementById('login-result');
            resultDiv.innerHTML = '<i class="bi bi-hourglass-split"></i> 測試中...';
            
            fetch('login.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text())
            .then(data => {
                if (data.includes('window.location.href')) {
                    resultDiv.innerHTML = '<i class="bi bi-check-circle status-success"></i> 登入測試成功！';
                } else if (data.includes('尚未註冊')) {
                    resultDiv.innerHTML = '<i class="bi bi-exclamation-triangle status-error"></i> 用戶不存在';
                } else if (data.includes('密碼錯誤')) {
                    resultDiv.innerHTML = '<i class="bi bi-exclamation-triangle status-error"></i> 密碼錯誤';
                } else {
                    resultDiv.innerHTML = '<i class="bi bi-x-circle status-error"></i> 登入失敗';
                }
            })
            .catch(error => {
                resultDiv.innerHTML = '<i class="bi bi-x-circle status-error"></i> 測試失敗：' + error.message;
            });
        });

        // 頁面載入時測試資料庫連接
        window.onload = function() {
            testDatabaseConnection();
        };
    </script>
</body>
</html>
