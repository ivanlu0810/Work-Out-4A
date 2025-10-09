# 飲食指南系統流程圖（Flow & Sequence）

> 本文件描述「飲食指南」模組的整體流程、資料流向與前後端互動序列。採用 Mermaid 繪圖語法，風格與 `training-plan-flow.md` 對齊。

---

## 1. 系統總覽（High‑level Flow）

```mermaid
flowchart LR
  A[使用者]-- 開啟頁面 -->B[前端 UI\ndiet-guide.html]
  B-- 取得食物/營養資料 -->C[(後端 API\nget_foods.php)]
  B-- 讀取使用者資料/目標/偏好 -->D[(後端 API\nget_user_profile.php)]
  B-- 產生/推薦餐單 -->E[餐單產生器 JS 邏輯]
  E-- 產出 weeklyMeals -->B
  B-- 渲染每日餐次/營養摘要 -->A
  B-- 儲存餐單/紀錄 -->F[(後端 API\nsave_meal_plan.php@POST)]
  F-- 寫入 -->G[(DB: meal_plans, meal_items)]
  C-- 讀取 -->H[(DB: foods, nutrients)]
  D-- 讀取 -->I[(DB: users, user_targets, preferences, inbody_records)]
```

---

## 2. 資料流與資料模型（Data Flow / Models）

- foods（食物主檔）
  - 核心欄位：`id`, `name`, `brand?`, `serving_size_g`, `kcal`, `protein_g`, `carb_g`, `fat_g`, `sugar_g`, `fiber_g`, `sodium_mg`, `tags[]`
- nutrients（可選：微量營養素）
  - 範例欄位：`vitamin_a_iu`, `vitamin_c_mg`, `calcium_mg`, `iron_mg`, ...
- user_targets（使用者目標）
  - 核心欄位：`user_id`, `kcal_target`, `protein_target_g`, `carb_target_g`, `fat_target_g`, `meals_per_day`, `diet_type`, `allergens[]`, `dislikes[]`
- preferences（使用者偏好/限制）
  - 核心欄位：`user_id`, `budget_level`, `cooking_time_min`, `cuisine`, `avoid_ingredients[]`
- meal_plans（餐單主檔）
  - 核心欄位：`id`, `user_id`, `week_start_date`, `week_number`, `plan_name`, `meals(JSON)`
  - `meals`（weeklyMeals）範例：
    ```json
    {
      "monday": {
        "breakfast": [
          {"foodId": 101, "name": "希臘優格", "serving": 200, "kcal": 130, "protein_g": 20},
          {"foodId": 205, "name": "藍莓", "serving": 80, "kcal": 45}
        ],
        "lunch": [ ... ],
        "dinner": [ ... ],
        "snacks": [ ... ],
        "summary": {"kcal": 1850, "protein_g": 135, "carb_g": 190, "fat_g": 55}
      },
      "tuesday": { ... }
    }
    ```

---

## 3. 頁面載入流程（Page Init）

```mermaid
sequenceDiagram
  autonumber
  participant U as 使用者
  participant FE as 前端 UI (diet-guide.html)
  participant API1 as get_foods.php
  participant API2 as get_user_profile.php
  participant API3 as save_meal_plan.php
  participant DB as MySQL

  U->>FE: 開啟飲食指南頁面
  FE->>API1: 取得食物與營養資料 (GET)
  API1->>DB: SELECT foods(+nutrients)
  DB-->>API1: 食物/營養列表
  API1-->>FE: JSON(foods)
  FE->>API2: 讀取使用者資料/目標/偏好 (GET)
  API2->>DB: SELECT user, inbody, targets, preferences
  DB-->>API2: 使用者/體組成/目標/偏好
  API2-->>FE: JSON(profile, inbody, targets, preferences)
  FE->>FE: 初始化 weeklyMeals（若無歷史則建立預設/空白）
  FE->>U: 渲染每日餐次與營養目標對照
```

---

## 3b. 使用者操作流程（User Journey）

```mermaid
flowchart TD
  UA[開啟頁面並看到 InBody 匯入成功提示]
  UA-->UB[填入/確認 年齡、性別、身高、體重、活動強度、健身目標]
  UB-->UC[點擊「計算營養需求」→ 取得 kcal/三大營養素目標]
  UC-->UD[檢視本週餐單與營養目標對照]

  UD-->VE[搜尋食物關鍵字]
  UD-->VF[按「所有類別」進行類別篩選]
  VE-->VG[點擊食物卡片「加入」選擇 餐次/份量]
  VF-->VG
  VG-->VH[即時計算並更新：每日/每週 kcal、蛋白、碳水、脂肪]
  VH-->VI{是否達標?}
  VI-- 否：自動建議高蛋白/高纖或同類替代品 -->VE
  VI-- 是：可進行儲存/導出 -->VJ[點擊「儲存餐單」]
  VJ-->VK[成功提示]
```

---

## 4. 餐單產生（規則/推薦/替換）

```mermaid
flowchart TD
  A[使用者操作]-- 選擇產生模式 -->B{模式}
  B-- 規則產生 -->C[generateMealsByTargets]
  B-- 推薦菜單 -->D[applyRecommendedMenus]
  B-- 單品替換 -->E[replaceMealItem]
  B-- 手動輸入 -->J[manualAddCustomFood]

  C-->F[依 kcal/三大營養素目標分配到各餐]
  F-->G[套用偏好/過敏/飲食型態過濾]
  D-->G
  E-->G
  J-->G
  G-->H[計算每日/每週營養匯總]
  H-->I[renderWeeklyMeals]
  I-->A
```

補充：
- 規則產生：
  - 以 `meals_per_day` 比例分配 `kcal/protein/carb/fat` 至各餐
  - 來源食物依 `diet_type`、`allergens`、`dislikes`、`avoid_ingredients` 過濾
  - 價格/烹調時間/菜系可作為排序權重
- 推薦菜單：
  - 以預設模組（減脂/增肌/均衡）對齊目標再微調份量
- 單品替換：
  - 維持餐次營養結構，重新挑選同類食物替代並回算份量

---

## 5. 儲存與同步（Save & Sync）

```mermaid
sequenceDiagram
  autonumber
  participant U as 使用者
  participant FE as 前端 UI
  participant API as save_meal_plan.php (POST)
  participant DB as MySQL

  U->>FE: 點擊「儲存餐單」
  FE->>API: POST weeklyMeals + week meta
  API->>DB: INSERT/UPDATE meal_plans & meal_items
  DB-->>API: OK/Err
  API-->>FE: JSON(success/err)
  FE->>U: 成功/失敗提示 (Toast/Alert)
```

---

## 6. 例外處理（Fallback & Resilience）

- 後端讀取失敗 → 轉為 LocalStorage 備份（`allMealPlans`）
- 營養不達標 → 高蛋白/高纖/低脂的候選清單優先補齊
- 食物不可用/下架 → 自動替換同屬性食物並提示
- 過敏衝突 → 阻止加入並提供替代建議

---

## 7. 主要 API 介面（簡述）

- `GET get_foods.php` → `{ success, data: Food[] }`
- `GET get_user_profile.php` → `{ success, data: { profile, inbody, targets, preferences } }`
- `POST save_meal_plan.php` → `{ success, plan_id? | error? }`

---

> Maintainer: Diet Guide Module  
> File: `dist/diet-guide-flow.md`


