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
    static async IAsyncEnumerable<string> OpenAIStream(
        HttpClient http,
        string model,
        string apiKey,
        List<ChatMessage> messages,
        Action<OpenAiUsage>? onUsage,
        [System.Runtime.CompilerServices.EnumeratorCancellation] CancellationToken ct)
    {
        if (string.IsNullOrWhiteSpace(apiKey))
            throw new InvalidOperationException("Missing OPENAI_API_KEY environment variable.");

        var url = "https://api.openai.com/v1/chat/completions";

        using var req = new HttpRequestMessage(HttpMethod.Post, url);
        req.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);
        req.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("text/event-stream"));
        req.Content = new StringContent(BuildOpenAiChatRequestJson(model, messages), Encoding.UTF8, "application/json");

        using var resp = await http.SendAsync(req, HttpCompletionOption.ResponseHeadersRead, ct);
        resp.EnsureSuccessStatusCode();

        await using var stream = await resp.Content.ReadAsStreamAsync(ct);
        using var reader = new StreamReader(stream);

        while (!ct.IsCancellationRequested)
        {
            var line = await reader.ReadLineAsync();
            if (line is null) break;
            if (!line.StartsWith("data:")) continue;

            var data = line[5..].Trim();
            if (data.Length == 0 || data == "[DONE]") continue;

            string? text = null;
            try
            {
                using var doc = JsonDocument.Parse(data);
                var root = doc.RootElement;

                if (root.TryGetProperty("usage", out var usageEl) && usageEl.ValueKind == JsonValueKind.Object)
                {
                    var promptTokens = usageEl.TryGetProperty("prompt_tokens", out var p) && p.ValueKind == JsonValueKind.Number ? p.GetInt32() : 0;
                    var completionTokens = usageEl.TryGetProperty("completion_tokens", out var c) && c.ValueKind == JsonValueKind.Number ? c.GetInt32() : 0;
                    var totalTokens = usageEl.TryGetProperty("total_tokens", out var t) && t.ValueKind == JsonValueKind.Number ? t.GetInt32() : (promptTokens + completionTokens);
                    onUsage?.Invoke(new OpenAiUsage(promptTokens, completionTokens, totalTokens));
                }

                if (root.TryGetProperty("choices", out var choices) &&
                    choices.ValueKind == JsonValueKind.Array &&
                    choices.GetArrayLength() > 0)
                {
                    var c0 = choices[0];
                    if (c0.TryGetProperty("delta", out var delta) &&
                        delta.TryGetProperty("content", out var contentEl) &&
                        contentEl.ValueKind == JsonValueKind.String)
                    {
                        text = contentEl.GetString();
                    }
                }
            }
            catch { }

            if (!string.IsNullOrEmpty(text))
                yield return text!;
        }
    }

    static string BuildOpenAiChatRequestJson(string model, List<ChatMessage> messages)
    {
        using var ms = new MemoryStream();
        using (var w = new Utf8JsonWriter(ms))
        {
            w.WriteStartObject();
            w.WriteString("model", model);
            w.WriteBoolean("stream", true);
            w.WriteNumber("temperature", 0.2);
            w.WritePropertyName("stream_options");
            w.WriteStartObject();
            w.WriteBoolean("include_usage", true);
            w.WriteEndObject();
            w.WritePropertyName("messages");
            w.WriteStartArray();

            foreach (var m in messages)
            {
                w.WriteStartObject();
                var role = m.Role switch
                {
                    "assistant" => "assistant",
                    "system" => "system",
                    _ => "user"
                };
                w.WriteString("role", role);
                w.WriteString("content", m.Content);
                w.WriteEndObject();
            }

            w.WriteEndArray();
            w.WriteEndObject();
        }

        return Encoding.UTF8.GetString(ms.ToArray());
    }

}

