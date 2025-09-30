-- 根據提供的79筆動作資料更新資料庫
-- 使用前請先備份資料庫！

USE test;

-- 第一步：先清空現有資料（可選，如果確定要完全重新開始）
-- DELETE FROM exercises;

-- 第二步：插入或更新所有79筆動作資料

-- 1. 肩膀動作 - 三角肌前束
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('啞鈴肩推', '肩膀前束', '使用啞鈴的肩推動作，主要鍛鍊三角肌前束', '初級', '啞鈴'),
('機械肩膀推舉器', '肩膀前束', '使用機械式肩膀推舉器，安全且穩定', '初級', '肩膀推舉器'),
('側肩上舉訓練機', '肩膀前束', '使用側肩上舉訓練機的推舉動作', '初級', '側肩上舉訓練機'),
('奧林匹克訓練台版', '肩膀前束', '奧林匹克訓練台版的推舉動作', '中級', '奧林匹克訓練台'),
('實力推', '肩膀前束', '實力推動作，鍛鍊三角肌前束', '中級', '槓鈴'),
('對握啞鈴推舉', '肩膀前束', '對握啞鈴推舉，鍛鍊三角肌前束', '中級', '啞鈴'),
('啞鈴前平舉', '肩膀前束', '啞鈴前平舉，針對三角肌前束', '初級', '啞鈴'),
('cable前平舉', '肩膀前束', '使用纜繩的前平舉動作', '初級', '纜繩機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 2. 肩膀動作 - 三角肌中束
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('啞鈴飛鳥', '肩膀中束', '啞鈴飛鳥動作，鍛鍊三角肌中束', '初級', '啞鈴'),
('阿諾肩推', '肩膀中束', '阿諾肩推，鍛鍊三角肌中束', '中級', '啞鈴'),
('直立划船', '肩膀中束', '直立划船動作，鍛鍊三角肌中束', '中級', '槓鈴'),
('cable側平舉', '肩膀中束', '使用纜繩的側平舉動作', '初級', '纜繩機'),
('側平舉（拉繩）', '肩膀中束', '使用拉繩的側平舉動作', '初級', '拉繩'),
('啞鈴側平舉', '肩膀中束', '啞鈴側平舉，針對三角肌中束', '初級', '啞鈴')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 3. 肩膀動作 - 三角肌後束
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('反向飛鳥', '肩膀後束', '反向飛鳥動作，鍛鍊三角肌後束', '初級', '啞鈴'),
('繩索面拉', '肩膀後束', '繩索面拉動作，鍛鍊三角肌後束', '初級', '纜繩機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 4. 胸部動作 - 上胸肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('雙功能蝴蝶訓練器', '上胸', '雙功能蝴蝶訓練器，鍛鍊上胸肌和中胸肌', '初級', '雙功能蝴蝶訓練器'),
('奧林匹克上斜推舉台板', '上胸', '奧林匹克上斜推舉台板，鍛鍊上胸肌', '中級', '奧林匹克上斜推舉台'),
('cable上斜臥推', '上胸', '使用纜繩的上斜臥推動作', '初級', '纜繩機'),
('史密斯上斜臥推', '上胸', '史密斯機上斜臥推，鍛鍊上胸肌', '初級', '史密斯機'),
('上斜啞鈴胸推', '上胸', '上斜啞鈴胸推，鍛鍊上胸肌', '初級', '啞鈴和上斜椅'),
('拉繩飛鳥', '上胸', '拉繩飛鳥動作，鍛鍊上胸肌和中胸肌', '初級', '拉繩'),
('下往上拉繩飛鳥', '上胸', '下往上拉繩飛鳥，鍛鍊上胸肌', '初級', '拉繩'),
('上斜槓鈴臥推', '上胸', '上斜槓鈴臥推，鍛鍊胸大肌上部', '中級', '槓鈴和上斜椅'),
('史密斯上斜臥推', '上胸', '史密斯機上斜臥推，鍛鍊胸大肌上部', '初級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 5. 胸部動作 - 中胸肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('合式胸部推舉器', '中胸', '合式胸部推舉器，鍛鍊中胸肌', '初級', '合式胸部推舉器'),
('蝴蝶機', '中胸', '蝴蝶機動作，鍛鍊中胸肌', '初級', '蝴蝶機'),
('坐式推胸訓練器', '中胸', '坐式推胸訓練器，鍛鍊中胸肌', '初級', '坐式推胸訓練器'),
('分動式寬距部推舉器', '中胸', '分動式寬距部推舉器，鍛鍊中胸肌', '初級', '分動式寬距部推舉器'),
('奧林匹克平躺台板', '中胸', '奧林匹克平躺台板，鍛鍊中胸肌', '中級', '奧林匹克平躺台'),
('分動式水平機', '中胸', '分動式水平機，鍛鍊中胸肌', '初級', '分動式水平機'),
('史密斯平板握推', '中胸', '史密斯機平板握推，鍛鍊中胸肌', '初級', '史密斯機'),
('啞鈴平板胸推', '中胸', '啞鈴平板胸推，鍛鍊中胸肌', '初級', '啞鈴'),
('低位夾胸（彈力繩/滑輪）', '中胸', '低位夾胸動作，鍛鍊中胸肌', '初級', '彈力繩或滑輪'),
('啞鈴臥推', '中胸', '啞鈴臥推，鍛鍊胸大肌', '初級', '啞鈴'),
('啞鈴飛鳥', '中胸', '啞鈴飛鳥，鍛鍊胸大肌', '初級', '啞鈴'),
('槓鈴臥推', '中胸', '槓鈴臥推，鍛鍊胸大肌', '中級', '槓鈴'),
('史密斯臥推', '中胸', '史密斯機臥推，鍛鍊胸大肌', '初級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 6. 胸部動作 - 下胸肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('奧林匹克下斜推舉台板', '下胸', '奧林匹克下斜推舉台板，鍛鍊下胸肌', '中級', '奧林匹克下斜推舉台'),
('槓桿式下斜臥推', '下胸', '槓桿式下斜臥推，鍛鍊下胸肌', '中級', '槓桿式下斜臥推機'),
('雙槓撐體', '下胸', '雙槓撐體，鍛鍊下胸肌和三頭肌', '中級', '雙槓'),
('雙繩高位夾胸（拉繩）', '下胸', '雙繩高位夾胸，鍛鍊下胸肌', '初級', '拉繩')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 7. 胸部動作 - 胸肌/三頭肌（複合動作）
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('伏地挺身', '中胸', '伏地挺身，鍛鍊胸肌和三頭肌', '初級', '無'),
('爆發式伏地挺身', '中胸', '爆發式伏地挺身，鍛鍊胸肌和三頭肌', '中級', '無')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 8. 背部動作
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('坐姿拉繩划船', '中背', '坐姿拉繩划船，鍛鍊背部', '初級', '拉繩'),
('直臂下拉', '上背', '直臂下拉，鍛鍊背闊肌', '初級', '纜繩機'),
('啞鈴划船', '中背', '啞鈴划船，鍛鍊背闊肌和菱形肌', '初級', '啞鈴'),
('單手啞鈴划船', '中背', '單手啞鈴划船，鍛鍊背闊肌', '中級', '啞鈴'),
('槓鈴划船', '中背', '槓鈴划船，鍛鍊背闊肌和菱形肌', '中級', '槓鈴'),
('史密斯彎腰划船', '中背', '史密斯機彎腰划船，鍛鍊背闊肌和菱形肌', '中級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 9. 手臂動作 - 肱二頭肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('拉繩二頭彎舉', '二頭肌', '拉繩二頭彎舉，鍛鍊肱二頭肌', '初級', '拉繩'),
('啞鈴二頭彎舉', '二頭肌', '啞鈴二頭彎舉，鍛鍊肱二頭肌', '初級', '啞鈴'),
('啞鈴錘式彎舉', '二頭肌', '啞鈴錘式彎舉，鍛鍊二頭肌和前臂', '初級', '啞鈴'),
('槓鈴二頭彎舉', '二頭肌', '槓鈴二頭彎舉，鍛鍊肱二頭肌', '中級', '槓鈴')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 10. 手臂動作 - 肱三頭肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('拉繩下壓', '三頭肌', '拉繩下壓，鍛鍊肱三頭肌', '初級', '拉繩'),
('頭上拉繩伸展', '三頭肌', '頭上拉繩伸展，鍛鍊肱三頭肌長頭', '初級', '拉繩'),
('啞鈴三頭伸展（頭上）', '三頭肌', '啞鈴三頭伸展，鍛鍊肱三頭肌', '初級', '啞鈴'),
('槓鈴窄握臥推', '三頭肌', '槓鈴窄握臥推，鍛鍊肱三頭肌', '中級', '槓鈴')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 11. 腿部動作 - 股四頭肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('啞鈴深蹲', '股四頭肌', '啞鈴深蹲，鍛鍊股四頭肌和臀大肌', '初級', '啞鈴'),
('啞鈴弓步蹲', '股四頭肌', '啞鈴弓步蹲，鍛鍊股四頭肌和臀大肌', '初級', '啞鈴'),
('槓鈴深蹲', '股四頭肌', '槓鈴深蹲，鍛鍊股四頭肌和臀大肌', '中級', '槓鈴'),
('前蹲（槓鈴前蹲）', '股四頭肌', '前蹲動作，鍛鍊股四頭肌', '中級', '槓鈴'),
('史密斯深蹲', '股四頭肌', '史密斯機深蹲，鍛鍊股四頭肌和臀大肌', '初級', '史密斯機'),
('史密斯前蹲', '股四頭肌', '史密斯機前蹲，鍛鍊股四頭肌', '中級', '史密斯機'),
('史密斯弓步蹲', '股四頭肌', '史密斯機弓步蹲，鍛鍊股四頭肌和臀大肌', '中級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 12. 腿部動作 - 股二頭肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('啞鈴羅馬尼亞硬舉', '股二頭肌', '啞鈴羅馬尼亞硬舉，鍛鍊股二頭肌和臀大肌', '中級', '啞鈴'),
('槓鈴硬舉', '股二頭肌', '槓鈴硬舉，鍛鍊臀大肌、股二頭肌和背部', '中級', '槓鈴'),
('史密斯硬舉（直腿）', '股二頭肌', '史密斯機直腿硬舉，鍛鍊股二頭肌和臀大肌', '中級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 13. 腿部動作 - 臀大肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('槓鈴臀推（Hip Thrust）', '臀肌', '槓鈴臀推，鍛鍊臀大肌', '中級', '槓鈴'),
('史密斯臀推', '臀肌', '史密斯機臀推，鍛鍊臀大肌和股二頭肌', '中級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 14. 小腿動作
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('史密斯提踵', '小腿', '史密斯機提踵，鍛鍊小腿肌群', '初級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 15. 腹部動作 - 腹斜肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('拉繩劈木（斧頭劈砍）', '側腹', '拉繩劈木動作，鍛鍊腹斜肌', '中級', '拉繩'),
('啞鈴側彎', '側腹', '啞鈴側彎，鍛鍊腹斜肌', '初級', '啞鈴')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 16. 腹部動作 - 腹直肌
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('拉繩捲腹', '上腹', '拉繩捲腹，鍛鍊腹直肌', '初級', '拉繩')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 17. 複合動作 - 肩膀和手臂
INSERT INTO exercises (name, target_muscle, description, difficulty_level, equipment_needed) VALUES
('啞鈴肩推', '肩膀前束', '啞鈴肩推，鍛鍊三角肌前束和三角肌中束', '初級', '啞鈴'),
('槓鈴推舉', '肩膀前束', '槓鈴推舉，鍛鍊三角肌和肱三頭肌', '中級', '槓鈴'),
('史密斯肩推', '肩膀前束', '史密斯機肩推，鍛鍊三角肌和肱三頭肌', '中級', '史密斯機')
ON DUPLICATE KEY UPDATE 
target_muscle = VALUES(target_muscle),
description = VALUES(description),
difficulty_level = VALUES(difficulty_level),
equipment_needed = VALUES(equipment_needed);

-- 檢查結果
SELECT 
    target_muscle as '肌群分類', 
    COUNT(*) as '動作數量',
    GROUP_CONCAT(name ORDER BY name SEPARATOR ', ') as '動作列表'
FROM exercises 
GROUP BY target_muscle 
ORDER BY COUNT(*) DESC;

-- 顯示總動作數量
SELECT COUNT(*) as '總動作數量' FROM exercises;
