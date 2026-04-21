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
    static Task UpdateConversationSummaryAsync(
        Conversation conv,
        CancellationToken ct)
    {
        _ = ct;

        if (conv.Messages.Count < 14) return Task.CompletedTask;

        var keepLast = 8;
        var toSummarize = conv.Messages.Take(Math.Max(0, conv.Messages.Count - keepLast)).ToList();
        if (toSummarize.Count < 6) return Task.CompletedTask;

        var recentUserAsks = toSummarize
            .Where(m => string.Equals(m.Role, "user", StringComparison.OrdinalIgnoreCase))
            .Select(m => NormalizeSummaryText(m.Content, 150))
            .Where(x => !string.IsNullOrWhiteSpace(x))
            .TakeLast(3)
            .ToList();

        var recentAssistantAnswers = toSummarize
            .Where(m => string.Equals(m.Role, "assistant", StringComparison.OrdinalIgnoreCase))
            .Select(m => NormalizeSummaryText(m.Content, 150))
            .Where(x => !string.IsNullOrWhiteSpace(x))
            .TakeLast(2)
            .ToList();

        var knownFacts = conv.MemoryFacts
            .Where(kvp => !string.IsNullOrWhiteSpace(kvp.Key) && !string.IsNullOrWhiteSpace(kvp.Value))
            .OrderBy(kvp => kvp.Key, StringComparer.Ordinal)
            .Take(6)
            .Select(kvp => $"{kvp.Key}:{NormalizeSummaryText(kvp.Value, 40)}")
            .ToList();

        var lines = new List<string>();
        if (recentUserAsks.Count > 0)
            lines.Add("- Mục tiêu gần đây: " + string.Join(" | ", recentUserAsks));
        if (knownFacts.Count > 0)
            lines.Add("- Dữ liệu đã biết: " + string.Join("; ", knownFacts));
        if (recentAssistantAnswers.Count > 0)
            lines.Add("- Hướng hỗ trợ gần đây: " + string.Join(" | ", recentAssistantAnswers));

        var lastUser = toSummarize.LastOrDefault(m => string.Equals(m.Role, "user", StringComparison.OrdinalIgnoreCase))?.Content ?? string.Empty;
        var pending = DetectPendingIntent(lastUser);
        if (!string.IsNullOrWhiteSpace(pending))
            lines.Add("- Điều còn thiếu: " + pending);

        conv.Summary = lines.Count > 0
            ? string.Join("\n", lines.Take(8))
            : "- Tóm tắt: hội thoại đang diễn ra, chưa có đủ dữ liệu ổn định.";

        conv.Messages = conv.Messages.TakeLast(keepLast).ToList();
        return Task.CompletedTask;

        static string NormalizeSummaryText(string? text, int maxChars)
        {
            if (string.IsNullOrWhiteSpace(text)) return string.Empty;
            var oneLine = Regex.Replace(text.Trim(), @"\s+", " ");
            return oneLine.Length <= maxChars ? oneLine : oneLine[..maxChars] + "...";
        }

        static string? DetectPendingIntent(string text)
        {
            if (string.IsNullOrWhiteSpace(text)) return null;
            var lower = text.ToLowerInvariant();
            if (Regex.IsMatch(lower, @"\b(size|cỡ|co|màu|mau|tồn kho|ton kho|còn hàng|con hang)\b"))
                return "Cần chốt mã sản phẩm cụ thể để tra size/màu/tồn kho chính xác.";
            if (Regex.IsMatch(lower, @"\b(đổi mật khẩu|doi mat khau|quên mật khẩu|quen mat khau)\b"))
                return "Cần hướng dẫn từng bước theo tài khoản người dùng.";
            if (Regex.IsMatch(lower, @"\b(đổi trả|doi tra|giao hàng|giao hang|thanh toán|thanh toan)\b"))
                return "Cần đối chiếu chính sách Help Center tương ứng.";
            return "Chờ câu hỏi kế tiếp để làm rõ nhu cầu.";
        }
    }

    static (string systemText, List<ChatMessage> promptMessages) BuildPromptWithTokenBudget(
        Conversation conv,
        string baseRules,                
        string? summary,                 
        string? memory,                  
        List<string> sourcesBlocks,      
        int maxPromptTokens = 7000,      
        int reserveForAnswerTokens = 800,
        int maxHistoryMessages = 10      
    )
    {
        static int EstTokens(string s) => string.IsNullOrEmpty(s) ? 0 : (s.Length / 4) + 1;

        static string CutChars(string s, int maxChars)
        {
            if (string.IsNullOrEmpty(s) || s.Length <= maxChars) return s;
            return s.Substring(0, maxChars) + "...";
        }

        static List<string> NormalizeSources(List<string> raw)
        {
            var seen = new HashSet<string>(StringComparer.Ordinal);
            var result = new List<string>();
            foreach (var s in raw.Where(x => !string.IsNullOrWhiteSpace(x)))
            {
                var oneLine = Regex.Replace(s.Trim(), @"\s+", " ");
                var key = oneLine.Length > 400 ? oneLine.Substring(0, 400) : oneLine;
                if (seen.Add(key))
                    result.Add(s.Trim());
            }
            return result;
        }

        var normalizedSources = NormalizeSources(sourcesBlocks);
        var sbSys = new StringBuilder();
        sbSys.AppendLine((baseRules ?? string.Empty).Trim());
        sbSys.AppendLine();

        if (!string.IsNullOrWhiteSpace(memory))
        {
            sbSys.AppendLine("MEMORY (FACTS):");
            sbSys.AppendLine(memory.Trim());
            sbSys.AppendLine();
        }

        if (!string.IsNullOrWhiteSpace(summary))
        {
            sbSys.AppendLine("CONVERSATION SUMMARY:");
            sbSys.AppendLine(summary.Trim());
            sbSys.AppendLine();
        }

        var sourcesJoined = string.Join("\n\n---\n\n", normalizedSources);
        sbSys.AppendLine("SOURCES:");
        sbSys.AppendLine(sourcesJoined);

        var systemText = sbSys.ToString().Trim();

        var history = conv.Messages
            .Where(m => m.Role == "user" || m.Role == "assistant")
            .TakeLast(maxHistoryMessages)
            .ToList();
        var prompt = new List<ChatMessage> { new("system", systemText) };
        prompt.AddRange(history);

        int budget = Math.Max(500, maxPromptTokens - reserveForAnswerTokens);

        int TotalTokens(List<ChatMessage> ms)
            => ms.Sum(m => EstTokens(m.Role) + EstTokens(m.Content) + 6);

        if (TotalTokens(prompt) > budget)
        {
            var fixedChars = (baseRules?.Length ?? 0) + (memory?.Length ?? 0) + (summary?.Length ?? 0) + 1200;
            var maxSourcesChars = Math.Max(1200, Math.Min(6000, (budget * 4) - fixedChars));
            var cutSources = sourcesJoined;
            if (cutSources.Length > maxSourcesChars)
                cutSources = CutChars(cutSources, maxSourcesChars);

            var sb2 = new StringBuilder();
            sb2.AppendLine((baseRules ?? string.Empty).Trim()).AppendLine();

            if (!string.IsNullOrWhiteSpace(memory))
            {
                sb2.AppendLine("MEMORY (FACTS):");
                sb2.AppendLine(memory.Trim()).AppendLine();
            }

            if (!string.IsNullOrWhiteSpace(summary))
            {
                sb2.AppendLine("CONVERSATION SUMMARY:");
                sb2.AppendLine(summary.Trim()).AppendLine();
            }

            sb2.AppendLine("SOURCES:");
            sb2.AppendLine(cutSources);

            systemText = sb2.ToString().Trim();
            prompt = new List<ChatMessage> { new("system", systemText) };
            prompt.AddRange(history);
        }

        while (TotalTokens(prompt) > budget && history.Count > 2)
        {
            history = history.Skip(Math.Min(2, history.Count - 1)).ToList();
            prompt = new List<ChatMessage> { new("system", systemText) };
            prompt.AddRange(history);
        }

        if (TotalTokens(prompt) > budget)
        {
            var cutSources = CutChars(sourcesJoined, 2500);

            var sb3 = new StringBuilder();
            sb3.AppendLine((baseRules ?? string.Empty).Trim()).AppendLine();

            if (!string.IsNullOrWhiteSpace(memory))
            {
                sb3.AppendLine("MEMORY (FACTS):");
                sb3.AppendLine(memory.Trim()).AppendLine();
            }

            if (!string.IsNullOrWhiteSpace(summary))
            {
                sb3.AppendLine("CONVERSATION SUMMARY:");
                sb3.AppendLine(summary.Trim()).AppendLine();
            }

            sb3.AppendLine("SOURCES:");
            sb3.AppendLine(cutSources);

            systemText = sb3.ToString().Trim();

            prompt = new List<ChatMessage> { new("system", systemText) };
            prompt.AddRange(history.TakeLast(4));
        }

        return (systemText, prompt);
    }

    static Task UpdateConversationMemoryAsync(
    Conversation conv,
    HttpClient openAi,
    string apiKey,
    CancellationToken ct)
    {
        _ = openAi;
        _ = apiKey;
        _ = ct;

        if (conv.Messages.Count == 0) return Task.CompletedTask;

        var window = conv.Messages.TakeLast(20).ToList();

        string? lastCode = null;
        string? lastSize = null;
        string? lastColor = null;
        string? lastIntent = null;
        string? lastOrderCode = null;

        var colorMap = new Dictionary<string, string>(StringComparer.OrdinalIgnoreCase)
        {
            ["đen"] = "đen", ["den"] = "đen",
            ["trắng"] = "trắng", ["trang"] = "trắng",
            ["xanh"] = "xanh", ["xanh nước"] = "xanh nước", ["xanh nuoc"] = "xanh nước",
            ["đỏ"] = "đỏ", ["do"] = "đỏ",
            ["hồng"] = "hồng", ["hong"] = "hồng",
            ["vàng"] = "vàng", ["vang"] = "vàng",
            ["nâu"] = "nâu", ["nau"] = "nâu",
            ["xám"] = "xám", ["xam"] = "xám"
        };

        foreach (var msg in window)
        {
            var text = msg.Content ?? "";
            if (string.IsNullOrWhiteSpace(text)) continue;
            var lower = text.ToLowerInvariant();

            var code = ExtractProductCode(text);
            if (!string.IsNullOrWhiteSpace(code)) lastCode = code;

            var sizeMatch = Regex.Match(lower, @"\bsize\s*([a-z0-9]{1,4})\b");
            if (sizeMatch.Success) lastSize = sizeMatch.Groups[1].Value.ToUpperInvariant();

            foreach (var kv in colorMap)
            {
                if (lower.Contains(kv.Key))
                    lastColor = kv.Value;
            }

            var orderMatch = Regex.Match(text.ToUpperInvariant(), @"\b(?:ORD|OD|DH|DON)\d{4,}\b");
            if (orderMatch.Success) lastOrderCode = orderMatch.Value;

            if (Regex.IsMatch(lower, @"\b(tồn kho|ton kho|còn hàng|con hang|stock)\b")) lastIntent = "tồn kho";
            else if (Regex.IsMatch(lower, @"\b(giá|gia|bao nhiêu|bao nhieu|mấy tiền|may tien)\b")) lastIntent = "giá";
            else if (Regex.IsMatch(lower, @"\b(size|cỡ|co|màu|mau)\b")) lastIntent = "biến thể";
            else if (Regex.IsMatch(lower, @"\b(đổi trả|doi tra|trả hàng|tra hang)\b")) lastIntent = "đổi trả";
            else if (Regex.IsMatch(lower, @"\b(giao hàng|giao hang|ship|vận chuyển|van chuyen)\b")) lastIntent = "giao hàng";
        }

        if (!string.IsNullOrWhiteSpace(lastCode)) conv.MemoryFacts["product_code"] = lastCode;
        if (!string.IsNullOrWhiteSpace(lastSize)) conv.MemoryFacts["size"] = lastSize;
        if (!string.IsNullOrWhiteSpace(lastColor)) conv.MemoryFacts["color"] = lastColor;
        if (!string.IsNullOrWhiteSpace(lastIntent)) conv.MemoryFacts["intent"] = lastIntent;
        if (!string.IsNullOrWhiteSpace(lastOrderCode)) conv.MemoryFacts["order_code"] = lastOrderCode;

        if (conv.MemoryFacts.Count == 0) return Task.CompletedTask;

        conv.Memory = string.Join("\n", conv.MemoryFacts
            .OrderBy(k => k.Key)
            .Select(kvp => $"- {kvp.Key}: {kvp.Value}"));
        return Task.CompletedTask;
    }
}



