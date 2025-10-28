-- 添加 exercise_date 欄位到 training_plan_exercises 表
-- 這樣可以直接儲存具體日期，不需要前端計算

USE test;

-- 添加日期欄位
ALTER TABLE training_plan_exercises 
ADD COLUMN exercise_date DATE NULL AFTER day_of_week;

-- 添加索引以加速日期查詢
ALTER TABLE training_plan_exercises 
ADD INDEX idx_exercise_date (exercise_date);

-- 說明：
-- exercise_date: 儲存該動作的具體日期（如 '2025-10-27'）
-- 這樣前端可以直接用日期查詢，不需要計算 day_of_week 對應的日期
