using Microsoft.Extensions.DependencyInjection;
using Microsoft.SemanticKernel;
using Microsoft.SemanticKernel.ChatCompletion;
using Microsoft.SemanticKernel.Connectors.OpenAI;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal interface IAgentOrchestrator
    {
        IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request);
    }

    internal interface IAgentPluginFactory
    {
        void ImportStorePlugin(Kernel kernel, AgentExecutionContext context);
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
        AgentRunPolicy Policy,
        AgentRuntimeState RuntimeState,
        Action<AgentToolCallLog>? ToolLogger,
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

    internal sealed class SemanticKernelAgentOrchestrator : IAgentOrchestrator
    {
        readonly IAgentPluginFactory _pluginFactory;

        public SemanticKernelAgentOrchestrator(IAgentPluginFactory pluginFactory)
        {
            _pluginFactory = pluginFactory;
        }

        public async IAsyncEnumerable<string> StreamAsync(AgentStreamRequest request)
        {
            _ = request.OnUsage;

            if (string.IsNullOrWhiteSpace(request.ApiKey))
                throw new InvalidOperationException("Missing OPENAI_API_KEY environment variable.");

            var kernelBuilder = Kernel.CreateBuilder();
            kernelBuilder.AddOpenAIChatCompletion(modelId: request.Model, apiKey: request.ApiKey);
            var kernel = kernelBuilder.Build();

            _pluginFactory.ImportStorePlugin(kernel, request.Context);

            var chatService = kernel.Services.GetRequiredService<IChatCompletionService>();
            var history = new ChatHistory();

            foreach (var m in request.Messages)
            {
                var role = (m.Role ?? string.Empty).Trim().ToLowerInvariant();
                var content = m.Content ?? string.Empty;
                if (string.IsNullOrWhiteSpace(content)) continue;

                switch (role)
                {
                    case "system":
                        history.AddSystemMessage(content);
                        break;
                    case "assistant":
                        history.AddAssistantMessage(content);
                        break;
                    default:
                        history.AddUserMessage(content);
                        break;
                }
            }

            var settings = new OpenAIPromptExecutionSettings
            {
                Temperature = 0.2,
                ToolCallBehavior = ToolCallBehavior.AutoInvokeKernelFunctions
            };

            await foreach (var part in chatService.GetStreamingChatMessageContentsAsync(
                               chatHistory: history,
                               executionSettings: settings,
                               kernel: kernel,
                               cancellationToken: request.CancellationToken))
            {
                if (!string.IsNullOrWhiteSpace(part.Content))
                    yield return part.Content!;
            }
        }
    }

    internal sealed class StoreAgentPluginFactory : IAgentPluginFactory
    {
        public void ImportStorePlugin(Kernel kernel, AgentExecutionContext context)
        {
            var storePlugin = new StoreAgentPlugin(context);

#pragma warning disable IL2026, IL3050
            var functions = new[]
            {
                KernelFunctionFactory.CreateFromMethod(
                    method: (Func<string, Task<string>>)storePlugin.SearchProductsAsync,
                    jsonSerializerOptions: AppJsonContext.Default.Options,
                    functionName: "search_products",
                    description: "Tim danh sach san pham theo query, vi du quan short, ao thun, ao gile."),
                KernelFunctionFactory.CreateFromMethod(
                    method: (Func<string, Task<string>>)storePlugin.GetProductsByCategoryAsync,
                    jsonSerializerOptions: AppJsonContext.Default.Options,
                    functionName: "get_products_by_category",
                    description: "Lay danh sach san pham theo danh muc, vi du short, jeans, thun, hoodie, gile."),
                KernelFunctionFactory.CreateFromMethod(
                    method: (Func<string, Task<string>>)storePlugin.GetProductByCodeAsync,
                    jsonSerializerOptions: AppJsonContext.Default.Options,
                    functionName: "get_product_by_code",
                    description: "Lay thong tin chi tiet san pham theo ma, vi du AT0006."),
                KernelFunctionFactory.CreateFromMethod(
                    method: (Func<string?, Task<string>>)storePlugin.GetProductVariantsByCodeAsync,
                    jsonSerializerOptions: AppJsonContext.Default.Options,
                    functionName: "get_product_variants_by_code",
                    description: "Lay size mau ton kho theo ma san pham, neu thieu ma thi dung ma gan nhat."),
                KernelFunctionFactory.CreateFromMethod(
                    method: (Func<string, Task<string>>)storePlugin.SearchKnowledgeAsync,
                    jsonSerializerOptions: AppJsonContext.Default.Options,
                    functionName: "search_knowledge",
                    description: "Tim noi dung help center nhu doi tra, giao hang, thanh toan, tai khoan.")
            };
            kernel.ImportPluginFromFunctions("store", functions);
#pragma warning restore IL2026, IL3050
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
