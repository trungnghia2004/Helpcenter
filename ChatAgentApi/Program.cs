using System.Linq;
using Microsoft.Extensions.DependencyInjection;

namespace ChatAgentApi;

public class Program
{
    public static async Task Main(string[] args)
    {
        var builder = WebApplication.CreateBuilder(args);

        builder.Services.AddCors();
        builder.Services.AddHttpClient("openai");
        builder.Services.AddHttpClient("laravel");

        builder.Services.ConfigureHttpJsonOptions(o =>
        {
            o.SerializerOptions.TypeInfoResolverChain.Insert(0, AppJsonContext.Default);
        });

        var app = builder.Build();

        app.UseCors(p => p.AllowAnyHeader().AllowAnyMethod().AllowAnyOrigin());
        app.UseDefaultFiles();
        app.UseStaticFiles();

        const string laravelBase = "http://localhost:8000";
        var openAiApiKey = Environment.GetEnvironmentVariable("OPENAI_API_KEY") ?? "";
        var openAiModel = Environment.GetEnvironmentVariable("OPENAI_CHAT_MODEL") ?? "gpt-4.1-mini";
        var openAiEmbedModel = Environment.GetEnvironmentVariable("OPENAI_EMBED_MODEL") ?? "text-embedding-3-small";
        var rateLimitPerMinute = ChatCore.ParseIntEnv("CHAT_RATE_LIMIT_PER_MIN", 60, min: 5, max: 600);
        var dailyTokenQuota = ChatCore.ParseIntEnv("CHAT_DAILY_TOKEN_QUOTA", 120_000, min: 5_000, max: 20_000_000);

        var knowledgeDir = Path.Combine(app.Environment.ContentRootPath, "knowledge");
        var indexPath = Path.Combine(knowledgeDir, "knowledge_index.jsonl");
        var dataDir = Path.Combine(app.Environment.ContentRootPath, "data");
        var logsDir = Path.Combine(app.Environment.ContentRootPath, "logs");
        Directory.CreateDirectory(dataDir);
        Directory.CreateDirectory(logsDir);

        var conversationsPath = Path.Combine(dataDir, "conversations.json");
        var dailyUsagePath = Path.Combine(dataDir, "daily_token_usage.json");
        var usageLogPath = Path.Combine(logsDir, "token_usage.jsonl");

        if (args.Contains("--index", StringComparer.OrdinalIgnoreCase))
        {
            if (string.IsNullOrWhiteSpace(openAiApiKey))
            {
                Console.WriteLine("Missing OPENAI_API_KEY. Set env var then re-run.");
                return;
            }

            Directory.CreateDirectory(knowledgeDir);
            var openAiClient = app.Services.GetRequiredService<IHttpClientFactory>().CreateClient("openai");

            await ChatCore.KnowledgeIndexer.BuildIndexJsonl(
                http: openAiClient,
                apiKey: openAiApiKey,
                embeddingModel: openAiEmbedModel,
                knowledgeDir: knowledgeDir,
                outPath: indexPath,
                chunkChars: 1800,
                overlapChars: 200,
                ct: CancellationToken.None
            );

            Console.WriteLine($"Index created: {indexPath}");
            return;
        }

        var kb = ChatCore.KnowledgeBase.Load(indexPath);
        var conversations = ChatCore.LoadConversations(conversationsPath);
        ChatCore.LoadDailyTokenUsage(dailyUsagePath);

        app.MapChatAgentApi(new ChatApiConfig(
            Conversations: conversations,
            KnowledgeBase: kb,
            ConversationsPath: conversationsPath,
            DailyUsagePath: dailyUsagePath,
            UsageLogPath: usageLogPath,
            KnowledgeDir: knowledgeDir,
            LaravelBase: laravelBase,
            OpenAiApiKey: openAiApiKey,
            OpenAiModel: openAiModel,
            OpenAiEmbedModel: openAiEmbedModel,
            RateLimitPerMinute: rateLimitPerMinute,
            DailyTokenQuota: dailyTokenQuota
        ));

        await app.RunAsync();
    }
}
