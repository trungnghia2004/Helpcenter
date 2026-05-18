using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using System.Linq;

namespace ChatAgentApi;

internal static partial class ChatCore
{
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
