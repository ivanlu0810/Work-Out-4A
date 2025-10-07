-- Level 1 兩天連續練 - 動作檢查與新增
-- 檢查所有圖片中的動作是否存在於資料庫

-- 1. 檢查動作存在性
SELECT '動作存在性檢查' as check_type,
       exercise_name,
       expected_muscle_group,
       CASE 
           WHEN EXISTS (
               SELECT 1 FROM exercises 
               WHERE name = exercise_name 
               AND target_muscle = expected_muscle_group
           ) THEN '✅ 存在'
           ELSE '❌ 不存在'
       END as status,
       (
           SELECT CONCAT('ID:', COALESCE(id, '無'), ' 肌群:', COALESCE(target_muscle, '無'))
           FROM exercises 
           WHERE name = exercise_name
           LIMIT 1
       ) as found_in_db
FROM (
    -- Day 1: 下半身+肩膀 (6個動作)
    SELECT '深蹲' as exercise_name, '股四頭肌' as expected_muscle_group, 'Day1' as day_info
    UNION ALL SELECT '羅馬尼亞硬舉', '股二頭肌', 'Day1'
    UNION ALL SELECT '分腿蹲', '股四頭肌', 'Day1'
    UNION ALL SELECT '啞鈴肩推', '肩膀前束', 'Day1'
    UNION ALL SELECT '側平舉', '肩膀中束', 'Day1'
    UNION ALL SELECT '臉拉', '肩膀後束', 'Day1'
    
    -- Day 2: 全身 (8個動作)  
    UNION ALL SELECT '胸推', '中胸', 'Day2'
    UNION ALL SELECT '上胸推', '上胸', 'Day2'
    UNION ALL SELECT '背闊下拉', '上背', 'Day2'
    UNION ALL SELECT '划船', '中背', 'Day2'
    UNION ALL SELECT '啞鈴肩推', '肩膀前束', 'Day2'
    UNION ALL SELECT '側平舉', '肩膀中束', 'Day2'
    UNION ALL SELECT '深蹲', '股四頭肌', 'Day2'
    UNION ALL SELECT '羅馬尼亞硬舉', '股二頭肌', 'Day2'
) as exercise_list
ORDER BY day_info, exercise_name;

-- 2. 新增缺失動作
INSERT IGNORE INTO exercises (
    name, target_muscle, hypertrophy_reps_min, hypertrophy_reps_max,
    fatloss_reps_min, fatloss_reps_max, hypertrophy_sets_min, hypertrophy_sets_max,
    fatloss_sets_min, fatloss_sets_max, hypertrophy_load_min_pct, hypertrophy_load_max_pct,
    fatloss_load_min_pct, fatloss_load_max_pct, instruction_full, instruction_short,
    difficulty, user_level, notes, video_url, instruction_cues
) VALUES 

-- Day 1 動作 (下半身+肩膀)
('深蹲', '股四頭肌', 8, 15, 12, 20, 3, 5, 3, 4, 60, 80, 40, 60,
'雙腳與肩同寬站立，腳尖稍微向外。下蹲時保持背部挺直，膝蓋不超過腳尖，蹲至大腿與地面平行後站起。',
'雙腳與肩同寬，下蹲至大腿平行地面，保持背部挺直。', 1, '新手',
'適合初學者的基礎腿部動作，可鍛鍊股四頭肌、臀肌和核心肌群', NULL,
'保持核心穩定，膝蓋與腳尖方向一致'),

('羅馬尼亞硬舉', '股二頭肌', 8, 15, 12, 20, 3, 5, 3, 4, 60, 80, 40, 60,
'雙腳與肩同寬站立，持槓鈴或啞鈴。保持背部挺直，臀部向後推，下放至膝蓋後方，然後站起。',
'臀部向後推，下放至膝蓋後方，保持背部挺直。', 2, '有基礎',
'針對後腿肌群的訓練動作，需要一定的技術', NULL,
'保持背部挺直，感受股二頭肌和臀肌發力'),

('分腿蹲', '股四頭肌', 8, 15, 12, 20, 3, 5, 3, 4, 50, 70, 30, 50,
'站立，一腳向前跨出成弓箭步姿勢。下蹲至前腿大腿與地面平行，然後站起。保持上身挺直。',
'向前跨出成弓箭步，下蹲至大腿平行地面。', 2, '有基礎',
'單腿訓練動作，鍛鍊股四頭肌、臀肌和平衡感', NULL,
'保持上身挺直，前腿膝蓋不超過腳尖'),

('啞鈴肩推', '肩膀前束', 8, 15, 12, 20, 3, 5, 3, 4, 60, 80, 40, 60,
'坐在椅子上或站立，雙手各持啞鈴於肩部高度。向上推舉至手臂完全伸展，然後緩慢下放。',
'啞鈴推舉至頭頂，保持穩定節奏。', 1, '新手',
'適合初學者的肩膀訓練動作，可鍛鍊三角肌前束', NULL,
'保持核心穩定，避免腰部過度拱起'),

('側平舉', '肩膀中束', 10, 15, 15, 25, 3, 5, 3, 4, 40, 60, 20, 40,
'站立或坐著，雙手各持啞鈴於身體兩側。向兩側舉起至肩膀高度，然後緩慢下放。',
'向兩側舉起至肩膀高度，感受肩膀中束發力。', 1, '新手',
'針對三角肌中束的孤立訓練動作', NULL,
'保持輕微彎曲，避免過度搖擺'),

('臉拉', '肩膀後束', 12, 20, 15, 25, 3, 4, 3, 4, 30, 50, 20, 40,
'面對繩索機或彈力帶，握住繩索或把手。將繩索拉向臉部，保持上臂平行地面，感受肩膀後束收縮。',
'拉向臉部，保持上臂平行地面，感受肩膀後束收縮。', 2, '有基礎',
'針對三角肌後束的訓練，改善圓肩問題', NULL,
'保持肩膀固定，專注後束收縮'),

-- Day 2 動作 (全身)
('胸推', '中胸', 8, 15, 12, 20, 3, 4, 3, 4, 60, 80, 40, 60,
'可進行啞鈴或槓鈴臥推動作。平躺，雙腳穩固踩地。握住重物，推起至手臂完全伸展，然後緩慢下放。',
'平躺推舉，完整動作幅度，控制速度。', 1, '新手',
'通用的胸部推舉動作，適合初學者', NULL,
'保持肩胛骨穩定，感受胸部發力'),

('上胸推', '上胸', 8, 15, 12, 20, 3, 4, 3, 4, 60, 80, 40, 60,
'調整椅子至30-45度上斜角度。進行啞鈴或槓鈴上斜推舉動作，感受上胸部發力。',
'上斜角度推舉，感受上胸部發力。', 1, '新手',  
'針對上胸部的訓練動作，適合初學者', NULL,
'保持肩胛骨穩定，感受上胸部收縮'),

('背闊下拉', '上背', 8, 15, 12, 20, 3, 4, 3, 4, 60, 75, 40, 60,
'坐在下拉機前，握住橫桿，手距略寬於肩。下拉至胸部上方，感受背部收縮，然後緩慢回放。',
'下拉至胸部上方，感受背部收縮。', 1, '新手',
'適合初學者的背部訓練動作，比引體向上更容易掌握', NULL,
'保持胸部挺起，用背部發力而非手臂'),

('划船', '中背', 8, 15, 12, 20, 3, 4, 3, 4, 60, 75, 40, 60,
'可進行啞鈴划船或槓鈴划船。向後拉至腰部，感受背部收縮，然後緩慢回放。',
'向後拉至腰部，感受背部收縮。', 1, '新手',
'通用的划船動作，鍛鍊中背部肌群', NULL,
'保持背部挺直，用背部發力而非手臂');
