# 分離 training-plan.html 的 CSS 和 JavaScript

$htmlFile = "training-plan.html"
$lines = Get-Content $htmlFile -Encoding UTF8

$cssLines = @()
$jsLines = @()
$htmlLines = @()

$inStyle = $false
$inScript = $false
$inHead = $false

$cssStartLine = 0
$cssEndLine = 0

# 找出 <style> 和 </style> 的位置
for ($i = 0; $i -lt $lines.Count; $i++) {
    if ($lines[$i] -match '<style>') {
        $inStyle = $true
        $cssStartLine = $i
        continue
    }
    if ($inStyle -and $lines[$i] -match '</style>') {
        $cssEndLine = $i
        $inStyle = $false
    }
}

Write-Host "CSS section found: lines $cssStartLine to $cssEndLine"

# 提取 CSS
$cssContent = ($lines[$cssStartLine..$cssEndLine] | Out-String).Trim()

# 保存 CSS 到檔案
$cssContent | Out-File -FilePath "assets/css/training-plan.css" -Encoding UTF8

Write-Host "CSS extracted to: assets/css/training-plan.css"
Write-Host "CSS size: $($cssContent.Length) characters"

