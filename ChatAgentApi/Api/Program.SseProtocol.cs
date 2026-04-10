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
    static void SetSseHeaders(HttpContext ctx)
    {
        ctx.Response.Headers.ContentType = "text/event-stream; charset=utf-8";
        ctx.Response.Headers.CacheControl = "no-cache";
        ctx.Response.Headers.Connection = "keep-alive";
        ctx.Response.Headers["X-Accel-Buffering"] = "no";
        ctx.Response.Headers["x-vercel-ai-ui-message-stream"] = "v1";
    }

    static string JsonEscape(string s)
        => s.Replace("\\", "\\\\").Replace("\"", "\\\"").Replace("\n", "\\n").Replace("\r", "");

    static Task SendStart(HttpContext ctx, string messageId)
        => SendData(ctx, $"{{\"type\":\"start\",\"messageId\":\"{JsonEscape(messageId)}\"}}");

    static Task SendTextStart(HttpContext ctx, string textId)
        => SendData(ctx, $"{{\"type\":\"text-start\",\"id\":\"{JsonEscape(textId)}\"}}");

    static Task SendTextDelta(HttpContext ctx, string textId, string delta)
        => SendData(ctx, $"{{\"type\":\"text-delta\",\"id\":\"{JsonEscape(textId)}\",\"delta\":\"{JsonEscape(delta)}\"}}");

    static Task SendTextEnd(HttpContext ctx, string textId)
        => SendData(ctx, $"{{\"type\":\"text-end\",\"id\":\"{JsonEscape(textId)}\"}}");

    static Task SendError(HttpContext ctx, string error)
        => SendData(ctx, $"{{\"type\":\"error\",\"error\":\"{JsonEscape(error)}\"}}");

    static async Task SendDone(HttpContext ctx)
    {
        await ctx.Response.WriteAsync("data: [DONE]\n\n");
        await ctx.Response.Body.FlushAsync();
    }

    static async Task SendData(HttpContext ctx, string json)
    {
        await ctx.Response.WriteAsync($"data: {json}\n\n");
        await ctx.Response.Body.FlushAsync();
    }
}

