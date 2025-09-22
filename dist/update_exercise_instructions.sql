-- 動作文字敘述補齊腳本
-- 1) 若無欄位，先新增 instruction_full(完整說明)、instruction_cues(重點/注意事項)
-- 2) 依照常見教學標準補齊各動作的中文敘述與重點

USE test;

-- 新增欄位（若尚未存在）
ALTER TABLE exercises 
  ADD COLUMN IF NOT EXISTS instruction_full TEXT NULL,
  ADD COLUMN IF NOT EXISTS instruction_cues TEXT NULL,
  ADD COLUMN IF NOT EXISTS instruction_short TEXT NULL;

-- 工具: instruction_cues 提供簡短實用的動作提示，用逗號分隔

-- ====== 拉繩/啞鈴常見動作 ======
UPDATE exercises SET 
  instruction_full = '把拉繩把手固定於胸部兩側偏後，手肘微彎。由胸部用力夾向身前，動作頂端保持胸肌擠壓1秒，再控制張開回到起始位置，全程保持肩胛微內收、下沉。',
  instruction_cues = '胸口抬起、肩胛後收、手肘固定、控制離心',
  instruction_short = '手肘微彎，胸部夾向身前，肩胛後收下沉'
WHERE name = '拉繩飛鳥';

UPDATE exercises SET 
  instruction_full = '拉繩固定於低點，身體微向前站穩，手肘微彎。由下往上呈弧線夾向胸前與下巴間高度，感受上胸收縮，頂端停1秒後緩慢回放。',
  instruction_cues = '上胸發力、核心收穩、勿聳肩、控制回程',
  instruction_short = '由下往上弧線夾胸，上胸發力，核心收穩'
WHERE name = '下往上拉繩飛鳥';

UPDATE exercises SET 
  instruction_full = '坐姿挺胸，雙腳踩穩，握住把手後上背保持緊繃。以背闊肌帶動手肘向身體後方拉至腹部或肚臍前，頂端停1秒，再慢慢還原。',
  instruction_cues = '挺胸、肩胛內收、背發力、拉至下腹',
  instruction_short = '挺胸坐穩，背闊肌發力拉至腹部，肩胛內收'
WHERE name = '坐姿拉繩划船';

UPDATE exercises SET 
  instruction_full = '站姿與拉力器面向，手臂伸直握桿，胸口抬起。用闊背與背闊肌下束把桿向髖部方向下拉，手肘維持伸直或微彎，頂端停1秒後慢慢回放。',
  instruction_cues = '手臂伸直、軀幹穩定、向髖部下拉',
  instruction_short = '手臂伸直，闊背發力向髖部下拉，軀幹穩定'
WHERE name = '直臂下拉';

UPDATE exercises SET 
  instruction_full = '站姿或坐姿皆可，手肘微彎、手臂向側方舉起至與地面平行或略低，感受三角肌中束收縮，再控制下降。重量以可控為主。',
  instruction_cues = '小指微高、不要聳肩、至肩平、控制擺盪',
  instruction_short = '手肘微彎側舉至肩平，小指微高，不要聳肩'
WHERE name IN ('側平舉（拉繩）','啞鈴側平舉');

UPDATE exercises SET 
  instruction_full = '站姿穩定，上臂貼身不晃動，手肘固定軌跡做彎舉至前臂與地面垂直或略過，再控制下降至手臂幾乎伸直但不鎖死。',
  instruction_cues = '上臂貼身、肘固定、全程控制、勿借力',
  instruction_short = '上臂貼身，手肘固定軌跡彎舉，全程控制'
WHERE name IN ('拉繩二頭彎舉','啞鈴二頭彎舉','啞鈴錘式彎舉','槓鈴二頭彎舉');

UPDATE exercises SET 
  instruction_full = '以三頭肌發力伸直手肘。下拉版本：手肘貼身向下伸直並擠壓三頭；頭上版本：手肘固定於頭部兩側向上伸直。動作底端控制回程。',
  instruction_cues = '手肘固定、上臂不晃、三頭擠壓、勿聳肩',
  instruction_short = '手肘固定，三頭肌發力伸直，上臂不晃動'
WHERE name IN ('拉繩下壓','頭上拉繩伸展','啞鈴三頭伸展（頭上）','槓鈴窄握臥推');

UPDATE exercises SET 
  instruction_full = '站姿斜跨拉繩，由上往下或由下往上做對角線劈砍軌跡，軀幹保持穩定，由核心與肩帶帶動，終點停1秒後回程。',
  instruction_cues = '核心收緊、骨盆穩、弧線路徑、控制速度',
  instruction_short = '核心收緊，對角線劈砍軌跡，軀幹穩定'
WHERE name = '拉繩劈木（斧頭劈砍）';

UPDATE exercises SET 
  instruction_full = '拉繩固定於高位，雙手抱握拉繩置於額前或胸前，利用腹直肌卷曲上身，將肋骨向骨盆收攏，頂端停1秒，控制回放。',
  instruction_cues = '骨盆穩、肋骨下收、腹部發力、控制離心',
  instruction_short = '肋骨向骨盆收攏，用腹部發力，骨盆穩定'
WHERE name = '拉繩捲腹';

UPDATE exercises SET 
  instruction_full = '平躺、肩胛收緊下沉，肩胛穩定後將槓／啞鈴推至手肘伸直，下降至上臂約45度或槓接近胸部，再推起至頂端擠壓胸肌。',
  instruction_cues = '肩胛夾緊、腳踩穩、槓路垂直、勿彈胸',
  instruction_short = '肩胛收緊下沉，推至手肘伸直，下降至胸部'
WHERE name IN ('啞鈴臥推','槓鈴臥推','史密斯臥推');

UPDATE exercises SET 
  instruction_full = '長凳上調至30–45度，上胸為主。肩胛收緊下沉後，將槓／啞鈴由鎖骨下方推起至手肘伸直，再控制下降至胸上方。',
  instruction_cues = '上胸為主、勿聳肩、肩胛穩、斜向推起',
  instruction_short = '上胸為主，鎖骨下方推起，肩胛穩定'
WHERE name IN ('上斜槓鈴臥推','史密斯上斜臥推');

UPDATE exercises SET 
  instruction_full = '身體前傾或胸貼凳，背部保持中立，將重量拉向下胸或肚臍位置，頂端夾緊肩胛後慢慢下降。',
  instruction_cues = '背闊發力、肩胛回收、肘45度、軀幹穩定',
  instruction_short = '背闊發力拉至肚臍，肩胛回收，軀幹穩定'
WHERE name IN ('啞鈴划船','單手啞鈴划船','槓鈴划船','史密斯彎腰划船');

-- ====== 下肢動作 ======
UPDATE exercises SET 
  instruction_full = '站距與髖同寬或略寬，深吸氣、核心收穩，屈髖屈膝下蹲至大腿與地面平行或更低，膝蓋跟隨腳尖方向。由腳跟與股四頭、臀肌發力站起。',
  instruction_cues = '膝蓋向外、背部中立、腳跟踩穩、核心張力',
  instruction_short = '膝蓋跟腳尖方向，核心收穩，腳跟發力站起'
WHERE name IN ('啞鈴深蹲','槓鈴深蹲','史密斯深蹲');

UPDATE exercises SET 
  instruction_full = '槓置鎖骨與前三角位置，肘抬高、胸口抬起，下蹲時軀幹更直立，重心維持在中足，起身時膝蓋與髖同時伸展。',
  instruction_cues = '肘抬高、核心緊、膝蓋跟腳尖、保持直立',
  instruction_short = '槓置鎖骨前，肘抬高，軀幹直立下蹲'
WHERE name IN ('前蹲（槓鈴前蹲）','史密斯前蹲');

UPDATE exercises SET 
  instruction_full = '向前跨一步降低身體至後膝接近地面，前腳腳跟踩穩、膝蓋與腳尖同方向；推回起始位置後換腳。',
  instruction_cues = '步幅穩定、軀幹直立、膝蓋不內扣、控制回程',
  instruction_short = '前腳跟踩穩，膝蓋與腳尖同方向，軀幹直立'
WHERE name IN ('啞鈴弓步蹲','史密斯弓步蹲');

UPDATE exercises SET 
  instruction_full = '髖主導後移，沿著大腿前側下滑至槓(或啞鈴)至膝下，背部中立，腿後側與臀部發力將身體拉回伸展位。',
  instruction_cues = '髖折疊、膝微彎、背中立、槓貼腿',
  instruction_short = '髖主導後移，槓貼腿下滑，腿後肌群發力'
WHERE name IN ('啞鈴羅馬尼亞硬舉','史密斯硬舉（直腿）','槓鈴硬舉');

UPDATE exercises SET 
  instruction_full = '上背靠長椅邊緣，槓或啞鈴置於髖摺處，雙腳與肩同寬。由臀肌上推至身體與大腿呈一直線，在頂端擠壓臀肌1–2秒後再下降。',
  instruction_cues = '下巴內收、骨盆中立、膝蓋外推、頂端擠壓',
  instruction_short = '臀肌上推至身體與大腿一直線，頂端擠壓'
WHERE name IN ('槓鈴臀推（Hip Thrust）','史密斯臀推');

UPDATE exercises SET 
  instruction_full = '踩穩站姿，僅以踝關節做伸展讓腳跟上提至最高，再緩慢下降至小腿完全延展，過程可扶物輔助平衡。',
  instruction_cues = '全程伸縮、頂端停1秒、勿左右晃、膝蓋伸直',
  instruction_short = '腳跟上提至最高，頂端停1秒，控制下降'
WHERE name IN ('啞鈴側彎' /*腹斜訓練保留既有描述*/,'史密斯提踵');

-- ====== 肩推/肩帶 ======
UPDATE exercises SET 
  instruction_full = '坐姿或站姿皆可，核心收穩、臀部夾緊，將槓/啞鈴由肩前推至手肘完全伸直，下降至下巴或鎖骨上方位置再推起。',
  instruction_cues = '肘略在前、直上直下、勿聳肩、核心穩定',
  instruction_short = '肩前推至手肘伸直，路徑直上直下，核心穩定'
WHERE name IN ('槓鈴推舉','史密斯肩推');

-- ====== 其他常見動作 ======
-- 伏地挺身系列
UPDATE exercises SET 
  instruction_full = '保持身體成一直線，手肘外展約45度，核心收緊，屈肘下降至胸部接近地面，再推起至手肘伸直。',
  instruction_cues = '身體一直線、手肘外展45度、核心收緊',
  instruction_short = '身體一直線，手肘外展45度，核心收緊'
WHERE name = '伏地挺身';

UPDATE exercises SET 
  instruction_full = '先確保基本伏地挺身熟練，再進行爆發推離地面，可加入拍手動作，落地時緩衝。',
  instruction_cues = '爆發推離、拍手可選、落地緩衝、核心穩定',
  instruction_short = '爆發推離地面，可拍手，落地緩衝'
WHERE name = '爆發式伏地挺身';

-- 啞鈴飛鳥
UPDATE exercises SET 
  instruction_full = '平躺，啞鈴舉至胸前，手肘微彎，以胸肌發力將啞鈴向兩側張開至胸部有拉伸感，再夾回胸前。',
  instruction_cues = '手肘微彎、胸肌發力、控制張開、夾回胸前',
  instruction_short = '手肘微彎，胸肌發力張開，再夾回胸前'
WHERE name = '啞鈴飛鳥';

-- 啞鈴深蹲
UPDATE exercises SET 
  instruction_full = '雙手持啞鈴於胸前或肩部，雙腳與肩同寬，核心收緊，下蹲至大腿與地面平行，再站起。',
  instruction_cues = '核心收緊、膝蓋跟腳尖、下蹲至平行、腳跟發力',
  instruction_short = '核心收緊，下蹲至平行，腳跟發力站起'
WHERE name = '啞鈴深蹲';

-- 啞鈴側彎
UPDATE exercises SET 
  instruction_full = '站姿，一手持啞鈴，身體向持啞鈴側彎曲，感受側腹拉伸，再回到中立位置。',
  instruction_cues = '側腹發力、軀幹穩定、控制彎曲、勿前後傾',
  instruction_short = '側腹發力彎曲，軀幹穩定，控制動作'
WHERE name = '啞鈴側彎';

-- ====== 補充其他常見動作的提示 ======
-- 低位夾胸
UPDATE exercises SET 
  instruction_full = '拉繩固定於低點，身體微向前站穩，手肘微彎。由下往上呈弧線夾向胸前，感受下胸收縮，頂端停1秒後緩慢回放。',
  instruction_cues = '下胸發力、核心收穩、勿聳肩、控制回程',
  instruction_short = '下胸發力，由下往上夾胸，核心收穩'
WHERE name = '低位夾胸（彈力繩/滑輪）';

-- 雙繩高位夾胸
UPDATE exercises SET 
  instruction_full = '拉繩固定於高點，身體微向後站穩，手肘微彎。由上往下呈弧線夾向胸前，感受上胸收縮，頂端停1秒後緩慢回放。',
  instruction_cues = '上胸發力、核心收穩、勿聳肩、控制回程',
  instruction_short = '上胸發力，由上往下夾胸，核心收穩'
WHERE name = '雙繩高位夾胸（拉繩）';

-- 上斜啞鈴胸推
UPDATE exercises SET 
  instruction_full = '長凳調至30-45度，上胸為主。肩胛收緊下沉後，將啞鈴由鎖骨下方推起至手肘伸直，再控制下降至胸上方。',
  instruction_cues = '上胸為主、勿聳肩、肩胛穩、斜向推起',
  instruction_short = '上胸為主，鎖骨下方推起，肩胛穩定'
WHERE name = '上斜啞鈴胸推';

-- 啞鈴平板胸推
UPDATE exercises SET 
  instruction_full = '平躺，肩胛收緊下沉，將啞鈴推至手肘伸直，下降至胸部有拉伸感，再推起至頂端擠壓胸肌。',
  instruction_cues = '肩胛夾緊、腳踩穩、啞鈴路徑垂直、勿彈胸',
  instruction_short = '肩胛收緊下沉，推至手肘伸直，下降至胸部'
WHERE name = '啞鈴平板胸推';

-- ====== 檢查結果 ======
SELECT name, target_muscle, instruction_full, instruction_cues, instruction_short
FROM exercises 
WHERE name IN (
  '拉繩飛鳥','下往上拉繩飛鳥','坐姿拉繩划船','直臂下拉','側平舉（拉繩）','啞鈴側平舉',
  '拉繩二頭彎舉','啞鈴二頭彎舉','啞鈴錘式彎舉','槓鈴二頭彎舉','拉繩下壓','頭上拉繩伸展','啞鈴三頭伸展（頭上）','槓鈴窄握臥推','拉繩劈木（斧頭劈砍）','拉繩捲腹',
  '啞鈴臥推','槓鈴臥推','史密斯臥推','上斜槓鈴臥推','史密斯上斜臥推','啞鈴飛鳥',
  '啞鈴划船','單手啞鈴划船','槓鈴划船','史密斯彎腰划船',
  '啞鈴深蹲','槓鈴深蹲','史密斯深蹲','前蹲（槓鈴前蹲）','史密斯前蹲',
  '啞鈴弓步蹲','史密斯弓步蹲','啞鈴羅馬尼亞硬舉','史密斯硬舉（直腿）','槓鈴硬舉',
  '槓鈴臀推（Hip Thrust）','史密斯臀推','史密斯提踵','啞鈴側彎',
  '槓鈴推舉','史密斯肩推','伏地挺身','爆發式伏地挺身'
)
ORDER BY name;


