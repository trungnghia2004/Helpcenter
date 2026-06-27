namespace ChatAgentApi;

internal sealed class ChatAuthenticationMiddleware
{
    readonly RequestDelegate _next;

    public ChatAuthenticationMiddleware(RequestDelegate next)
    {
        _next = next;
    }

    public async Task InvokeAsync(HttpContext context, IChatAuthenticationService authService)
    {
        if (!HttpMethods.IsOptions(context.Request.Method) &&
            context.Request.Path.Equals("/api/chat", StringComparison.OrdinalIgnoreCase))
        {
            await authService.GetAuthenticatedUserKeyAsync(context, context.RequestAborted);
        }

        await _next(context);
    }
}
