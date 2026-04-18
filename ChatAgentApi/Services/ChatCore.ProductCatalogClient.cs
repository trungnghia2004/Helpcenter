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
    static async Task<JsonElement?> GetProductByCodeAsync(HttpClient http, string baseUrl, string code, CancellationToken ct)
    {
        var url = $"{baseUrl}/api/products/by-code/{Uri.EscapeDataString(code)}";
        var json = await GetJsonWithCacheAsync(
            cacheKey: $"by-code:{code.ToUpperInvariant()}",
            ttl: TimeSpan.FromSeconds(45),
            fetch: async () =>
            {
                using var resp = await http.GetAsync(url, ct);
                if (!resp.IsSuccessStatusCode) return null;
                return await resp.Content.ReadAsStringAsync(ct);
            });
        if (string.IsNullOrWhiteSpace(json)) return null;

        using var doc = JsonDocument.Parse(json);
        return doc.RootElement.Clone();
    }

    static async Task<JsonElement?> SearchFirstProductAsync(HttpClient http, string baseUrl, string q, CancellationToken ct)
    {
        var items = await SearchProductsAsync(http, baseUrl, q, ct);
        return items.Count > 0 ? items[0] : null;
    }

    static async Task<List<JsonElement>> GetProductsByCategoryAsync(HttpClient http, string baseUrl, string categoryKeyword, CancellationToken ct)
    {
        var url = $"{baseUrl}/api/products/by-category?q={Uri.EscapeDataString(categoryKeyword)}";
        var json = await GetJsonWithCacheAsync(
            cacheKey: $"by-category:{categoryKeyword.ToLowerInvariant()}",
            ttl: TimeSpan.FromSeconds(40),
            fetch: async () =>
            {
                using var resp = await http.GetAsync(url, ct);
                if (!resp.IsSuccessStatusCode) return null;
                return await resp.Content.ReadAsStringAsync(ct);
            });
        if (string.IsNullOrWhiteSpace(json)) return new List<JsonElement>();

        using var doc = JsonDocument.Parse(json);
        var arr = doc.RootElement;
        if (arr.ValueKind != JsonValueKind.Array) return new List<JsonElement>();

        return arr.EnumerateArray()
            .Select(x => x.Clone())
            .Take(12)
            .ToList();
    }

    static async Task<List<JsonElement>> SearchProductsAsync(HttpClient http, string baseUrl, string q, CancellationToken ct)
    {
        var results = new List<JsonElement>();
        var seen = new HashSet<long>();
        var plainQ = RemoveDiacritics(q.ToLowerInvariant());
        var keywords = ExtractSearchKeywords(q);
        var strictCategory = ExtractStrictCategoryKeyword(plainQ);

        foreach (var query in BuildSearchQueries(q))
        {
            var url = $"{baseUrl}/api/products/search?q={Uri.EscapeDataString(query)}";
            var json = await GetJsonWithCacheAsync(
                cacheKey: $"search:{query.ToLowerInvariant()}",
                ttl: TimeSpan.FromSeconds(30),
                fetch: async () =>
                {
                    using var resp = await http.GetAsync(url, ct);
                    if (!resp.IsSuccessStatusCode) return null;
                    return await resp.Content.ReadAsStringAsync(ct);
                });
            if (string.IsNullOrWhiteSpace(json)) continue;

            using var doc = JsonDocument.Parse(json);
            var arr = doc.RootElement;
            if (arr.ValueKind != JsonValueKind.Array) continue;

            foreach (var item in arr.EnumerateArray())
            {
                if (!item.TryGetProperty("productID", out var pidEl)) continue;
                var pid = pidEl.GetInt64();
                if (!seen.Add(pid)) continue;
                results.Add(item.Clone());
                if (results.Count >= 25) break;
            }
        }

        var ranked = results
            .Select(item => new
            {
                item,
                score = ScoreProductForQuery(item, plainQ, keywords, strictCategory)
            })
            .Where(x => x.score > 0)
            .OrderByDescending(x => x.score)
            .Take(8)
            .Select(x => x.item)
            .ToList();

        return ranked;
    }

    static IEnumerable<string> BuildSearchQueries(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) yield break;

        var original = q.Trim();
        if (original.Length > 0) yield return original;

        var lower = Regex.Replace(original.ToLowerInvariant(), @"\s+", " ").Trim();

        string[] keyPhrases =
        {
            "quần short", "quan short", "short",
            "quần jean", "quan jean", "jean",
            "áo gile", "ao gile", "gile",
            "hoodie", "áo thun", "ao thun", "thun",
            "áo", "ao", "quần", "quan"
        };
        foreach (var k in keyPhrases)
            if (lower.Contains(k)) yield return k;

        var stop = new HashSet<string>
        {
            "co","có","nhung","những","loai","loại","nao","nào","san","sản","pham","phẩm",
            "cua","của","cho","toi","tôi","muon","muốn","biet","biết","con","còn","khong","không"
        };

        var tokens = Regex.Matches(lower, @"[\p{L}\p{Nd}]+")
            .Select(m => m.Value)
            .Where(t => t.Length >= 3 && !stop.Contains(t))
            .Distinct()
            .Take(4)
            .ToList();

        foreach (var t in tokens) yield return t;
    }

    static HashSet<string> ExtractSearchKeywords(string q)
    {
        var plain = RemoveDiacritics((q ?? string.Empty).ToLowerInvariant());
        var keywords = new HashSet<string>(StringComparer.OrdinalIgnoreCase);

        string[] phrases =
        {
            "ao gile", "gile",
            "ao thun", "thun",
            "hoodie",
            "quan short", "short",
            "quan jean", "jean",
            "ao", "quan"
        };

        foreach (var p in phrases)
            if (plain.Contains(p))
                keywords.Add(p);

        return keywords;
    }

    static string? ExtractCategoryKeyword(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return null;
        var plain = RemoveDiacritics(q.ToLowerInvariant());

        if (plain.Contains("short")) return "Short";
        if (plain.Contains("jean")) return "Jeans";
        if (plain.Contains("hoodie")) return "Hoodie";
        if (plain.Contains("gile")) return "Gile";
        if (plain.Contains("thun")) return "Thun";
        if (plain.Contains("khoac")) return "Khoa";

        return null;
    }

    static string? ExtractStrictCategoryKeyword(string plainQ)
    {
        string[] strict = { "gile", "short", "jean", "thun", "hoodie" };
        return strict.FirstOrDefault(plainQ.Contains);
    }

    static int ScoreProductForQuery(JsonElement item, string plainQ, HashSet<string> keywords, string? strictCategory)
    {
        var name = item.TryGetProperty("productName", out var n) ? (n.GetString() ?? string.Empty) : string.Empty;
        var category = item.TryGetProperty("categoryName", out var cat) ? (cat.GetString() ?? string.Empty) : string.Empty;
        var code = item.TryGetProperty("productCode", out var c) ? (c.GetString() ?? string.Empty) : string.Empty;
        var plainName = RemoveDiacritics(name.ToLowerInvariant());
        var plainCategory = RemoveDiacritics(category.ToLowerInvariant());
        var combined = $"{plainName} {plainCategory}".Trim();
        var score = 0;

        if (!string.IsNullOrWhiteSpace(strictCategory) && !combined.Contains(strictCategory))
            return 0;

        foreach (var kw in keywords)
        {
            if (combined.Contains(kw)) score += kw.Contains(' ') ? 4 : 3;
        }

        if (!string.IsNullOrWhiteSpace(code) && plainQ.Contains(code.ToLowerInvariant())) score += 6;
        if (score == 0 && keywords.Count == 0) score = 1;

        return score;
    }

    static async Task<JsonElement?> GetVariantsAsync(HttpClient http, string baseUrl, long productId, CancellationToken ct)
    {
        var url = $"{baseUrl}/api/products/{productId}/variants";
        var json = await GetJsonWithCacheAsync(
            cacheKey: $"variants:{productId}",
            ttl: TimeSpan.FromSeconds(20),
            fetch: async () =>
            {
                using var resp = await http.GetAsync(url, ct);
                if (!resp.IsSuccessStatusCode) return null;
                return await resp.Content.ReadAsStringAsync(ct);
            });
        if (string.IsNullOrWhiteSpace(json)) return null;

        using var doc = JsonDocument.Parse(json);
        return doc.RootElement.Clone();
    }

    static async Task<string?> GetJsonWithCacheAsync(
        string cacheKey,
        TimeSpan ttl,
        Func<Task<string?>> fetch)
    {
        if (LaravelCache.TryGetValue(cacheKey, out var hit) && hit.ExpiresAtUtc > DateTime.UtcNow)
            return hit.Json;

        var fresh = await fetch();
        if (!string.IsNullOrWhiteSpace(fresh))
            LaravelCache[cacheKey] = new CacheItem(fresh, DateTime.UtcNow.Add(ttl));

        return fresh;
    }
}


