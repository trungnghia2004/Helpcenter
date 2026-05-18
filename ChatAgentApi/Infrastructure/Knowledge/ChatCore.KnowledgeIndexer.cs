using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal static class KnowledgeIndexer
    {
        sealed class InsufficientQuotaException : Exception
        {
            public InsufficientQuotaException(string message) : base(message) { }
        }

        public static async Task BuildIndexJsonl(
            HttpClient http,
            string apiKey,
            string embeddingModel,
            string knowledgeDir,
            string outPath,
            int chunkChars,
            int overlapChars,
            CancellationToken ct)
        {
            var files = Directory.EnumerateFiles(knowledgeDir, "*.md", SearchOption.AllDirectories).ToList();
            if (files.Count == 0)
            {
                Console.WriteLine($"No .md files found in: {knowledgeDir}");
                File.WriteAllText(outPath, "");
                return;
            }

            using var fs = new FileStream(outPath, FileMode.Create, FileAccess.Write, FileShare.None);
            using var sw = new StreamWriter(fs, Encoding.UTF8);

            var id = 0;
            var successChunks = 0;
            var failedChunks = 0;
            var lexicalOnlyChunks = 0;
            var embeddingDisabledByQuota = false;
            foreach (var f in files)
            {
                var source = Path.GetFileName(f);
                var text = await File.ReadAllTextAsync(f, ct);

                foreach (var chunk in ChunkText(text, chunkChars, overlapChars))
                {
                    id++;

                    if (embeddingDisabledByQuota)
                    {
                        var lineLexical = BuildJsonlLine(id.ToString(), source, chunk, Array.Empty<float>());
                        await sw.WriteLineAsync(lineLexical);
                        await sw.FlushAsync();
                        lexicalOnlyChunks++;
                        Console.WriteLine($"Indexed (lexical-only) {source} chunk #{id}");
                        continue;
                    }

                    try
                    {
                        var vec = await EmbedAsync(http, apiKey, embeddingModel, chunk, ct);
                        var line = BuildJsonlLine(id.ToString(), source, chunk, vec);
                        await sw.WriteLineAsync(line);
                        await sw.FlushAsync();
                        successChunks++;
                        Console.WriteLine($"Indexed {source} chunk #{id}");
                    }
                    catch (InsufficientQuotaException ex)
                    {
                        embeddingDisabledByQuota = true;
                        var lineLexical = BuildJsonlLine(id.ToString(), source, chunk, Array.Empty<float>());
                        await sw.WriteLineAsync(lineLexical);
                        await sw.FlushAsync();
                        lexicalOnlyChunks++;
                        Console.WriteLine($"[WARN] {ex.Message}");
                        Console.WriteLine($"[WARN] Switch to lexical-only indexing from chunk #{id}.");
                    }
                    catch (HttpRequestException ex)
                    {
                        failedChunks++;
                        Console.WriteLine($"[WARN] Skip chunk #{id} ({source}) due to embedding error: {ex.Message}");
                    }
                }
            }

            Console.WriteLine(
                $"Knowledge indexing done. Embedding: {successChunks}, Lexical-only: {lexicalOnlyChunks}, Failed: {failedChunks}");
        }

        public static IEnumerable<string> ChunkText(string text, int chunkChars, int overlapChars)
        {
            text = text.Replace("\r\n", "\n");
            if (chunkChars <= 0) yield break;

            var i = 0;
            while (i < text.Length)
            {
                var take = Math.Min(chunkChars, text.Length - i);
                var chunk = text.Substring(i, take).Trim();
                if (!string.IsNullOrWhiteSpace(chunk))
                    yield return chunk;

                if (i + take >= text.Length) break;
                i = Math.Max(0, i + take - overlapChars);
            }
        }

        public static async Task<float[]> EmbedAsync(HttpClient http, string apiKey, string model, string text, CancellationToken ct)
        {
            var url = "https://api.openai.com/v1/embeddings";
            const int maxAttempts = 6;

            for (var attempt = 1; attempt <= maxAttempts; attempt++)
            {
                using var req = new HttpRequestMessage(HttpMethod.Post, url);
                req.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);
                req.Content = new StringContent(BuildEmbedRequestJson(model, text), Encoding.UTF8, "application/json");

                using var resp = await http.SendAsync(req, ct);
                if (resp.IsSuccessStatusCode)
                {
                    var json = await resp.Content.ReadAsStringAsync(ct);

                    using var doc = JsonDocument.Parse(json);
                    var values = doc.RootElement.GetProperty("data")[0].GetProperty("embedding");

                    var vec = new float[values.GetArrayLength()];
                    var idx = 0;
                    foreach (var v in values.EnumerateArray())
                        vec[idx++] = (float)v.GetDouble();

                    return vec;
                }

                var status = (int)resp.StatusCode;
                var body = await SafeReadBodyAsync(resp, ct);
                if (status == 429 && body.Contains("\"insufficient_quota\"", StringComparison.OrdinalIgnoreCase))
                    throw new InsufficientQuotaException("OpenAI quota is insufficient (429 insufficient_quota).");

                if (!IsRetryableStatus(status) || attempt == maxAttempts)
                {
                    throw new HttpRequestException($"Embedding request failed with {status} ({resp.ReasonPhrase}). Body: {body}");
                }

                var delay = ResolveRetryDelay(resp, attempt);
                Console.WriteLine($"[RETRY] Embedding attempt {attempt}/{maxAttempts} got {status}. Wait {delay.TotalSeconds:0.0}s...");
                await Task.Delay(delay, ct);
            }

            throw new HttpRequestException("Embedding request failed after retries.");
        }

        static bool IsRetryableStatus(int status)
            => status == 429 || status == 500 || status == 502 || status == 503 || status == 504;

        static TimeSpan ResolveRetryDelay(HttpResponseMessage resp, int attempt)
        {
            var retry = resp.Headers.RetryAfter;
            if (retry?.Delta is TimeSpan delta && delta > TimeSpan.Zero)
                return CapDelay(delta);

            if (retry?.Date is DateTimeOffset date)
            {
                var until = date - DateTimeOffset.UtcNow;
                if (until > TimeSpan.Zero)
                    return CapDelay(until);
            }

            var jitterMs = Random.Shared.Next(50, 450);
            var seconds = Math.Pow(2, attempt - 1);
            return CapDelay(TimeSpan.FromSeconds(seconds) + TimeSpan.FromMilliseconds(jitterMs));
        }

        static TimeSpan CapDelay(TimeSpan delay)
            => delay > TimeSpan.FromSeconds(30) ? TimeSpan.FromSeconds(30) : delay;

        static async Task<string> SafeReadBodyAsync(HttpResponseMessage resp, CancellationToken ct)
        {
            try
            {
                var body = await resp.Content.ReadAsStringAsync(ct);
                if (string.IsNullOrWhiteSpace(body)) return "<empty>";
                return body.Length <= 600 ? body : body[..600] + "...";
            }
            catch
            {
                return "<unavailable>";
            }
        }

        static string BuildEmbedRequestJson(string model, string text)
        {
            using var ms = new MemoryStream();
            using (var w = new Utf8JsonWriter(ms))
            {
                w.WriteStartObject();
                w.WriteString("model", model);
                w.WriteString("input", text);
                w.WriteEndObject();
            }
            return Encoding.UTF8.GetString(ms.ToArray());
        }

        static string BuildJsonlLine(string id, string source, string text, float[] vec)
        {
            using var ms = new MemoryStream();
            using (var w = new Utf8JsonWriter(ms))
            {
                w.WriteStartObject();
                w.WriteString("id", id);
                w.WriteString("source", source);
                w.WriteString("text", text);

                w.WritePropertyName("vector");
                w.WriteStartArray();
                for (int i = 0; i < vec.Length; i++)
                    w.WriteNumberValue(vec[i]);
                w.WriteEndArray();

                w.WriteEndObject();
            }
            return Encoding.UTF8.GetString(ms.ToArray());
        }
    }
}
