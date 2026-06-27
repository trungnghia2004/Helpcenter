using Microsoft.Extensions.DependencyInjection;
using Microsoft.SemanticKernel;
using Microsoft.SemanticKernel.ChatCompletion;
using Microsoft.SemanticKernel.Connectors.OpenAI;
using System.Text;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal interface IAgentOrchestrator
    {
        IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request);
    }

    internal sealed record AgentStreamRequest(
        List<ChatMessage> Messages,
        AgentExecutionContext Context,
        CancellationToken CancellationToken
    );

    internal sealed record AgentExecutionContext(
        HttpClient LaravelHttp,
        string LaravelBase,
        KnowledgeBase KnowledgeBase,
        string KnowledgeDir,
        string? LastKnownProductCode,
        string ConversationId,
        string UserKey,
        string TraceId,
        AgentRunPolicy Policy,
        AgentRuntimeState RuntimeState,
        Action<AgentToolCallLog>? ToolLogger,
        Action<AgentStepLog>? StepLogger,
        string? PlannerHint,
        CancellationToken CancellationToken
    );

    internal sealed class AgentRuntimeState
    {
        int _toolCallCount;

        public int IncrementToolCallCount()
            => Interlocked.Increment(ref _toolCallCount);

        public int ToolCallCount
            => Volatile.Read(ref _toolCallCount);
    }

    internal sealed class SemanticKernelAgentOrchestrator : IAgentOrchestrator
    {
        readonly IServiceProvider _serviceProvider;

        public SemanticKernelAgentOrchestrator(IServiceProvider serviceProvider)
        {
            _serviceProvider = serviceProvider;
        }

        public async IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request)
        {
            var kernel = _serviceProvider.GetRequiredService<Kernel>();

            var plugin = new StoreKernelPlugin(request.Context, kernel);
            kernel.Plugins.AddFromObject(plugin, "store");

            var chatService = kernel.GetRequiredService<IChatCompletionService>();
            var settings = new OpenAIPromptExecutionSettings
            {
                Temperature = 0.2,
                FunctionChoiceBehavior = FunctionChoiceBehavior.Auto()
            };

            var history = BuildChatHistory(request.Messages, request.Context.PlannerHint);

            LogStep(
                request.Context,
                stepNo: 0,
                phase: "plan",
                detail: request.Context.PlannerHint ?? "no_planner_hint");

            var started = DateTime.UtcNow;
            var fullAnswer = new StringBuilder();

            var stream = chatService.GetStreamingChatMessageContentsAsync(
                history,
                executionSettings: settings,
                kernel: kernel,
                cancellationToken: request.CancellationToken);
            await using var streamEnumerator = stream.GetAsyncEnumerator(request.CancellationToken);

            while (true)
            {
                StreamingChatMessageContent chunk;
                try
                {
                    if (!await streamEnumerator.MoveNextAsync())
                        break;
                    chunk = streamEnumerator.Current;
                }
                catch (Exception ex)
                {
                    LogStep(
                        request.Context,
                        stepNo: 1,
                        phase: "model_error",
                        detail: ex.Message,
                        succeeded: false,
                        latencyMs: (long)(DateTime.UtcNow - started).TotalMilliseconds);
                    throw;
                }

                var text = chunk.Content ?? string.Empty;
                if (string.IsNullOrWhiteSpace(text))
                    continue;

                fullAnswer.Append(text);
                yield return text;
            }

            var finalText = CleanUserFacingLiveText(fullAnswer.ToString().Trim());
            if (string.IsNullOrWhiteSpace(finalText))
                finalText = "Minh chua co them thong tin de ket luan chac chan.";

            LogStep(
                request.Context,
                stepNo: 1,
                phase: "model_answer",
                detail: $"answer_chars={finalText.Length};tool_calls={request.Context.RuntimeState.ToolCallCount}",
                succeeded: true,
                latencyMs: (long)(DateTime.UtcNow - started).TotalMilliseconds);

            if (fullAnswer.Length == 0)
                yield return finalText;
        }

        static ChatHistory BuildChatHistory(List<ChatMessage> messages, string? plannerHint)
        {
            var history = new ChatHistory();
            if (!string.IsNullOrWhiteSpace(plannerHint))
                history.AddSystemMessage($"EXECUTION_PLAN_HINT:\n{plannerHint}");

            foreach (var msg in messages)
            {
                var content = msg.Content ?? string.Empty;
                if (string.IsNullOrWhiteSpace(content))
                    continue;

                var role = (msg.Role ?? string.Empty).Trim().ToLowerInvariant();
                switch (role)
                {
                    case "system":
                        history.AddSystemMessage(content);
                        break;
                    case "assistant":
                        history.AddAssistantMessage(content);
                        break;
                    default:
                        history.AddUserMessage(content);
                        break;
                }
            }

            return history;
        }

        static void LogStep(
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
}
