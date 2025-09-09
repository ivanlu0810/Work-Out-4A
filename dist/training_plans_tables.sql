-- 訓練計畫相關資料表
-- 使用現有資料庫
USE test;

-- 1. 訓練計畫主表
CREATE TABLE IF NOT EXISTS training_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    week_start_date DATE NOT NULL,
    week_number INT NOT NULL,
    plan_name VARCHAR(255) DEFAULT '訓練計畫',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_week (user_id, week_number),
    INDEX idx_user_date (user_id, week_start_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. 訓練計畫動作明細表
CREATE TABLE IF NOT EXISTS training_plan_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    plan_id INT NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    exercise_id INT NOT NULL,
    exercise_name VARCHAR(255) NOT NULL,
    muscle_group VARCHAR(50) NOT NULL,
    sets INT DEFAULT 0,
    reps INT DEFAULT 0,
    weight DECIMAL(5,2) NULL,
    rest_time INT NULL,
    notes TEXT NULL,
    order_index INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (plan_id) REFERENCES training_plans(id) ON DELETE CASCADE,
    INDEX idx_plan_day (plan_id, day_of_week),
    INDEX idx_exercise (exercise_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. 訓練記錄表（可選，用於記錄實際訓練）
CREATE TABLE IF NOT EXISTS workout_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    session_date DATE NOT NULL,
    day_of_week ENUM('monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday') NOT NULL,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    total_duration INT NULL COMMENT '總訓練時間（分鐘）',
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_date (user_id, session_date),
    INDEX idx_plan_date (plan_id, session_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. 訓練動作記錄表（可選，用於記錄實際訓練動作）
CREATE TABLE IF NOT EXISTS workout_exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id INT NOT NULL,
    exercise_id INT NOT NULL,
    exercise_name VARCHAR(255) NOT NULL,
    muscle_group VARCHAR(50) NOT NULL,
    planned_sets INT DEFAULT 0,
    planned_reps INT DEFAULT 0,
    planned_weight DECIMAL(5,2) NULL,
    actual_sets INT DEFAULT 0,
    actual_reps INT DEFAULT 0,
    actual_weight DECIMAL(5,2) NULL,
    rest_time INT NULL,
    notes TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id) REFERENCES workout_sessions(id) ON DELETE CASCADE,
    INDEX idx_session (session_id),
    INDEX idx_exercise (exercise_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 插入範例資料（可選）
INSERT INTO training_plans (user_id, week_start_date, week_number, plan_name) VALUES
(9, '2024-12-23', 0, '本週訓練計畫'),
(9, '2024-12-30', 1, '下週訓練計畫');

-- 插入範例動作（可選）
INSERT INTO training_plan_exercises (plan_id, day_of_week, exercise_id, exercise_name, muscle_group, sets, reps, weight, rest_time, notes) VALUES
(1, 'monday', 1, '伏地挺身', '胸', 3, 15, NULL, 60, '初級訓練'),
(1, 'monday', 2, '臥推', '胸', 4, 12, 50.0, 90, '中級訓練'),
(1, 'tuesday', 0, '休息', '休息', 0, 0, NULL, NULL, '休息日'),
(1, 'wednesday', 3, '深蹲', '腿', 4, 20, NULL, 60, '腿部訓練'),
(1, 'thursday', 4, '引體向上', '背', 3, 8, NULL, 120, '背部訓練'),
(1, 'friday', 5, '平板支撐', '腹部', 3, 60, NULL, 30, '核心訓練'),
(1, 'saturday', 6, '二頭彎舉', '手臂', 3, 12, 10.0, 60, '手臂訓練'),
(1, 'sunday', 0, '休息', '休息', 0, 0, NULL, NULL, '休息日');
