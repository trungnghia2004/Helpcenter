using System.Text.Json.Serialization;

namespace ChatAgentApi;

[JsonSerializable(typeof(ChatRequest))]
[JsonSerializable(typeof(ChatMessage))]
[JsonSerializable(typeof(List<ChatMessage>))]
public partial class AppJsonContext : JsonSerializerContext { }
