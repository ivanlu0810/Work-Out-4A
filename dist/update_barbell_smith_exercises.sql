-- 更新槓鈴和史密斯機動作的詳細資料
-- 根據提供的表格資料填入

USE test;

-- 更新槓鈴臥推
UPDATE exercises SET 
    hypertrophy_reps_min = 6, hypertrophy_reps_max = 10,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 4, hypertrophy_sets_max = 5,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 85,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 3, user_level = '有基礎'
WHERE name = '槓鈴臥推';

-- 更新上斜槓鈴臥推
UPDATE exercises SET 
    hypertrophy_reps_min = 6, hypertrophy_reps_max = 10,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 4, hypertrophy_sets_max = 5,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 3, user_level = '有基礎'
WHERE name = '上斜槓鈴臥推';

-- 更新槓鈴划船
UPDATE exercises SET 
    hypertrophy_reps_min = 6, hypertrophy_reps_max = 10,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 4, hypertrophy_sets_max = 5,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 3, user_level = '有基礎'
WHERE name = '槓鈴划船';

-- 更新槓鈴硬舉
UPDATE exercises SET 
    hypertrophy_reps_min = 5, hypertrophy_reps_max = 8,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 4, hypertrophy_sets_max = 6,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 75, hypertrophy_load_max_pct = 90,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 4, user_level = '有基礎'
WHERE name = '槓鈴硬舉';

-- 更新槓鈴深蹲
UPDATE exercises SET 
    hypertrophy_reps_min = 6, hypertrophy_reps_max = 10,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 4, hypertrophy_sets_max = 5,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 85,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 3, user_level = '有基礎'
WHERE name = '槓鈴深蹲';

-- 更新前蹲（槓鈴前蹲）
UPDATE exercises SET 
    hypertrophy_reps_min = 6, hypertrophy_reps_max = 10,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 4, hypertrophy_sets_max = 5,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 65, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 4, user_level = '有基礎'
WHERE name = '前蹲（槓鈴前蹲）';

-- 更新槓鈴推舉
UPDATE exercises SET 
    hypertrophy_reps_min = 6, hypertrophy_reps_max = 10,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 4, hypertrophy_sets_max = 5,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 3, user_level = '有基礎'
WHERE name = '槓鈴推舉';

-- 更新槓鈴二頭彎舉
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '槓鈴二頭彎舉';

-- 更新槓鈴窄握臥推
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 3, user_level = '有基礎'
WHERE name = '槓鈴窄握臥推';

-- 更新槓鈴臀推（Hip Thrust）
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 20,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 65, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 2, user_level = '新手'
WHERE name = '槓鈴臀推（Hip Thrust）';

-- 更新史密斯深蹲
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯深蹲';

-- 更新史密斯前蹲
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 65, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 3, user_level = '有基礎'
WHERE name = '史密斯前蹲';

-- 更新史密斯臀推
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 20,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯臀推';

-- 更新史密斯硬舉（直腿）
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 3, user_level = '有基礎'
WHERE name = '史密斯硬舉（直腿）';

-- 更新史密斯臥推
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 65,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯臥推';

-- 更新史密斯上斜臥推
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 65, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯上斜臥推';

-- 更新史密斯肩推
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯肩推';

-- 更新史密斯提踵
UPDATE exercises SET 
    hypertrophy_reps_min = 12, hypertrophy_reps_max = 20,
    fatloss_reps_min = 20, fatloss_reps_max = 25,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 50, hypertrophy_load_max_pct = 65,
    fatloss_load_min_pct = 40, fatloss_load_max_pct = 50,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯提踵';

-- 更新史密斯彎腰划船
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯彎腰划船';

-- 更新史密斯弓步蹲
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 75,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '史密斯弓步蹲';

-- 檢查更新結果
SELECT 
    name as '動作名稱',
    target_muscle as '目標肌群',
    hypertrophy_reps_min as '增肌次數(最小)',
    hypertrophy_reps_max as '增肌次數(最大)',
    hypertrophy_sets_min as '增肌組數(最小)',
    hypertrophy_sets_max as '增肌組數(最大)',
    hypertrophy_load_min_pct as '增肌重量%(最小)',
    hypertrophy_load_max_pct as '增肌重量%(最大)',
    difficulty as '難度',
    user_level as '使用者等級'
FROM exercises 
WHERE name IN (
    '槓鈴臥推', '上斜槓鈴臥推', '槓鈴划船', '槓鈴硬舉', '槓鈴深蹲',
    '前蹲（槓鈴前蹲）', '槓鈴推舉', '槓鈴二頭彎舉', '槓鈴窄握臥推', '槓鈴臀推（Hip Thrust）',
    '史密斯深蹲', '史密斯前蹲', '史密斯臀推', '史密斯硬舉（直腿）', '史密斯臥推',
    '史密斯上斜臥推', '史密斯肩推', '史密斯提踵', '史密斯彎腰划船', '史密斯弓步蹲'
)
ORDER BY name;
