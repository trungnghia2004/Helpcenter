using System.Collections.Concurrent;
using System.Globalization;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using System.Linq;

namespace ChatAgentApi;

public partial class Program
{
    static int EstimateTokenCount(string text)
    {
        if (string.IsNullOrEmpty(text)) return 0;
        return (text.Length / 4) + 1;
    }

    static int EstimateTokenCount(List<ChatMessage> messages)
    {
        if (messages is null || messages.Count == 0) return 0;
        var total = 0;
        foreach (var m in messages)
            total += 6 + EstimateTokenCount(m.Role) + EstimateTokenCount(m.Content);
        return total;
    }

    static string CleanUserFacingLiveText(string text)
    {
        if (string.IsNullOrWhiteSpace(text)) return text;
        var cleaned = text
            .Split('\n')
            .Select(x => x.TrimEnd('\r'))
            .Where(x =>
                !x.StartsWith("LIVE PRODUCT DATA:", StringComparison.Ordinal) &&
                !x.StartsWith("LIVE PRODUCT LIST:", StringComparison.Ordinal))
            .ToList();
        return string.Join("\n", cleaned).Trim();
    }
}

