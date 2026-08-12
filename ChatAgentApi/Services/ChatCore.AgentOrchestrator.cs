namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal interface IAgentOrchestrator
    {
        IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request);
    }

    internal interface IAgentModelBackend
    {
        IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request);
    }

    internal sealed record AgentStreamRequest(
        string Model,
        string ApiKey,
        List<ChatMessage> Messages,
        AgentExecutionContext Context,
        CancellationToken CancellationToken
    );

    internal sealed record AgentExecutionContext(
        string? LastKnownProductCode,
        string ConversationId,
        string UserKey,
        string TraceId,
        AgentRunPolicy Policy,
        AgentRuntimeState RuntimeState,
        Action<AgentToolCallLog>? ToolLogger,
        Action<AgentStepLog>? StepLogger,
        string? PlannerHint,
        bool AllowToolCalls
    );

    internal sealed record AgentToolExecutionContext(
        string? LastKnownProductCode,
        AgentRunPolicy Policy,
        AgentRuntimeState RuntimeState,
        string ConversationId,
        string UserKey,
        Action<AgentToolCallLog>? ToolLogger,
        CancellationToken CancellationToken
    );

    internal sealed class AgentToolExecutionContextAccessor
    {
        public AgentToolExecutionContext? Current { get; set; }
    }

    internal sealed class AgentRuntimeState
    {
        int _toolCallCount;

        public int IncrementToolCallCount()
            => Interlocked.Increment(ref _toolCallCount);

        public int ToolCallCount
            => Volatile.Read(ref _toolCallCount);
    }

    internal sealed class AgentOrchestrator : IAgentOrchestrator
    {
        readonly IAgentModelBackend _backend;

        public AgentOrchestrator(IAgentModelBackend backend)
        {
            _backend = backend;
        }

        public IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request)
            => _backend.StreamAsync(request);
    }

    static void LogAgentStep(
        AgentExecutionContext ctx,
        int stepNo,
        string phase,
        string detail,
        string? responseId = null,
        string? toolName = null,
        bool? succeeded = null,
        long? latencyMs = null)
    {
        ctx.StepLogger?.Invoke(new AgentStepLog
        {
            AtUtc = DateTime.UtcNow,
            TraceId = ctx.TraceId,
            ConversationId = ctx.ConversationId,
            UserKey = ctx.UserKey,
            StepNo = stepNo,
            Phase = phase,
            Detail = detail,
            ResponseId = responseId,
            ToolName = toolName,
            Succeeded = succeeded,
            LatencyMs = latencyMs
        });
    }
}
