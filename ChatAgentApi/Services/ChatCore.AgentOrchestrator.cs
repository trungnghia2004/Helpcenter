using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal interface IAgentOrchestrator
    {
        IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request);
    }

    internal sealed record AgentStreamRequest(
        string Model,
        string ApiKey,
        List<ChatMessage> Messages,
        AgentExecutionContext Context,
        Action<OpenAiUsage>? OnUsage,
        CancellationToken CancellationToken
    );

    internal sealed record AgentExecutionContext(
        HttpClient OpenAiHttp,
        HttpClient LaravelHttp,
        string LaravelBase,
        KnowledgeBase KnowledgeBase,
        string KnowledgeDir,
        string OpenAiApiKey,
        string OpenAiEmbedModel,
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

    internal sealed record ToolCallOutputInput(string CallId, string Output);

    internal sealed class OpenAiResponsesAgentOrchestrator : IAgentOrchestrator
    {
        const int MaxReasoningSteps = 8;

        public async IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request)
        {
            if (string.IsNullOrWhiteSpace(request.ApiKey))
                throw new InvalidOperationException("Missing OPENAI_API_KEY environment variable.");

            var plugin = new StoreAgentPlugin(request.Context);
            var toolOutputsInput = (List<ToolCallOutputInput>?)null;
            string? previousResponseId = null;
            var promptTokens = 0;
            var completionTokens = 0;
            var totalTokens = 0;
            var finalText = string.Empty;
            var plannerHint = string.IsNullOrWhiteSpace(request.Context.PlannerHint)
                ? BuildPlannerHintFromMessages(request.Messages)
                : request.Context.PlannerHint!;

            LogStep(
                request.Context,
                stepNo: 0,
                phase: "plan",
                detail: plannerHint);

            for (var step = 0; step < MaxReasoningSteps; step++)
            {
                var stepNo = step + 1;
                var started = DateTime.UtcNow;
                var payload = BuildResponsesPayload(
                    model: request.Model,
                    messages: request.Messages,
                    previousResponseId: previousResponseId,
                    toolOutputsInput: toolOutputsInput,
                    plannerHint: plannerHint);

                LogStep(
                    request.Context,
                    stepNo: stepNo,
                    phase: "model_request",
                    detail: $"response_call previous_response_id={previousResponseId ?? "null"}");

                using var httpReq = new HttpRequestMessage(HttpMethod.Post, "https://api.openai.com/v1/responses");
                httpReq.Headers.Authorization = new AuthenticationHeaderValue("Bearer", request.ApiKey);
                httpReq.Content = new StringContent(payload, Encoding.UTF8, "application/json");

                using var resp = await request.Context.OpenAiHttp.SendAsync(httpReq, request.CancellationToken);
                var body = await resp.Content.ReadAsStringAsync(request.CancellationToken);
                if (!resp.IsSuccessStatusCode)
                {
                    var status = (int)resp.StatusCode;
                    throw new HttpRequestException($"Response status code does not indicate success: {status} ({resp.ReasonPhrase}).");
                }

                using var doc = JsonDocument.Parse(body);
                var root = doc.RootElement;

                if (root.TryGetProperty("id", out var idEl) && idEl.ValueKind == JsonValueKind.String)
                    previousResponseId = idEl.GetString();

                ReadUsage(root, ref promptTokens, ref completionTokens, ref totalTokens);

                var functionCalls = ExtractFunctionCalls(root);
                if (functionCalls.Count == 0)
                {
                    finalText = ExtractOutputText(root);
                    LogStep(
                        request.Context,
                        stepNo: stepNo,
                        phase: "model_answer",
                        detail: $"final_text_chars={finalText.Length}",
                        responseId: previousResponseId,
                        succeeded: true,
                        latencyMs: (long)(DateTime.UtcNow - started).TotalMilliseconds);
                    break;
                }

                LogStep(
                    request.Context,
                    stepNo: stepNo,
                    phase: "tool_plan",
                    detail: $"tool_calls={functionCalls.Count}",
                    responseId: previousResponseId,
                    succeeded: true,
                    latencyMs: (long)(DateTime.UtcNow - started).TotalMilliseconds);

                toolOutputsInput = new List<ToolCallOutputInput>(functionCalls.Count);
                foreach (var fc in functionCalls)
                {
                    var toolStarted = DateTime.UtcNow;
                    var output = await InvokeToolAsync(plugin, fc.name, fc.argumentsJson);
                    toolOutputsInput.Add(new ToolCallOutputInput(fc.callId, output));
                    var toolSuccess = !output.Contains(" tam thoi loi:", StringComparison.OrdinalIgnoreCase) &&
                                      !output.StartsWith("Tool ", StringComparison.Ordinal);
                    LogStep(
                        request.Context,
                        stepNo: stepNo,
                        phase: "tool_result",
                        detail: $"call_id={fc.callId};output_chars={output.Length}",
                        responseId: previousResponseId,
                        toolName: fc.name,
                        succeeded: toolSuccess,
                        latencyMs: (long)(DateTime.UtcNow - toolStarted).TotalMilliseconds);
                }
            }

            if (string.IsNullOrWhiteSpace(finalText))
                finalText = "Mình không có thêm thông tin để kết luận chắc chắn.";

            finalText = CleanUserFacingLiveText(finalText.Trim());
            foreach (var chunk in SplitForSse(finalText, 160))
                yield return chunk;

            LogStep(
                request.Context,
                stepNo: MaxReasoningSteps,
                phase: "final",
                detail: $"answer_chars={finalText.Length};tool_calls={request.Context.RuntimeState.ToolCallCount}",
                responseId: previousResponseId,
                succeeded: true);

            if (request.OnUsage is not null)
            {
                if (totalTokens <= 0)
                    totalTokens = promptTokens + completionTokens;
                request.OnUsage(new OpenAiUsage(promptTokens, completionTokens, totalTokens));
            }
        }

        static string BuildResponsesPayload(
            string model,
            List<ChatMessage> messages,
            string? previousResponseId,
            List<ToolCallOutputInput>? toolOutputsInput,
            string plannerHint)
        {
            using var ms = new MemoryStream();
            using var writer = new Utf8JsonWriter(ms);

            writer.WriteStartObject();
            writer.WriteString("model", model);
            writer.WriteNumber("temperature", 0.2);
            writer.WriteString("tool_choice", "auto");
            if (!string.IsNullOrWhiteSpace(previousResponseId))
                writer.WriteString("previous_response_id", previousResponseId);

            writer.WritePropertyName("tools");
            WriteToolSchemas(writer);

            writer.WritePropertyName("input");
            if (toolOutputsInput is { Count: > 0 })
                WriteToolOutputsInput(writer, toolOutputsInput);
            else
                WriteInitialInput(writer, messages, plannerHint);

            writer.WriteEndObject();
            writer.Flush();
            return Encoding.UTF8.GetString(ms.ToArray());
        }

        static void WriteInitialInput(Utf8JsonWriter writer, List<ChatMessage> messages, string plannerHint)
        {
            writer.WriteStartArray();
            if (!string.IsNullOrWhiteSpace(plannerHint))
            {
                writer.WriteStartObject();
                writer.WriteString("role", "system");
                writer.WritePropertyName("content");
                writer.WriteStartArray();
                writer.WriteStartObject();
                writer.WriteString("type", "input_text");
                writer.WriteString("text", $"EXECUTION_PLAN_HINT:\n{plannerHint}");
                writer.WriteEndObject();
                writer.WriteEndArray();
                writer.WriteEndObject();
            }
            foreach (var m in messages)
            {
                var text = m.Content ?? string.Empty;
                if (string.IsNullOrWhiteSpace(text)) continue;

                var role = (m.Role ?? string.Empty).Trim().ToLowerInvariant();
                role = role switch
                {
                    "system" => "system",
                    "assistant" => "assistant",
                    _ => "user"
                };

                writer.WriteStartObject();
                writer.WriteString("role", role);
                writer.WritePropertyName("content");
                writer.WriteStartArray();
                writer.WriteStartObject();
                writer.WriteString("type", "input_text");
                writer.WriteString("text", text);
                writer.WriteEndObject();
                writer.WriteEndArray();
                writer.WriteEndObject();
            }
            writer.WriteEndArray();
        }

        static void WriteToolOutputsInput(Utf8JsonWriter writer, List<ToolCallOutputInput> outputs)
        {
            writer.WriteStartArray();
            foreach (var x in outputs)
            {
                writer.WriteStartObject();
                writer.WriteString("type", "function_call_output");
                writer.WriteString("call_id", x.CallId);
                writer.WriteString("output", x.Output);
                writer.WriteEndObject();
            }
            writer.WriteEndArray();
        }

        static void WriteToolSchemas(Utf8JsonWriter writer)
        {
            writer.WriteStartArray();

            WriteFunctionTool(
                writer,
                name: "search_products",
                description: "Tim danh sach san pham theo query, vi du quan short, ao thun, ao gile.",
                propertyName: "query",
                propertyDescription: "Tu khoa tim san pham",
                requiredPropertyName: "query");

            WriteFunctionTool(
                writer,
                name: "get_products_by_category",
                description: "Lay danh sach san pham theo danh muc, vi du short, jeans, thun, hoodie, gile.",
                propertyName: "category",
                propertyDescription: "Ten danh muc",
                requiredPropertyName: "category");

            WriteFunctionTool(
                writer,
                name: "get_product_by_code",
                description: "Lay thong tin chi tiet san pham theo ma, vi du AT0006.",
                propertyName: "productCode",
                propertyDescription: "Ma san pham",
                requiredPropertyName: "productCode");

            WriteFunctionTool(
                writer,
                name: "get_product_variants_by_code",
                description: "Lay size mau ton kho theo ma san pham, neu thieu ma thi dung ma gan nhat.",
                propertyName: "productCode",
                propertyDescription: "Ma san pham (co the bo trong)",
                requiredPropertyName: null);

            WriteFunctionTool(
                writer,
                name: "search_knowledge",
                description: "Tim noi dung help center nhu doi tra, giao hang, thanh toan, tai khoan.",
                propertyName: "query",
                propertyDescription: "Noi dung can tim trong knowledge",
                requiredPropertyName: "query");

            writer.WriteEndArray();
        }

        static void WriteFunctionTool(
            Utf8JsonWriter writer,
            string name,
            string description,
            string propertyName,
            string propertyDescription,
            string? requiredPropertyName)
        {
            writer.WriteStartObject();
            writer.WriteString("type", "function");
            writer.WriteString("name", name);
            writer.WriteString("description", description);
            writer.WritePropertyName("parameters");
            writer.WriteStartObject();
            writer.WriteString("type", "object");
            writer.WritePropertyName("properties");
            writer.WriteStartObject();
            writer.WritePropertyName(propertyName);
            writer.WriteStartObject();
            writer.WriteString("type", "string");
            writer.WriteString("description", propertyDescription);
            writer.WriteEndObject();
            writer.WriteEndObject();
            if (!string.IsNullOrWhiteSpace(requiredPropertyName))
            {
                writer.WritePropertyName("required");
                writer.WriteStartArray();
                writer.WriteStringValue(requiredPropertyName);
                writer.WriteEndArray();
            }
            writer.WriteBoolean("additionalProperties", false);
            writer.WriteEndObject();
            writer.WriteEndObject();
        }

        static List<(string callId, string name, string argumentsJson)> ExtractFunctionCalls(JsonElement root)
        {
            var calls = new List<(string callId, string name, string argumentsJson)>();
            if (!root.TryGetProperty("output", out var outputArr) || outputArr.ValueKind != JsonValueKind.Array)
                return calls;

            foreach (var item in outputArr.EnumerateArray())
            {
                if (!item.TryGetProperty("type", out var typeEl) || typeEl.ValueKind != JsonValueKind.String)
                    continue;

                var type = typeEl.GetString() ?? string.Empty;
                if (!string.Equals(type, "function_call", StringComparison.Ordinal))
                    continue;

                var callId = item.TryGetProperty("call_id", out var callIdEl) && callIdEl.ValueKind == JsonValueKind.String
                    ? callIdEl.GetString() ?? string.Empty
                    : string.Empty;

                var name = item.TryGetProperty("name", out var nameEl) && nameEl.ValueKind == JsonValueKind.String
                    ? nameEl.GetString() ?? string.Empty
                    : string.Empty;

                var argsJson = item.TryGetProperty("arguments", out var argsEl) && argsEl.ValueKind == JsonValueKind.String
                    ? argsEl.GetString() ?? "{}"
                    : "{}";

                if (string.IsNullOrWhiteSpace(callId) || string.IsNullOrWhiteSpace(name))
                    continue;

                calls.Add((callId, name, argsJson));
            }

            return calls;
        }

        static string ExtractOutputText(JsonElement root)
        {
            if (root.TryGetProperty("output_text", out var outText) && outText.ValueKind == JsonValueKind.String)
            {
                var txt = outText.GetString();
                if (!string.IsNullOrWhiteSpace(txt))
                    return txt!;
            }

            var sb = new StringBuilder();
            if (!root.TryGetProperty("output", out var outputArr) || outputArr.ValueKind != JsonValueKind.Array)
                return sb.ToString();

            foreach (var item in outputArr.EnumerateArray())
            {
                if (!item.TryGetProperty("type", out var typeEl) || typeEl.ValueKind != JsonValueKind.String)
                    continue;
                if (!string.Equals(typeEl.GetString(), "message", StringComparison.Ordinal))
                    continue;
                if (!item.TryGetProperty("content", out var contentArr) || contentArr.ValueKind != JsonValueKind.Array)
                    continue;

                foreach (var contentItem in contentArr.EnumerateArray())
                {
                    if (!contentItem.TryGetProperty("text", out var textEl) || textEl.ValueKind != JsonValueKind.String)
                        continue;

                    var text = textEl.GetString();
                    if (!string.IsNullOrWhiteSpace(text))
                        sb.Append(text);
                }
            }

            return sb.ToString();
        }

        static void ReadUsage(JsonElement root, ref int promptTokens, ref int completionTokens, ref int totalTokens)
        {
            if (!root.TryGetProperty("usage", out var usage) || usage.ValueKind != JsonValueKind.Object)
                return;

            promptTokens += ReadUsageInt(usage, "input_tokens");
            completionTokens += ReadUsageInt(usage, "output_tokens");
            totalTokens += ReadUsageInt(usage, "total_tokens");

            static int ReadUsageInt(JsonElement usageObj, string prop)
            {
                if (!usageObj.TryGetProperty(prop, out var el)) return 0;
                return el.ValueKind == JsonValueKind.Number && el.TryGetInt32(out var n) ? n : 0;
            }
        }

        static IEnumerable<string> SplitForSse(string text, int maxChars)
        {
            if (string.IsNullOrEmpty(text))
            {
                yield break;
            }

            if (maxChars < 32) maxChars = 32;

            for (var i = 0; i < text.Length; i += maxChars)
            {
                var take = Math.Min(maxChars, text.Length - i);
                yield return text.Substring(i, take);
            }
        }

        static async Task<string> InvokeToolAsync(StoreAgentPlugin plugin, string toolName, string argumentsJson)
        {
            try
            {
                var args = ParseArguments(argumentsJson);
                return toolName switch
                {
                    "search_products" => await plugin.SearchProductsAsync(args.TryGetValue("query", out var query) ? query ?? string.Empty : string.Empty),
                    "get_products_by_category" => await plugin.GetProductsByCategoryAsync(args.TryGetValue("category", out var category) ? category ?? string.Empty : string.Empty),
                    "get_product_by_code" => await plugin.GetProductByCodeAsync(args.TryGetValue("productCode", out var code) ? code ?? string.Empty : string.Empty),
                    "get_product_variants_by_code" => await plugin.GetProductVariantsByCodeAsync(args.TryGetValue("productCode", out var vCode) ? vCode : null),
                    "search_knowledge" => await plugin.SearchKnowledgeAsync(args.TryGetValue("query", out var q) ? q ?? string.Empty : string.Empty),
                    _ => $"Tool {toolName} khong duoc ho tro."
                };
            }
            catch (Exception ex)
            {
                return $"Tool {toolName} loi: {ex.Message}";
            }
        }

        static Dictionary<string, string?> ParseArguments(string argumentsJson)
        {
            var map = new Dictionary<string, string?>(StringComparer.Ordinal);
            if (string.IsNullOrWhiteSpace(argumentsJson)) return map;

            try
            {
                using var doc = JsonDocument.Parse(argumentsJson);
                if (doc.RootElement.ValueKind != JsonValueKind.Object) return map;
                foreach (var prop in doc.RootElement.EnumerateObject())
                {
                    var value = prop.Value.ValueKind switch
                    {
                        JsonValueKind.String => prop.Value.GetString(),
                        JsonValueKind.Null => null,
                        _ => prop.Value.ToString()
                    };
                    map[prop.Name] = value;
                }
            }
            catch
            {
            }

            return map;
        }

        static string BuildPlannerHintFromMessages(List<ChatMessage> messages)
        {
            var lastUser = messages.LastOrDefault(m => string.Equals(m.Role, "user", StringComparison.OrdinalIgnoreCase))?.Content ?? string.Empty;
            var plain = RemoveDiacritics(lastUser.ToLowerInvariant());
            if (string.IsNullOrWhiteSpace(plain))
                return "Hoi ro yeu cau truoc khi goi nhieu tool.";
            if (ExtractProductCode(lastUser) is not null)
                return "Goi get_product_by_code truoc, sau do get_product_variants_by_code neu can size/mau/ton kho.";
            if (Regex.IsMatch(plain, @"\b(size|kich co|mau|con hang|ton kho|stock)\b"))
                return "Goi search_products hoac get_products_by_category de tim ma san pham, roi goi get_product_variants_by_code.";
            if (Regex.IsMatch(plain, @"\b(doi mat khau|quen mat khau|tai khoan|giao hang|thanh toan|doi tra|bao mat)\b"))
                return "Goi search_knowledge truoc; neu da du thong tin thi ket luan ngan gon.";
            if (Regex.IsMatch(plain, @"\b(ao|quan|short|jean|thun|hoodie|gile|san pham|sp)\b"))
                return "Goi search_products hoac get_products_by_category va loc theo dieu kien mau/size neu co.";
            return "Chon tool toi thieu de tra loi chinh xac va ngan gon.";
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

    sealed class StoreAgentPlugin
    {
        readonly AgentExecutionContext _ctx;

        public StoreAgentPlugin(AgentExecutionContext ctx)
        {
            _ctx = ctx;
        }

        async Task<string> ExecuteToolAsync(
            string toolName,
            string input,
            Func<Task<string>> execute)
        {
            var started = DateTime.UtcNow;
            var succeeded = false;
            string output = string.Empty;
            string? error = null;
            var callNo = _ctx.RuntimeState.IncrementToolCallCount();
            var maxToolCalls = Math.Max(1, _ctx.Policy.MaxToolCalls);
            var maxOutputChars = Math.Max(200, _ctx.Policy.MaxToolOutputChars);

            try
            {
                if (callNo > maxToolCalls)
                {
                    error = "policy_max_tool_calls";
                    output = $"Da vuot gioi han goi tool ({maxToolCalls}). Hay tong hop tu ket qua da co.";
                    return output;
                }

                output = await execute();
                output = Truncate(output, maxOutputChars);
                succeeded = true;
                return output;
            }
            catch (Exception ex)
            {
                error = ex.Message;
                output = $"Tool {toolName} tam thoi loi: {ex.Message}";
                return output;
            }
            finally
            {
                _ctx.ToolLogger?.Invoke(new AgentToolCallLog
                {
                    AtUtc = DateTime.UtcNow,
                    ConversationId = _ctx.ConversationId,
                    UserKey = _ctx.UserKey,
                    ToolName = toolName,
                    Input = Truncate(input, 300),
                    OutputPreview = Truncate(output, 500),
                    Succeeded = succeeded,
                    LatencyMs = (long)(DateTime.UtcNow - started).TotalMilliseconds,
                    Error = error
                });
            }
        }

        static string Truncate(string value, int maxChars)
        {
            if (string.IsNullOrEmpty(value)) return value;
            return value.Length <= maxChars ? value : value[..maxChars];
        }

        public Task<string> SearchProductsAsync(string query)
            => ExecuteToolAsync("search_products", query, async () =>
            {
                if (string.IsNullOrWhiteSpace(query))
                    return "Khong co tu khoa tim kiem.";

                var items = await ChatCore.SearchProductsAsync(
                    _ctx.LaravelHttp,
                    _ctx.LaravelBase,
                    query,
                    _ctx.CancellationToken);

                if (items.Count == 0)
                    return "Khong tim thay san pham phu hop.";

                return CleanUserFacingLiveText(FormatProductList(items, maxItems: 8));
            });

        public Task<string> GetProductsByCategoryAsync(string category)
            => ExecuteToolAsync("get_products_by_category", category, async () =>
            {
                if (string.IsNullOrWhiteSpace(category))
                    return "Thieu danh muc.";

                var items = await ChatCore.GetProductsByCategoryAsync(
                    _ctx.LaravelHttp,
                    _ctx.LaravelBase,
                    category,
                    _ctx.CancellationToken);

                if (items.Count == 0)
                    return "Khong tim thay san pham trong danh muc nay.";

                return CleanUserFacingLiveText(FormatProductList(items, maxItems: 10));
            });

        public Task<string> GetProductByCodeAsync(string productCode)
            => ExecuteToolAsync("get_product_by_code", productCode, async () =>
            {
                if (string.IsNullOrWhiteSpace(productCode))
                    return "Thieu ma san pham.";

                var item = await ChatCore.GetProductByCodeAsync(
                    _ctx.LaravelHttp,
                    _ctx.LaravelBase,
                    productCode.Trim(),
                    _ctx.CancellationToken);

                if (item is null)
                    return $"Khong tim thay san pham ma {productCode}.";

                var p = item.Value;
                if (p.TryGetProperty("productCode", out var pCodeEl))
                {
                    var pCode = pCodeEl.GetString();
                    if (!string.IsNullOrWhiteSpace(pCode))
                        LastProductCodeByRequester[_ctx.UserKey] = pCode!;
                }

                return CleanUserFacingLiveText(FormatProduct(p));
            });

        public Task<string> GetProductVariantsByCodeAsync(string? productCode = null)
            => ExecuteToolAsync("get_product_variants_by_code", productCode ?? string.Empty, async () =>
            {
                var code = productCode?.Trim();
                if (string.IsNullOrWhiteSpace(code))
                    code = _ctx.LastKnownProductCode;
                if (string.IsNullOrWhiteSpace(code) &&
                    LastProductCodeByRequester.TryGetValue(_ctx.UserKey, out var remembered))
                    code = remembered;

                if (string.IsNullOrWhiteSpace(code))
                    return "Thieu ma san pham de kiem tra size mau ton kho.";

                var product = await ChatCore.GetProductByCodeAsync(
                    _ctx.LaravelHttp,
                    _ctx.LaravelBase,
                    code,
                    _ctx.CancellationToken);
                if (product is null)
                    return $"Khong tim thay san pham ma {code}.";

                var p = product.Value;
                if (!p.TryGetProperty("productID", out var pid))
                    return $"San pham ma {code} khong co productID de tra bien the.";

                var variants = await ChatCore.GetVariantsAsync(
                    _ctx.LaravelHttp,
                    _ctx.LaravelBase,
                    pid.GetInt64(),
                    _ctx.CancellationToken);

                if (variants is null)
                    return $"Khong co du lieu bien the cho ma {code}.";

                return FormatVariants(variants.Value, maxLines: 25);
            });

        public Task<string> SearchKnowledgeAsync(string query)
            => ExecuteToolAsync("search_knowledge", query, async () =>
            {
                if (string.IsNullOrWhiteSpace(query))
                    return "Thieu cau hoi.";

                var quick = TryGetLocalKnowledgeAnswer(query, _ctx.KnowledgeDir);
                if (!string.IsNullOrWhiteSpace(quick))
                    return quick!;

                if (_ctx.KnowledgeBase.Chunks.Count == 0)
                    return "Knowledge base hien dang trong.";

                try
                {
                    if (!string.IsNullOrWhiteSpace(_ctx.OpenAiApiKey))
                    {
                        var qVec = await KnowledgeIndexer.EmbedAsync(
                            http: _ctx.OpenAiHttp,
                            apiKey: _ctx.OpenAiApiKey,
                            model: _ctx.OpenAiEmbedModel,
                            text: query,
                            ct: _ctx.CancellationToken);

                        var topVec = _ctx.KnowledgeBase.SearchTopK(qVec, k: 3)
                            .Where(x => x.score >= 0.35f)
                            .ToList();

                        if (topVec.Count > 0)
                            return KnowledgeBase.FormatSources(topVec, maxCharsPerChunk: 900);
                    }
                }
                catch
                {
                }

                var topLexical = _ctx.KnowledgeBase.SearchTopKLexical(query, k: 3)
                    .Where(x => x.score >= 1f)
                    .ToList();
                if (topLexical.Count > 0)
                    return KnowledgeBase.FormatSources(topLexical, maxCharsPerChunk: 900);

                return "Khong tim thay thong tin phu hop trong knowledge.";
            });
    }
}
