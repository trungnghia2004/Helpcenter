using System.Text.Json;
using Microsoft.Extensions.AI;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal interface IProductCatalogService
    {
        Task<List<JsonElement>> SearchProductsAsync(string query, CancellationToken cancellationToken);

        Task<List<JsonElement>> GetProductsByCategoryAsync(string category, CancellationToken cancellationToken);

        Task<JsonElement?> GetProductByCodeAsync(string productCode, CancellationToken cancellationToken);

        Task<JsonElement?> GetVariantsAsync(long productId, CancellationToken cancellationToken);
    }

    internal sealed class ProductCatalogService : IProductCatalogService
    {
        readonly IHttpClientFactory _httpClientFactory;
        readonly ChatAgentOptions _options;

        internal HttpClient? OverrideHttpClient { get; set; }

        internal string? OverrideBaseUrl { get; set; }

        public ProductCatalogService(
            IHttpClientFactory httpClientFactory,
            Microsoft.Extensions.Options.IOptions<ChatAgentOptions> options)
        {
            _httpClientFactory = httpClientFactory;
            _options = options.Value;
        }

        public Task<List<JsonElement>> SearchProductsAsync(string query, CancellationToken cancellationToken)
            => ChatCore.SearchProductsAsync(
                ResolveHttpClient(),
                ResolveBaseUrl(),
                query,
                cancellationToken);

        public Task<List<JsonElement>> GetProductsByCategoryAsync(string category, CancellationToken cancellationToken)
            => ChatCore.GetProductsByCategoryAsync(
                ResolveHttpClient(),
                ResolveBaseUrl(),
                category,
                cancellationToken);

        public Task<JsonElement?> GetProductByCodeAsync(string productCode, CancellationToken cancellationToken)
            => ChatCore.GetProductByCodeAsync(
                ResolveHttpClient(),
                ResolveBaseUrl(),
                productCode,
                cancellationToken);

        public Task<JsonElement?> GetVariantsAsync(long productId, CancellationToken cancellationToken)
            => ChatCore.GetVariantsAsync(
                ResolveHttpClient(),
                ResolveBaseUrl(),
                productId,
                cancellationToken);

        HttpClient ResolveHttpClient()
            => OverrideHttpClient ?? _httpClientFactory.CreateClient("laravel");

        string ResolveBaseUrl()
            => string.IsNullOrWhiteSpace(OverrideBaseUrl) ? _options.LaravelBase : OverrideBaseUrl;
    }

    internal interface IKnowledgeSearchService
    {
        Task<string> SearchAsync(string query, CancellationToken cancellationToken);
    }

    internal sealed class KnowledgeSearchService : IKnowledgeSearchService
    {
        readonly ChatAgentRuntime _runtime;
        readonly IEmbeddingGenerator<string, Embedding<float>> _embeddingService;

        public KnowledgeSearchService(
            ChatAgentRuntime runtime,
            IEmbeddingGenerator<string, Embedding<float>> embeddingService)
        {
            _runtime = runtime;
            _embeddingService = embeddingService;
        }

        public async Task<string> SearchAsync(string query, CancellationToken cancellationToken)
        {
            if (string.IsNullOrWhiteSpace(query))
                return "Thiếu câu hỏi.";

            var quick = TryGetLocalKnowledgeAnswer(query, _runtime.KnowledgeDir);
            if (!string.IsNullOrWhiteSpace(quick))
                return quick!;

            if (_runtime.KnowledgeBase.Chunks.Count == 0)
                return "Knowledge base hien dang trong.";

            try
            {
                var embedding = await _embeddingService.GenerateAsync(
                    query,
                    cancellationToken: cancellationToken);
                var qVec = embedding.Vector.ToArray();

                if (qVec.Length > 0)
                {
                    var topVec = _runtime.KnowledgeBase.SearchTopK(qVec, k: 3)
                        .Where(x => x.score >= 0.35f)
                        .ToList();

                    if (topVec.Count > 0)
                        return KnowledgeBase.FormatSources(topVec, maxCharsPerChunk: 900);
                }
            }
            catch
            {
            }

            var topLexical = _runtime.KnowledgeBase.SearchTopKLexical(query, k: 3)
                .Where(x => x.score >= 1f)
                .ToList();
            if (topLexical.Count > 0)
                return KnowledgeBase.FormatSources(topLexical, maxCharsPerChunk: 900);

            return "Không tìm thấy thông tin phù hợp trong knowledge.";
        }
    }
}
