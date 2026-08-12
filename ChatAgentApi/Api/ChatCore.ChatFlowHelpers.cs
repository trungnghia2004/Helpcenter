namespace ChatAgentApi;

internal static partial class ChatCore
{
    static AgentExecutionContext BuildAgentExecutionContext(
        string? lastKnownProductCode,
        string conversationId,
        string userKey,
        string traceId,
        AgentRunPolicy policy,
        AgentRuntimeState runtimeState,
        Action<AgentToolCallLog>? toolLogger,
        Action<AgentStepLog>? stepLogger,
        string? plannerHint,
        bool allowToolCalls)
    {
        return new AgentExecutionContext(
            LastKnownProductCode: lastKnownProductCode,
            ConversationId: conversationId,
            UserKey: userKey,
            TraceId: traceId,
            Policy: policy,
            RuntimeState: runtimeState,
            ToolLogger: toolLogger,
            StepLogger: stepLogger,
            PlannerHint: plannerHint,
            AllowToolCalls: allowToolCalls);
    }
}
