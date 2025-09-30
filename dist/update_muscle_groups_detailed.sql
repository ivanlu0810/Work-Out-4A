-- 詳細更新資料庫中 exercises 表的 target_muscle 欄位
-- 根據實際資料庫中的動作名稱進行細分

USE test;

-- 1. 更新胸部動作 - 細分為上胸、中胸、下胸

-- 上胸部動作
UPDATE exercises SET target_muscle = '上胸' WHERE name LIKE '%上斜%' OR name IN (
    '上斜啞鈴臥推', '上斜槓鈴臥推', '上斜啞鈴飛鳥', '上斜胸推機', '上斜纜繩夾胸'
);

-- 中胸部動作
UPDATE exercises SET target_muscle = '中胸' WHERE name LIKE '%平板%' OR name IN (
    '平板啞鈴臥推', '平板槓鈴臥推', '平板啞鈴飛鳥', '胸推機', '蝴蝶機', 
    '伏地挺身', '史密斯機臥推', '平板纜繩夾胸', '啞鈴地板臥推', '雙功能蝴蝶訓練器'
);

-- 下胸部動作
UPDATE exercises SET target_muscle = '下胸' WHERE name LIKE '%下斜%' OR name IN (
    '下斜啞鈴臥推', '下斜槓鈴臥推', '下斜啞鈴飛鳥', '下斜胸推機', 
    '雙槓撐體', '下斜纜繩夾胸'
);

-- 特殊胸部動作（根據動作特點分類）
UPDATE exercises SET target_muscle = '中胸' WHERE name IN (
    '窄握臥推', '寬握臥推', '啞鈴擠壓推', '單臂啞鈴臥推'
);

-- 2. 更新肩膀動作 - 細分為前束、中束、後束

-- 前束動作（推舉類）
UPDATE exercises SET target_muscle = '肩膀前束' WHERE name LIKE '%推%' OR name LIKE '%推舉%' OR name IN (
    '啞鈴肩推', '機械肩膀推舉器', '奧林匹克訓練台版', '實力推', 
    '對握啞鈴推舉', '阿諾肩推', '肩推'
);

-- 前束動作（前平舉類）
UPDATE exercises SET target_muscle = '肩膀前束' WHERE name LIKE '%前平舉%' OR name LIKE '%前舉%' OR name IN (
    '啞鈴前平舉', 'cable前平舉', '直立划船'
);

-- 中束動作（側平舉類）
UPDATE exercises SET target_muscle = '肩膀中束' WHERE name LIKE '%側平舉%' OR name LIKE '%側舉%' OR name IN (
    'cable側平舉', '側平舉', '啞鈴側平舉'
);

-- 後束動作（後平舉類）
UPDATE exercises SET target_muscle = '肩膀後束' WHERE name LIKE '%後平舉%' OR name LIKE '%後舉%' OR name LIKE '%反向%' OR name IN (
    '反向飛鳥', '繩索面拉', '纜繩後平舉', '後平舉'
);

-- 3. 更新背部動作 - 細分為上背、中背、下背

-- 上背動作（下拉類）
UPDATE exercises SET target_muscle = '上背' WHERE name LIKE '%下拉%' OR name LIKE '%引體%' OR name IN (
    '高位下拉', '引體向上', '纜繩下拉', '寬握引體向上', '窄握引體向上'
);

-- 中背動作（划船類）
UPDATE exercises SET target_muscle = '中背' WHERE name LIKE '%划船%' OR name LIKE '%划%' OR name IN (
    '纜繩划船', '坐姿划船', '槓鈴划船', 'T槓划船', '單臂划船', '啞鈴划船'
);

-- 下背動作（硬舉類）
UPDATE exercises SET target_muscle = '下背' WHERE name LIKE '%硬舉%' OR name LIKE '%反向%' OR name IN (
    '反向划船', '硬舉', '羅馬尼亞硬舉', '直腿硬舉'
);

-- 4. 更新手臂動作 - 細分為二頭肌、三頭肌

-- 二頭肌動作
UPDATE exercises SET target_muscle = '二頭肌' WHERE name LIKE '%二頭%' OR name LIKE '%彎舉%' OR name IN (
    '二頭彎舉', '錘式彎舉', '纜繩彎舉', '槓鈴彎舉', '啞鈴彎舉', '纜繩彎舉'
);

-- 三頭肌動作
UPDATE exercises SET target_muscle = '三頭肌' WHERE name LIKE '%三頭%' OR name LIKE '%撐體%' OR name LIKE '%下壓%' OR name LIKE '%伸展%' OR name IN (
    '三頭撐體', '三頭下壓', '過頭三頭伸展', '啞鈴三頭伸展', 
    '纜繩三頭下壓', '窄握臥推'
);

-- 5. 更新腿部動作 - 細分為股四頭肌、股二頭肌、臀肌、小腿

-- 股四頭肌動作（深蹲類）
UPDATE exercises SET target_muscle = '股四頭肌' WHERE name LIKE '%深蹲%' OR name LIKE '%腿推%' OR name LIKE '%登階%' OR name IN (
    '深蹲', '啞鈴深蹲', '槓鈴深蹲', '腿推機', '登階', '保加利亞分腿蹲', '側蹲'
);

-- 股二頭肌動作（彎舉類）
UPDATE exercises SET target_muscle = '股二頭肌' WHERE name LIKE '%腿彎舉%' OR name LIKE '%彎舉%' OR name IN (
    '腿彎舉', '羅馬尼亞硬舉', '直腿硬舉'
);

-- 臀肌動作
UPDATE exercises SET target_muscle = '臀肌' WHERE name LIKE '%弓箭步%' OR name LIKE '%相撲%' OR name LIKE '%臀%' OR name IN (
    '弓箭步', '相撲深蹲', '臀推', '單腿臀推', '保加利亞分腿蹲'
);

-- 小腿動作
UPDATE exercises SET target_muscle = '小腿' WHERE name LIKE '%提踵%' OR name LIKE '%踮腳%' OR name IN (
    '提踵', '坐姿提踵', '單腿提踵'
);

-- 6. 更新腹部動作 - 細分為上腹、下腹、側腹、核心

-- 上腹動作
UPDATE exercises SET target_muscle = '上腹' WHERE name LIKE '%仰臥起坐%' OR name LIKE '%捲腹%' OR name IN (
    '仰臥起坐', '捲腹', '自行車捲腹'
);

-- 下腹動作
UPDATE exercises SET target_muscle = '下腹' WHERE name LIKE '%抬腿%' OR name LIKE '%舉腿%' OR name LIKE '%反向%' OR name IN (
    '仰臥抬腿', '懸垂舉腿', '反向捲腹'
);

-- 側腹動作
UPDATE exercises SET target_muscle = '側腹' WHERE name LIKE '%轉體%' OR name LIKE '%側%' OR name IN (
    '俄羅斯轉體', '側捲腹', '側平板支撐'
);

-- 核心動作
UPDATE exercises SET target_muscle = '核心' WHERE name LIKE '%平板%' OR name LIKE '%支撐%' OR name LIKE '%死蟲%' OR name LIKE '%V字%' OR name LIKE '%登山%' OR name IN (
    '平板支撐', '死蟲式', 'V字支撐', '登山者'
);

-- 7. 顯示更新結果
SELECT 
    target_muscle, 
    COUNT(*) as count,
    GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') as exercises
FROM exercises 
GROUP BY target_muscle 
ORDER BY target_muscle;

-- 8. 檢查是否有遺漏的動作
SELECT id, name, target_muscle 
FROM exercises 
WHERE target_muscle IN ('胸', '肩膀', '背', '手臂', '腿', '腹部')
ORDER BY target_muscle, name;
