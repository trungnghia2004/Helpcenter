using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal sealed record IntentRoutingDecision(
        string Intent,
        string? SearchQuery = null);

    static async Task<IntentRoutingDecision?> TryClassifyIntentAsync(
        HttpClient openAiHttp,
        string apiKey,
        string model,
        string userText,
        CancellationToken cancellationToken)
    {
        if (string.IsNullOrWhiteSpace(apiKey) ||
            string.IsNullOrWhiteSpace(model) ||
            string.IsNullOrWhiteSpace(userText))
        {
            return null;
        }

        using var timeout = CancellationTokenSource.CreateLinkedTokenSource(cancellationToken);
        timeout.CancelAfter(TimeSpan.FromSeconds(3));

        var body = new
        {
            model,
            store = false,
            stream = false,
            max_output_tokens = 140,
            input = new object[]
            {
                new
                {
                    role = "system",
                    content =
                        "You route user requests for a clothing store assistant. " +
                        "Return JSON only with fields intent and search_query. " +
                        "intent must be one of: product_search, knowledge, general. " +
                        "Use product_search for product discovery, style, category, outfit, sportswear, gym, running, activewear, clothing use-case, or vague clothing shopping intent. " +
                        "Use knowledge for store policy/account/order/help-center questions. " +
                        "search_query should be a short Vietnamese query suitable for product search, or null."
                },
                new
                {
                    role = "user",
                    content = userText.Trim()
                }
            }
        };

        using var request = new HttpRequestMessage(HttpMethod.Post, "https://api.openai.com/v1/responses");
        request.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);
        request.Content = new StringContent(
            JsonSerializer.Serialize(body),
            Encoding.UTF8,
            "application/json");

        using var response = await openAiHttp.SendAsync(request, timeout.Token);
        if (!response.IsSuccessStatusCode)
            return null;

        var payload = await response.Content.ReadAsStringAsync(timeout.Token);
        var text = TryExtractResponseText(payload);
        if (string.IsNullOrWhiteSpace(text))
            return null;

        var json = ExtractJsonObject(text);
        if (string.IsNullOrWhiteSpace(json))
            return null;

        using var doc = JsonDocument.Parse(json);
        var root = doc.RootElement;
        var intent = root.TryGetProperty("intent", out var intentEl) && intentEl.ValueKind == JsonValueKind.String
            ? NormalizeRoutingIntent(intentEl.GetString())
            : null;

        if (intent is null)
            return null;

        var searchQuery = root.TryGetProperty("search_query", out var searchEl) && searchEl.ValueKind == JsonValueKind.String
            ? searchEl.GetString()?.Trim()
            : null;
        if (intent == "product_search" && string.IsNullOrWhiteSpace(searchQuery))
            searchQuery = userText.Trim();

        return new IntentRoutingDecision(intent, searchQuery);
    }

    static string? NormalizeRoutingIntent(string? raw)
    {
        var value = raw?.Trim().ToLowerInvariant();
        return value switch
        {
            "product_search" or "product" or "product_lookup" or "catalog" => "product_search",
            "knowledge" or "help" or "policy" => "knowledge",
            "general" or "other" => "general",
            _ => null
        };
    }

    static string? TryExtractResponseText(string payload)
    {
        if (string.IsNullOrWhiteSpace(payload))
            return null;

        using var doc = JsonDocument.Parse(payload);
        var root = doc.RootElement;

        if (root.TryGetProperty("output_text", out var outputText) &&
            outputText.ValueKind == JsonValueKind.String)
        {
            return outputText.GetString();
        }

        if (!root.TryGetProperty("output", out var output) || output.ValueKind != JsonValueKind.Array)
            return null;

        var sb = new StringBuilder();
        foreach (var item in output.EnumerateArray())
        {
            if (!item.TryGetProperty("content", out var content) || content.ValueKind != JsonValueKind.Array)
                continue;

            foreach (var part in content.EnumerateArray())
            {
                if (!part.TryGetProperty("text", out var textEl) || textEl.ValueKind != JsonValueKind.String)
                    continue;

                sb.Append(textEl.GetString());
            }
        }

        return sb.Length == 0 ? null : sb.ToString();
    }

    static string? ExtractJsonObject(string text)
    {
        if (string.IsNullOrWhiteSpace(text))
            return null;

        var trimmed = text.Trim();
        if (trimmed.StartsWith("```", StringComparison.Ordinal))
        {
            var firstBrace = trimmed.IndexOf('{');
            var lastBrace = trimmed.LastIndexOf('}');
            if (firstBrace >= 0 && lastBrace > firstBrace)
                return trimmed.Substring(firstBrace, lastBrace - firstBrace + 1);
        }

        if (trimmed.StartsWith("{", StringComparison.Ordinal) &&
            trimmed.EndsWith("}", StringComparison.Ordinal))
        {
            return trimmed;
        }

        var start = trimmed.IndexOf('{');
        var end = trimmed.LastIndexOf('}');
        if (start >= 0 && end > start)
            return trimmed.Substring(start, end - start + 1);

        return null;
    }
}
