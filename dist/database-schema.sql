-- 健習生資料庫結構 SQL 腳本
-- 版本：1.0
-- 建立日期：2024年12月

-- 1. 使用者資料表
CREATE TABLE users (
    user_id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(20) NOT NULL DEFAULT 'user',
    gender VARCHAR(10) NOT NULL,
    age INT NOT NULL,
    height_cm FLOAT NOT NULL,
    weight_kg FLOAT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. INBODY 測量記錄表
CREATE TABLE inbody_records (
    record_id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    test_date DATETIME NOT NULL,
    skeletal_muscle FLOAT NOT NULL,
    body_fat FLOAT NOT NULL,
    fat_percentage FLOAT NOT NULL,
    basal_metabolism FLOAT NOT NULL,
    bmi FLOAT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_user_date (user_id, test_date)
);

-- 3. 動作資料表
CREATE TABLE exercises (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    muscle_group VARCHAR(50) NOT NULL,
    target_muscle VARCHAR(100),
    description TEXT,
    difficulty_level VARCHAR(20) NOT NULL DEFAULT 'beginner',
    equipment_needed VARCHAR(100),
    instructions TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_muscle_difficulty (muscle_group, difficulty_level)
);

-- 4. 訓練計畫表
CREATE TABLE training_plans (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    week_start_date DATE NOT NULL,
    week_number INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    exercises JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_user_week (user_id, week_start_date)
);

-- 5. 餐單計畫表
CREATE TABLE meal_plans (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    week_start_date DATE NOT NULL,
    week_number INT NOT NULL,
    plan_name VARCHAR(100) NOT NULL,
    meals JSON NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_user_week (user_id, week_start_date)
);

-- 6. 食物營養資料表
CREATE TABLE foods (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    brand VARCHAR(50),
    serving_size_g FLOAT NOT NULL,
    kcal FLOAT NOT NULL,
    protein_g FLOAT NOT NULL,
    carb_g FLOAT NOT NULL,
    fat_g FLOAT NOT NULL,
    sugar_g FLOAT,
    fiber_g FLOAT,
    sodium_mg FLOAT,
    tags JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name_brand (name, brand)
);

-- 7. 使用者目標設定表
CREATE TABLE user_targets (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL UNIQUE,
    kcal_target FLOAT NOT NULL,
    protein_target_g FLOAT NOT NULL,
    carb_target_g FLOAT NOT NULL,
    fat_target_g FLOAT NOT NULL,
    meals_per_day INT NOT NULL DEFAULT 3,
    diet_type VARCHAR(50) NOT NULL DEFAULT 'balanced',
    allergens JSON,
    dislikes JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);

-- 8. 使用者偏好設定表
CREATE TABLE user_preferences (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL UNIQUE,
    budget_level VARCHAR(20) NOT NULL DEFAULT 'medium',
    cooking_time_min INT NOT NULL DEFAULT 30,
    cuisine VARCHAR(50),
    avoid_ingredients JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id)
);
