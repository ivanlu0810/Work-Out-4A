-- 為 exercises 表格新增 video_url 欄位（如果不存在）
-- 並更新 cable上斜臥推 的影片路徑

-- 1. 檢查並新增 video_url 欄位
ALTER TABLE exercises 
ADD COLUMN IF NOT EXISTS video_url VARCHAR(500) DEFAULT NULL COMMENT '影片檔案路徑';

-- 2. 更新 cable上斜臥推 的影片路徑
UPDATE exercises 
SET video_url = '/健習生/dist/assets/videos/cable上斜臥推.mp4'
WHERE name = 'cable上斜臥推';

-- 3. 檢查更新結果
SELECT id, name, video_url 
FROM exercises 
WHERE name = 'cable上斜臥推';

-- 4. 查看所有有影片的動作
SELECT id, name, video_url 
FROM exercises 
WHERE video_url IS NOT NULL AND video_url != '';
