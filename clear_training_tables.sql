-- 清空訓練計畫相關的三個資料表
-- 注意：這會刪除所有資料，請謹慎使用

-- 1. 清空 training_plan_completion 表（訓練計畫完成記錄）
DELETE FROM training_plan_completion;

-- 2. 清空 training_plan_exercises 表（訓練計畫動作記錄）
DELETE FROM training_plan_exercises;

-- 3. 清空 training_plans 表（訓練計畫主表）
DELETE FROM training_plans;

-- 重置自動遞增ID（可選）
ALTER TABLE training_plan_completion AUTO_INCREMENT = 1;
ALTER TABLE training_plan_exercises AUTO_INCREMENT = 1;
ALTER TABLE training_plans AUTO_INCREMENT = 1;

-- 顯示清空結果
SELECT 'training_plan_completion' as table_name, COUNT(*) as remaining_records FROM training_plan_completion
UNION ALL
SELECT 'training_plan_exercises' as table_name, COUNT(*) as remaining_records FROM training_plan_exercises
UNION ALL
SELECT 'training_plans' as table_name, COUNT(*) as remaining_records FROM training_plans;

