-- 為 training_plan_completion 表格新增個別動作記錄欄位
-- 根據實際表格結構新增欄位

-- 1. 新增 exercise_id 欄位（放在 user_id 之後）
ALTER TABLE `training_plan_completion` 
ADD COLUMN `exercise_id` INT(11) DEFAULT NULL COMMENT '動作ID' AFTER `user_id`;

-- 2. 新增 exercise_name 欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `exercise_name` VARCHAR(255) DEFAULT NULL COMMENT '動作名稱' AFTER `exercise_id`;

-- 3. 新增 muscle_group 欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `muscle_group` VARCHAR(255) DEFAULT NULL COMMENT '肌群' AFTER `exercise_name`;

-- 4. 新增 sets 欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `sets` INT(11) DEFAULT 0 COMMENT '組數' AFTER `muscle_group`;

-- 5. 新增 reps 欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `reps` INT(11) DEFAULT 0 COMMENT '次數' AFTER `sets`;

-- 6. 新增 weight 欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `weight` DECIMAL(5,2) DEFAULT NULL COMMENT '重量' AFTER `reps`;

-- 7. 新增個別動作完成狀態欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `individual_completed` TINYINT(1) DEFAULT 0 COMMENT '個別動作是否完成 (0:未完成, 1:已完成)' AFTER `weight`;

-- 8. 新增個別動作完成時間欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `individual_completed_at` TIMESTAMP NULL DEFAULT NULL COMMENT '個別動作完成時間' AFTER `individual_completed`;

-- 9. 新增個別動作備註欄位
ALTER TABLE `training_plan_completion` 
ADD COLUMN `individual_notes` TEXT DEFAULT NULL COMMENT '個別動作備註' AFTER `individual_completed_at`;

-- 10. 新增複合索引以提升查詢效能
ALTER TABLE `training_plan_completion` 
ADD INDEX `idx_exercise_lookup` (`plan_id`, `user_id`, `week_number`, `day_of_week`, `exercise_id`);

-- 11. 新增唯一索引防止重複記錄
ALTER TABLE `training_plan_completion` 
ADD UNIQUE KEY `uk_exercise_completion` (`plan_id`, `user_id`, `week_number`, `day_of_week`, `exercise_id`);

