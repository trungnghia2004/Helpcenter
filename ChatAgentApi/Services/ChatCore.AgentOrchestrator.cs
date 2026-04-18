using Microsoft.Extensions.DependencyInjection;
using Microsoft.SemanticKernel;
using Microsoft.SemanticKernel.ChatCompletion;
using Microsoft.SemanticKernel.Connectors.OpenAI;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    sealed record AgentExecutionContext(
        HttpClient OpenAiHttp,
        HttpClient LaravelHttp,
        string LaravelBase,
        KnowledgeBase KnowledgeBase,
        string KnowledgeDir,
        string OpenAiApiKey,
        string OpenAiEmbedModel,
        string? LastKnownProductCode,
        string RequesterKey,
        CancellationToken CancellationToken
    );

    static async IAsyncEnumerable<string> OpenAIAgentStream(
        HttpClient http,
        string model,
        string apiKey,
        List<ChatMessage> messages,
        AgentExecutionContext agentContext,
        Action<OpenAiUsage>? onUsage,
        [System.Runtime.CompilerServices.EnumeratorCancellation] CancellationToken ct)
    {
        _ = http;
        _ = onUsage;

        if (string.IsNullOrWhiteSpace(apiKey))
            throw new InvalidOperationException("Missing OPENAI_API_KEY environment variable.");

        var kernelBuilder = Kernel.CreateBuilder();
        kernelBuilder.AddOpenAIChatCompletion(modelId: model, apiKey: apiKey);
        var kernel = kernelBuilder.Build();
        var storePlugin = new StoreAgentPlugin(agentContext);

#pragma warning disable IL2026, IL3050
        var functions = new[]
        {
            KernelFunctionFactory.CreateFromMethod(
                method: (Func<string, Task<string>>)storePlugin.SearchProductsAsync,
                functionName: "search_products",
                description: "Tim danh sach san pham theo query, vi du quan short, ao thun, ao gile."),
            KernelFunctionFactory.CreateFromMethod(
                method: (Func<string, Task<string>>)storePlugin.GetProductsByCategoryAsync,
                functionName: "get_products_by_category",
                description: "Lay danh sach san pham theo danh muc, vi du short, jeans, thun, hoodie, gile."),
            KernelFunctionFactory.CreateFromMethod(
                method: (Func<string, Task<string>>)storePlugin.GetProductByCodeAsync,
                functionName: "get_product_by_code",
                description: "Lay thong tin chi tiet san pham theo ma, vi du AT0006."),
            KernelFunctionFactory.CreateFromMethod(
                method: (Func<string?, Task<string>>)storePlugin.GetProductVariantsByCodeAsync,
                functionName: "get_product_variants_by_code",
                description: "Lay size mau ton kho theo ma san pham, neu thieu ma thi dung ma gan nhat."),
            KernelFunctionFactory.CreateFromMethod(
                method: (Func<string, Task<string>>)storePlugin.SearchKnowledgeAsync,
                functionName: "search_knowledge",
                description: "Tim noi dung help center nhu doi tra, giao hang, thanh toan, tai khoan.")
        };
#pragma warning restore IL2026, IL3050

        kernel.ImportPluginFromFunctions("store", functions);

        var chatService = kernel.Services.GetRequiredService<IChatCompletionService>();

        var history = new ChatHistory();
        foreach (var m in messages)
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
                           cancellationToken: ct))
        {
            if (!string.IsNullOrWhiteSpace(part.Content))
                yield return part.Content!;
        }
    }

    sealed class StoreAgentPlugin
    {
        readonly AgentExecutionContext _ctx;

        public StoreAgentPlugin(AgentExecutionContext ctx)
        {
            _ctx = ctx;
        }

        public async Task<string> SearchProductsAsync(string query)
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
        }

        public async Task<string> GetProductsByCategoryAsync(string category)
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
        }

        public async Task<string> GetProductByCodeAsync(string productCode)
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
                    LastProductCodeByRequester[_ctx.RequesterKey] = pCode!;
            }

            return CleanUserFacingLiveText(FormatProduct(p));
        }

        public async Task<string> GetProductVariantsByCodeAsync(string? productCode = null)
        {
            var code = productCode?.Trim();
            if (string.IsNullOrWhiteSpace(code))
                code = _ctx.LastKnownProductCode;
            if (string.IsNullOrWhiteSpace(code) &&
                LastProductCodeByRequester.TryGetValue(_ctx.RequesterKey, out var remembered))
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
        }

        public async Task<string> SearchKnowledgeAsync(string query)
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
        }
    }
}
