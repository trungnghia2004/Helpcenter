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

    internal static ChatAgentOptions Load(IConfiguration configuration)
    {
        var options = new ChatAgentOptions();
        configuration.GetSection(SectionName).Bind(options);

        options.LaravelBase = ReadStringEnv("LARAVEL_BASE_URL", options.LaravelBase);
        options.OpenAiApiKey = ReadStringEnv("OPENAI_API_KEY", options.OpenAiApiKey);
        options.OpenAiModel = ReadStringEnv("OPENAI_CHAT_MODEL", options.OpenAiModel);
        options.OpenAiEmbedModel = ReadStringEnv("OPENAI_EMBED_MODEL", options.OpenAiEmbedModel);

        options.AgentMaxToolCalls = ReadIntEnv("AGENT_MAX_TOOL_CALLS", options.AgentMaxToolCalls, min: 1, max: 30);
        options.AgentMaxToolOutputChars = ReadIntEnv("AGENT_MAX_TOOL_OUTPUT_CHARS", options.AgentMaxToolOutputChars, min: 200, max: 4000);
        options.RateLimitPerMinute = ReadIntEnv("CHAT_RATE_LIMIT_PER_MIN", options.RateLimitPerMinute, min: 5, max: 600);
        options.DailyTokenQuota = ReadIntEnv("CHAT_DAILY_TOKEN_QUOTA", options.DailyTokenQuota, min: 5_000, max: 20_000_000);

        return options;
    }

    static string ReadStringEnv(string key, string fallback)
    {
        var raw = Environment.GetEnvironmentVariable(key);
        return string.IsNullOrWhiteSpace(raw) ? fallback : raw.Trim();
    }

    static int ReadIntEnv(string key, int fallback, int min, int max)
    {
        var raw = Environment.GetEnvironmentVariable(key);
        if (!int.TryParse(raw, out var value)) value = fallback;
        if (value < min) value = min;
        if (value > max) value = max;
        return value;
    }
}
