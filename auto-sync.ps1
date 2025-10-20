# 自動監控檔案變化並同步到 XAMPP
# 使用方法：.\.autosync.ps1

$SourceDir = ".\dist"
$TargetDir = "C:\xampp\htdocs\workout"

Write-Host "🎯 開始監控檔案變化..." -ForegroundColor Green
Write-Host "📁 監控目錄: $SourceDir" -ForegroundColor Cyan
Write-Host "🎯 目標目錄: $TargetDir" -ForegroundColor Cyan
Write-Host "按 Ctrl+C 停止監控" -ForegroundColor Yellow
Write-Host ""

# 創建 FileSystemWatcher
$watcher = New-Object System.IO.FileSystemWatcher
$watcher.Path = Resolve-Path $SourceDir
$watcher.IncludeSubdirectories = $true
$watcher.EnableRaisingEvents = $true

# 定義同步函數
function Sync-Files {
    try {
        Write-Host "🔄 偵測到檔案變化，正在同步..." -ForegroundColor Yellow
        xcopy "$SourceDir\*" "$TargetDir\*" /E /H /Y /Q
        
        Write-Host "✅ 同步完成於 $(Get-Date -Format 'HH:mm:ss')" -ForegroundColor Green
        Write-Host ""
    }
    catch {
        Write-Host "❌ 同步失敗: $($_.Exception.Message)" -ForegroundColor Red
    }
}

# 註冊事件處理器
Register-ObjectEvent -InputObject $watcher -EventName "Changed" -Action { Sync-Files }
Register-ObjectEvent -InputObject $watcher -EventName "Created" -Action { Sync-Files }
Register-ObjectEvent -InputObject $watcher -EventName "Deleted" -Action { Sync-Files }
Register-ObjectEvent -InputObject $watcher -EventName "Renamed" -Action { Sync-Files }

try {
    while ($true) {
        Start-Sleep -Seconds 1
    }
}
finally {
    $watcher.Dispose()
    Get-EventSubscriber | Unregister-Event
}

