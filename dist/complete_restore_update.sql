-- 完整恢復和更新動作資料的 SQL 腳本
-- 使用前請先備份資料庫！

USE test;

-- 第一步：恢復所有動作到基本分類
-- 確保不會遺失任何動作

-- 1. 恢復胸部動作（包含所有可能的胸部動作）
UPDATE exercises SET target_muscle = '胸' WHERE 
    name LIKE '%胸%' OR 
    name LIKE '%臥推%' OR 
    name LIKE '%飛鳥%' OR 
    name LIKE '%伏地%' OR 
    name LIKE '%推%' OR
    name LIKE '%夾胸%' OR
    name LIKE '%蝴蝶%' OR
    name LIKE '%撐體%' OR
    name LIKE '%啞鈴%' OR
    name LIKE '%槓鈴%';

-- 2. 恢復肩膀動作
UPDATE exercises SET target_muscle = '肩膀' WHERE 
    name LIKE '%肩%' OR 
    name LIKE '%推舉%' OR 
    name LIKE '%平舉%' OR 
    name LIKE '%飛鳥%' OR
    name LIKE '%划船%' OR
    name LIKE '%面拉%' OR
    name LIKE '%奧林匹克%' OR
    name LIKE '%實力%' OR
    name LIKE '%阿諾%' OR
    name LIKE '%直立%';

-- 3. 恢復背部動作
UPDATE exercises SET target_muscle = '背' WHERE 
    name LIKE '%背%' OR 
    name LIKE '%划船%' OR 
    name LIKE '%下拉%' OR 
    name LIKE '%引體%' OR
    name LIKE '%硬舉%' OR
    name LIKE '%划%' OR
    name LIKE '%纜繩%' OR
    name LIKE '%坐姿%' OR
    name LIKE '%T槓%' OR
    name LIKE '%單臂%';

-- 4. 恢復腿部動作
UPDATE exercises SET target_muscle = '腿' WHERE 
    name LIKE '%腿%' OR 
    name LIKE '%深蹲%' OR 
    name LIKE '%弓箭步%' OR 
    name LIKE '%硬舉%' OR
    name LIKE '%蹲%' OR
    name LIKE '%推%' OR
    name LIKE '%彎舉%' OR
    name LIKE '%保加利亞%' OR
    name LIKE '%羅馬尼亞%' OR
    name LIKE '%相撲%' OR
    name LIKE '%登階%' OR
    name LIKE '%側蹲%';

-- 5. 恢復手臂動作
UPDATE exercises SET target_muscle = '手臂' WHERE 
    name LIKE '%二頭%' OR 
    name LIKE '%三頭%' OR 
    name LIKE '%彎舉%' OR 
    name LIKE '%撐體%' OR
    name LIKE '%下壓%' OR
    name LIKE '%伸展%' OR
    name LIKE '%錘式%' OR
    name LIKE '%過頭%' OR
    name LIKE '%纜繩%' OR
    name LIKE '%槓鈴%';

-- 6. 恢復腹部動作
UPDATE exercises SET target_muscle = '腹部' WHERE 
    name LIKE '%腹%' OR 
    name LIKE '%核心%' OR 
    name LIKE '%平板%' OR 
    name LIKE '%捲腹%' OR
    name LIKE '%支撐%' OR
    name LIKE '%死蟲%' OR
    name LIKE '%V字%' OR
    name LIKE '%登山%' OR
    name LIKE '%仰臥%' OR
    name LIKE '%抬腿%' OR
    name LIKE '%轉體%' OR
    name LIKE '%側%';

-- 第二步：細分胸部動作

-- 上胸部動作
UPDATE exercises SET target_muscle = '上胸' WHERE 
    (name LIKE '%上斜%' OR name LIKE '%上胸%') AND target_muscle = '胸';

-- 中胸部動作
UPDATE exercises SET target_muscle = '中胸' WHERE 
    (name LIKE '%平板%' OR name LIKE '%中胸%' OR 
     name IN ('胸推機', '蝴蝶機', '伏地挺身', '史密斯機臥推', '雙功能蝴蝶訓練器', '啞鈴地板臥推', '平板啞鈴臥推', '平板槓鈴臥推', '平板啞鈴飛鳥', '平板纜繩夾胸')) 
    AND target_muscle = '胸';

-- 下胸部動作
UPDATE exercises SET target_muscle = '下胸' WHERE 
    (name LIKE '%下斜%' OR name LIKE '%下胸%' OR name = '雙槓撐體') 
    AND target_muscle = '胸';

-- 第三步：細分肩膀動作

-- 肩膀前束動作
UPDATE exercises SET target_muscle = '肩膀前束' WHERE 
    (name LIKE '%推%' OR name LIKE '%推舉%' OR name LIKE '%前平舉%' OR name = '直立划船' OR
     name IN ('啞鈴肩推', '機械肩膀推舉器', '奧林匹克訓練台版', '實力推', '對握啞鈴推舉', '阿諾肩推', '肩推')) 
    AND target_muscle = '肩膀';

-- 肩膀中束動作
UPDATE exercises SET target_muscle = '肩膀中束' WHERE 
    (name LIKE '%側平舉%' OR name LIKE '%側舉%' OR name LIKE '%側%') 
    AND target_muscle = '肩膀';

-- 肩膀後束動作
UPDATE exercises SET target_muscle = '肩膀後束' WHERE 
    (name LIKE '%後平舉%' OR name LIKE '%後舉%' OR name LIKE '%反向%' OR name = '繩索面拉' OR name LIKE '%面拉%') 
    AND target_muscle = '肩膀';

-- 第四步：細分背部動作

-- 上背動作
UPDATE exercises SET target_muscle = '上背' WHERE 
    (name LIKE '%下拉%' OR name LIKE '%引體%' OR name LIKE '%纜繩下拉%') 
    AND target_muscle = '背';

-- 中背動作
UPDATE exercises SET target_muscle = '中背' WHERE 
    (name LIKE '%划船%' OR name LIKE '%划%' OR name LIKE '%坐姿%' OR name LIKE '%纜繩%' OR name LIKE '%T槓%' OR name LIKE '%單臂%') 
    AND target_muscle = '背';

-- 下背動作
UPDATE exercises SET target_muscle = '下背' WHERE 
    (name LIKE '%硬舉%' OR name = '反向划船' OR name LIKE '%羅馬尼亞%') 
    AND target_muscle = '背';

-- 第五步：細分手臂動作

-- 二頭肌動作
UPDATE exercises SET target_muscle = '二頭肌' WHERE 
    (name LIKE '%二頭%' OR name LIKE '%彎舉%' OR name LIKE '%錘式%') 
    AND target_muscle = '手臂';

-- 三頭肌動作
UPDATE exercises SET target_muscle = '三頭肌' WHERE 
    (name LIKE '%三頭%' OR name LIKE '%撐體%' OR name LIKE '%下壓%' OR name LIKE '%伸展%' OR name LIKE '%過頭%') 
    AND target_muscle = '手臂';

-- 第六步：細分腿部動作

-- 股四頭肌動作
UPDATE exercises SET target_muscle = '股四頭肌' WHERE 
    (name LIKE '%深蹲%' OR name LIKE '%腿推%' OR name LIKE '%登階%' OR name = '側蹲' OR name LIKE '%蹲%') 
    AND target_muscle = '腿';

-- 股二頭肌動作
UPDATE exercises SET target_muscle = '股二頭肌' WHERE 
    (name LIKE '%腿彎舉%' OR name LIKE '%羅馬尼亞%' OR name LIKE '%彎舉%') 
    AND target_muscle = '腿';

-- 臀肌動作
UPDATE exercises SET target_muscle = '臀肌' WHERE 
    (name LIKE '%弓箭步%' OR name LIKE '%相撲%' OR name LIKE '%臀%' OR name LIKE '%保加利亞%') 
    AND target_muscle = '腿';

-- 第七步：細分腹部動作

-- 上腹動作
UPDATE exercises SET target_muscle = '上腹' WHERE 
    (name LIKE '%仰臥起坐%' OR name LIKE '%捲腹%' OR name LIKE '%仰臥%') 
    AND target_muscle = '腹部';

-- 下腹動作
UPDATE exercises SET target_muscle = '下腹' WHERE 
    (name LIKE '%抬腿%' OR name LIKE '%舉腿%' OR name LIKE '%反向%') 
    AND target_muscle = '腹部';

-- 側腹動作
UPDATE exercises SET target_muscle = '側腹' WHERE 
    (name LIKE '%轉體%' OR name LIKE '%側%') 
    AND target_muscle = '腹部';

-- 核心動作
UPDATE exercises SET target_muscle = '核心' WHERE 
    (name LIKE '%平板%' OR name LIKE '%支撐%' OR name LIKE '%死蟲%' OR name LIKE '%V字%' OR name LIKE '%登山%') 
    AND target_muscle = '腹部';

-- 第八步：檢查結果

-- 顯示各肌群分類統計
SELECT 
    target_muscle as '肌群分類', 
    COUNT(*) as '動作數量'
FROM exercises 
GROUP BY target_muscle 
ORDER BY COUNT(*) DESC;

-- 顯示總動作數量
SELECT COUNT(*) as '總動作數量' FROM exercises;

-- 檢查是否有遺漏的動作（仍使用基本分類）
SELECT 
    id as 'ID',
    name as '動作名稱',
    target_muscle as '目前分類'
FROM exercises 
WHERE target_muscle IN ('胸', '肩膀', '背', '手臂', '腿', '腹部') 
ORDER BY target_muscle, name;

-- 顯示肩膀動作細分結果
SELECT 
    target_muscle as '肩膀分類',
    COUNT(*) as '動作數量',
    GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') as '動作列表'
FROM exercises 
WHERE target_muscle LIKE '%肩膀%' OR target_muscle LIKE '%束%'
GROUP BY target_muscle 
ORDER BY target_muscle;

-- 顯示胸部動作細分結果
SELECT 
    target_muscle as '胸部分類',
    COUNT(*) as '動作數量',
    GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') as '動作列表'
FROM exercises 
WHERE target_muscle LIKE '%胸%'
GROUP BY target_muscle 
ORDER BY target_muscle;
