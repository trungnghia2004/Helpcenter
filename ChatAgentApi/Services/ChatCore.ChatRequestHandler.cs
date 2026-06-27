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
        readonly IChatAuthenticationService _authService;

        public ChatRequestHandler(
            ChatAgentRuntime runtime,
            IHttpClientFactory httpClientFactory,
            IAgentOrchestrator agentOrchestrator,
            IChatAuthenticationService authService)
        {
            _runtime = runtime;
            _httpClientFactory = httpClientFactory;
            _agentOrchestrator = agentOrchestrator;
            _authService = authService;
        }

        public Task HandleAsync(HttpContext ctx)
            => HandleChatAsync(
                ctx,
                _runtime,
                _httpClientFactory,
                _agentOrchestrator,
                _authService);
    }
}
