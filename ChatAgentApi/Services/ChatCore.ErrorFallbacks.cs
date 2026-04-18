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
    static bool IsRateLimitError(Exception ex)
    {
        var msg = ex.ToString();
        return msg.Contains("429", StringComparison.OrdinalIgnoreCase) ||
               msg.Contains("Too Many Requests", StringComparison.OrdinalIgnoreCase) ||
               msg.Contains("RESOURCE_EXHAUSTED", StringComparison.OrdinalIgnoreCase);
    }

    static bool IsUnauthorizedError(Exception ex)
    {
        var msg = ex.ToString();
        return msg.Contains("401", StringComparison.OrdinalIgnoreCase) ||
               msg.Contains("Unauthorized", StringComparison.OrdinalIgnoreCase) ||
               msg.Contains("invalid_api_key", StringComparison.OrdinalIgnoreCase);
    }

    static string BuildRateLimitFallbackFromSources(List<string> sourcesBlocks)
    {
        var live = sourcesBlocks.FirstOrDefault(s =>
            s.StartsWith("LIVE PRODUCT LIST:", StringComparison.Ordinal) ||
            s.StartsWith("LIVE PRODUCT DATA:", StringComparison.Ordinal));

        if (!string.IsNullOrWhiteSpace(live))
        {
            return CleanUserFacingLiveText(live) +
                   "\nDịch vụ AI đang tạm thời không khả dụng. Bạn có thể tiếp tục hỏi theo mã sản phẩm để mình tra nhanh giúp bạn.";
        }

        return "Dịch vụ AI đang tạm thời không khả dụng. Bạn vui lòng thử lại sau ít phút.";
    }
}



