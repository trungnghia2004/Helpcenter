using System.Net;
using System.Text;
using System.Text.Json.Nodes;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal static async Task RunSemanticKernelSmokeAsync(IServiceProvider services, CancellationToken cancellationToken)
    {
        using var scope = services.CreateScope();
        var runtime = scope.ServiceProvider.GetRequiredService<ChatAgentRuntime>();
        var accessor = scope.ServiceProvider.GetRequiredService<AgentToolExecutionContextAccessor>();
        var productCatalogService = scope.ServiceProvider.GetRequiredService<ProductCatalogService>();
        var toolBridge = scope.ServiceProvider.GetRequiredService<ISemanticKernelToolBridge>();

        ReplaceProductCatalogService(productCatalogService);

        accessor.Current = new AgentToolExecutionContext(
            LastKnownProductCode: "AT0001",
            Policy: new AgentRunPolicy
            {
                MaxToolCalls = 10,
                MaxToolOutputChars = 2000
            },
            RuntimeState: new AgentRuntimeState(),
            ConversationId: "smoke-conversation",
            UserKey: "user:smoke",
            ToolLogger: null,
            CancellationToken: cancellationToken);

        try
        {
            var tools = toolBridge.BuildTools();
            Require(HasTool(tools, "search_products"), "Missing tool metadata for search_products.");
            Require(HasTool(tools, "get_products_by_category"), "Missing tool metadata for get_products_by_category.");
            Require(HasTool(tools, "get_product_by_code"), "Missing tool metadata for get_product_by_code.");
            Require(HasTool(tools, "get_product_variants_by_code"), "Missing tool metadata for get_product_variants_by_code.");
            Require(HasTool(tools, "search_knowledge"), "Missing tool metadata for search_knowledge.");

            var knowledge = await toolBridge.InvokeAsync(
                "search_knowledge",
                """{"query":"chinh sach doi tra"}""",
                cancellationToken);
            Require(!string.IsNullOrWhiteSpace(knowledge), "search_knowledge returned empty output.");
            Require(
                !RemoveDiacritics(knowledge).Contains("khong tim thay", StringComparison.OrdinalIgnoreCase),
                "search_knowledge returned not-found output.");

            var searchProducts = await toolBridge.InvokeAsync(
                "search_products",
                """{"query":"ao hoodie"}""",
                cancellationToken);
            Require(searchProducts.Contains("AT0001", StringComparison.Ordinal), "search_products did not return expected product code.");

            var getProduct = await toolBridge.InvokeAsync(
                "get_product_by_code",
                """{"productCode":"AT0001"}""",
                cancellationToken);
            Require(
                getProduct.Contains("AT0001", StringComparison.Ordinal) &&
                getProduct.Contains("Ao Hoodie Smoke", StringComparison.Ordinal),
                "get_product_by_code did not return expected product detail output.");

            var getVariants = await toolBridge.InvokeAsync(
                "get_product_variants_by_code",
                """{"productCode":"AT0001"}""",
                cancellationToken);
            Require(getVariants.Contains("VARIANTS", StringComparison.Ordinal), "get_product_variants_by_code did not return variants output.");
            Require(getVariants.Contains("Size M", StringComparison.Ordinal) || getVariants.Contains("Size L", StringComparison.Ordinal), "get_product_variants_by_code did not return expected sizes.");

            Console.WriteLine("[SMOKE] Semantic Kernel tool bridge: PASS");
            Console.WriteLine("[SMOKE] search_knowledge sample:");
            Console.WriteLine(TrimForConsole(knowledge));
            Console.WriteLine("[SMOKE] search_products sample:");
            Console.WriteLine(TrimForConsole(searchProducts));
            Console.WriteLine("[SMOKE] get_product_by_code sample:");
            Console.WriteLine(TrimForConsole(getProduct));
            Console.WriteLine("[SMOKE] get_product_variants_by_code sample:");
            Console.WriteLine(TrimForConsole(getVariants));
        }
        finally
        {
            accessor.Current = null;
            productCatalogService.OverrideHttpClient?.Dispose();
            productCatalogService.OverrideHttpClient = null;
            productCatalogService.OverrideBaseUrl = null;
        }
    }

    static void ReplaceProductCatalogService(ProductCatalogService service)
    {
        service.OverrideHttpClient = new HttpClient(new SmokeProductApiHandler())
        {
            BaseAddress = new Uri("http://smoke.local")
        };
        service.OverrideBaseUrl = "http://smoke.local";
    }

    static bool HasTool(JsonArray tools, string toolName)
    {
        foreach (var tool in tools)
        {
            if (tool is not JsonObject obj)
                continue;

            var name = obj["name"]?.GetValue<string>();
            if (string.Equals(name, toolName, StringComparison.Ordinal))
                return true;
        }

        return false;
    }

    static void Require(bool condition, string message)
    {
        if (!condition)
            throw new InvalidOperationException(message);
    }

    static string TrimForConsole(string text)
    {
        const int maxChars = 320;
        if (string.IsNullOrWhiteSpace(text))
            return "(empty)";

        var normalized = text.Trim();
        return normalized.Length <= maxChars ? normalized : normalized[..maxChars] + "...";
    }

    sealed class SmokeProductApiHandler : HttpMessageHandler
    {
        protected override Task<HttpResponseMessage> SendAsync(HttpRequestMessage request, CancellationToken cancellationToken)
        {
            var path = request.RequestUri?.AbsolutePath ?? "/";
            var query = request.RequestUri?.Query ?? string.Empty;

            if (path.Equals("/api/products/search", StringComparison.OrdinalIgnoreCase))
                return Task.FromResult(Json(SearchProductsPayload()));

            if (path.Equals("/api/products/by-category", StringComparison.OrdinalIgnoreCase))
                return Task.FromResult(Json(SearchProductsPayload()));

            if (path.Equals("/api/products/by-code/AT0001", StringComparison.OrdinalIgnoreCase))
                return Task.FromResult(Json(ProductPayload()));

            if (path.Equals("/api/products/101/variants", StringComparison.OrdinalIgnoreCase))
                return Task.FromResult(Json(VariantsPayload()));

            return Task.FromResult(new HttpResponseMessage(HttpStatusCode.NotFound)
            {
                Content = new StringContent(
                    $"{{\"error\":\"not_found\",\"path\":\"{path}{query}\"}}",
                    Encoding.UTF8,
                    "application/json")
            });
        }

        static HttpResponseMessage Json(string payload)
        {
            return new HttpResponseMessage(HttpStatusCode.OK)
            {
                Content = new StringContent(payload, Encoding.UTF8, "application/json")
            };
        }

        static string SearchProductsPayload()
            => """
               [
                 {
                   "productID": 101,
                   "productCode": "AT0001",
                   "productName": "Ao Hoodie Smoke",
                   "productSellPrice": 299000,
                   "categoryName": "Hoodie",
                   "productDesc": "Ao hoodie dung de smoke test."
                 }
               ]
               """;

        static string ProductPayload()
            => """
               {
                 "productID": 101,
                 "productCode": "AT0001",
                 "productName": "Ao Hoodie Smoke",
                 "productSellPrice": 299000,
                 "categoryName": "Hoodie",
                 "productDesc": "Ao hoodie dung de smoke test Semantic Kernel tool bridge."
               }
               """;

        static string VariantsPayload()
            => """
               [
                 { "sizeName": "M", "colorName": "Den", "productQuantity": 5 },
                 { "sizeName": "L", "colorName": "Den", "productQuantity": 2 },
                 { "sizeName": "L", "colorName": "Xam", "productQuantity": 1 }
               ]
               """;
    }
}
