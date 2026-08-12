using System.Net.Http.Headers;
using System.Runtime.CompilerServices;
using System.Text;
using System.Text.Json;
using System.Text.Json.Nodes;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal sealed class ResponsesBackend : IAgentModelBackend
    {
        static readonly JsonSerializerOptions JsonOptions = new(JsonSerializerDefaults.Web);

        readonly IHttpClientFactory _httpClientFactory;
        readonly AgentToolExecutionContextAccessor _toolContextAccessor;
        readonly ISemanticKernelToolBridge _toolBridge;

        public ResponsesBackend(
            IHttpClientFactory httpClientFactory,
            AgentToolExecutionContextAccessor toolContextAccessor,
            ISemanticKernelToolBridge toolBridge)
        {
            _httpClientFactory = httpClientFactory;
            _toolContextAccessor = toolContextAccessor;
            _toolBridge = toolBridge;
        }

        public async IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request)
        {
            var fullAnswer = new StringBuilder();
            var input = BuildInputItems(request);
            var stepNo = 0;
            var started = DateTime.UtcNow;
            _toolContextAccessor.Current = new AgentToolExecutionContext(
                LastKnownProductCode: request.Context.LastKnownProductCode,
                Policy: request.Context.Policy,
                RuntimeState: request.Context.RuntimeState,
                ConversationId: request.Context.ConversationId,
                UserKey: request.Context.UserKey,
                ToolLogger: request.Context.ToolLogger,
                CancellationToken: request.CancellationToken);

            try
            {
                LogAgentStep(
                    request.Context,
                    stepNo: 0,
                    phase: "plan",
                    detail: request.Context.PlannerHint ?? "no_planner_hint");

                while (true)
                {
                    ResponseEnvelope? envelope = null;
                    await foreach (var emitted in StreamResponseAsync(
                        request,
                        input,
                        stepNo + 1,
                        fullAnswer,
                        started,
                        request.CancellationToken))
                    {
                        if (emitted.Kind == StreamEmitKind.Text && emitted.Text is not null)
                        {
                            yield return emitted.Text;
                            continue;
                        }

                        if (emitted.Kind == StreamEmitKind.Completed)
                            envelope = emitted.Envelope;
                    }

                    if (envelope is null)
                        throw new InvalidOperationException("OpenAI Responses stream ended without a completed response.");

                    var responseId = envelope.ResponseId;
                    var toolCalls = envelope.ToolCalls;
                    if (toolCalls.Count == 0)
                    {
                        if (fullAnswer.Length == 0 && envelope.Response is not null)
                            AppendOutputText(fullAnswer, envelope.Response.Value);

                        LogAgentStep(
                            request.Context,
                            stepNo: ++stepNo,
                            phase: "model_answer",
                            detail: $"answer_chars={fullAnswer.Length};tool_calls={request.Context.RuntimeState.ToolCallCount}",
                            responseId: responseId,
                            succeeded: true,
                            latencyMs: (long)(DateTime.UtcNow - started).TotalMilliseconds);
                        break;
                    }

                    LogAgentStep(
                        request.Context,
                        stepNo: ++stepNo,
                        phase: "tool_call",
                        detail: $"tool_calls={toolCalls.Count}",
                        responseId: responseId,
                        succeeded: true);

                    if (envelope.Response is not null)
                        AppendOutputItems(input, envelope.Response.Value);

                    foreach (var toolCall in toolCalls)
                    {
                        var toolOutput = await _toolBridge.InvokeAsync(toolCall.Name, toolCall.Arguments, request.CancellationToken);
                        input.Add(new JsonObject
                        {
                            ["type"] = "function_call_output",
                            ["call_id"] = toolCall.CallId,
                            ["output"] = toolOutput
                        });

                        LogAgentStep(
                            request.Context,
                            stepNo: ++stepNo,
                            phase: "tool_result",
                            detail: $"tool={toolCall.Name};chars={toolOutput.Length}",
                            toolName: toolCall.Name,
                            succeeded: true);
                    }
                }

                var finalText = CleanUserFacingLiveText(fullAnswer.ToString().Trim());
                if (string.IsNullOrWhiteSpace(finalText))
                    finalText = "Minh chua co them thong tin de ket luan chac chan.";

                if (fullAnswer.Length == 0)
                {
                    foreach (var chunk in ChunkText(finalText, 120))
                        yield return chunk;
                }
            }
            finally
            {
                _toolContextAccessor.Current = null;
            }
        }

        HttpRequestMessage BuildHttpRequest(AgentStreamRequest request, JsonArray input, int stepNo)
        {
            var tools = request.Context.AllowToolCalls
                ? _toolBridge.BuildTools()
                : new JsonArray();
            var body = new JsonObject
            {
                ["model"] = request.Model,
                ["input"] = input,
                ["tools"] = tools,
                ["tool_choice"] = request.Context.AllowToolCalls ? "auto" : "none",
                ["parallel_tool_calls"] = false,
                ["store"] = false,
                ["stream"] = true
            };

            LogAgentStep(
                request.Context,
                stepNo: stepNo,
                phase: "model_request",
                detail: $"messages={request.Messages.Count};tools={(request.Context.AllowToolCalls ? 5 : 0)}");

            var httpRequest = new HttpRequestMessage(HttpMethod.Post, "https://api.openai.com/v1/responses");
            httpRequest.Headers.Authorization = new AuthenticationHeaderValue("Bearer", request.ApiKey);
            httpRequest.Content = new StringContent(body.ToJsonString(JsonOptions), Encoding.UTF8, "application/json");
            return httpRequest;
        }

        async Task<HttpResponseMessage> SendAsync(HttpRequestMessage request, CancellationToken cancellationToken)
        {
            var http = _httpClientFactory.CreateClient("openai");
            return await http.SendAsync(request, HttpCompletionOption.ResponseHeadersRead, cancellationToken);
        }

        static JsonArray BuildInputItems(AgentStreamRequest request)
        {
            var input = new JsonArray();

            if (!string.IsNullOrWhiteSpace(request.Context.PlannerHint))
            {
                input.Add(new JsonObject
                {
                    ["role"] = "developer",
                    ["content"] = $"EXECUTION_PLAN_HINT:\n{request.Context.PlannerHint}"
                });
            }

            foreach (var message in request.Messages)
            {
                var content = message.Content?.Trim();
                if (string.IsNullOrWhiteSpace(content))
                    continue;

                var role = NormalizeRole(message.Role);
                input.Add(new JsonObject
                {
                    ["role"] = role,
                    ["content"] = content
                });
            }

            return input;
        }

        static string NormalizeRole(string? role)
        {
            var normalized = role?.Trim().ToLowerInvariant();
            return normalized switch
            {
                "assistant" => "assistant",
                "system" => "system",
                "developer" => "developer",
                _ => "user"
            };
        }

        static List<ToolCall> ReadToolCalls(JsonElement root)
        {
            var results = new List<ToolCall>();
            if (!root.TryGetProperty("output", out var output) || output.ValueKind != JsonValueKind.Array)
                return results;

            foreach (var item in output.EnumerateArray())
            {
                if (!string.Equals(TryGetString(item, "type"), "function_call", StringComparison.Ordinal))
                    continue;

                var callId = TryGetString(item, "call_id");
                var name = TryGetString(item, "name");
                var arguments = TryGetString(item, "arguments") ?? "{}";
                if (string.IsNullOrWhiteSpace(callId) || string.IsNullOrWhiteSpace(name))
                    continue;

                results.Add(new ToolCall(callId, name, arguments));
            }

            return results;
        }

        static void AppendOutputItems(JsonArray input, JsonElement root)
        {
            if (!root.TryGetProperty("output", out var output) || output.ValueKind != JsonValueKind.Array)
                return;

            foreach (var item in output.EnumerateArray())
                input.Add(JsonNode.Parse(item.GetRawText())!);
        }

        async IAsyncEnumerable<StreamEmit> StreamResponseAsync(
            AgentStreamRequest request,
            JsonArray input,
            int stepNo,
            StringBuilder fullAnswer,
            DateTime started,
            [EnumeratorCancellation] CancellationToken cancellationToken)
        {
            using var openAiRequest = BuildHttpRequest(request, input, stepNo);
            using var response = await SendAsync(openAiRequest, cancellationToken);

            if (!response.IsSuccessStatusCode)
            {
                var body = await response.Content.ReadAsStringAsync(cancellationToken);
                EnsureSuccess(response, body);
            }

            await using var stream = await response.Content.ReadAsStreamAsync(cancellationToken);
            using var reader = new StreamReader(stream);
            JsonElement? completedResponse = null;
            var responseId = string.Empty;
            var toolCallsByItemId = new Dictionary<string, ToolCallBuilder>(StringComparer.Ordinal);

            await foreach (var payload in ReadSsePayloadsAsync(reader, cancellationToken))
            {
                using var document = JsonDocument.Parse(payload);
                var root = document.RootElement;
                var type = TryGetString(root, "type");

                switch (type)
                {
                    case "response.created":
                        responseId = TryGetString(root, "response", "id") ?? responseId;
                        break;

                    case "response.output_text.delta":
                        var delta = TryGetString(root, "delta");
                        if (!string.IsNullOrEmpty(delta))
                        {
                            fullAnswer.Append(delta);
                            yield return StreamEmit.ForText(delta);
                        }
                        break;

                    case "response.output_item.added":
                        RegisterFunctionCall(root, toolCallsByItemId);
                        break;

                    case "response.function_call_arguments.delta":
                        AppendFunctionCallDelta(root, toolCallsByItemId);
                        break;

                    case "response.function_call_arguments.done":
                        CompleteFunctionCall(root, toolCallsByItemId);
                        break;

                    case "response.completed":
                        if (root.TryGetProperty("response", out var responseNode))
                        {
                            completedResponse = responseNode.Clone();
                            responseId = TryGetString(responseNode, "id") ?? responseId;
                        }
                        break;

                    case "error":
                        throw new HttpRequestException($"OpenAI Responses stream error: {payload}");
                }
            }

            if (completedResponse is null)
                throw new InvalidOperationException("OpenAI Responses stream did not produce response.completed.");

            var responseElement = completedResponse.Value;
            var responseToolCalls = ReadToolCalls(responseElement);
            if (responseToolCalls.Count == 0 && toolCallsByItemId.Count > 0)
                responseToolCalls = toolCallsByItemId.Values.Select(x => x.ToToolCall()).ToList();

            LogAgentStep(
                request.Context,
                stepNo: stepNo,
                phase: "model_stream_completed",
                detail: $"response_id={responseId};tool_calls={responseToolCalls.Count}",
                responseId: responseId,
                succeeded: true,
                latencyMs: (long)(DateTime.UtcNow - started).TotalMilliseconds);

            yield return StreamEmit.ForCompleted(new ResponseEnvelope(
                ResponseId: responseId,
                Response: responseElement,
                ToolCalls: responseToolCalls));
        }

        static async IAsyncEnumerable<string> ReadSsePayloadsAsync(
            StreamReader reader,
            [EnumeratorCancellation] CancellationToken cancellationToken)
        {
            var data = new StringBuilder();

            while (true)
            {
                cancellationToken.ThrowIfCancellationRequested();
                var line = await reader.ReadLineAsync(cancellationToken);
                if (line is null)
                    break;

                if (line.Length == 0)
                {
                    if (data.Length > 0)
                    {
                        yield return data.ToString();
                        data.Clear();
                    }

                    continue;
                }

                if (line.StartsWith("data:", StringComparison.Ordinal))
                {
                    var chunk = line[5..].TrimStart();
                    if (string.Equals(chunk, "[DONE]", StringComparison.Ordinal))
                        yield break;

                    if (data.Length > 0)
                        data.Append('\n');
                    data.Append(chunk);
                }
            }

            if (data.Length > 0)
                yield return data.ToString();
        }

        static void AppendOutputText(StringBuilder buffer, JsonElement root)
        {
            var outputText = TryGetString(root, "output_text");
            if (!string.IsNullOrWhiteSpace(outputText))
            {
                buffer.Clear();
                buffer.Append(outputText);
                return;
            }

            if (!root.TryGetProperty("output", out var output) || output.ValueKind != JsonValueKind.Array)
                return;

            foreach (var item in output.EnumerateArray())
            {
                if (!string.Equals(TryGetString(item, "type"), "message", StringComparison.Ordinal))
                    continue;

                if (!item.TryGetProperty("content", out var content) || content.ValueKind != JsonValueKind.Array)
                    continue;

                foreach (var part in content.EnumerateArray())
                {
                    var type = TryGetString(part, "type");
                    if (!string.Equals(type, "output_text", StringComparison.Ordinal) &&
                        !string.Equals(type, "text", StringComparison.Ordinal))
                    {
                        continue;
                    }

                    var text = TryGetString(part, "text");
                    if (!string.IsNullOrWhiteSpace(text))
                        buffer.Append(text);
                }
            }
        }

        static string? TryGetString(JsonElement element, string propertyName)
        {
            if (!element.TryGetProperty(propertyName, out var value))
                return null;

            return value.ValueKind switch
            {
                JsonValueKind.String => value.GetString(),
                JsonValueKind.Null => null,
                _ => value.ToString()
            };
        }

        static string? TryGetString(JsonElement element, string parentPropertyName, string propertyName)
        {
            if (!element.TryGetProperty(parentPropertyName, out var parent))
                return null;

            return TryGetString(parent, propertyName);
        }

        static void RegisterFunctionCall(JsonElement root, Dictionary<string, ToolCallBuilder> toolCallsByItemId)
        {
            if (!root.TryGetProperty("item", out var item))
                return;

            if (!string.Equals(TryGetString(item, "type"), "function_call", StringComparison.Ordinal))
                return;

            var itemId = TryGetString(item, "id");
            if (string.IsNullOrWhiteSpace(itemId))
                return;

            var builder = GetOrCreateToolCallBuilder(itemId, toolCallsByItemId);
            builder.Name = TryGetString(item, "name") ?? builder.Name;
            builder.CallId = TryGetString(item, "call_id") ?? builder.CallId;
            builder.Arguments = TryGetString(item, "arguments") ?? builder.Arguments;
        }

        static void AppendFunctionCallDelta(JsonElement root, Dictionary<string, ToolCallBuilder> toolCallsByItemId)
        {
            var itemId = TryGetString(root, "item_id");
            var delta = TryGetString(root, "delta");
            if (string.IsNullOrWhiteSpace(itemId) || string.IsNullOrEmpty(delta))
                return;

            var builder = GetOrCreateToolCallBuilder(itemId, toolCallsByItemId);
            builder.ArgumentChunks.Append(delta);
        }

        static void CompleteFunctionCall(JsonElement root, Dictionary<string, ToolCallBuilder> toolCallsByItemId)
        {
            if (root.TryGetProperty("item", out var item))
            {
                var itemId = TryGetString(item, "id");
                if (string.IsNullOrWhiteSpace(itemId))
                    return;

                var builder = GetOrCreateToolCallBuilder(itemId, toolCallsByItemId);
                builder.Name = TryGetString(item, "name") ?? builder.Name;
                builder.CallId = TryGetString(item, "call_id") ?? builder.CallId;
                builder.Arguments = TryGetString(item, "arguments") ?? builder.Arguments;
                builder.IsDone = true;
                return;
            }

            var fallbackItemId = TryGetString(root, "item_id");
            if (string.IsNullOrWhiteSpace(fallbackItemId))
                return;

            GetOrCreateToolCallBuilder(fallbackItemId, toolCallsByItemId).IsDone = true;
        }

        static ToolCallBuilder GetOrCreateToolCallBuilder(string itemId, Dictionary<string, ToolCallBuilder> toolCallsByItemId)
        {
            if (!toolCallsByItemId.TryGetValue(itemId, out var builder))
            {
                builder = new ToolCallBuilder(itemId);
                toolCallsByItemId[itemId] = builder;
            }

            return builder;
        }

        static IEnumerable<string> ChunkText(string text, int chunkSize)
        {
            if (string.IsNullOrEmpty(text))
                yield break;

            var index = 0;
            while (index < text.Length)
            {
                var take = Math.Min(chunkSize, text.Length - index);
                yield return text.Substring(index, take);
                index += take;
            }
        }

        static void EnsureSuccess(HttpResponseMessage response, string body)
        {
            if (response.IsSuccessStatusCode)
                return;

            var excerpt = body.Length <= 1000 ? body : body[..1000];
            throw new HttpRequestException(
                $"OpenAI Responses API failed ({(int)response.StatusCode} {response.ReasonPhrase}): {excerpt}");
        }

        sealed record ToolCall(string CallId, string Name, string Arguments);
        sealed record ResponseEnvelope(string ResponseId, JsonElement? Response, List<ToolCall> ToolCalls);
        enum StreamEmitKind
        {
            Text,
            Completed
        }

        sealed record StreamEmit(StreamEmitKind Kind, string? Text, ResponseEnvelope? Envelope)
        {
            public static StreamEmit ForText(string text) => new(StreamEmitKind.Text, text, null);
            public static StreamEmit ForCompleted(ResponseEnvelope envelope) => new(StreamEmitKind.Completed, null, envelope);
        }

        sealed class ToolCallBuilder
        {
            public ToolCallBuilder(string itemId)
            {
                ItemId = itemId;
            }

            public string ItemId { get; }

            public string? CallId { get; set; }

            public string? Name { get; set; }

            public string? Arguments { get; set; }

            public bool IsDone { get; set; }

            public StringBuilder ArgumentChunks { get; } = new();

            public ToolCall ToToolCall()
            {
                var arguments = !string.IsNullOrWhiteSpace(Arguments)
                    ? Arguments!
                    : ArgumentChunks.ToString();

                return new ToolCall(
                    CallId ?? ItemId,
                    Name ?? "unknown_tool",
                    string.IsNullOrWhiteSpace(arguments) ? "{}" : arguments);
            }
        }
    }
}
