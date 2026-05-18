using System.Text.Json;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    static readonly HashSet<string> EphemeralConversationFactKeys = new(StringComparer.OrdinalIgnoreCase)
    {
        "product_code",
        "size",
        "color",
        "intent"
    };

    internal static void LoadUserMemories(string path)
    {
        lock (UserMemoryFileLock)
        {
            UserMemories.Clear();
            if (!File.Exists(path)) return;

            try
            {
                var json = File.ReadAllText(path);
                var data = JsonSerializer.Deserialize(
                    json,
                    AppJsonContext.Default.DictionaryStringUserMemoryRecord);

                if (data is not null)
                {
                    foreach (var kv in data)
                    {
                        if (string.IsNullOrWhiteSpace(kv.Key)) continue;
                        kv.Value.UserKey = string.IsNullOrWhiteSpace(kv.Value.UserKey) ? kv.Key : kv.Value.UserKey;
                        kv.Value.Facts ??= new Dictionary<string, string>(StringComparer.Ordinal);
                        UserMemories[kv.Key] = kv.Value;
                    }
                }
            }
            catch
            {
            }

            TrimOldUserMemories(DateTime.UtcNow.AddDays(-45));
            PersistUserMemories(path);
        }
    }

    static void PersistUserMemories(string path)
    {
        try
        {
            var tempPath = path + ".tmp";
            var json = JsonSerializer.Serialize(
                UserMemories,
                AppJsonContext.Default.DictionaryStringUserMemoryRecord);

            File.WriteAllText(tempPath, json);
            File.Move(tempPath, path, overwrite: true);
        }
        catch
        {
        }
    }

    static void TrimOldUserMemories(DateTime minUtc)
    {
        var keys = UserMemories.Keys.ToList();
        foreach (var k in keys)
        {
            if (!UserMemories.TryGetValue(k, out var record)) continue;
            if (record.UpdatedAtUtc < minUtc)
                UserMemories.Remove(k);
        }
    }

    internal static string BuildUserMemoryForPrompt(string userKey, int maxFacts = 8)
    {
        if (string.IsNullOrWhiteSpace(userKey)) return string.Empty;

        lock (UserMemoryFileLock)
        {
            if (!UserMemories.TryGetValue(userKey, out var record) || record.Facts.Count == 0)
                return string.Empty;

            var facts = record.Facts
                .Where(kvp => !string.IsNullOrWhiteSpace(kvp.Key) && !string.IsNullOrWhiteSpace(kvp.Value))
                .Where(kvp => !EphemeralConversationFactKeys.Contains(kvp.Key))
                .OrderBy(kvp => kvp.Key, StringComparer.Ordinal)
                .Take(Math.Max(1, maxFacts))
                .Select(kvp => $"- {kvp.Key}: {kvp.Value}");

            return string.Join("\n", facts);
        }
    }

    internal static void MergeUserMemoryFromConversation(string path, string userKey, Conversation conv)
    {
        if (string.IsNullOrWhiteSpace(userKey)) return;
        if (conv.MemoryFacts.Count == 0) return;

        lock (UserMemoryFileLock)
        {
            if (!UserMemories.TryGetValue(userKey, out var record))
            {
                record = new UserMemoryRecord
                {
                    UserKey = userKey,
                    Facts = new Dictionary<string, string>(StringComparer.Ordinal),
                    UpdatedAtUtc = DateTime.UtcNow
                };
                UserMemories[userKey] = record;
            }

            foreach (var kvp in conv.MemoryFacts)
            {
                if (string.IsNullOrWhiteSpace(kvp.Key) || string.IsNullOrWhiteSpace(kvp.Value))
                    continue;
                if (EphemeralConversationFactKeys.Contains(kvp.Key))
                    continue;

                record.Facts[kvp.Key] = kvp.Value;
            }

            record.UpdatedAtUtc = DateTime.UtcNow;
            PersistUserMemories(path);
        }
    }
}
