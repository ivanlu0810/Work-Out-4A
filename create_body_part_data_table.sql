-- 建立部位數據資料表
-- 用於存儲用戶輸入的部位肌肉指數和部位脂肪指數

CREATE TABLE body_part_data (
    id INT AUTO_INCREMENT PRIMARY KEY COMMENT '主鍵ID',
    user_id VARCHAR(50) NOT NULL COMMENT '用戶ID',
    record_id VARCHAR(50) NOT NULL COMMENT '關聯基本健康數據的記錄ID',
    measurement_date DATE NOT NULL COMMENT '測量日期',
    
    -- 部位肌肉指數 (0-100)
    chest_muscle_index DECIMAL(5,2) DEFAULT NULL COMMENT '胸部肌肉指數',
    arm_muscle_index DECIMAL(5,2) DEFAULT NULL COMMENT '手臂肌肉指數',
    leg_muscle_index DECIMAL(5,2) DEFAULT NULL COMMENT '腿部肌肉指數',
    core_muscle_index DECIMAL(5,2) DEFAULT NULL COMMENT '核心肌肉指數',
    
    -- 部位脂肪指數 (0-100)
    chest_fat_index DECIMAL(5,2) DEFAULT NULL COMMENT '胸部脂肪指數',
    arm_fat_index DECIMAL(5,2) DEFAULT NULL COMMENT '手臂脂肪指數',
    leg_fat_index DECIMAL(5,2) DEFAULT NULL COMMENT '腿部脂肪指數',
    core_fat_index DECIMAL(5,2) DEFAULT NULL COMMENT '核心脂肪指數',
    
    -- 時間戳記
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT '建立時間',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新時間',
    
    -- 索引
    INDEX idx_user_id (user_id) COMMENT '用戶ID索引',
    INDEX idx_record_id (record_id) COMMENT '記錄ID索引',
    INDEX idx_measurement_date (measurement_date) COMMENT '測量日期索引',
    INDEX idx_user_date (user_id, measurement_date) COMMENT '用戶日期複合索引',
    
    -- 外鍵約束（請根據您的實際資料表名稱調整）
    -- FOREIGN KEY (user_id) REFERENCES users(userid) ON DELETE CASCADE,
    -- FOREIGN KEY (record_id) REFERENCES health_data(record_id) ON DELETE CASCADE
    
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='部位數據表';

-- 添加檢查約束確保指數值在合理範圍內
ALTER TABLE body_part_data 
ADD CONSTRAINT chk_chest_muscle_index CHECK (chest_muscle_index IS NULL OR (chest_muscle_index >= 0 AND chest_muscle_index <= 100)),
ADD CONSTRAINT chk_arm_muscle_index CHECK (arm_muscle_index IS NULL OR (arm_muscle_index >= 0 AND arm_muscle_index <= 100)),
ADD CONSTRAINT chk_leg_muscle_index CHECK (leg_muscle_index IS NULL OR (leg_muscle_index >= 0 AND leg_muscle_index <= 100)),
ADD CONSTRAINT chk_core_muscle_index CHECK (core_muscle_index IS NULL OR (core_muscle_index >= 0 AND core_muscle_index <= 100)),
ADD CONSTRAINT chk_chest_fat_index CHECK (chest_fat_index IS NULL OR (chest_fat_index >= 0 AND chest_fat_index <= 100)),
ADD CONSTRAINT chk_arm_fat_index CHECK (arm_fat_index IS NULL OR (arm_fat_index >= 0 AND arm_fat_index <= 100)),
ADD CONSTRAINT chk_leg_fat_index CHECK (leg_fat_index IS NULL OR (leg_fat_index >= 0 AND leg_fat_index <= 100)),
ADD CONSTRAINT chk_core_fat_index CHECK (core_fat_index IS NULL OR (core_fat_index >= 0 AND core_fat_index <= 100));

-- 顯示建立結果
SHOW CREATE TABLE body_part_data;
