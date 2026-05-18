using System.Collections.Concurrent;

namespace ChatAgentApi;

internal sealed class ChatAgentRuntime
{
    public ChatAgentRuntime(
        ConcurrentDictionary<string, Conversation> conversations,
        ChatCore.KnowledgeBase knowledgeBase,
        string conversationsPath,
        string dailyUsagePath,
        string userMemoriesPath,
        string usageLogPath,
        string agentToolLogPath,
        string agentStepLogPath,
        string knowledgeDir,
        string indexPath,
        ChatAgentOptions options,
        AgentRunPolicy agentPolicy)
    {
        Conversations = conversations;
        KnowledgeBase = knowledgeBase;
        ConversationsPath = conversationsPath;
        DailyUsagePath = dailyUsagePath;
        UserMemoriesPath = userMemoriesPath;
        UsageLogPath = usageLogPath;
        AgentToolLogPath = agentToolLogPath;
        AgentStepLogPath = agentStepLogPath;
        KnowledgeDir = knowledgeDir;
        IndexPath = indexPath;
        Options = options;
        AgentPolicy = agentPolicy;
    }

    public ConcurrentDictionary<string, Conversation> Conversations { get; }
    public ChatCore.KnowledgeBase KnowledgeBase { get; }
    public string ConversationsPath { get; }
    public string DailyUsagePath { get; }
    public string UserMemoriesPath { get; }
    public string UsageLogPath { get; }
    public string AgentToolLogPath { get; }
    public string AgentStepLogPath { get; }
    public string KnowledgeDir { get; }
    public string IndexPath { get; }
    public ChatAgentOptions Options { get; }
    public AgentRunPolicy AgentPolicy { get; }
}
