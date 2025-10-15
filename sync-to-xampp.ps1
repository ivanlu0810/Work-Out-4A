# 自動同步 dist 目錄到 XAMPP htdocs\workout
# 使用方法：.\.synctoxampp.ps1

$SourceDir = ".\dist"
$TargetDir = "C:\xampp\htdocs\workout"

Write-Host "🔄 開始同步檔案到 XAMPP..." -ForegroundColor Green

try {
    # 複製所有檔案
    xcopy "$SourceDir\*" "$TargetDir\" /E /I /Y /Q
    
    Write-Host "✅ 檔案同步完成！" -ForegroundColor Green
    Write-Host "📍 XAMPP 路徑: $TargetDir" -ForegroundColor Cyan
    Write-Host "🌐 瀏覽器連結: http://localhost/workout/index.html" -ForegroundColor Cyan
}
catch {
    Write-Host "❌ 同步失敗: $($_.Exception.Message)" -ForegroundColor Red
}

Read-Host "Press Enter to continue..."
