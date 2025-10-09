-- 健習生系統 - 完成狀態記錄資料庫結構 (最終修正版)
-- 用於記錄訓練動作、組別、課程和計畫的完成狀態
-- 建立時間：2024年12月
-- 最終修正版：解決重複欄位和資料表不存在問題

-- ==============================================
-- 1. 安全地修改現有資料表 - 新增完成狀態欄位
-- ==============================================

-- 修改 workout_exercises 表，新增完成狀態欄位
-- 檢查欄位是否存在，避免重複新增
SET @sql = 'ALTER TABLE workout_exercises 
ADD COLUMN is_completed TINYINT(1) DEFAULT 0 COMMENT ''是否完成 (0:未完成, 1:已完成)'',
ADD COLUMN completed_at TIMESTAMP NULL COMMENT ''完成時間'',
ADD COLUMN completion_status ENUM(''completed'', ''skipped'', ''partial'') DEFAULT ''completed'' COMMENT ''完成狀態''';
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'workout_exercises') > 0 
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'workout_exercises' AND COLUMN_NAME = 'is_completed') = 0,
    @sql, 
    'SELECT ''workout_exercises table not found or is_completed column already exists'' as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 修改 workout_sessions 表，新增完成狀態欄位
SET @sql = 'ALTER TABLE workout_sessions 
ADD COLUMN is_completed TINYINT(1) DEFAULT 0 COMMENT ''是否完成 (0:未完成, 1:已完成)'',
ADD COLUMN completed_at TIMESTAMP NULL COMMENT ''完成時間'',
ADD COLUMN completion_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT ''完成百分比''';
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'workout_sessions') > 0 
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'workout_sessions' AND COLUMN_NAME = 'is_completed') = 0,
    @sql, 
    'SELECT ''workout_sessions table not found or is_completed column already exists'' as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 修改 training_plans 表，新增完成狀態欄位
SET @sql = 'ALTER TABLE training_plans 
ADD COLUMN completion_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT ''整體完成百分比'',
ADD COLUMN last_completed_date DATE NULL COMMENT ''最後完成日期''';
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'training_plans') > 0 
    AND (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'training_plans' AND COLUMN_NAME = 'completion_percentage') = 0,
    @sql, 
    'SELECT ''training_plans table not found or completion_percentage column already exists'' as message'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ==============================================
-- 2. 新增完成狀態記錄表
-- ==============================================

-- 運動完成記錄表
CREATE TABLE IF NOT EXISTS exercise_completion_logs (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id INT(11) NOT NULL COMMENT '訓練課程ID',
    exercise_id INT(11) NOT NULL COMMENT '運動ID',
    user_id INT(11) NOT NULL COMMENT '使用者ID',
    completion_status ENUM('completed', 'skipped', 'partial') NOT NULL COMMENT '完成狀態',
    actual_sets_completed INT(11) DEFAULT 0 COMMENT '實際完成組數',
    actual_reps_completed INT(11) DEFAULT 0 COMMENT '實際完成次數',
    actual_weight_used DECIMAL(5,2) DEFAULT NULL COMMENT '實際使用重量',
    completion_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '完成時間',
    notes TEXT COMMENT '完成備註',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    PRIMARY KEY (id),
    INDEX idx_session_id (session_id),
    INDEX idx_exercise_id (exercise_id),
    INDEX idx_user_id (user_id),
    INDEX idx_completion_time (completion_time),
    INDEX idx_completion_status (completion_status)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='運動完成記錄表';

-- 訓練組別完成記錄表 (對應圖片中的三欄完成狀態)
CREATE TABLE IF NOT EXISTS workout_group_completion (
    id INT(11) NOT NULL AUTO_INCREMENT,
    session_id INT(11) NOT NULL COMMENT '訓練課程ID',
    user_id INT(11) NOT NULL COMMENT '使用者ID',
    group_name VARCHAR(100) NOT NULL COMMENT '組別名稱 (如：推日、拉日、腿日、上肢、下肢、核心)',
    group_order INT(11) DEFAULT 0 COMMENT '組別順序',
    is_completed TINYINT(1) DEFAULT 0 COMMENT '是否完成 (0:未完成, 1:已完成)',
    completed_at TIMESTAMP NULL COMMENT '完成時間',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT '完成百分比',
    total_exercises INT(11) DEFAULT 0 COMMENT '總運動數',
    completed_exercises INT(11) DEFAULT 0 COMMENT '完成運動數',
    skipped_exercises INT(11) DEFAULT 0 COMMENT '跳過運動數',
    notes TEXT COMMENT '完成備註',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    PRIMARY KEY (id),
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_group_name (group_name),
    INDEX idx_group_order (group_order),
    INDEX idx_completed_at (completed_at)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='訓練組別完成記錄表';

-- 訓練計畫完成記錄表 (按週按日記錄)
CREATE TABLE IF NOT EXISTS training_plan_completion (
    id INT(11) NOT NULL AUTO_INCREMENT,
    plan_id INT(11) NOT NULL COMMENT '訓練計畫ID',
    user_id INT(11) NOT NULL COMMENT '使用者ID',
    week_number INT(11) NOT NULL COMMENT '週數',
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL COMMENT '星期幾',
    is_completed TINYINT(1) DEFAULT 0 COMMENT '是否完成 (0:未完成, 1:已完成)',
    completed_at TIMESTAMP NULL COMMENT '完成時間',
    completion_percentage DECIMAL(5,2) DEFAULT 0.00 COMMENT '完成百分比',
    total_exercises INT(11) DEFAULT 0 COMMENT '總運動數',
    completed_exercises INT(11) DEFAULT 0 COMMENT '完成運動數',
    skipped_exercises INT(11) DEFAULT 0 COMMENT '跳過運動數',
    session_duration INT(11) DEFAULT NULL COMMENT '訓練時間(分鐘)',
    notes TEXT COMMENT '完成備註',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    PRIMARY KEY (id),
    INDEX idx_plan_id (plan_id),
    INDEX idx_user_id (user_id),
    INDEX idx_week_day (week_number, day_of_week),
    INDEX idx_completed_at (completed_at),
    INDEX idx_is_completed (is_completed)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='訓練計畫完成記錄表';

-- 使用者完成統計表 (用於顯示進度和成就)
CREATE TABLE IF NOT EXISTS user_completion_stats (
    id INT(11) NOT NULL AUTO_INCREMENT,
    user_id INT(11) NOT NULL COMMENT '使用者ID',
    stat_date DATE NOT NULL COMMENT '統計日期',
    total_sessions INT(11) DEFAULT 0 COMMENT '總訓練課程數',
    completed_sessions INT(11) DEFAULT 0 COMMENT '完成訓練課程數',
    total_exercises INT(11) DEFAULT 0 COMMENT '總運動數',
    completed_exercises INT(11) DEFAULT 0 COMMENT '完成運動數',
    total_workout_time INT(11) DEFAULT 0 COMMENT '總訓練時間(分鐘)',
    current_streak INT(11) DEFAULT 0 COMMENT '目前連續完成天數',
    longest_streak INT(11) DEFAULT 0 COMMENT '最長連續完成天數',
    weekly_completion_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT '週完成率',
    monthly_completion_rate DECIMAL(5,2) DEFAULT 0.00 COMMENT '月完成率',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    PRIMARY KEY (id),
    UNIQUE KEY unique_user_date (user_id, stat_date),
    INDEX idx_user_id (user_id),
    INDEX idx_stat_date (stat_date)
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='使用者完成統計表';

-- ==============================================
-- 3. 建立視圖 - 方便查詢完成狀態
-- ==============================================

-- 使用者完成狀態總覽視圖 (使用 IF EXISTS 檢查)
DROP VIEW IF EXISTS user_completion_overview;
CREATE VIEW user_completion_overview AS
SELECT 
    u.user_id,
    u.username,
    COUNT(DISTINCT ws.id) as total_sessions,
    COUNT(DISTINCT CASE WHEN ws.is_completed = 1 THEN ws.id END) as completed_sessions,
    COUNT(DISTINCT ecl.id) as total_exercise_logs,
    COUNT(DISTINCT CASE WHEN ecl.completion_status = 'completed' THEN ecl.id END) as completed_exercises,
    COALESCE(AVG(ws.completion_percentage), 0) as avg_session_completion,
    MAX(ws.completed_at) as last_workout_date,
    COALESCE(SUM(ws.total_duration), 0) as total_workout_time
FROM user u
LEFT JOIN workout_sessions ws ON u.user_id = ws.user_id
LEFT JOIN exercise_completion_logs ecl ON ws.id = ecl.session_id
GROUP BY u.user_id, u.username;

-- 訓練計畫完成狀態視圖
DROP VIEW IF EXISTS training_plan_completion_view;
CREATE VIEW training_plan_completion_view AS
SELECT 
    tp.id as plan_id,
    tp.user_id,
    tp.plan_name,
    tp.week_number,
    tp.week_start_date,
    COUNT(DISTINCT tpc.day_of_week) as total_days,
    COUNT(DISTINCT CASE WHEN tpc.is_completed = 1 THEN tpc.day_of_week END) as completed_days,
    COALESCE(AVG(tpc.completion_percentage), 0) as avg_completion_percentage,
    COALESCE(SUM(tpc.total_exercises), 0) as total_exercises,
    COALESCE(SUM(tpc.completed_exercises), 0) as completed_exercises,
    COALESCE(SUM(tpc.session_duration), 0) as total_duration
FROM training_plans tp
LEFT JOIN training_plan_completion tpc ON tp.id = tpc.plan_id
GROUP BY tp.id, tp.user_id, tp.plan_name, tp.week_number, tp.week_start_date;

-- ==============================================
-- 4. 插入範例資料 (可選)
-- ==============================================

-- 插入範例完成記錄
INSERT IGNORE INTO exercise_completion_logs (session_id, exercise_id, user_id, completion_status, actual_sets_completed, actual_reps_completed) VALUES
(1, 1, 1, 'completed', 3, 12),
(1, 2, 1, 'completed', 3, 10),
(1, 3, 1, 'partial', 2, 8);

-- ==============================================
-- 5. 驗證建立結果
-- ==============================================

-- 檢查所有完成狀態相關的資料表是否建立成功
SELECT 'exercise_completion_logs' as table_name, COUNT(*) as `exists` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'exercise_completion_logs'
UNION ALL
SELECT 'workout_group_completion' as table_name, COUNT(*) as `exists` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'workout_group_completion'
UNION ALL
SELECT 'training_plan_completion' as table_name, COUNT(*) as `exists` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'training_plan_completion'
UNION ALL
SELECT 'user_completion_stats' as table_name, COUNT(*) as `exists` FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_completion_stats';

-- 檢查現有資料表是否新增了完成狀態欄位
SELECT 
    TABLE_NAME,
    COLUMN_NAME,
    DATA_TYPE,
    IS_NULLABLE,
    COLUMN_DEFAULT,
    COLUMN_COMMENT
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_SCHEMA = DATABASE() 
AND TABLE_NAME IN ('workout_exercises', 'workout_sessions', 'training_plans')
AND COLUMN_NAME IN ('is_completed', 'completed_at', 'completion_percentage', 'completion_status', 'last_completed_date')
ORDER BY TABLE_NAME, COLUMN_NAME;

-- ==============================================
-- 完成狀態記錄系統建立完成
-- ==============================================

-- 使用說明：
-- 1. exercise_completion_logs: 記錄每個運動的完成狀態
-- 2. workout_group_completion: 記錄訓練組別(推日/拉日/腿日)的完成狀態
-- 3. training_plan_completion: 記錄訓練計畫的完成狀態
-- 4. user_completion_stats: 記錄使用者的完成統計資料
-- 5. 視圖提供方便的查詢介面

-- 注意事項：
-- - 所有完成狀態都使用 TINYINT(1) 儲存 (0:未完成, 1:已完成)
-- - 完成百分比使用 DECIMAL(5,2) 儲存，範圍 0.00-100.00
-- - 使用 IF NOT EXISTS 避免重複建立資料表
-- - 檢查欄位是否存在再新增，避免重複欄位錯誤
-- - 索引優化查詢效能

-- 後續步驟：
-- 1. 執行驗證查詢確認所有資料表和欄位都建立成功
-- 2. 測試插入和查詢功能
-- 3. 如需要，可以手動設定外鍵約束
-- 4. 整合到前端介面中
