let conversationId = localStorage.getItem("chat_conversation_id");
if (!conversationId) {
  conversationId = (crypto.randomUUID ? crypto.randomUUID() : String(Date.now())).replace(/-/g, "");
  localStorage.setItem("chat_conversation_id", conversationId);
}

const chat = document.getElementById("chat");
const form = document.getElementById("form");
const input = document.getElementById("input");

function write(t) {
  chat.textContent += t;
  chat.scrollTop = chat.scrollHeight;
}

form.addEventListener("submit", async (e) => {
  e.preventDefault();

  const q = input.value.trim();
  if (!q) return;
  input.value = "";

  write("\n\nBạn: " + q + "\nBot: ");

  const res = await fetch("/api/chat", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({
      conversationId,
      messages: [{ role: "user", content: q }]
    })
  });

  if (!res.ok) {
    const txt = await res.text().catch(() => "");
    write(`\n[HTTP ${res.status}] ${txt}`);
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
        if (data === "[DONE]") return;

        let part;
        try { part = JSON.parse(data); } catch { continue; }

        if (part.type === "text-delta") write(part.delta || "");
        if (part.type === "error") write("\n[Lỗi] " + (part.error || "unknown"));
      }
    }
  }
});
