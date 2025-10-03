-- 二分化(上下分離)訓練菜單設計
-- 基於專業健身建議和資料庫中的動作

-- 1. 檢查可用動作數量（按肌群分類）
SELECT 
    '肌群動作統計' as type,
    target_muscle as muscle_group,
    COUNT(*) as exercise_count,
    GROUP_CONCAT(name SEPARATOR ', ') as available_exercises
FROM exercises 
WHERE target_muscle IN (
    -- 上半身肌群
    '上胸', '中胸', '下胸', '肩膀前束', '肩膀中束', '肩膀後束',
    '上背', '中背', '下背', '三頭肌', '二頭肌',
    -- 下半身肌群  
    '股四頭肌', '股二頭肌', '臀肌', '小腿', '核心', '上腹', '下腹', '側腹'
)
GROUP BY target_muscle
ORDER BY 
    CASE 
        WHEN target_muscle IN ('上胸', '中胸', '下胸', '肩膀前束', '肩膀中束', '肩膀後束', '上背', '中背', '下背', '三頭肌', '二頭肌') THEN 1
        ELSE 2
    END,
    target_muscle;

-- 2. 二分化訓練菜單建議
-- 上半身訓練 (週一、週四) - 8個動作
-- 下半身訓練 (週二、週五) - 8個動作

-- 上半身動作組合建議：
-- 1. 胸部動作 x2 (上胸1個 + 中胸1個)
-- 2. 背部動作 x2 (上背1個 + 中背1個)  
-- 3. 肩膀動作 x2 (前束1個 + 中束1個)
-- 4. 手臂動作 x2 (二頭1個 + 三頭1個)

-- 下半身動作組合建議：
-- 1. 股四頭肌動作 x2
-- 2. 股二頭肌動作 x2
-- 3. 臀肌動作 x2
-- 4. 核心動作 x2

-- 3. 檢查每個肌群是否有足夠的動作
SELECT 
    '動作充足性檢查' as check_type,
    target_muscle as muscle_group,
    COUNT(*) as available_count,
    CASE 
        WHEN COUNT(*) >= 2 THEN '✅ 充足'
        WHEN COUNT(*) = 1 THEN '⚠️ 僅1個'
        ELSE '❌ 無動作'
    END as status
FROM exercises 
WHERE target_muscle IN (
    '上胸', '中胸', '下胸', '肩膀前束', '肩膀中束', '肩膀後束',
    '上背', '中背', '下背', '三頭肌', '二頭肌',
    '股四頭肌', '股二頭肌', '臀肌', '核心', '上腹', '下腹', '側腹'
)
GROUP BY target_muscle
ORDER BY status DESC, available_count DESC;
