-- 餐食計畫表格
CREATE TABLE IF NOT EXISTS meal_plans (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(50) NOT NULL,
    date DATE NOT NULL,
    target_calories INT DEFAULT 0,
    target_protein DECIMAL(8,2) DEFAULT 0,
    target_carbs DECIMAL(8,2) DEFAULT 0,
    target_fat DECIMAL(8,2) DEFAULT 0,
    actual_calories INT DEFAULT 0,
    actual_protein DECIMAL(8,2) DEFAULT 0,
    actual_carbs DECIMAL(8,2) DEFAULT 0,
    actual_fat DECIMAL(8,2) DEFAULT 0,
    meals_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_date (user_id, date),
    INDEX idx_user_id (user_id),
    INDEX idx_date (date)
);

-- 餐食項目表格
CREATE TABLE IF NOT EXISTS meal_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    meal_plan_id INT NOT NULL,
    meal_type ENUM('breakfast', 'lunch', 'dinner', 'snack') NOT NULL,
    food_name VARCHAR(100) NOT NULL,
    quantity_grams DECIMAL(8,2) NOT NULL,
    calories_per_100g DECIMAL(8,2) NOT NULL,
    protein_per_100g DECIMAL(8,2) NOT NULL,
    carbs_per_100g DECIMAL(8,2) NOT NULL,
    fat_per_100g DECIMAL(8,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (meal_plan_id) REFERENCES meal_plans(id) ON DELETE CASCADE,
    INDEX idx_meal_plan_id (meal_plan_id),
    INDEX idx_meal_type (meal_type)
);

-- 插入範例資料
INSERT IGNORE INTO meal_plans (user_id, date, target_calories, target_protein, target_carbs, target_fat) 
VALUES ('demo_user', CURDATE(), 2500, 150, 300, 100);

-- 插入範例餐食項目
INSERT IGNORE INTO meal_items (meal_plan_id, meal_type, food_name, quantity_grams, calories_per_100g, protein_per_100g, carbs_per_100g, fat_per_100g)
SELECT 
    mp.id,
    'breakfast',
    '雞胸肉',
    100,
    165,
    31.0,
    0.0,
    3.6
FROM meal_plans mp 
WHERE mp.user_id = 'demo_user' AND mp.date = CURDATE()
LIMIT 1;
