// 分離 training-plan.html 的 CSS 和 JavaScript
const fs = require('fs');
const path = require('path');

const htmlFile = 'training-plan.html';
const content = fs.readFileSync(htmlFile, 'utf8');

// 找出 CSS 內容（<style>...</style>）
const cssMatch = content.match(/<style>([\s\S]*?)<\/style>/);
if (!cssMatch) {
    console.error('未找到 <style> 標籤');
    process.exit(1);
}

const cssContent = cssMatch[1].trim();
console.log(`CSS 內容長度: ${cssContent.length} 字符`);

// 保存 CSS 檔案
if (!fs.existsSync('assets/css')) {
    fs.mkdirSync('assets/css', { recursive: true });
}

fs.writeFileSync('assets/css/training-plan.css', cssContent, 'utf8');
console.log('✓ CSS 已提取到: assets/css/training-plan.css');

// 找出 JavaScript 內容（在 </body> 之前的所有 script 內容）
const bodyEndMatch = content.indexOf('</body>');
if (bodyEndMatch === -1) {
    console.error('未找到 </body> 標籤');
    process.exit(1);
}

// 從 <body> 之後找 JavaScript
const bodyMatch = content.match(/<body>([\s\S]*?)(?=<\/body>)/);
if (!bodyMatch) {
    console.error('未找到 <body> 內容');
    process.exit(1);
}

const bodyContent = bodyMatch[1];
// 尋找內嵌的 <script> 標籤
const scriptMatches = bodyContent.matchAll(/<script>([\s\S]*?)<\/script>/g);

let jsContent = '';
let scriptCount = 0;
for (const match of scriptMatches) {
    jsContent += match[1] + '\n\n';
    scriptCount++;
}

console.log(`找到 ${scriptCount} 個 script 標籤`);

// 保存 JavaScript 檔案
if (!fs.existsSync('assets/js')) {
    fs.mkdirSync('assets/js', { recursive: true });
}

if (jsContent.trim().length > 0) {
    fs.writeFileSync('assets/js/training-plan.js', jsContent.trim(), 'utf8');
    console.log('✓ JavaScript 已提取到: assets/js/training-plan.js');
}

console.log('\n分離完成！');

