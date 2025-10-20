# 健習生本地測試環境設置指南

## 快速開始

### 方法一：使用 PHP 內建服務器（推薦）

1. **確保 XAMPP 已安裝並啟動 MySQL**
   - 打開 XAMPP 控制面板
   - 啟動 MySQL 服務

2. **運行啟動腳本**
   ```bash
   # 雙擊運行
   start_local.bat
   ```

3. **訪問測試頁面**
   - 打開瀏覽器訪問：http://localhost:8000/test_local.php
   - 這會顯示資料庫連接測試和快速連結

### 方法二：使用 XAMPP Apache（如果端口 80 可用）

1. **運行 XAMPP 啟動腳本**
   ```bash
   # 雙擊運行
   start_xampp.bat
   ```

2. **訪問測試頁面**
   - 打開瀏覽器訪問：http://localhost:8080/Work-Out-4A-main/dist/test_local.php

## 資料庫設置

### 確認資料庫存在
您的 `test` 資料庫應該包含以下主要表：
- `user` - 用戶表
- `inbody_records` - 健康記錄表
- `exercises` - 運動表
- `training_plans` - 訓練計畫表
- `food` - 食物表
- `meal_plans` - 餐單計畫表

### 測試用戶
如果沒有測試用戶，可以創建一個：
```sql
INSERT INTO user (user_id, username, email, password_hash, gender, age, height_cm, weight_kg) 
VALUES ('test_user_1', 'test_user', 'test@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '男性', 25, 170, 70);
-- 密碼是: password
```

## 功能測試

### 1. 資料庫連接測試
- 訪問測試頁面會自動檢查資料庫連接
- 顯示連接狀態和現有表

### 2. 用戶登入測試
- 使用測試用戶：`test_user`
- 密碼：`password`

### 3. 主要功能頁面
- **主頁**: http://localhost:8000/index.php
- **登入頁面**: http://localhost:8000/auth-login.html
- **訓練計畫**: http://localhost:8000/training-plan.html
- **營養指南**: http://localhost:8000/nutrition-guide.html

## 故障排除

### 資料庫連接失敗
1. 確認 MySQL 服務正在運行
2. 檢查端口 3306 是否可用
3. 確認 `test` 資料庫存在

### PHP 錯誤
1. 確認 PHP 在系統 PATH 中
2. 檢查 PHP 擴展是否正確安裝（mysqli, pdo_mysql）

### 端口衝突
- 如果端口 8000 被佔用，可以修改 `start_local.bat` 中的端口號
- 如果端口 80 被佔用，使用端口 8080

## 環境配置

### 自動環境檢測
系統會自動檢測是否為本地環境：
- 本地環境：使用 `localhost:3306`
- 遠端環境：使用 ngrok 配置

### 手動配置
如果需要手動配置，修改以下檔案中的資料庫設定：
- `login.php`
- `save_health_data.php`
- `get_health_stats.php`
- `test_db_connection.php`

## 開發建議

1. **使用測試頁面**：`test_local.php` 提供快速測試功能
2. **檢查日誌**：查看瀏覽器開發者工具的 Console 和 Network 標籤
3. **資料庫管理**：使用 phpMyAdmin 管理資料庫
4. **版本控制**：建議使用 Git 管理代碼變更

## 聯繫支援

如果遇到問題，請檢查：
1. XAMPP 服務狀態
2. 資料庫連接設定
3. PHP 錯誤日誌
4. 瀏覽器控制台錯誤
