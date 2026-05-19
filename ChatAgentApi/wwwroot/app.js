let conversationId = localStorage.getItem("chat_conversation_id");
if (!conversationId) {
  conversationId = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now())).replace(/-/g, "");
  localStorage.setItem("chat_conversation_id", conversationId);
}

const chat = document.getElementById("chat");
const form = document.getElementById("form");
const input = document.getElementById("input");
let currentBotStart = -1;

function getAuthToken() {
  const keys = ["auth_token", "access_token", "token"];
  for (const k of keys) {
    const v = localStorage.getItem(k) || sessionStorage.getItem(k);
    if (v && v.trim()) return v.trim();
  }
  return null;
}

function renderAuthRequired() {
  chat.textContent = "Vui lòng đăng nhập để sử dụng hỗ trợ.";
  if (form) form.style.display = "none";
}

function write(t) {
  chat.textContent += t;
  chat.scrollTop = chat.scrollHeight;
}

function normalizeProductListLines(text) {
  if (!text) return text;
  return text
    .replace(/(\d(?:,\d{3})*\s*VND)\s*-\s*/g, "$1\n- ")
    .replace(/(VND)\s*(Bạn muốn|Ban muon)/g, "$1\n$2");
}

function finalizeCurrentBotMessage() {
  if (currentBotStart < 0) return;
  const all = chat.textContent || "";
  if (currentBotStart > all.length) {
    currentBotStart = -1;
    return;
  }

  const before = all.slice(0, currentBotStart);
  const botText = all.slice(currentBotStart);
  const normalized = normalizeProductListLines(botText);
  if (normalized !== botText) {
    chat.textContent = before + normalized;
    chat.scrollTop = chat.scrollHeight;
  }

  currentBotStart = -1;
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();
  const authToken = getAuthToken();
  if (!authToken) {
    renderAuthRequired();
    return;
  }

  const q = input.value.trim();
  if (!q) return;
  input.value = "";

  write("\n\nBạn: " + q + "\nBot: ");
  currentBotStart = chat.textContent.length;

  const res = await fetch("/api/chat", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      "Authorization": `Bearer ${authToken}`
    },
    body: JSON.stringify({
      conversationId,
      messages: [{ role: "user", content: q }]
    })
  });

  if (res.status === 401) {
    let msg = "Vui lòng đăng nhập để sử dụng hỗ trợ.";
    try {
      const data = await res.json();
      if (data && typeof data.message === "string" && data.message.trim()) {
        msg = data.message.trim();
      }
    } catch {}
    write("\n" + msg);
    renderAuthRequired();
    finalizeCurrentBotMessage();
    return;
  }

  if (!res.ok) {
    let msg = `HTTP ${res.status}`;
    try {
      const data = await res.json();
      if (data && typeof data.message === "string" && data.message.trim()) {
        msg = data.message.trim();
      }
    } catch {
      const txt = await res.text().catch(() => "");
      if (txt) msg = txt;
    }
    write("\n" + msg);
    finalizeCurrentBotMessage();
    return;
  }

  const serverConversationId = res.headers.get("x-conversation-id");
  if (serverConversationId && serverConversationId !== conversationId) {
    conversationId = serverConversationId;
    localStorage.setItem("chat_conversation_id", conversationId);
  }

  const reader = res.body.getReader();
  const decoder = new TextDecoder();
  let buffer = "";

  while (true) {
    const { value, done } = await reader.read();
    if (done) break;

    buffer += decoder.decode(value, { stream: true });
    const blocks = buffer.split("\n\n");
    buffer = blocks.pop() || "";

    for (const block of blocks) {
      for (const line of block.split("\n")) {
        if (!line.startsWith("data:")) continue;

        const data = line.slice(5).trim();
        if (data === "[DONE]") {
          finalizeCurrentBotMessage();
          return;
        }

        let part;
        try { part = JSON.parse(data); } catch { continue; }

        if (part.type === "text-delta") write(part.delta || "");
        if (part.type === "error") write("\n[Lỗi] " + (part.error || "unknown"));
      }
    }
  }

  finalizeCurrentBotMessage();
});

if (!getAuthToken()) {
  renderAuthRequired();
}
