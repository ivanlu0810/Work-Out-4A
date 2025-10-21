const chatIcon = document.getElementById("chat-icon");
const chatWindow = document.getElementById("chat-window");
const header = chatWindow.querySelector(".header");
const input = chatWindow.querySelector("input");
const button = chatWindow.querySelector("button");
const body = chatWindow.querySelector(".body");

let offsetX = 0;
let offsetY = 0;
let isDragging = false;
let wasDragged = false;
let draggingElement = null;

// --------- 拖曳功能 ----------
function clamp(value, min, max) {
  return Math.max(min, Math.min(max, value));
}

function syncChatWindowToIcon() {
  const iconRect = chatIcon.getBoundingClientRect();
  const wrapperRect = chatIcon.offsetParent.getBoundingClientRect(); // 相對外層 chat-container

  const chatWidth = chatWindow.offsetWidth || 300;
  const left = iconRect.right - wrapperRect.left - chatWidth;
  const top = iconRect.bottom - wrapperRect.top + 10;

  chatWindow.style.left = `${left}px`;
  chatWindow.style.top = `${top}px`;
}

window.onload = () => {
  syncChatWindowToIcon();

  // ✅ 監控 chatWindow 是否被手動 resize，若有則重新對齊
  const resizeObserver = new ResizeObserver(() => {
    syncChatWindowToIcon();
  });
  resizeObserver.observe(chatWindow);
};

window.addEventListener("resize", syncChatWindowToIcon);

function enableDrag(element) {
  element.addEventListener("mousedown", (e) => {
    isDragging = true;
    wasDragged = false;
    draggingElement = element;
    offsetX = e.clientX - element.offsetLeft;
    offsetY = e.clientY - element.offsetTop;
    e.preventDefault();
  });
}

document.addEventListener("mousemove", (e) => {
  if (isDragging && draggingElement) {
    wasDragged = true;
    let x = e.clientX - offsetX;
    let y = e.clientY - offsetY;

    x = clamp(x, 0, window.innerWidth - draggingElement.offsetWidth);
    y = clamp(y, 0, window.innerHeight - draggingElement.offsetHeight);

    draggingElement.style.left = `${x}px`;
    draggingElement.style.top = `${y}px`;

    syncChatWindowToIcon(); // 如果 chatWindow 要跟著 icon 動
  }
});

document.addEventListener("mouseup", () => {
  isDragging = false;
  draggingElement = null;
});

// --------- Chat Icon 開關 ----------
chatIcon.addEventListener("click", () => {
  if (wasDragged) {
    wasDragged = false;
    return;
  }

  const isHidden =
    chatWindow.style.visibility === "hidden" ||
    chatWindow.style.visibility === "";
  chatWindow.style.visibility = isHidden ? "visible" : "hidden";
  chatWindow.style.opacity = isHidden ? "1" : "0";

  if (isHidden) {
    syncChatWindowToIcon(); // ✅ 每次開啟時重新定位
  }
});
let resizeTimeout;
window.addEventListener("resize", () => {
  clearTimeout(resizeTimeout);
  resizeTimeout = setTimeout(syncChatWindowToIcon, 150);
});

enableDrag(chatIcon);

// --------- 訊息處理 ----------
function appendMessage(text, sender = "user") {
  const messageDiv = document.createElement("div");
  messageDiv.classList.add("message", sender);

  if (sender === "bot") {
    messageDiv.innerHTML = marked.parse(text); // markdown support
  } else {
    messageDiv.textContent = text;
  }

  body.appendChild(messageDiv);
  body.scrollTop = body.scrollHeight;
}

// === 打字中小點點 ===
let typingBubble = null;

function showTyping() {
  // 建立一個「正在輸出」的訊息泡泡
  typingBubble = document.createElement("div");
  typingBubble.classList.add("message", "bot", "typing");
  typingBubble.innerHTML = `
    <span>教練正在輸出</span>
    <span class="typing-indicator">
      <span class="dot"></span><span class="dot"></span><span class="dot"></span>
    </span>
  `;
  body.appendChild(typingBubble);
  body.scrollTop = body.scrollHeight;
}

function hideTyping() {
  if (typingBubble && typingBubble.parentNode) {
    typingBubble.parentNode.removeChild(typingBubble);
  }
  typingBubble = null;
}

// === 打字機效果：先逐字顯示純文字，最後再轉 Markdown ===
async function typeWriterMarkdown(fullText, speed = 12) {
  // 建立一個目標訊息泡泡（暫以純文字顯示）
  const msg = document.createElement("div");
  msg.classList.add("message", "bot", "typing-target");
  msg.textContent = ""; // 從空字串開始打
  body.appendChild(msg);
  body.scrollTop = body.scrollHeight;

  // 逐字顯示
  for (let i = 0; i < fullText.length; i++) {
    msg.textContent += fullText[i];
    // 微調速率：換行與標點稍作停頓更自然
    const ch = fullText[i];
    const pause = (ch === "\n") ? 10 : (/[，。！？,.]/.test(ch) ? speed + 30 : speed);
    await new Promise(r => setTimeout(r, pause));
    body.scrollTop = body.scrollHeight;
  }

  // 完成後把純文字轉成 Markdown（維持你原本使用 marked 的做法）
  msg.classList.remove("typing-target");
  msg.innerHTML = marked.parse(fullText);
  body.scrollTop = body.scrollHeight;
}


async function sendMessage() {
  const text = input.value.trim();
  if (!text) return;

  appendMessage(text, "user");
  input.value = "";

  // 立刻顯示「打字中」微動畫（降低空窗焦慮）
  showTyping();

  const userId = document.getElementById("user-id").textContent;

  try {
    const payload = JSON.stringify({
      user_id: userId,
      messages: [{ role: "user", content: text }],
    });

    // 1) 優先打同資料夾的 chatbox.php（自動適配 ngrok / localhost）
    const siblingEndpoint = new URL("chatbox.php", window.location.href).href;

    let response;
    try {
      response = await fetch(siblingEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: payload,
      });
      if (!response.ok) throw new Error(`HTTP ${response.status}`);
    } catch (e1) {
      // 2) 失敗時回退到固定 ngrok 端點（當你用 ngrok 測試時仍可用）
      const ngrokEndpoint = "https://jianshen.ngrok.app/%E5%81%A5%E7%BF%92%E7%94%9F/dist/chatbox.php";
      response = await fetch(ngrokEndpoint, {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: payload,
      });
    }

    const data = await response.json();
    const aiReply = data.reply || "❌ 無法取得回覆";

    // 關掉點點動畫，改用打字機效果輸出 AI 內容
    hideTyping();
    await typeWriterMarkdown(aiReply, 12);

  } catch (err) {
    console.error("錯誤：", err);
    hideTyping();
    appendMessage("❌ 發送失敗，請稍後再試", "bot");
  }
}

// === 左下 + 右下 角落縮放（只改寬/高，不動定位；相容原本對齊邏輯） ===
(function enableDualCornerResize() {
  const chatWin = document.getElementById('chat-window');
  if (!chatWin) return;

  // 若之前有黑點版：清掉殘留與 inline 設定
  chatWin.querySelectorAll('.resize-dot').forEach(n => n.remove());
  if (chatWin.style.resize === 'none') chatWin.style.resize = ''; // 恢復原生右下把手（若本來有）

  // 建立左下與（可選）右下把手（若已存在就略過）
  let handleBL = chatWin.querySelector('.resizer.bl');
  if (!handleBL) {
    handleBL = document.createElement('div');
    handleBL.className = 'resizer bl';
    handleBL.setAttribute('aria-hidden', 'true');
    chatWin.appendChild(handleBL);
  }
  let handleBR = chatWin.querySelector('.resizer.br');
  if (!handleBR) {
    handleBR = document.createElement('div');
    handleBR.className = 'resizer br';
    handleBR.setAttribute('aria-hidden', 'true');
    chatWin.appendChild(handleBR);
  }

  // 讀取最小尺寸（若 CSS 未設，給預設）
  const getMinWH = () => {
    const s = getComputedStyle(chatWin);
    const minW = parseInt(s.minWidth)  || 280;
    const minH = parseInt(s.minHeight) || 300;
    return { minW, minH };
  };

  let resizing = false;
  let mode = null; // 'bl' | 'br'
  let startX = 0, startY = 0, startW = 0, startH = 0;

  const onStart = (e, m) => {
    resizing = true;
    mode = m;
    startX = e.clientX;
    startY = e.clientY;
    startW = chatWin.offsetWidth;
    startH = chatWin.offsetHeight;
    document.body.style.userSelect = 'none';
    if (e.target.setPointerCapture && e.pointerId != null) {
      e.target.setPointerCapture(e.pointerId);
    }
    e.preventDefault();
    e.stopPropagation();
  };

  const onMove = (e) => {
    if (!resizing) return;
    const dx = e.clientX - startX; // → 右正
    const dy = e.clientY - startY; // → 下正
    const { minW, minH } = getMinWH();

    let newW = startW;
    let newH = Math.max(minH, startH + dy);

    if (mode === 'bl') {
      // 左下：往左拖加寬、往右拖縮小
      newW = Math.max(minW, startW - dx);
    } else if (mode === 'br') {
      // 右下：往右拖加寬、往左拖縮小
      newW = Math.max(minW, startW + dx);
    }

    chatWin.style.width  = newW + 'px';
    chatWin.style.height = newH + 'px';
    // 不調整 left/right；若你原本有 ResizeObserver 負責對齊，會自動貼齊
  };

  const onEnd = (e) => {
    if (!resizing) return;
    resizing = false;
    mode = null;
    document.body.style.userSelect = '';
    if (e.target.releasePointerCapture && e.pointerId != null) {
      e.target.releasePointerCapture(e.pointerId);
    }
  };

  // 使用 Pointer Events（同時支援滑鼠/觸控/手寫筆）
  const bind = (el, m) => {
    el.addEventListener('pointerdown', (e) => onStart(e, m));
  };
  bind(handleBL, 'bl');
  bind(handleBR, 'br');

  window.addEventListener('pointermove', onMove, { passive: false });
  window.addEventListener('pointerup', onEnd);
})();


// --------- 發送事件 ----------
button.addEventListener("click", sendMessage);
input.addEventListener("keydown", (e) => {
  if (e.key === "Enter") sendMessage();
});
