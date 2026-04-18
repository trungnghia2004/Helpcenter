using System.Collections.Concurrent;
using System.Globalization;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using System.Linq;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    static async Task<ChatRequest> ParseIncomingChatRequestAsync(HttpRequest request, CancellationToken ct)
    {
        try
        {
            using var doc = await JsonDocument.ParseAsync(request.Body, cancellationToken: ct);
            var root = doc.RootElement;

            string? conversationId = null;
            if (root.TryGetProperty("conversationId", out var convEl) && convEl.ValueKind == JsonValueKind.String)
                conversationId = convEl.GetString();
            else if (root.TryGetProperty("id", out var idEl) && idEl.ValueKind == JsonValueKind.String)
                conversationId = idEl.GetString();
            else if (root.TryGetProperty("chatId", out var chatIdEl) && chatIdEl.ValueKind == JsonValueKind.String)
                conversationId = chatIdEl.GetString();

            string? userId = null;
            if (root.TryGetProperty("userId", out var userIdEl) && userIdEl.ValueKind == JsonValueKind.String)
                userId = userIdEl.GetString();

            string? appId = null;
            if (root.TryGetProperty("appId", out var appIdEl) && appIdEl.ValueKind == JsonValueKind.String)
                appId = appIdEl.GetString();

            var messages = new List<ChatMessage>();
            if (root.TryGetProperty("messages", out var messagesEl) && messagesEl.ValueKind == JsonValueKind.Array)
            {
                foreach (var m in messagesEl.EnumerateArray())
                {
                    var role = m.TryGetProperty("role", out var r) && r.ValueKind == JsonValueKind.String
                        ? (r.GetString() ?? "user")
                        : "user";

                    var text = ExtractMessageText(m);
                    if (!string.IsNullOrWhiteSpace(text))
                        messages.Add(new ChatMessage(role, CanonicalizeUserText(text.Trim())));
                }
            }

            if (messages.Count == 0 &&
                root.TryGetProperty("prompt", out var promptEl) &&
                promptEl.ValueKind == JsonValueKind.String)
            {
                var prompt = promptEl.GetString();
                if (!string.IsNullOrWhiteSpace(prompt))
                    messages.Add(new ChatMessage("user", CanonicalizeUserText(prompt.Trim())));
            }

            return new ChatRequest(conversationId, messages, userId, appId);
        }
        catch
        {
            return new ChatRequest(null, new List<ChatMessage>());
        }
    }

    static string ExtractMessageText(JsonElement message)
    {
        var sb = new StringBuilder();

        if (message.TryGetProperty("content", out var content))
            AppendAnyText(content, sb);

        if (message.TryGetProperty("parts", out var parts))
            AppendAnyText(parts, sb);

        return sb.ToString().Trim();
    }

    static void AppendAnyText(JsonElement el, StringBuilder sb)
    {
        switch (el.ValueKind)
        {
            case JsonValueKind.String:
                var s = el.GetString();
                if (!string.IsNullOrWhiteSpace(s))
                {
                    if (sb.Length > 0) sb.Append('\n');
                    sb.Append(s);
                }
                break;

            case JsonValueKind.Array:
                foreach (var item in el.EnumerateArray())
                    AppendAnyText(item, sb);
                break;

            case JsonValueKind.Object:
                if (el.TryGetProperty("text", out var textEl))
                    AppendAnyText(textEl, sb);
                else if (el.TryGetProperty("content", out var contentEl))
                    AppendAnyText(contentEl, sb);
                else if (el.TryGetProperty("value", out var valueEl))
                    AppendAnyText(valueEl, sb);
                break;
        }
    }
}



