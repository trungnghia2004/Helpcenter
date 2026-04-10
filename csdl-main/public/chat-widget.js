(function () {
  // TODO: đổi thành URL thật của C# backend (public)
  const CHAT_API_URL = "http://localhost:5000/api/chat";


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

  function mount() {
    const btn = el("button", { id: "ai-chat-btn", text: "Chat hỗ trợ" });

    const box = el("div", { id: "ai-chat-box" });

    const header = el("div", { id: "ai-chat-header" });
    header.appendChild(el("div", { text: "Hỗ trợ cửa hàng" }));

    const closeBtn = el("button", { id: "ai-chat-close", text: "×" });
    header.appendChild(closeBtn);

    const log = el("div", { id: "ai-chat-log" });
    const form = el("form", { id: "ai-chat-form" });
    const input = el("input", {
      id: "ai-chat-input",
      type: "text",
      placeholder: "Nhập câu hỏi…",
      autocomplete: "off",
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
      const q = input.value.trim();
      if (!q) return;
      input.value = "";

      write(log, "\n\nBạn: " + q + "\nBot: ");

      let res;
      try {
        res = await fetch(CHAT_API_URL, {
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify({
            conversationId,
            messages: [{ role: "user", content: q }],
          }),
        });
      } catch (err) {
        write(log, "\n[Lỗi fetch] " + err);
        return;
      }

      if (!res.ok) {
        const txt = await res.text().catch(() => "");
        write(log, `\n[HTTP ${res.status}] ${txt}`);
        return;
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
            if (data === "[DONE]") return;

            let part;
            try { part = JSON.parse(data); } catch { continue; }

            if (part.type === "text-delta") {
              write(log, part.delta || "");
            } else if (part.type === "error") {
              write(log, "\n[Lỗi] " + (part.error || "unknown"));
            }
          }
        }
      }
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", mount);
  } else {
    mount();
  }
})();
