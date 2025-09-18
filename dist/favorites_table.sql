-- 建立我的最愛資料表（與現有登入流程一致：資料庫 test，表 user(user_id)）
-- 使用前請先切換至對應資料庫：USE test;

CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(255) NOT NULL COMMENT '動作名稱，做為每位用戶的唯一鍵',
    exercise_json LONGTEXT NOT NULL COMMENT '完整動作資料（JSON 字串）',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_name (user_id, name),
    KEY idx_user (user_id),
    CONSTRAINT fk_favorites_user FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);


