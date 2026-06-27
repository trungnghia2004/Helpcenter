using Microsoft.Extensions.Options;
using Microsoft.SemanticKernel;
using Microsoft.SemanticKernel.Connectors.OpenAI;

namespace ChatAgentApi;

internal static class ServiceCollectionExtensions
{
    internal static IServiceCollection AddChatAgentApplication(this IServiceCollection services, IConfiguration configuration, IHostEnvironment environment)
    {
        var bootstrapOptions = ChatAgentOptions.CreateBootstrap(configuration);
        var bootstrapErrors = ChatAgentOptions.Validate(bootstrapOptions);
        if (bootstrapErrors.Count > 0)
            throw new InvalidOperationException(string.Join(Environment.NewLine, bootstrapErrors));

        services.AddOptions<ChatAgentOptions>()
            .Bind(configuration.GetSection(ChatAgentOptions.SectionName))
            .PostConfigure(ChatAgentOptions.ApplyEnvironmentOverrides)
            .Validate(
                options => ChatAgentOptions.Validate(options).Count == 0,
                "ChatAgent options are invalid.")
            .ValidateOnStart();

        services.AddAppMiddleware(bootstrapOptions);
        services.AddHttpClient("openai");
        services.AddHttpClient("laravel");
        services.AddOpenAIChatCompletion(
            modelId: bootstrapOptions.OpenAiModel,
            apiKey: bootstrapOptions.OpenAiApiKey);
#pragma warning disable SKEXP0010
        services.AddOpenAIEmbeddingGenerator(
            modelId: bootstrapOptions.OpenAiEmbedModel,
            apiKey: bootstrapOptions.OpenAiApiKey);
#pragma warning restore SKEXP0010
        services.AddTransient<Kernel>(sp => new Kernel(sp));
        services.AddScoped<IChatAuthenticationService, ChatAuthenticationService>();
        services.AddScoped<ChatCore.IAgentOrchestrator, ChatCore.SemanticKernelAgentOrchestrator>();
        services.AddScoped<ChatCore.IChatRequestHandler, ChatCore.ChatRequestHandler>();

        services.AddSingleton(sp => BuildRuntime(
            environment,
            sp.GetRequiredService<IOptions<ChatAgentOptions>>().Value));

        return services;
    }

    static ChatAgentRuntime BuildRuntime(IHostEnvironment environment, ChatAgentOptions options)
    {
        var sourceKnowledgeDir = Path.Combine(environment.ContentRootPath, "knowledge");
        var runtimeRoot = Path.Combine(environment.ContentRootPath, "runtime");
        var runtimeKnowledgeDir = Path.Combine(runtimeRoot, "knowledge");
        var dataDir = Path.Combine(runtimeRoot, "data");
        var logsDir = Path.Combine(runtimeRoot, "logs");
        var indexPath = Path.Combine(runtimeKnowledgeDir, "knowledge_index.jsonl");

        Directory.CreateDirectory(sourceKnowledgeDir);
        Directory.CreateDirectory(runtimeRoot);
        Directory.CreateDirectory(runtimeKnowledgeDir);
        Directory.CreateDirectory(dataDir);
        Directory.CreateDirectory(logsDir);

        var conversationsPath = Path.Combine(dataDir, "conversations.json");
        var dailyUsagePath = Path.Combine(dataDir, "daily_token_usage.json");
        var userMemoriesPath = Path.Combine(dataDir, "user_memories.json");
        var usageLogPath = Path.Combine(logsDir, "token_usage.jsonl");
        var agentToolLogPath = Path.Combine(logsDir, "agent_tool_calls.jsonl");
        var agentStepLogPath = Path.Combine(logsDir, "agent_steps.jsonl");

        MigrateLegacyFileIfMissing(Path.Combine(environment.ContentRootPath, "data", "conversations.json"), conversationsPath);
        MigrateLegacyFileIfMissing(Path.Combine(environment.ContentRootPath, "data", "daily_token_usage.json"), dailyUsagePath);
        MigrateLegacyFileIfMissing(Path.Combine(environment.ContentRootPath, "data", "user_memories.json"), userMemoriesPath);
        MigrateLegacyFileIfMissing(Path.Combine(environment.ContentRootPath, "logs", "token_usage.jsonl"), usageLogPath);
        MigrateLegacyFileIfMissing(Path.Combine(environment.ContentRootPath, "logs", "agent_tool_calls.jsonl"), agentToolLogPath);
        MigrateLegacyFileIfMissing(Path.Combine(environment.ContentRootPath, "logs", "agent_steps.jsonl"), agentStepLogPath);
        MigrateLegacyFileIfMissing(Path.Combine(environment.ContentRootPath, "knowledge", "knowledge_index.jsonl"), indexPath);

        var conversations = ChatCore.LoadConversations(conversationsPath);
        ChatCore.LoadDailyTokenUsage(dailyUsagePath);
        ChatCore.LoadUserMemories(userMemoriesPath);
        var kb = ChatCore.KnowledgeBase.Load(indexPath);

        var agentPolicy = new AgentRunPolicy
        {
            MaxToolCalls = options.AgentMaxToolCalls,
            MaxToolOutputChars = options.AgentMaxToolOutputChars
        };

        return new ChatAgentRuntime(
            conversations: conversations,
            knowledgeBase: kb,
            conversationsPath: conversationsPath,
            dailyUsagePath: dailyUsagePath,
            userMemoriesPath: userMemoriesPath,
            usageLogPath: usageLogPath,
            agentToolLogPath: agentToolLogPath,
            agentStepLogPath: agentStepLogPath,
            knowledgeDir: sourceKnowledgeDir,
            indexPath: indexPath,
            options: options,
            agentPolicy: agentPolicy);
    }

    static void MigrateLegacyFileIfMissing(string legacyPath, string newPath)
    {
        if (File.Exists(newPath) || !File.Exists(legacyPath))
            return;

        var parent = Path.GetDirectoryName(newPath);
        if (!string.IsNullOrWhiteSpace(parent))
            Directory.CreateDirectory(parent);

        File.Copy(legacyPath, newPath, overwrite: false);
    }
}
