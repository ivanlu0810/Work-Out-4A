-- 更新資料庫中 exercises 表的 target_muscle 欄位
-- 將大方向分類細分為更精確的肌群分類

USE test;

-- 更新胸部動作 - 細分為上胸、中胸、下胸
UPDATE exercises SET target_muscle = '上胸' WHERE name IN (
    '上斜啞鈴臥推', '上斜槓鈴臥推', '上斜啞鈴飛鳥', '上斜胸推機', '上斜纜繩夾胸'
);

UPDATE exercises SET target_muscle = '中胸' WHERE name IN (
    '平板啞鈴臥推', '平板槓鈴臥推', '平板啞鈴飛鳥', '胸推機', '蝴蝶機', 
    '伏地挺身', '史密斯機臥推', '平板纜繩夾胸', '啞鈴地板臥推'
);

UPDATE exercises SET target_muscle = '下胸' WHERE name IN (
    '下斜啞鈴臥推', '下斜槓鈴臥推', '下斜啞鈴飛鳥', '下斜胸推機', 
    '雙槓撐體', '下斜纜繩夾胸'
);

-- 特殊胸部動作保持為中胸
UPDATE exercises SET target_muscle = '中胸' WHERE name IN (
    '窄握臥推', '寬握臥推', '啞鈴擠壓推', '單臂啞鈴臥推'
);

-- 更新肩膀動作 - 細分為肩膀前束、肩膀中束、肩膀後束
UPDATE exercises SET target_muscle = '肩膀前束' WHERE name IN (
    '啞鈴肩推', '機械肩膀推舉器', '奧林匹克訓練台版', '實力推', 
    '對握啞鈴推舉', '阿諾肩推', '啞鈴前平舉', 'cable前平舉', '直立划船'
);

UPDATE exercises SET target_muscle = '肩膀中束' WHERE name IN (
    'cable側平舉', '側平舉'
);

UPDATE exercises SET target_muscle = '肩膀後束' WHERE name IN (
    '反向飛鳥', '繩索面拉', '纜繩後平舉'
);

-- 更新背部動作 - 細分為上背、中背、下背
UPDATE exercises SET target_muscle = '上背' WHERE name IN (
    '高位下拉', '引體向上', '纜繩下拉'
);

UPDATE exercises SET target_muscle = '中背' WHERE name IN (
    '纜繩划船', '坐姿划船', '槓鈴划船', 'T槓划船', '單臂划船'
);

UPDATE exercises SET target_muscle = '下背' WHERE name IN (
    '反向划船', '啞鈴划船', '硬舉', '羅馬尼亞硬舉'
);

-- 更新手臂動作 - 細分為二頭肌、三頭肌
UPDATE exercises SET target_muscle = '二頭肌' WHERE name IN (
    '二頭彎舉', '錘式彎舉', '纜繩彎舉', '槓鈴彎舉', '啞鈴彎舉'
);

UPDATE exercises SET target_muscle = '三頭肌' WHERE name IN (
    '三頭撐體', '三頭下壓', '過頭三頭伸展', '啞鈴三頭伸展', 
    '纜繩三頭下壓', '窄握臥推'
);

-- 更新腿部動作 - 細分為股四頭肌、股二頭肌、臀肌、小腿
UPDATE exercises SET target_muscle = '股四頭肌' WHERE name IN (
    '深蹲', '啞鈴深蹲', '槓鈴深蹲', '腿推機', '登階', '保加利亞分腿蹲'
);

UPDATE exercises SET target_muscle = '股二頭肌' WHERE name IN (
    '腿彎舉', '羅馬尼亞硬舉', '直腿硬舉'
);

UPDATE exercises SET target_muscle = '臀肌' WHERE name IN (
    '弓箭步', '側蹲', '相撲深蹲', '臀推', '單腿臀推'
);

UPDATE exercises SET target_muscle = '小腿' WHERE name IN (
    '提踵', '坐姿提踵', '單腿提踵'
);

-- 更新腹部動作 - 細分為上腹、下腹、側腹
UPDATE exercises SET target_muscle = '上腹' WHERE name IN (
    '仰臥起坐', '捲腹', '自行車捲腹'
);

UPDATE exercises SET target_muscle = '下腹' WHERE name IN (
    '仰臥抬腿', '懸垂舉腿', '反向捲腹'
);

UPDATE exercises SET target_muscle = '側腹' WHERE name IN (
    '俄羅斯轉體', '側捲腹', '側平板支撐'
);

UPDATE exercises SET target_muscle = '核心' WHERE name IN (
    '平板支撐', '死蟲式', 'V字支撐', '登山者'
);

-- 顯示更新結果
SELECT target_muscle, COUNT(*) as count 
FROM exercises 
GROUP BY target_muscle 
ORDER BY target_muscle;
