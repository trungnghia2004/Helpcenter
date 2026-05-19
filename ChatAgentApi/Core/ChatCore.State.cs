using System.Collections.Concurrent;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal sealed record CacheItem(string Json, DateTime ExpiresAtUtc);
    internal sealed record RecentProductHint(string ProductCode, DateTime AtUtc);
    internal sealed record AuthUserHint(string UserKey, DateTime ExpiresAtUtc);

    internal static readonly ConcurrentDictionary<string, CacheItem> LaravelCache = new();
    internal static readonly ConcurrentDictionary<string, RecentProductHint> RecentProductByRequester = new(StringComparer.Ordinal);
    internal static readonly ConcurrentDictionary<string, AuthUserHint> AuthTokenCache = new(StringComparer.Ordinal);
    internal static readonly object ConversationFileLock = new();
    internal static readonly object UsageFileLock = new();
    internal static readonly object UserMemoryFileLock = new();
    internal static readonly Dictionary<string, long> DailyTokenUsage = new(StringComparer.Ordinal);
    internal static readonly Dictionary<string, UserMemoryRecord> UserMemories = new(StringComparer.Ordinal);
}
