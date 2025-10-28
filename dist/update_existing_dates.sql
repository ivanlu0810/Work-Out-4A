-- 更新現有資料的 exercise_date
-- 根據 training_plans 的 week_start_date 和 day_of_week 計算具體日期

USE test;

-- 步驟 1: 查看 plan_id 40 的資訊
SELECT id, week_start_date, week_number, plan_name 
FROM training_plans 
WHERE id = 40;

-- 步驟 2: 查看 plan_id 40 的動作分布
SELECT day_of_week, COUNT(*) as count
FROM training_plan_exercises
WHERE plan_id = 40
GROUP BY day_of_week;

-- 步驟 3: 更新 exercise_date
-- 假設 week_start_date 是 2025-10-26（週日），那麼：
-- monday = 2025-10-27
-- wednesday = 2025-10-29
-- friday = 2025-10-31

UPDATE training_plan_exercises
SET exercise_date = CASE
    WHEN day_of_week = 'monday' THEN '2025-10-27'
    WHEN day_of_week = 'tuesday' THEN '2025-10-28'
    WHEN day_of_week = 'wednesday' THEN '2025-10-29'
    WHEN day_of_week = 'thursday' THEN '2025-10-30'
    WHEN day_of_week = 'friday' THEN '2025-10-31'
    WHEN day_of_week = 'saturday' THEN '2025-11-01'
    WHEN day_of_week = 'sunday' THEN '2025-10-26'
END
WHERE plan_id = 40 AND exercise_date IS NULL;

-- 驗證更新結果
SELECT id, day_of_week, exercise_date, exercise_name 
FROM training_plan_exercises 
WHERE plan_id = 40 
ORDER BY exercise_date, order_index 
LIMIT 10;
