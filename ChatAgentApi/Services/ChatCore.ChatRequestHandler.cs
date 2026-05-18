namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal interface IChatRequestHandler
    {
        Task HandleAsync(HttpContext ctx);
    }

    internal sealed class ChatRequestHandler : IChatRequestHandler
    {
        readonly ChatAgentRuntime _runtime;
        readonly IHttpClientFactory _httpClientFactory;
        readonly IAgentOrchestrator _agentOrchestrator;

        public ChatRequestHandler(
            ChatAgentRuntime runtime,
            IHttpClientFactory httpClientFactory,
            IAgentOrchestrator agentOrchestrator)
        {
            _runtime = runtime;
            _httpClientFactory = httpClientFactory;
            _agentOrchestrator = agentOrchestrator;
        }

        public Task HandleAsync(HttpContext ctx)
            => HandleChatAsync(
                ctx,
                _runtime,
                _httpClientFactory,
                _agentOrchestrator);
    }
}
