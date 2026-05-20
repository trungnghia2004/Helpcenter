(function () {
  const SAME_ORIGIN_API = window.location.origin.replace(/\/+$/, "") + "/api/chat";

  const DEFAULT_CHAT_API_CANDIDATES = [
    SAME_ORIGIN_API,
    "http://localhost:5000/api/chat",
    "http://127.0.0.1:5000/api/chat"
  ];

  const configuredApi =
    (window.CHAT_API_URL || window.__CHAT_API_URL || "").trim();

  const CHAT_API_CANDIDATES = configuredApi
    ? [configuredApi].concat(DEFAULT_CHAT_API_CANDIDATES)
    : DEFAULT_CHAT_API_CANDIDATES;

  const CONVERSATION_STORAGE_KEY = "ai_chat_conversation_id";
  const CHAT_LOG_STORAGE_KEY = "ai_chat_log_text";
  let conversationId = localStorage.getItem(CONVERSATION_STORAGE_KEY) || null;
  let isOpen = false;
  let currentUserId = null;
  let currentBotStart = -1;

  function getAuthToken() {
    const keys = ["auth_token", "access_token", "token"];
    for (const k of keys) {
      const v = localStorage.getItem(k) || sessionStorage.getItem(k);
      if (v && String(v).trim()) return String(v).trim();
    }
    return null;
  }

  async function getSessionUser() {
    try {
      const res = await fetch("/chat-auth/me", {
        method: "GET",
        credentials: "include",
        headers: { "Accept": "application/json" }
      });
      if (!res.ok) return null;
      const data = await res.json().catch(() => null);
      if (!data || !data.ok || data.id == null) return null;
      return data;
    } catch {
      return null;
    }
  }

  function el(tag, attrs = {}, children = []) {
    const e = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => {
      if (k === "class") e.className = v;
      else if (k === "text") e.textContent = v;
      else e.setAttribute(k, v);
    });
    children.forEach((c) => e.appendChild(c));
    return e;
  }

  function write(logEl, text) {
    logEl.textContent += text;
    logEl.scrollTop = logEl.scrollHeight;
    try {
      localStorage.setItem(CHAT_LOG_STORAGE_KEY, logEl.textContent || "");
    } catch {}
  }

  function normalizeBotText(text) {
    if (!text) return text;
    return text
      .replace(/(\d(?:,\d{3})*\s*VND)\s*-\s*/g, "$1\n- ")
      .replace(/(VND)\s*(Bạn muốn|Ban muon)/g, "$1\n$2")
      .replace(/(sản phẩm)\s*-\s*Size/gi, "$1\n- Size");
  }

  function finalizeCurrentBotMessage(logEl) {
    if (currentBotStart < 0) return;
    const all = logEl.textContent || "";
    if (currentBotStart > all.length) {
      currentBotStart = -1;
      return;
    }

    const before = all.slice(0, currentBotStart);
    const botText = all.slice(currentBotStart);
    const normalized = normalizeBotText(botText);
    if (normalized !== botText) {
      logEl.textContent = before + normalized;
      logEl.scrollTop = logEl.scrollHeight;
    }

    currentBotStart = -1;
  }

  function uniq(values) {
    const seen = new Set();
    const result = [];
    for (const v of values) {
      const key = String(v || "").trim();
      if (!key) continue;
      if (seen.has(key)) continue;
      seen.add(key);
      result.push(key);
    }
    return result;
  }

  async function fetchChatWithFallback(payload, token) {
    const urls = uniq(CHAT_API_CANDIDATES);
    let lastError = null;

    for (const url of urls) {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 20000);
      try {
        const headers = { "Content-Type": "application/json" };
        if (token && token.trim()) headers["Authorization"] = `Bearer ${token}`;
        if (payload && payload.userId) headers["x-user-id"] = String(payload.userId);

        const res = await fetch(url, {
          method: "POST",
          headers,
          credentials: "include",
          body: JSON.stringify(payload),
          signal: controller.signal
        });

        clearTimeout(timeout);
        if (res.ok) return { res, url };

        const contentType = (res.headers.get("content-type") || "").toLowerCase();
        const isHtml = contentType.includes("text/html");
        const isLikelyWrongRoute = res.status === 404 || res.status === 405 || res.status === 419;
        const isSameOrigin = url.indexOf(window.location.origin) === 0;

        if ((isLikelyWrongRoute || isHtml) && (isSameOrigin || urls.length > 1)) {
          lastError = new Error(`HTTP ${res.status} from ${url} (likely wrong server/route)`);
          continue;
        }

        return { res, url };
      } catch (err) {
        clearTimeout(timeout);
        lastError = err;
      }
    }

    throw lastError || new Error("Failed to connect to chat API.");
  }

  function mount() {
    const btn = el("button", { id: "ai-chat-btn", text: "Chat hỗ trợ" });
    const box = el("div", { id: "ai-chat-box" });
    const header = el("div", { id: "ai-chat-header" });
    header.appendChild(el("div", { text: "Hỗ trợ cửa hàng" }));

    const closeBtn = el("button", { id: "ai-chat-close", text: "x" });
    header.appendChild(closeBtn);

    const log = el("div", { id: "ai-chat-log" });
    try {
      const saved = localStorage.getItem(CHAT_LOG_STORAGE_KEY) || "";
      if (saved) {
        log.textContent = saved;
        log.scrollTop = log.scrollHeight;
      }
    } catch {}
    const form = el("form", { id: "ai-chat-form" });
    const input = el("input", {
      id: "ai-chat-input",
      type: "text",
      placeholder: "Nhập câu hỏi...",
      autocomplete: "off"
    });
    const send = el("button", { id: "ai-chat-send", type: "submit", text: "Gửi" });

    form.appendChild(input);
    form.appendChild(send);

    box.appendChild(header);
    box.appendChild(log);
    box.appendChild(form);

    document.body.appendChild(btn);
    document.body.appendChild(box);

    function toggle(open) {
      isOpen = open;
      box.style.display = open ? "block" : "none";
      if (open) input.focus();
    }

    btn.addEventListener("click", () => toggle(!isOpen));
    closeBtn.addEventListener("click", () => toggle(false));

    form.addEventListener("submit", async (e) => {
      e.preventDefault();
      const authToken = getAuthToken();
      const sessionUser = await getSessionUser();
      if (!authToken && !sessionUser) return;
      const userId = sessionUser && sessionUser.id != null ? String(sessionUser.id) : (currentUserId || "");
      if (userId) currentUserId = userId;

      const q = input.value.trim();
      if (!q) return;
      input.value = "";

      write(log, "\n\nBan: " + q + "\nBot: ");
      currentBotStart = log.textContent.length;

      let res;
      let usedUrl = "";
      try {
        const r = await fetchChatWithFallback({
          conversationId,
          messages: [{ role: "user", content: q }],
          userId: currentUserId || undefined
        }, authToken);
        res = r.res;
        usedUrl = r.url;
      } catch (err) {
        write(log, "\n[Loi fetch] Khong ket noi duoc chat API.");
        write(log, "\n- Da thu: " + uniq(CHAT_API_CANDIDATES).join(" | "));
        write(log, "\n- Loi: " + (err && err.message ? err.message : String(err)));
        write(log, "\n- Kiem tra backend C# dang chay va mo cong 5000.");
        finalizeCurrentBotMessage(log);
        return;
      }

      if (!res.ok) {
        let jsonMessage = "";
        try {
          const data = await res.json();
          jsonMessage = data && typeof data.message === "string" ? data.message.trim() : "";
        } catch {}

        if (res.status === 401) {
          write(log, "\n" + (jsonMessage || "Vui long dang nhap de su dung ho tro."));
          finalizeCurrentBotMessage(log);
          return;
        }

        const txt = await res.text().catch(() => "");
        const compact = (txt || jsonMessage || "").replace(/\s+/g, " ").trim();
        const preview = compact.slice(0, 360);
        if (compact.toLowerCase().includes("<!doctype html")) {
          write(log, `\n[HTTP ${res.status}] API URL dang tro sai server/route.`);
          write(log, `\n- URL: ${usedUrl}`);
          write(log, `\n- Da thu: ${uniq(CHAT_API_CANDIDATES).join(" | ")}`);
        } else {
          write(log, `\n[HTTP ${res.status}] ${preview}`);
        }
        finalizeCurrentBotMessage(log);
        return;
      }

      const serverConversationId = res.headers.get("x-conversation-id");
      if (serverConversationId && serverConversationId !== conversationId) {
        conversationId = serverConversationId;
        try {
          localStorage.setItem(CONVERSATION_STORAGE_KEY, conversationId);
        } catch {}
      }

      if (!res.body || !res.body.getReader) {
        write(log, "\n[Loi] Trinh duyet khong ho tro stream response.");
        finalizeCurrentBotMessage(log);
        return;
      }

      const reader = res.body.getReader();
      const decoder = new TextDecoder();
      let buffer = "";

      try {
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
                finalizeCurrentBotMessage(log);
                return;
              }

              let part;
              try {
                part = JSON.parse(data);
              } catch {
                continue;
              }

              if (part.type === "text-delta") {
                write(log, part.delta || "");
              } else if (part.type === "error") {
                write(log, "\n[Loi] " + (part.error || "unknown"));
              }
            }
          }
        }
      } catch (err) {
        write(log, "\n[Loi stream] " + (err && err.message ? err.message : String(err)));
        if (usedUrl) write(log, "\n(API: " + usedUrl + ")");
      } finally {
        finalizeCurrentBotMessage(log);
      }
    });
  }

  async function bootstrap() {
    const token = getAuthToken();
    const sessionUser = await getSessionUser();
    if (!token && !sessionUser) return;
    if (sessionUser && sessionUser.id != null) currentUserId = String(sessionUser.id);

    if (document.readyState === "loading") {
      document.addEventListener("DOMContentLoaded", mount);
    } else {
      mount();
    }
  }

  bootstrap();
})();
