-- 在phpMyAdmin中建立部位數據資料表
-- 直接複製以下SQL並在phpMyAdmin的SQL標籤中執行

CREATE TABLE `body_part_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` varchar(50) NOT NULL,
  `record_id` varchar(50) NOT NULL,
  `measurement_date` date NOT NULL,
  `chest_muscle_index` decimal(5,2) DEFAULT NULL,
  `arm_muscle_index` decimal(5,2) DEFAULT NULL,
  `leg_muscle_index` decimal(5,2) DEFAULT NULL,
  `core_muscle_index` decimal(5,2) DEFAULT NULL,
  `chest_fat_index` decimal(5,2) DEFAULT NULL,
  `arm_fat_index` decimal(5,2) DEFAULT NULL,
  `leg_fat_index` decimal(5,2) DEFAULT NULL,
  `core_fat_index` decimal(5,2) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_record_id` (`record_id`),
  KEY `idx_measurement_date` (`measurement_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
