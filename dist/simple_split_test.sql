-- 簡易二分化隨機菜單測試
-- 檢查二分化隨機菜單的動作匹配問題

-- 1. 快速檢查所有需要的肌群動作
SELECT 
    target_muscle as muscle_group,
    COUNT(*) as count,
    MIN(name) as sample_exercise
FROM exercises 
WHERE target_muscle IN (
    '中胸', '上背', '中背', '肩膀前束', '肩膀中束', '二頭肌',
    '股四頭肌', '股二頭肌', '臀肌', '核心'
)
GROUP BY target_muscle
ORDER BY target_muscle;

-- 2. 檢查具體的動作名稱
SELECT 
    target_muscle,
    name,
    id
FROM exercises 
WHERE target_muscle IN ('中胸', '股四頭肌')
ORDER BY target_muscle, name
LIMIT 10;
