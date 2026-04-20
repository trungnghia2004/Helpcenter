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

  let conversationId = null;
  let isOpen = false;

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

  async function fetchChatWithFallback(payload) {
    const urls = uniq(CHAT_API_CANDIDATES);
    let lastError = null;

    for (const url of urls) {
      const controller = new AbortController();
      const timeout = setTimeout(() => controller.abort(), 20000);
      try {
        const res = await fetch(url, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
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
    const btn = el("button", { id: "ai-chat-btn", text: "Chat ho tro" });
    const box = el("div", { id: "ai-chat-box" });
    const header = el("div", { id: "ai-chat-header" });
    header.appendChild(el("div", { text: "Ho tro cua hang" }));

    const closeBtn = el("button", { id: "ai-chat-close", text: "x" });
    header.appendChild(closeBtn);

    const log = el("div", { id: "ai-chat-log" });
    const form = el("form", { id: "ai-chat-form" });
    const input = el("input", {
      id: "ai-chat-input",
      type: "text",
      placeholder: "Nhap cau hoi...",
      autocomplete: "off"
    });
    const send = el("button", { id: "ai-chat-send", type: "submit", text: "Gui" });

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
      const q = input.value.trim();
      if (!q) return;
      input.value = "";

      write(log, "\n\nBan: " + q + "\nBot: ");

      let res;
      let usedUrl = "";
      try {
        const r = await fetchChatWithFallback({
          conversationId,
          messages: [{ role: "user", content: q }]
        });
        res = r.res;
        usedUrl = r.url;
      } catch (err) {
        write(log, "\n[Loi fetch] Khong ket noi duoc chat API.");
        write(log, "\n- Da thu: " + uniq(CHAT_API_CANDIDATES).join(" | "));
        write(log, "\n- Loi: " + (err && err.message ? err.message : String(err)));
        write(log, "\n- Kiem tra backend C# dang chay va mo cong 5000.");
        return;
      }

      if (!res.ok) {
        const txt = await res.text().catch(() => "");
        const compact = (txt || "").replace(/\s+/g, " ").trim();
        const preview = compact.slice(0, 360);
        if (compact.toLowerCase().includes("<!doctype html")) {
          write(log, `\n[HTTP ${res.status}] API URL dang tro sai server/route.`);
          write(log, `\n- URL: ${usedUrl}`);
          write(log, `\n- Da thu: ${uniq(CHAT_API_CANDIDATES).join(" | ")}`);
        } else {
          write(log, `\n[HTTP ${res.status}] ${preview}`);
        }
        return;
      }

      const serverConversationId = res.headers.get("x-conversation-id");
      if (serverConversationId && serverConversationId !== conversationId) {
        conversationId = serverConversationId;
      }

      if (!res.body || !res.body.getReader) {
        write(log, "\n[Loi] Trinh duyet khong ho tro stream response.");
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
              if (data === "[DONE]") return;

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
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
  } else {
    mount();
  }
})();
