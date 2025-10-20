@echo off
echo 🔄 同步檔案到 XAMPP...
xcopy "dist\*" "C:\xampp\htdocs\workout\" /E /Y >nul
echo ✅ 同步完成！
echo 🌐 請重新整理瀏覽器: http://localhost/workout/index.html
pause

