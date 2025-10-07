-- 檢查二分化隨機菜單需要的動作
-- 確保每個肌群都有足夠的安全動作供隨機選擇

-- 1. 檢查新手友善動作的可用性
SELECT 
    '肌群動作統計' as type,
    target_muscle as muscle_group,
    COUNT(*) as total_count,
    SUM(CASE 
        WHEN name IN (
            '平板槓鈴臥推', '啞鈴臥推', '胸推機', '伏地挺身',
            '上斜啞鈴臥推', '上斜槓鈴臥推'
        ) THEN 1 ELSE 0 
    END) as chest_safe_count,
    GROUP_CONCAT(CASE 
        WHEN name IN (
            '平板槓鈴臥推', '啞鈴臥推', '胸推機', '伏地挺身',
            '上斜啞鈴臥推', '上斜槓鈴臥推'
        ) THEN name ELSE NULL END SEPARATOR ', ') as chest_safe_exercises
FROM exercises 
WHERE target_muscle = '中胸'
GROUP BY target_muscle

UNION ALL

SELECT 
    '肌群動作統計' as type,
    target_muscle as muscle_group,
    COUNT(*) as total_count,
    SUM(CASE 
        WHEN name IN (
            '高位下拉', '坐姿划船', '槓鈴划船', '啞鈴划船',
            '引體向上', '反向划船'
        ) THEN 1 ELSE 0 
    END) as back_safe_count,
    GROUP_CONCAT(CASE 
        WHEN name IN (
            '高位下拉', '坐姿划船', '槓鈴划船', '啞鈴划船',
            '引體向上', '反向划船'
        ) THEN name ELSE NULL END SEPARATOR ', ') as back_safe_exercises
FROM exercises 
WHERE target_muscle IN ('上背', '中背')
GROUP BY target_muscle

ORDER BY muscle_group;

-- 2. 檢查所有需要的肌群是否有動作
SELECT 
    '肌群動作可用性' as check_type,
    muscle_group,
    COUNT(*) as available_exercises,
    CASE 
        WHEN COUNT(*) >= 2 THEN '✅ 充足'
        WHEN COUNT(*) = 1 THEN '⚠️ 僅1個'
        ELSE '❌ 無動作'
    END as status,
    GROUP_CONCAT(name SEPARATOR ', ') as exercise_list
FROM exercises 
WHERE target_muscle IN ('中胸', '上背', '中背', '肩膀前束', '肩膀中束', '二頭肌', 
                       '股四頭肌', '股二頭肌', '臀肌', '核心')
GROUP BY target_muscle
ORDER BY target_muscle;
