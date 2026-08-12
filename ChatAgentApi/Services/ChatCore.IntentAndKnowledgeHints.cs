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
    static bool IsKnowledgeIntent(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return false;
        var plain = RemoveDiacritics(q.ToLowerInvariant());

        return plain.Contains("bang size", StringComparison.Ordinal) ||
               plain.Contains("size guide", StringComparison.Ordinal) ||
               plain.Contains("kich co", StringComparison.Ordinal) ||
               plain.Contains("giao hang", StringComparison.Ordinal) ||
               plain.Contains("ship", StringComparison.Ordinal) ||
               plain.Contains("van chuyen", StringComparison.Ordinal) ||
               plain.Contains("thanh toan", StringComparison.Ordinal) ||
               plain.Contains("doi tra", StringComparison.Ordinal) ||
               plain.Contains("tra hang", StringComparison.Ordinal) ||
               plain.Contains("hoan tien", StringComparison.Ordinal) ||
               plain.Contains("theo doi don", StringComparison.Ordinal) ||
               plain.Contains("kiem tra don", StringComparison.Ordinal) ||
               plain.Contains("don hang", StringComparison.Ordinal) ||
               plain.Contains("cach mua hang", StringComparison.Ordinal) ||
               plain.Contains("cach dat hang", StringComparison.Ordinal) ||
               plain.Contains("huong dan mua", StringComparison.Ordinal) ||
               plain.Contains("mua hang", StringComparison.Ordinal) ||
               plain.Contains("dat hang", StringComparison.Ordinal) ||
               plain.Contains("bao mat", StringComparison.Ordinal) ||
               plain.Contains("chinh sach bao mat", StringComparison.Ordinal) ||
               plain.Contains("doi mat khau", StringComparison.Ordinal) ||
               plain.Contains("quen mat khau", StringComparison.Ordinal) ||
               plain.Contains("mat khau", StringComparison.Ordinal) ||
               plain.Contains("tai khoan", StringComparison.Ordinal) ||
               plain.Contains("dang nhap", StringComparison.Ordinal);
    }

    static string? TryGetLocalKnowledgeAnswer(string query, string knowledgeDir)
    {
        if (string.IsNullOrWhiteSpace(query)) return null;

        var plain = RemoveDiacritics(query.ToLowerInvariant());
        string? file = null;

        if (plain.Contains("bang size") || plain.Contains("size guide") || plain.Contains("kich co"))
            file = "size-guide.md";
        else if (plain.Contains("giao hang") || plain.Contains("ship") || plain.Contains("van chuyen"))
            file = "shipping.md";
        else if (plain.Contains("thanh toan"))
            file = "payment.md";
        else if (plain.Contains("doi tra") || plain.Contains("tra hang") || plain.Contains("hoan tien"))
            file = "faq.md";
        else if (plain.Contains("theo doi don") || plain.Contains("kiem tra don") || plain.Contains("don hang"))
            file = "order-tracking.md";
        else if (plain.Contains("cach mua hang") || plain.Contains("cach dat hang") || plain.Contains("huong dan mua") || plain.Contains("mua hang") || plain.Contains("dat hang"))
            file = "ordering.md";
        else if (plain.Contains("bao mat") || plain.Contains("chinh sach bao mat"))
            file = "privacy-policy.md";
        else if (plain.Contains("doi mat khau") || plain.Contains("quen mat khau") || plain.Contains("mat khau") || plain.Contains("tai khoan") || plain.Contains("dang nhap"))
            file = "account.md";

        if (string.IsNullOrWhiteSpace(file)) return null;

        var path = Path.Combine(knowledgeDir, file);
        if (!File.Exists(path)) return null;

        try
        {
            var txt = File.ReadAllText(path);
            if (string.IsNullOrWhiteSpace(txt)) return null;
            if (txt.Length > 1400) txt = txt[..1400] + "...";
            return BuildLocalKnowledgeResponse(file, txt.Trim(), query);
        }
        catch
        {
            return null;
        }
    }

    static string BuildLocalKnowledgeResponse(string file, string content, string query)
    {
        if (WantsDetailedAnswer(query))
            return content;

        var lines = content
            .Split('\n')
            .Select(x => x.Trim())
            .Where(x => !string.IsNullOrWhiteSpace(x))
            .Where(x => !x.StartsWith("#", StringComparison.Ordinal))
            .ToList();

        var picks = lines
            .Where(x => x.StartsWith("-", StringComparison.Ordinal) || Regex.IsMatch(x, @"^\d+\."))
            .Take(6)
            .ToList();

        if (picks.Count == 0)
            picks = lines.Take(4).ToList();

        return string.Join("\n", picks);
    }

    static bool WantsDetailedAnswer(string query)
    {
        var plain = RemoveDiacritics(query.ToLowerInvariant());
        return plain.Contains("chi tiet", StringComparison.Ordinal) ||
               plain.Contains("day du", StringComparison.Ordinal) ||
               plain.Contains("toan bo", StringComparison.Ordinal) ||
               plain.Contains("full", StringComparison.Ordinal);
    }

    static bool IsProductIntent(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return false;
        if (ExtractProductCode(q) is not null) return true;

        var plain = RemoveDiacritics(q.ToLowerInvariant());
        if (Regex.IsMatch(plain, @"\b(ao|quan|short|hoodie|jean|thun|gile)\b"))
            return true;
        if (Regex.IsMatch(plain, @"\b(do the thao|the thao|sportswear)\b"))
            return true;
        if (Regex.IsMatch(plain, @"\b(san pham|sp)\b"))
            return true;
        if (Regex.IsMatch(plain, @"\b(bao nhieu|may tien|gia)\b"))
            return true;
        if (Regex.IsMatch(plain, @"\b(size|kich co|mau|con hang|ton kho|stock)\b"))
            return true;

        return false;
    }

    static bool IsVariantIntent(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return false;
        var plain = RemoveDiacritics(q.ToLowerInvariant());

        return Regex.IsMatch(plain, @"\b(size|kich co|mau|con hang|ton kho|stock)\b");
    }

    static bool IsBrowseIntent(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return false;
        var plain = RemoveDiacritics(q.ToLowerInvariant().Trim());

        string[] kws =
        {
            "loai nao", "nhung loai", "co nhung", "goi y", "danh sach"
        };
        if (kws.Any(plain.Contains)) return true;

        if (Regex.IsMatch(plain, @"\b(co|shop co)\b") &&
            Regex.IsMatch(plain, @"\b(khong)\b") &&
            IsProductIntent(q))
            return true;

        var hasCategory = Regex.IsMatch(plain, @"\b(ao|quan|short|jean|thun|hoodie|gile)\b");
        var hasBrowseWord = Regex.IsMatch(plain, @"\b(nao|nhung|cac|goi y|danh sach|san pham|sp)\b");
        return hasCategory && hasBrowseWord;
    }

    static bool IsCategoryOnlyIntent(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return false;
        if (ExtractProductCode(q) is not null) return false;
        if (IsVariantIntent(q)) return false;

        var plain = RemoveDiacritics(q.ToLowerInvariant());
        var stop = new HashSet<string>(StringComparer.OrdinalIgnoreCase)
        {
            "san", "pham", "sp", "co", "nhung", "cac", "loai", "la", "toi", "muon", "tim", "kiem"
        };
        var allowedCategoryWords = new HashSet<string>(StringComparer.OrdinalIgnoreCase)
        {
            "ao", "quan", "short", "jean", "hoodie", "thun", "gile", "khoac"
        };

        var tokens = Regex.Matches(plain, @"[a-z0-9]+")
            .Select(m => m.Value)
            .Where(t => !stop.Contains(t))
            .ToList();

        if (tokens.Count == 0) return false;
        if (tokens.Count > 4) return false;
        if (!tokens.Any(t => allowedCategoryWords.Contains(t))) return false;

        return tokens.All(t => allowedCategoryWords.Contains(t));
    }

    static bool IsCategoryOverviewIntent(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return false;
        var plain = RemoveDiacritics(q.ToLowerInvariant());

        if (Regex.IsMatch(plain, @"\b(danh muc|loai hang|nganh hang)\b"))
            return true;

        var asksAllProducts =
            Regex.IsMatch(plain, @"\b(san pham|sp)\b") &&
            (Regex.IsMatch(plain, @"\b(nhung|cac|tat ca)\b") || plain.Contains("dang co", StringComparison.Ordinal));
        if (asksAllProducts)
            return true;

        if (plain.Contains("shop co gi", StringComparison.Ordinal) ||
            plain.Contains("hien co gi", StringComparison.Ordinal))
            return true;

        return false;
    }

    static string RemoveDiacritics(string text)
    {
        if (string.IsNullOrWhiteSpace(text)) return text;
        text = CanonicalizeUserText(text).ToLowerInvariant();

        const string vi = "àáạảãâầấậẩẫăằắặẳẵèéẹẻẽêềếệểễìíịỉĩòóọỏõôồốộổỗơờớợởỡùúụủũưừứựửữỳýỵỷỹđ";
        const string ascii = "aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd";

        var sb = new StringBuilder(text.Length);
        foreach (var ch in text)
        {
            var idx = vi.IndexOf(ch);
            sb.Append(idx >= 0 ? ascii[idx] : ch);
        }

        return sb.ToString();
    }

    static string CanonicalizeUserText(string text)
    {
        if (string.IsNullOrWhiteSpace(text)) return text;
        var decoded = DecodeEscapedUnicode(text);
        return RepairMojibakeUtf8(decoded);
    }

    static string DecodeEscapedUnicode(string text)
    {
        if (string.IsNullOrWhiteSpace(text)) return text;
        if (!Regex.IsMatch(text, @"\\u[0-9a-fA-F]{4}")) return text;

        try
        {
            return Regex.Unescape(text);
        }
        catch
        {
            return text;
        }
    }

    static string RepairMojibakeUtf8(string text)
    {
        if (string.IsNullOrWhiteSpace(text)) return text;
        if (!LooksLikeMojibake(text)) return text;

        try
        {
            var latin1 = Encoding.GetEncoding("ISO-8859-1");
            var bytes = latin1.GetBytes(text);
            var repaired = Encoding.UTF8.GetString(bytes);
            if (string.IsNullOrWhiteSpace(repaired)) return text;

            if (LooksLikeVietnamese(repaired) || !LooksLikeMojibake(repaired))
                return repaired;
        }
        catch
        {
        }

        return text;
    }

    static bool LooksLikeMojibake(string text)
    {
        return text.Contains("Ã", StringComparison.Ordinal) ||
               text.Contains("Â", StringComparison.Ordinal) ||
               text.Contains("Ä", StringComparison.Ordinal) ||
               text.Contains("á»", StringComparison.Ordinal) ||
               text.Contains("áº", StringComparison.Ordinal) ||
               text.Contains("Æ", StringComparison.Ordinal) ||
               text.Contains("â", StringComparison.Ordinal);
    }

    static bool LooksLikeVietnamese(string text)
    {
        return Regex.IsMatch(
            text,
            "[ăâđêôơưáàảãạắằẳẵặấầẩẫậéèẻẽẹếềểễệíìỉĩịóòỏõọốồổỗộớờởỡợúùủũụứừửữựýỳỷỹỵ]",
            RegexOptions.IgnoreCase | RegexOptions.CultureInvariant);
    }

    static string? ExtractProductCode(string q)
    {
        if (string.IsNullOrWhiteSpace(q)) return null;
        var m = Regex.Match(q.ToUpperInvariant(), @"\b[A-Z]{2}\d{3,5}\b");
        return m.Success ? m.Value : null;
    }

    static string? ExtractRecentProductCode(Conversation conv)
    {
        if (conv.MemoryFacts.TryGetValue("product_code", out var memCode) &&
            !string.IsNullOrWhiteSpace(memCode))
            return memCode.Trim().ToUpperInvariant();

        for (int i = conv.Messages.Count - 1; i >= 0; i--)
        {
            var msg = conv.Messages[i];
            var code = ExtractProductCodeForContext(msg.Content, msg.Role);
            if (!string.IsNullOrWhiteSpace(code))
                return code;
        }

        return null;
    }

    static string? ExtractProductCodeForContext(string? text, string? role)
    {
        if (string.IsNullOrWhiteSpace(text))
            return null;

        var normalizedRole = (role ?? string.Empty).Trim().ToLowerInvariant();

        if (normalizedRole == "user")
            return ExtractProductCodeIgnoreExamples(text);

        if (normalizedRole == "assistant" && LooksLikeProductPayload(text))
            return ExtractProductCodeIgnoreExamples(text);

        return null;
    }

    static string? ExtractProductCodeIgnoreExamples(string text)
    {
        if (string.IsNullOrWhiteSpace(text))
            return null;

        var matches = Regex.Matches(text.ToUpperInvariant(), @"\b[A-Z]{2}\d{3,5}\b");
        foreach (Match match in matches)
        {
            if (!match.Success) continue;
            if (IsExampleCodeContext(text, match.Index)) continue;
            return match.Value;
        }

        return null;
    }

    static bool IsExampleCodeContext(string text, int codeStartIndex)
    {
        if (string.IsNullOrWhiteSpace(text) || codeStartIndex < 0)
            return false;

        var start = Math.Max(0, codeStartIndex - 12);
        var len = Math.Min(12, codeStartIndex);
        if (len <= 0) return false;

        var prefix = RemoveDiacritics(text.Substring(start, len).ToLowerInvariant());
        return prefix.Contains("vd", StringComparison.Ordinal) ||
               prefix.Contains("vi du", StringComparison.Ordinal);
    }

    static bool LooksLikeProductPayload(string text)
    {
        if (string.IsNullOrWhiteSpace(text))
            return false;

        var plain = RemoveDiacritics(text.ToLowerInvariant());

        return plain.Contains("productid", StringComparison.Ordinal) ||
               plain.Contains("ma:", StringComparison.Ordinal) ||
               plain.Contains("ten:", StringComparison.Ordinal) ||
               plain.Contains("gia:", StringComparison.Ordinal) ||
               plain.Contains("vnd", StringComparison.Ordinal) ||
               plain.Contains("|", StringComparison.Ordinal);
    }
}
