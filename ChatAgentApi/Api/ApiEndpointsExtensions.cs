namespace ChatAgentApi;

internal static class ApiEndpointsExtensions
{
    internal static void MapChatAgentApi(this WebApplication app)
    {
        ChatCore.MapApiEndpoints(app);
    }
}
