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
    internal static class KnowledgeIndexer
    {
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
            foreach (var f in files)
            {
                var source = Path.GetFileName(f);
                var text = await File.ReadAllTextAsync(f, ct);

                foreach (var chunk in ChunkText(text, chunkChars, overlapChars))
                {
                    id++;
                    var vec = await EmbedAsync(http, apiKey, embeddingModel, chunk, ct);
                    var line = BuildJsonlLine(id.ToString(), source, chunk, vec);
                    await sw.WriteLineAsync(line);
                    await sw.FlushAsync();
                    Console.WriteLine($"Indexed {source} chunk #{id}");
                }
            }
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

            using var req = new HttpRequestMessage(HttpMethod.Post, url);
            req.Headers.Authorization = new AuthenticationHeaderValue("Bearer", apiKey);
            req.Content = new StringContent(BuildEmbedRequestJson(model, text), Encoding.UTF8, "application/json");

            using var resp = await http.SendAsync(req, ct);
            resp.EnsureSuccessStatusCode();

            var json = await resp.Content.ReadAsStringAsync(ct);

            using var doc = JsonDocument.Parse(json);
            var values = doc.RootElement.GetProperty("data")[0].GetProperty("embedding");

            var vec = new float[values.GetArrayLength()];
            var idx = 0;
            foreach (var v in values.EnumerateArray())
                vec[idx++] = (float)v.GetDouble();

            return vec;
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

    internal sealed class KnowledgeChunk
    {
        public required string Id { get; init; }
        public required string Source { get; init; }
        public required string Text { get; init; }
        public required float[] Vector { get; init; }
    }

    internal sealed class KnowledgeBase
    {
        public List<KnowledgeChunk> Chunks { get; } = new();

        public static KnowledgeBase Load(string indexPath)
        {
            var kb = new KnowledgeBase();
            if (!File.Exists(indexPath)) return kb;

            foreach (var line in File.ReadLines(indexPath))
            {
                var s = line.Trim();
                if (s.Length == 0) continue;

                try
                {
                    using var doc = JsonDocument.Parse(s);
                    var r = doc.RootElement;

                    var id = r.GetProperty("id").GetString() ?? "";
                    var source = r.GetProperty("source").GetString() ?? "";
                    var text = r.GetProperty("text").GetString() ?? "";

                    var vArr = r.GetProperty("vector");
                    var vec = new float[vArr.GetArrayLength()];
                    var i = 0;
                    foreach (var v in vArr.EnumerateArray())
                        vec[i++] = (float)v.GetDouble();

                    kb.Chunks.Add(new KnowledgeChunk { Id = id, Source = source, Text = text, Vector = vec });
                }
                catch { }
            }

            Console.WriteLine($"Loaded knowledge chunks: {kb.Chunks.Count}");
            return kb;
        }

        public List<(KnowledgeChunk chunk, float score)> SearchTopK(float[] query, int k)
        {
            var results = new List<(KnowledgeChunk chunk, float score)>(Chunks.Count);
            foreach (var c in Chunks)
            {
                var score = Cosine(query, c.Vector);
                results.Add((c, score));
            }
            return results.OrderByDescending(x => x.score).Take(k).ToList();
        }

        public List<(KnowledgeChunk chunk, float score)> SearchTopKLexical(string query, int k)
        {
            var q = RemoveDiacritics((query ?? string.Empty).ToLowerInvariant());
            var tokens = Regex.Matches(q, @"[\p{L}\p{Nd}]+")
                .Select(m => m.Value)
                .Where(t => t.Length >= 2)
                .Distinct()
                .ToList();

            var scored = new List<(KnowledgeChunk chunk, float score)>(Chunks.Count);
            foreach (var c in Chunks)
            {
                var text = RemoveDiacritics((c.Text ?? string.Empty).ToLowerInvariant());
                var score = 0f;
                foreach (var t in tokens)
                {
                    if (text.Contains(t, StringComparison.Ordinal))
                        score += t.Length >= 4 ? 2f : 1f;
                }
                if (score > 0) scored.Add((c, score));
            }

            return scored.OrderByDescending(x => x.score).Take(k).ToList();
        }

        public static string FormatSources(List<(KnowledgeChunk chunk, float score)> top, int maxCharsPerChunk = 900)
        {
            if (top.Count == 0) return "";

            var sb = new StringBuilder();
            var i = 1;
            foreach (var (chunk, score) in top)
            {
                var text = chunk.Text ?? "";
                if (text.Length > maxCharsPerChunk) text = text[..maxCharsPerChunk] + "...";

                sb.AppendLine($"[KB{i}] ({chunk.Source}) score={score:0.###}");
                sb.AppendLine(text);
                sb.AppendLine();
                i++;
            }
            return sb.ToString().TrimEnd();
        }

        static float Cosine(float[] a, float[] b)
        {
            var len = Math.Min(a.Length, b.Length);
            double dot = 0, na = 0, nb = 0;

            for (int i = 0; i < len; i++)
            {
                var x = a[i];
                var y = b[i];
                dot += x * y;
                na += x * x;
                nb += y * y;
            }

            var denom = Math.Sqrt(na) * Math.Sqrt(nb);
            if (denom == 0) return 0;
            return (float)(dot / denom);
        }
    }
}



