using System.Text.Json.Serialization;

namespace ChatAgentApi;

[JsonSerializable(typeof(ChatRequest))]
[JsonSerializable(typeof(ChatMessage))]
[JsonSerializable(typeof(List<ChatMessage>))]
[JsonSerializable(typeof(Conversation))]
[JsonSerializable(typeof(List<Conversation>))]
[JsonSerializable(typeof(Dictionary<string, string>))]
[JsonSerializable(typeof(Dictionary<string, long>))]
[JsonSerializable(typeof(UserMemoryRecord))]
[JsonSerializable(typeof(Dictionary<string, UserMemoryRecord>))]
[JsonSerializable(typeof(TokenUsageLog))]
[JsonSerializable(typeof(AgentToolCallLog))]
public partial class AppJsonContext : JsonSerializerContext { }
