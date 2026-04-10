namespace ChatAgentApi;

public record ChatRequest(
    string? ConversationId,
    List<ChatMessage> Messages,
    string? UserId = null,
    string? AppId = null
);

public record ChatMessage(string Role, string Content);

public sealed class Conversation
{
    public string Id { get; set; } = default!;
    public List<ChatMessage> Messages { get; set; } = new();
    public string? Summary { get; set; }
    public string? Memory { get; set; }
    public Dictionary<string, string> MemoryFacts { get; set; } = new();
    public DateTime CreatedAtUtc { get; set; }
    public DateTime? UpdatedAtUtc { get; set; }
}
