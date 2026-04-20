using System.Collections.Concurrent;

namespace ChatAgentApi;

internal sealed record ChatApiConfig(
    ConcurrentDictionary<string, Conversation> Conversations,
    ChatCore.KnowledgeBase KnowledgeBase,
    string ConversationsPath,
    string DailyUsagePath,
    string UserMemoriesPath,
    string UsageLogPath,
    string AgentToolLogPath,
    string KnowledgeDir,
    string LaravelBase,
    string OpenAiApiKey,
    string OpenAiModel,
    string OpenAiEmbedModel,
    AgentRunPolicy AgentPolicy,
    int RateLimitPerMinute,
    int DailyTokenQuota
);

internal static class ApiEndpointsExtensions
{
    internal static void MapChatAgentApi(this WebApplication app, ChatApiConfig config)
    {
        ChatCore.MapApiEndpoints(
            app: app,
            conversations: config.Conversations,
            kb: config.KnowledgeBase,
            conversationsPath: config.ConversationsPath,
            dailyUsagePath: config.DailyUsagePath,
            userMemoriesPath: config.UserMemoriesPath,
            usageLogPath: config.UsageLogPath,
            agentToolLogPath: config.AgentToolLogPath,
            knowledgeDir: config.KnowledgeDir,
            laravelBase: config.LaravelBase,
            openAiApiKey: config.OpenAiApiKey,
            openAiModel: config.OpenAiModel,
            openAiEmbedModel: config.OpenAiEmbedModel,
            agentPolicy: config.AgentPolicy,
            rateLimitPerMinute: config.RateLimitPerMinute,
            dailyTokenQuota: config.DailyTokenQuota
        );
    }
}
