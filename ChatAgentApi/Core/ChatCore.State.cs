using System.Collections.Concurrent;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal sealed record CacheItem(string Json, DateTime ExpiresAtUtc);

    internal static readonly ConcurrentDictionary<string, CacheItem> LaravelCache = new();
    internal static readonly ConcurrentDictionary<string, string> LastProductCodeByRequester = new(StringComparer.Ordinal);
    internal static readonly object ConversationFileLock = new();
    internal static readonly object UsageFileLock = new();
    internal static readonly object RateLimitLock = new();
    internal static readonly ConcurrentDictionary<string, Queue<DateTime>> RequestWindows = new();
    internal static readonly Dictionary<string, long> DailyTokenUsage = new(StringComparer.Ordinal);
}
