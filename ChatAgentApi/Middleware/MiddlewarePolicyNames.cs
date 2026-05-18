namespace ChatAgentApi;

internal static class MiddlewarePolicyNames
{
    internal const string ChatPolicyName = "chat-per-user";
    internal const string ChatTimeoutPolicyName = "chat-timeout";
    internal const string CorsPolicyName = "chat-open";
}
