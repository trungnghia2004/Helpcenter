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
    sealed record OpenAiUsage(int PromptTokens, int CompletionTokens, int TotalTokens);

    internal static int ParseIntEnv(string key, int fallback, int min, int max)
    {
        var raw = Environment.GetEnvironmentVariable(key);
        if (!int.TryParse(raw, out var value)) return fallback;
        if (value < min) return min;
        if (value > max) return max;
        return value;
    }

    static string BuildRequesterKey(ChatRequest req, HttpContext ctx)
    {
        if (!string.IsNullOrWhiteSpace(req.UserId))
            return $"user:{req.UserId.Trim()}";

        var ip = ctx.Connection.RemoteIpAddress?.ToString();
        if (!string.IsNullOrWhiteSpace(ip))
            return $"ip:{ip}";

        return "ip:unknown";
    }

    static bool IsRateLimited(string requesterKey, int limitPerMinute, out int retryAfterSeconds)
    {
        lock (RateLimitLock)
        {
            var now = DateTime.UtcNow;
            var windowStart = now.AddMinutes(-1);
            var queue = RequestWindows.GetOrAdd(requesterKey, _ => new Queue<DateTime>());

            while (queue.Count > 0 && queue.Peek() < windowStart)
                queue.Dequeue();

            if (queue.Count >= limitPerMinute)
            {
                var oldest = queue.Peek();
                retryAfterSeconds = Math.Max(1, (int)Math.Ceiling((oldest.AddMinutes(1) - now).TotalSeconds));
                return true;
            }

            queue.Enqueue(now);
            retryAfterSeconds = 0;
            return false;
        }
    }

    static bool IsDailyQuotaExceeded(string requesterKey, int dailyQuota, out long usedToday)
    {
        var usageKey = $"{DateOnly.FromDateTime(DateTime.UtcNow):yyyy-MM-dd}|{requesterKey}";
        lock (UsageFileLock)
        {
            DailyTokenUsage.TryGetValue(usageKey, out usedToday);
            return usedToday >= dailyQuota;
        }
    }

    static void AddDailyTokenUsage(string usagePath, string requesterKey, int tokens)
    {
        if (tokens <= 0) return;

        var usageKey = $"{DateOnly.FromDateTime(DateTime.UtcNow):yyyy-MM-dd}|{requesterKey}";
        lock (UsageFileLock)
        {
            DailyTokenUsage.TryGetValue(usageKey, out var cur);
            DailyTokenUsage[usageKey] = cur + tokens;
            TrimOldDailyUsage(DateOnly.FromDateTime(DateTime.UtcNow).AddDays(-14));
            PersistDailyTokenUsage(usagePath);
        }
    }

    internal static void LoadDailyTokenUsage(string usagePath)
    {
        lock (UsageFileLock)
        {
            DailyTokenUsage.Clear();
            if (!File.Exists(usagePath)) return;

            try
            {
                var json = File.ReadAllText(usagePath);
                var data = JsonSerializer.Deserialize(
                    json,
                    AppJsonContext.Default.DictionaryStringInt64);
                if (data is null) return;
                foreach (var kv in data)
                    DailyTokenUsage[kv.Key] = kv.Value;
            }
            catch { }

            TrimOldDailyUsage(DateOnly.FromDateTime(DateTime.UtcNow).AddDays(-14));
            PersistDailyTokenUsage(usagePath);
        }
    }

    static void PersistDailyTokenUsage(string usagePath)
    {
        var tempPath = usagePath + ".tmp";
        var json = JsonSerializer.Serialize(
            DailyTokenUsage,
            AppJsonContext.Default.DictionaryStringInt64);
        File.WriteAllText(tempPath, json);
        File.Move(tempPath, usagePath, overwrite: true);
    }

    static void TrimOldDailyUsage(DateOnly minDay)
    {
        var keys = DailyTokenUsage.Keys.ToList();
        foreach (var key in keys)
        {
            var p = key.IndexOf('|');
            if (p <= 0) continue;
            var dayPart = key[..p];
            if (DateOnly.TryParse(dayPart, out var day) && day < minDay)
                DailyTokenUsage.Remove(key);
        }
    }

    internal static ConcurrentDictionary<string, Conversation> LoadConversations(string path)
    {
        var map = new ConcurrentDictionary<string, Conversation>();
        if (!File.Exists(path)) return map;

        try
        {
            var json = File.ReadAllText(path);
            var list = JsonSerializer.Deserialize(
                json,
                AppJsonContext.Default.ListConversation) ?? new();
            foreach (var conv in list)
            {
                if (string.IsNullOrWhiteSpace(conv.Id)) continue;
                conv.MemoryFacts ??= new Dictionary<string, string>();
                conv.Messages ??= new List<ChatMessage>();
                map[conv.Id] = conv;
            }
        }
        catch { }

        return map;
    }

    static void PersistConversations(string path, ConcurrentDictionary<string, Conversation> conversations)
    {
        lock (ConversationFileLock)
        {
            try
            {
                var list = conversations.Values
                    .OrderByDescending(c => c.UpdatedAtUtc ?? c.CreatedAtUtc)
                    .Take(2000)
                    .ToList();
                var json = JsonSerializer.Serialize(
                    list,
                    AppJsonContext.Default.ListConversation);
                var tempPath = path + ".tmp";
                File.WriteAllText(tempPath, json);
                File.Move(tempPath, path, overwrite: true);
            }
            catch { }
        }
    }

    static void AppendTokenUsageLog(string logPath, TokenUsageLog log)
    {
        try
        {
            var line = JsonSerializer.Serialize(
                log,
                AppJsonContext.Default.TokenUsageLog);
            lock (UsageFileLock)
            {
                File.AppendAllText(logPath, line + Environment.NewLine);
            }
        }
        catch { }
    }
}



