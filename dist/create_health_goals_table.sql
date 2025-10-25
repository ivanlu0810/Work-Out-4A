-- 健康目標資料表
-- 用於儲存使用者的健康目標設定

CREATE TABLE health_goals (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    goal_weight DECIMAL(5,1) NOT NULL,
    goal_fat_percentage DECIMAL(4,1) NOT NULL,
    goal_muscle DECIMAL(5,1) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    UNIQUE KEY unique_user_goals (user_id)
);

-- 建立索引以提升查詢效能
CREATE INDEX idx_health_goals_user_id ON health_goals(user_id);
