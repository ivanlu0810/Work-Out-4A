-- 更新動作詳細資料（reps、sets、load等）
-- 根據提供的表格資料填入

USE test;

-- 更新拉繩飛鳥
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '拉繩飛鳥';

-- 更新下往上拉繩飛鳥
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '下往上拉繩飛鳥';

-- 更新坐姿拉繩划船
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 60, fatloss_load_max_pct = 70,
    difficulty = 2, user_level = '新手'
WHERE name = '坐姿拉繩划船';

-- 更新直臂下拉
UPDATE exercises SET 
    hypertrophy_reps_min = 10, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '直臂下拉';

-- 更新側平舉（拉繩）
UPDATE exercises SET 
    hypertrophy_reps_min = 10, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 50, hypertrophy_load_max_pct = 60,
    fatloss_load_min_pct = 40, fatloss_load_max_pct = 50,
    difficulty = 2, user_level = '新手'
WHERE name = '側平舉（拉繩）';

-- 更新拉繩二頭彎舉
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '拉繩二頭彎舉';

-- 更新拉繩下壓
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '拉繩下壓';

-- 更新頭上拉繩伸展
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 50, hypertrophy_load_max_pct = 60,
    fatloss_load_min_pct = 40, fatloss_load_max_pct = 50,
    difficulty = 2, user_level = '新手'
WHERE name = '頭上拉繩伸展';

-- 更新拉繩劈木（斧頭劈砍）
UPDATE exercises SET 
    hypertrophy_reps_min = 10, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 50, hypertrophy_load_max_pct = 60,
    fatloss_load_min_pct = 40, fatloss_load_max_pct = 50,
    difficulty = 3, user_level = '有基礎'
WHERE name = '拉繩劈木（斧頭劈砍）';

-- 更新拉繩捲腹
UPDATE exercises SET 
    hypertrophy_reps_min = 15, hypertrophy_reps_max = 20,
    fatloss_reps_min = 20, fatloss_reps_max = 25,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 30, hypertrophy_load_max_pct = 40,
    fatloss_load_min_pct = 20, fatloss_load_max_pct = 30,
    difficulty = 2, user_level = '新手'
WHERE name = '拉繩捲腹';

-- 更新啞鈴臥推
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 60, fatloss_load_max_pct = 70,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴臥推';

-- 更新啞鈴飛鳥
UPDATE exercises SET 
    hypertrophy_reps_min = 10, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 50, hypertrophy_load_max_pct = 60,
    fatloss_load_min_pct = 40, fatloss_load_max_pct = 50,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴飛鳥';

-- 更新啞鈴划船
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 60, fatloss_load_max_pct = 70,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴划船';

-- 更新單手啞鈴划船
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 3, user_level = '有基礎'
WHERE name = '單手啞鈴划船';

-- 更新啞鈴側平舉
UPDATE exercises SET 
    hypertrophy_reps_min = 10, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 40, hypertrophy_load_max_pct = 50,
    fatloss_load_min_pct = 30, fatloss_load_max_pct = 40,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴側平舉';

-- 更新啞鈴二頭彎舉
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴二頭彎舉';

-- 更新啞鈴錘式彎舉
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴錘式彎舉';

-- 更新啞鈴三頭伸展（頭上）
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 50, hypertrophy_load_max_pct = 60,
    fatloss_load_min_pct = 40, fatloss_load_max_pct = 50,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴三頭伸展（頭上）';

-- 更新啞鈴深蹲
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 60, fatloss_load_max_pct = 70,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴深蹲';

-- 更新啞鈴弓步蹲
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 60, hypertrophy_load_max_pct = 70,
    fatloss_load_min_pct = 50, fatloss_load_max_pct = 60,
    difficulty = 3, user_level = '有基礎'
WHERE name = '啞鈴弓步蹲';

-- 更新啞鈴羅馬尼亞硬舉
UPDATE exercises SET 
    hypertrophy_reps_min = 8, hypertrophy_reps_max = 12,
    fatloss_reps_min = 12, fatloss_reps_max = 15,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 70, hypertrophy_load_max_pct = 80,
    fatloss_load_min_pct = 60, fatloss_load_max_pct = 70,
    difficulty = 3, user_level = '有基礎'
WHERE name = '啞鈴羅馬尼亞硬舉';

-- 更新啞鈴側彎
UPDATE exercises SET 
    hypertrophy_reps_min = 15, hypertrophy_reps_max = 20,
    fatloss_reps_min = 20, fatloss_reps_max = 25,
    hypertrophy_sets_min = 3, hypertrophy_sets_max = 4,
    fatloss_sets_min = 3, fatloss_sets_max = 4,
    hypertrophy_load_min_pct = 30, hypertrophy_load_max_pct = 40,
    fatloss_load_min_pct = 20, fatloss_load_max_pct = 30,
    difficulty = 2, user_level = '新手'
WHERE name = '啞鈴側彎';

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
    '拉繩飛鳥', '下往上拉繩飛鳥', '坐姿拉繩划船', '直臂下拉', 
    '側平舉（拉繩）', '拉繩二頭彎舉', '拉繩下壓', '頭上拉繩伸展',
    '拉繩劈木（斧頭劈砍）', '拉繩捲腹', '啞鈴臥推', '啞鈴飛鳥',
    '啞鈴划船', '單手啞鈴划船', '啞鈴側平舉', '啞鈴二頭彎舉',
    '啞鈴錘式彎舉', '啞鈴三頭伸展（頭上）', '啞鈴深蹲', '啞鈴弓步蹲',
    '啞鈴羅馬尼亞硬舉', '啞鈴側彎'
)
ORDER BY name;
