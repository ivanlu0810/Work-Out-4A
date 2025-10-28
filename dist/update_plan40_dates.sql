-- 更新 plan_id 40 的 exercise_date
-- 假設 week_start_date 是 2025-10-26（週日），調整到週一後：
-- monday = 2025-10-27
-- wednesday = 2025-10-29  
-- friday = 2025-10-31

USE test;

-- 先確認 plan_id 40 的 week_start_date
SELECT id, week_start_date FROM training_plans WHERE id = 40;

-- 更新 exercise_date
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
WHERE plan_id = 40;

-- 驗證結果
SELECT id, day_of_week, exercise_date, exercise_name 
FROM training_plan_exercises 
WHERE plan_id = 40 
ORDER BY exercise_date, order_index;
