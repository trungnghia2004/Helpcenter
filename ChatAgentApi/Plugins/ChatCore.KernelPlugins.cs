using System.ComponentModel;
using Microsoft.Extensions.AI;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.SemanticKernel;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    sealed class StoreKernelPlugin
    {
        readonly AgentExecutionContext _ctx;
        readonly IEmbeddingGenerator<string, Embedding<float>>? _embeddingService;

        public StoreKernelPlugin(AgentExecutionContext ctx, Kernel kernel)
        {
            _ctx = ctx;
            _embeddingService = kernel.Services.GetService<IEmbeddingGenerator<string, Embedding<float>>>();
        }

        async Task<string> ExecuteToolAsync(string toolName, string input, Func<Task<string>> execute)
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

        [KernelFunction("search_products")]
        [Description("Tim danh sach san pham theo tu khoa, vi du ao thun, quan short, ao gile")]
        public Task<string> SearchProductsAsync([Description("Tu khoa tim kiem san pham")] string query)
            => ExecuteToolAsync("search_products", query, async () =>
            {
                if (string.IsNullOrWhiteSpace(query))
                    return "Không có từ khóa tìm kiếm.";

                var items = await ChatCore.SearchProductsAsync(_ctx.LaravelHttp, _ctx.LaravelBase, query, _ctx.CancellationToken);
                if (items.Count == 0)
                    return "Không tìm thấy sản phẩm phù hợp.";

                return CleanUserFacingLiveText(FormatProductList(items, maxItems: 8));
            });

        [KernelFunction("get_products_by_category")]
        [Description("Lay danh sach san pham theo danh muc, vi du short, jeans, thun, hoodie, gile")]
        public Task<string> GetProductsByCategoryAsync([Description("Ten danh muc")] string category)
            => ExecuteToolAsync("get_products_by_category", category, async () =>
            {
                if (string.IsNullOrWhiteSpace(category))
                    return "Thiếu danh mục.";

                var items = await ChatCore.GetProductsByCategoryAsync(_ctx.LaravelHttp, _ctx.LaravelBase, category, _ctx.CancellationToken);
                if (items.Count == 0)
                    return "Không tìm thấy sản phẩm trong danh mục này.";

                return CleanUserFacingLiveText(FormatProductList(items, maxItems: 10));
            });

        [KernelFunction("get_product_by_code")]
        [Description("Lay thong tin chi tiet san pham theo ma, vi du AT0006")]
        public Task<string> GetProductByCodeAsync([Description("Ma san pham")] string productCode)
            => ExecuteToolAsync("get_product_by_code", productCode, async () =>
            {
                if (string.IsNullOrWhiteSpace(productCode))
                    return "Thiếu mã sản phẩm.";

                var item = await ChatCore.GetProductByCodeAsync(_ctx.LaravelHttp, _ctx.LaravelBase, productCode.Trim(), _ctx.CancellationToken);
                if (item is null)
                    return $"Không tìm thấy sản phẩm mã {productCode}.";

                var p = item.Value;
                return CleanUserFacingLiveText(FormatProduct(p));
            });

        [KernelFunction("get_product_variants_by_code")]
        [Description("Lay thong tin size, mau va ton kho theo ma san pham")]
        public Task<string> GetProductVariantsByCodeAsync([Description("Ma san pham, co the bo trong de dung ma gan nhat")] string? productCode = null)
            => ExecuteToolAsync("get_product_variants_by_code", productCode ?? string.Empty, async () =>
            {
                var code = productCode?.Trim();
                if (string.IsNullOrWhiteSpace(code))
                    code = _ctx.LastKnownProductCode;

                if (string.IsNullOrWhiteSpace(code))
                    return "Thiếu mã sản phẩm để kiểm tra size/màu/tồn kho.";

                var product = await ChatCore.GetProductByCodeAsync(_ctx.LaravelHttp, _ctx.LaravelBase, code, _ctx.CancellationToken);
                if (product is null)
                    return $"Không tìm thấy sản phẩm mã {code}.";

                var p = product.Value;
                if (!p.TryGetProperty("productID", out var pid))
                    return $"San pham ma {code} khong co productID de tra bien the.";

                var variants = await ChatCore.GetVariantsAsync(_ctx.LaravelHttp, _ctx.LaravelBase, pid.GetInt64(), _ctx.CancellationToken);
                if (variants is null)
                    return $"Không có dữ liệu biến thể cho mã {code}.";

                return FormatVariants(variants.Value, maxLines: 25);
            });

        [KernelFunction("search_knowledge")]
        [Description("Tim thong tin help center nhu doi tra, giao hang, thanh toan, tai khoan")]
        public Task<string> SearchKnowledgeAsync([Description("Noi dung can tim trong knowledge")] string query)
            => ExecuteToolAsync("search_knowledge", query, async () =>
            {
                if (string.IsNullOrWhiteSpace(query))
                    return "Thiếu câu hỏi.";

                var quick = TryGetLocalKnowledgeAnswer(query, _ctx.KnowledgeDir);
                if (!string.IsNullOrWhiteSpace(quick))
                    return quick!;

                if (_ctx.KnowledgeBase.Chunks.Count == 0)
                    return "Knowledge base hien dang trong.";

                if (_embeddingService is not null)
                {
                    try
                    {
                        var embedding = await _embeddingService.GenerateAsync(
                            query,
                            cancellationToken: _ctx.CancellationToken);
                        var qVec = embedding.Vector.ToArray();

                        if (qVec.Length > 0)
                        {
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
                }

                var topLexical = _ctx.KnowledgeBase.SearchTopKLexical(query, k: 3)
                    .Where(x => x.score >= 1f)
                    .ToList();
                if (topLexical.Count > 0)
                    return KnowledgeBase.FormatSources(topLexical, maxCharsPerChunk: 900);

                return "Không tìm thấy thông tin phù hợp trong knowledge.";
            });
    }
}
