# 訓練計畫頁面前後端分離指南

## 問題
`training-plan.html` 檔案有 11782 行，包含：
- HTML 結構
- CSS 樣式（內嵌在 `<style>` 標籤）
- JavaScript 邏輯（內嵌在頁面）

這導致：
1. 檔案太大，載入緩慢
2. 維護困難
3. 無法有效利用瀏覽器快取

## 解決方案

### 步驟 1：分離 CSS
從 line 20-3205 提取 CSS 內容到：
- `assets/css/training-plan.css`

### 步驟 2：分離 JavaScript  
從 line 3832 開始提取 JavaScript 內容到：
- `assets/js/training-plan.js`

### 步驟 3：更新 HTML
在 `<head>` 中添加 CSS 引用：
```html
<link rel="stylesheet" href="assets/css/training-plan.css">
```

在 `</body>` 之前添加 JavaScript 引用：
```html
<script src="assets/js/training-plan.js"></script>
```

## 自動化腳本

使用提供的 Python 腳本自動完成分離：

```bash
python split_tp.py
```

## 注意事項

1. **備份原始檔案**：執行分離前請先備份 `training-plan.html`
2. **測試功能**：分離後要測試所有功能是否正常
3. **路徑檢查**：確保 CSS/JS 引用路徑正確

## 預期效果

- HTML 檔案大小減少約 95%
- CSS 和 JS 可被瀏覽器快取
- 程式碼更容易維護
- 頁面載入速度提升

