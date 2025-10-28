-- 查詢 plan_id 40 的 week_start_date

USE test;

SELECT id, week_start_date, week_number 
FROM training_plans 
WHERE id = 40;
