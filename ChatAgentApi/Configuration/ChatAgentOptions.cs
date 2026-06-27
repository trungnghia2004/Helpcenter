namespace ChatAgentApi;

internal sealed class ChatAgentOptions
{
    public const string SectionName = "ChatAgent";

    public string LaravelBase { get; set; } = "http://localhost:8000";
    public string OpenAiApiKey { get; set; } = "";
    public string OpenAiModel { get; set; } = "gpt-4.1-mini";
    public string OpenAiEmbedModel { get; set; } = "text-embedding-3-small";
    public int AgentMaxToolCalls { get; set; } = 8;
    public int AgentMaxToolOutputChars { get; set; } = 1200;
    public int RateLimitPerMinute { get; set; } = 60;
    public int DailyTokenQuota { get; set; } = 120_000;
    public List<string> ForwardedHeadersKnownProxies { get; set; } = new();
    public List<string> ForwardedHeadersKnownNetworks { get; set; } = new();

    internal static ChatAgentOptions CreateBootstrap(IConfiguration configuration)
    {
        var options = new ChatAgentOptions();
        configuration.GetSection(SectionName).Bind(options);
        ApplyEnvironmentOverrides(options);
        return options;
    }

    internal static void ApplyEnvironmentOverrides(ChatAgentOptions options)
    {
        options.LaravelBase = ReadStringEnv("LARAVEL_BASE_URL", options.LaravelBase);
        options.OpenAiApiKey = ReadStringEnv("OPENAI_API_KEY", options.OpenAiApiKey);
        options.OpenAiModel = ReadStringEnv("OPENAI_CHAT_MODEL", options.OpenAiModel);
        options.OpenAiEmbedModel = ReadStringEnv("OPENAI_EMBED_MODEL", options.OpenAiEmbedModel);

        options.AgentMaxToolCalls = ReadIntEnv("AGENT_MAX_TOOL_CALLS", options.AgentMaxToolCalls);
        options.AgentMaxToolOutputChars = ReadIntEnv("AGENT_MAX_TOOL_OUTPUT_CHARS", options.AgentMaxToolOutputChars);
        options.RateLimitPerMinute = ReadIntEnv("CHAT_RATE_LIMIT_PER_MIN", options.RateLimitPerMinute);
        options.DailyTokenQuota = ReadIntEnv("CHAT_DAILY_TOKEN_QUOTA", options.DailyTokenQuota);
        options.ForwardedHeadersKnownProxies = ReadStringListEnv("FORWARDED_HEADERS_KNOWN_PROXIES", options.ForwardedHeadersKnownProxies);
        options.ForwardedHeadersKnownNetworks = ReadStringListEnv("FORWARDED_HEADERS_KNOWN_NETWORKS", options.ForwardedHeadersKnownNetworks);
    }

    internal static IReadOnlyList<string> Validate(ChatAgentOptions options)
    {
        var errors = new List<string>();

        if (!Uri.TryCreate(options.LaravelBase, UriKind.Absolute, out _))
            errors.Add("ChatAgent:LaravelBase must be an absolute URI.");
        if (string.IsNullOrWhiteSpace(options.OpenAiApiKey))
            errors.Add("OPENAI_API_KEY or ChatAgent:OpenAiApiKey is required.");
        if (string.IsNullOrWhiteSpace(options.OpenAiModel))
            errors.Add("ChatAgent:OpenAiModel is required.");
        if (string.IsNullOrWhiteSpace(options.OpenAiEmbedModel))
            errors.Add("ChatAgent:OpenAiEmbedModel is required.");
        if (options.AgentMaxToolCalls is < 1 or > 30)
            errors.Add("ChatAgent:AgentMaxToolCalls must be between 1 and 30.");
        if (options.AgentMaxToolOutputChars is < 200 or > 4000)
            errors.Add("ChatAgent:AgentMaxToolOutputChars must be between 200 and 4000.");
        if (options.RateLimitPerMinute is < 5 or > 600)
            errors.Add("ChatAgent:RateLimitPerMinute must be between 5 and 600.");
        if (options.DailyTokenQuota is < 5_000 or > 20_000_000)
            errors.Add("ChatAgent:DailyTokenQuota must be between 5000 and 20000000.");
        foreach (var proxy in options.ForwardedHeadersKnownProxies)
        {
            if (!System.Net.IPAddress.TryParse(proxy, out _))
                errors.Add($"ChatAgent:ForwardedHeadersKnownProxies contains invalid IP address '{proxy}'.");
        }
        foreach (var network in options.ForwardedHeadersKnownNetworks)
        {
            if (!System.Net.IPNetwork.TryParse(network, out _))
                errors.Add($"ChatAgent:ForwardedHeadersKnownNetworks contains invalid CIDR '{network}'.");
        }

        return errors;
    }

    static string ReadStringEnv(string key, string fallback)
    {
        var raw = Environment.GetEnvironmentVariable(key);
        return string.IsNullOrWhiteSpace(raw) ? fallback : raw.Trim();
    }

    static int ReadIntEnv(string key, int fallback)
    {
        var raw = Environment.GetEnvironmentVariable(key);
        return int.TryParse(raw, out var value) ? value : fallback;
    }

    static List<string> ReadStringListEnv(string key, List<string> fallback)
    {
        var raw = Environment.GetEnvironmentVariable(key);
        if (string.IsNullOrWhiteSpace(raw))
            return fallback;

        return raw
            .Split(',', StringSplitOptions.RemoveEmptyEntries | StringSplitOptions.TrimEntries)
            .Where(value => !string.IsNullOrWhiteSpace(value))
            .Distinct(StringComparer.OrdinalIgnoreCase)
            .ToList();
    }
}
