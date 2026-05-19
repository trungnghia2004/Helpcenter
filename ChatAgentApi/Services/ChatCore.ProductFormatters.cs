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
    static string FormatProduct(JsonElement p)
    {
        var id = p.TryGetProperty("productID", out var pid) ? pid.GetInt64() : 0;
        var code = ReadStringProperty(p, "productCode", "code", "product_code");
        var name = ReadStringProperty(p, "productName", "name", "product_name");
        var price = p.TryGetProperty("productSellPrice", out var pr) ? ReadDecimalFlexible(pr) : 0m;
        var desc = ReadStringProperty(p, "productDesc", "description", "desc", "product_description");

        if (!string.IsNullOrWhiteSpace(desc) && desc.Length > 280) desc = desc[..280] + "...";
        if (string.IsNullOrWhiteSpace(desc)) desc = "(chưa có mô tả từ nguồn dữ liệu)";

        return
            "LIVE PRODUCT DATA:\n" +
            $"- productID: {id}\n" +
            $"- Mã: {code}\n" +
            $"- Tên: {name}\n" +
            $"- Giá: {price:N0} VND\n" +
            $"- Mô tả: {desc}\n";
    }

    static string ReadStringProperty(JsonElement source, params string[] keys)
    {
        foreach (var key in keys)
        {
            if (source.TryGetProperty(key, out var value))
            {
                var text = value.GetString();
                if (!string.IsNullOrWhiteSpace(text))
                    return text;
            }
        }
        return string.Empty;
    }

    static string FormatProductList(List<JsonElement> products, int maxItems = 5)
    {
        var sb = new StringBuilder();
        sb.AppendLine("LIVE PRODUCT LIST:");

        var count = 0;
        foreach (var p in products)
        {
            var code = p.TryGetProperty("productCode", out var c) ? c.GetString() : "";
            var name = p.TryGetProperty("productName", out var n) ? n.GetString() : "";
            var price = p.TryGetProperty("productSellPrice", out var pr) ? ReadDecimalFlexible(pr) : 0m;
            sb.AppendLine($"- {code} | {name} | {price:N0} VND");
            count++;
            if (count >= maxItems) break;
        }

        return sb.ToString().TrimEnd();
    }

    static decimal ReadDecimalFlexible(JsonElement el)
    {
        if (el.ValueKind == JsonValueKind.Number && el.TryGetDecimal(out var n))
            return n;

        if (el.ValueKind == JsonValueKind.String)
        {
            var s = el.GetString();
            if (!string.IsNullOrWhiteSpace(s) &&
                decimal.TryParse(s, NumberStyles.Number, CultureInfo.InvariantCulture, out var d))
                return d;
        }

        return 0m;
    }

    static string FormatVariants(JsonElement variantsArr, int maxLines = 20)
    {
        if (variantsArr.ValueKind != JsonValueKind.Array || variantsArr.GetArrayLength() == 0)
            return "VARIANTS: (không có dữ liệu size/màu trong hệ thống)";

        var map = new Dictionary<string, List<(string color, int qty)>>();

        foreach (var v in variantsArr.EnumerateArray())
        {
            if (!v.TryGetProperty("sizeName", out var sizeEl)) continue;
            if (!v.TryGetProperty("colorName", out var colorEl)) continue;
            if (!v.TryGetProperty("productQuantity", out var qtyEl)) continue;

            var size = (sizeEl.GetString() ?? "").Trim();
            var color = (colorEl.GetString() ?? "").Trim();
            var qty = qtyEl.ValueKind == JsonValueKind.Number && qtyEl.TryGetInt32(out var n) ? n : 0;
            if (string.IsNullOrWhiteSpace(size) || string.IsNullOrWhiteSpace(color)) continue;

            if (!map.TryGetValue(size, out var list))
            {
                list = new();
                map[size] = list;
            }
            list.Add((color, qty));
        }

        if (map.Count == 0)
            return "VARIANTS: (không có dữ liệu size/màu hợp lệ trong hệ thống)";

        var sb = new StringBuilder();
        sb.AppendLine("VARIANTS (size / màu / tồn kho):");

        var lines = 0;
        foreach (var (size, list) in map.OrderBy(x => x.Key))
        {
            var parts = list.OrderBy(x => x.color).Select(x => $"{x.color}:{x.qty}");
            sb.AppendLine($"- Size {size}: {string.Join(", ", parts)}");
            lines++;
            if (lines >= maxLines) break;
        }

        return sb.ToString().TrimEnd();
    }
}



