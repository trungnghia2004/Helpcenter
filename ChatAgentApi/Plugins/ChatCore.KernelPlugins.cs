using System.ComponentModel;
using Microsoft.SemanticKernel;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal sealed class StoreKernelPlugin
    {
        internal const string PluginName = "store";

        readonly AgentToolExecutionContextAccessor _contextAccessor;
        readonly IProductCatalogService _productCatalogService;
        readonly IKnowledgeSearchService _knowledgeSearchService;

        public StoreKernelPlugin(
            AgentToolExecutionContextAccessor contextAccessor,
            IProductCatalogService productCatalogService,
            IKnowledgeSearchService knowledgeSearchService)
        {
            _contextAccessor = contextAccessor;
            _productCatalogService = productCatalogService;
            _knowledgeSearchService = knowledgeSearchService;
        }

        AgentToolExecutionContext Context
            => _contextAccessor.Current ?? throw new InvalidOperationException("Agent tool execution context is not available.");

        async Task<string> ExecuteToolAsync(string toolName, string input, Func<CancellationToken, Task<string>> execute)
        {
            var ctx = Context;
            var started = DateTime.UtcNow;
            var succeeded = false;
            string output = string.Empty;
            string? error = null;
            var callNo = ctx.RuntimeState.IncrementToolCallCount();
            var maxToolCalls = Math.Max(1, ctx.Policy.MaxToolCalls);
            var maxOutputChars = Math.Max(200, ctx.Policy.MaxToolOutputChars);

            try
            {
                if (callNo > maxToolCalls)
                {
                    error = "policy_max_tool_calls";
                    output = $"Da vuot gioi han goi tool ({maxToolCalls}). Hay tong hop tu ket qua da co.";
                    return output;
                }

                output = await execute(ctx.CancellationToken);
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
                ctx.ToolLogger?.Invoke(new AgentToolCallLog
                {
                    AtUtc = DateTime.UtcNow,
                    ConversationId = ctx.ConversationId,
                    UserKey = ctx.UserKey,
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
            if (string.IsNullOrEmpty(value))
                return value;

            return value.Length <= maxChars ? value : value[..maxChars];
        }

        [KernelFunction("search_products")]
        [Description("Tim danh sach san pham theo tu khoa, vi du ao thun, quan short, ao gile")]
        public Task<string> SearchProductsAsync([Description("Tu khoa tim kiem san pham")] string query)
            => ExecuteToolAsync("search_products", query, async cancellationToken =>
            {
                if (string.IsNullOrWhiteSpace(query))
                    return "Không có từ khóa tìm kiếm.";

                var items = await _productCatalogService.SearchProductsAsync(query, cancellationToken);
                if (items.Count == 0)
                    return "Không tìm thấy sản phẩm phù hợp.";

                return CleanUserFacingLiveText(FormatProductList(items, maxItems: 8));
            });

        [KernelFunction("get_products_by_category")]
        [Description("Lay danh sach san pham theo danh muc, vi du short, jeans, thun, hoodie, gile")]
        public Task<string> GetProductsByCategoryAsync([Description("Ten danh muc")] string category)
            => ExecuteToolAsync("get_products_by_category", category, async cancellationToken =>
            {
                if (string.IsNullOrWhiteSpace(category))
                    return "Thiếu danh mục.";

                var items = await _productCatalogService.GetProductsByCategoryAsync(category, cancellationToken);
                if (items.Count == 0)
                    return "Không tìm thấy sản phẩm trong danh mục này.";

                return CleanUserFacingLiveText(FormatProductList(items, maxItems: 10));
            });

        [KernelFunction("get_product_by_code")]
        [Description("Lay thong tin chi tiet san pham theo ma, vi du AT0006")]
        public Task<string> GetProductByCodeAsync([Description("Ma san pham")] string productCode)
            => ExecuteToolAsync("get_product_by_code", productCode, async cancellationToken =>
            {
                if (string.IsNullOrWhiteSpace(productCode))
                    return "Thiếu mã sản phẩm.";

                var item = await _productCatalogService.GetProductByCodeAsync(productCode.Trim(), cancellationToken);
                if (item is null)
                    return $"Không tìm thấy sản phẩm mã {productCode}.";

                return CleanUserFacingLiveText(FormatProduct(item.Value));
            });

        [KernelFunction("get_product_variants_by_code")]
        [Description("Lay thong tin size, mau va ton kho theo ma san pham")]
        public Task<string> GetProductVariantsByCodeAsync([Description("Ma san pham, co the bo trong de dung ma gan nhat")] string? productCode = null)
            => ExecuteToolAsync("get_product_variants_by_code", productCode ?? string.Empty, async cancellationToken =>
            {
                var code = productCode?.Trim();
                if (string.IsNullOrWhiteSpace(code))
                    code = Context.LastKnownProductCode;

                if (string.IsNullOrWhiteSpace(code))
                    return "Thiếu mã sản phẩm để kiểm tra size/màu/tồn kho.";

                var product = await _productCatalogService.GetProductByCodeAsync(code, cancellationToken);
                if (product is null)
                    return $"Không tìm thấy sản phẩm mã {code}.";

                var p = product.Value;
                if (!p.TryGetProperty("productID", out var pid))
                    return $"San pham ma {code} khong co productID de tra bien the.";

                var variants = await _productCatalogService.GetVariantsAsync(pid.GetInt64(), cancellationToken);
                if (variants is null)
                    return $"Không có dữ liệu biến thể cho mã {code}.";

                return FormatVariants(variants.Value, maxLines: 25);
            });

        [KernelFunction("search_knowledge")]
        [Description("Tim thong tin help center nhu doi tra, giao hang, thanh toan, tai khoan")]
        public Task<string> SearchKnowledgeAsync([Description("Noi dung can tim trong knowledge")] string query)
            => ExecuteToolAsync("search_knowledge", query, cancellationToken =>
                _knowledgeSearchService.SearchAsync(query, cancellationToken));
    }
}
