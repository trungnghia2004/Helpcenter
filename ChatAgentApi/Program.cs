using System.Collections.Concurrent;
using System.Globalization;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using System.Linq;
namespace ChatAgentApi;

public partial class Program
{
    sealed record CacheItem(string Json, DateTime ExpiresAtUtc);
    static readonly ConcurrentDictionary<string, CacheItem> LaravelCache = new();
    static readonly ConcurrentDictionary<string, string> LastProductCodeByRequester = new(StringComparer.Ordinal);
    static readonly object ConversationFileLock = new();
    static readonly object UsageFileLock = new();
    static readonly object RateLimitLock = new();
    static readonly ConcurrentDictionary<string, Queue<DateTime>> RequestWindows = new();
    static readonly Dictionary<string, long> DailyTokenUsage = new(StringComparer.Ordinal);

    public static async Task Main(string[] args)
    {
        var builder = WebApplication.CreateBuilder(args);

        builder.Services.AddCors();
        builder.Services.AddHttpClient("openai");
        builder.Services.AddHttpClient("laravel");

        builder.Services.ConfigureHttpJsonOptions(o =>
        {
            o.SerializerOptions.TypeInfoResolverChain.Insert(0, AppJsonContext.Default);
        });

        var app = builder.Build();

        app.UseCors(p => p.AllowAnyHeader().AllowAnyMethod().AllowAnyOrigin());
        app.UseDefaultFiles();
        app.UseStaticFiles();

        const string LARAVEL_BASE = "http://localhost:8000";
        var openAiApiKey = Environment.GetEnvironmentVariable("OPENAI_API_KEY") ?? "";
        var openAiModel = Environment.GetEnvironmentVariable("OPENAI_CHAT_MODEL") ?? "gpt-4.1-mini";
        var openAiEmbedModel = Environment.GetEnvironmentVariable("OPENAI_EMBED_MODEL") ?? "text-embedding-3-small";
        var rateLimitPerMinute = ParseIntEnv("CHAT_RATE_LIMIT_PER_MIN", 60, min: 5, max: 600);
        var dailyTokenQuota = ParseIntEnv("CHAT_DAILY_TOKEN_QUOTA", 120_000, min: 5_000, max: 20_000_000);

        var knowledgeDir = Path.Combine(app.Environment.ContentRootPath, "knowledge");
        var indexPath = Path.Combine(knowledgeDir, "knowledge_index.jsonl");
        var dataDir = Path.Combine(app.Environment.ContentRootPath, "data");
        var logsDir = Path.Combine(app.Environment.ContentRootPath, "logs");
        Directory.CreateDirectory(dataDir);
        Directory.CreateDirectory(logsDir);
        var conversationsPath = Path.Combine(dataDir, "conversations.json");
        var dailyUsagePath = Path.Combine(dataDir, "daily_token_usage.json");
        var usageLogPath = Path.Combine(logsDir, "token_usage.jsonl");

        if (args.Contains("--index", StringComparer.OrdinalIgnoreCase))
        {
            if (string.IsNullOrWhiteSpace(openAiApiKey))
            {
                Console.WriteLine("Missing OPENAI_API_KEY. Set env var then re-run.");
                return;
            }

            Directory.CreateDirectory(knowledgeDir);

            var openAiClient = app.Services.GetRequiredService<IHttpClientFactory>().CreateClient("openai");

            await KnowledgeIndexer.BuildIndexJsonl(
                http: openAiClient,
                apiKey: openAiApiKey,
                embeddingModel: openAiEmbedModel,
                knowledgeDir: knowledgeDir,
                outPath: indexPath,
                chunkChars: 1800,
                overlapChars: 200,
                ct: CancellationToken.None
            );

            Console.WriteLine($"Index created: {indexPath}");
            return;
        }

        var kb = KnowledgeBase.Load(indexPath);
        var conversations = LoadConversations(conversationsPath);
        LoadDailyTokenUsage(dailyUsagePath);

        app.MapPost("/api/chat", async (HttpContext ctx) =>
        {
            var req = await ParseIncomingChatRequestAsync(ctx.Request, ctx.RequestAborted);
            var requestStarted = DateTime.UtcNow;
            var requesterKey = BuildRequesterKey(req, ctx);
            var overQuota = IsDailyQuotaExceeded(requesterKey, dailyTokenQuota, out var usedToday);

            var openAiHttp = ctx.RequestServices.GetRequiredService<IHttpClientFactory>().CreateClient("openai");
            var laravelHttp = ctx.RequestServices.GetRequiredService<IHttpClientFactory>().CreateClient("laravel");

            var conversationId = string.IsNullOrWhiteSpace(req.ConversationId)
                ? Guid.NewGuid().ToString("N")
                : req.ConversationId!;

            var conv = conversations.GetOrAdd(conversationId, _ => new Conversation
            {
                Id = conversationId,
                CreatedAtUtc = DateTime.UtcNow
            });

            if (req.Messages is { Count: > 1 })
            {
                conv.Messages = req.Messages.ToList();
            }
            else if (req.Messages is { Count: 1 })
            {
                foreach (var m in req.Messages)
                {
                    var last = conv.Messages.LastOrDefault();
                    if (last is null || last.Role != m.Role || last.Content != m.Content)
                        conv.Messages.Add(m);
                }
            }

            SetSseHeaders(ctx);
            ctx.Response.Headers["x-conversation-id"] = conversationId;

            if (IsRateLimited(requesterKey, rateLimitPerMinute, out var retryAfterSeconds))
            {
                var messageIdRate = "m_" + Guid.NewGuid().ToString("N");
                var textIdRate = "t_" + Guid.NewGuid().ToString("N");
                var text = $"Bạn gửi yêu cầu quá nhanh. Vui lòng thử lại sau khoảng {retryAfterSeconds} giây.";

                await SendStart(ctx, messageIdRate);
                await SendTextStart(ctx, textIdRate);
                await SendTextDelta(ctx, textIdRate, text);
                await SendTextEnd(ctx, textIdRate);
                await SendDone(ctx);

                conv.Messages.Add(new ChatMessage("assistant", text));
                conv.UpdatedAtUtc = DateTime.UtcNow;
                PersistConversations(conversationsPath, conversations);
                AppendTokenUsageLog(
                    usageLogPath,
                    new TokenUsageLog
                    {
                        AtUtc = DateTime.UtcNow,
                        ConversationId = conversationId,
                        UserKey = requesterKey,
                        Model = "rate-limit",
                        PromptTokens = 0,
                        CompletionTokens = 0,
                        TotalTokens = 0,
                        LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds,
                        Note = "rate_limited"
                    });
                return;
            }

            if (overQuota)
            {
                var messageIdQuota = "m_" + Guid.NewGuid().ToString("N");
                var textIdQuota = "t_" + Guid.NewGuid().ToString("N");
                var text = $"Bạn đã dùng hết quota token trong ngày ({usedToday:N0}/{dailyTokenQuota:N0}). Vui lòng thử lại vào ngày mai.";

                await SendStart(ctx, messageIdQuota);
                await SendTextStart(ctx, textIdQuota);
                await SendTextDelta(ctx, textIdQuota, text);
                await SendTextEnd(ctx, textIdQuota);
                await SendDone(ctx);

                conv.Messages.Add(new ChatMessage("assistant", text));
                conv.UpdatedAtUtc = DateTime.UtcNow;
                PersistConversations(conversationsPath, conversations);
                AppendTokenUsageLog(
                    usageLogPath,
                    new TokenUsageLog
                    {
                        AtUtc = DateTime.UtcNow,
                        ConversationId = conversationId,
                        UserKey = requesterKey,
                        Model = "quota-block",
                        PromptTokens = 0,
                        CompletionTokens = 0,
                        TotalTokens = 0,
                        LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds,
                        Note = "daily_quota_exceeded"
                    });
                return;
            }

            var lastUser = conv.Messages.LastOrDefault(m => m.Role == "user")?.Content?.Trim() ?? "";

            if (IsGreetingIntent(lastUser))
            {
                var messageIdGreet = "m_" + Guid.NewGuid().ToString("N");
                var textIdGreet = "t_" + Guid.NewGuid().ToString("N");
                var greeting = "Chào bạn! Mình là trợ lý Help Center của cửa hàng. Bạn cần mình hỗ trợ thông tin gì?";

                await SendStart(ctx, messageIdGreet);
                await SendTextStart(ctx, textIdGreet);
                await SendTextDelta(ctx, textIdGreet, greeting);
                await SendTextEnd(ctx, textIdGreet);
                await SendDone(ctx);

                conv.Messages.Add(new ChatMessage("assistant", greeting));
                conv.UpdatedAtUtc = DateTime.UtcNow;
                PersistConversations(conversationsPath, conversations);
                AppendTokenUsageLog(
                    usageLogPath,
                    new TokenUsageLog
                    {
                        AtUtc = DateTime.UtcNow,
                        ConversationId = conversationId,
                        UserKey = requesterKey,
                        Model = "deterministic:greeting",
                        PromptTokens = 0,
                        CompletionTokens = EstimateTokenCount(greeting),
                        TotalTokens = EstimateTokenCount(greeting),
                        LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds
                    });
                return;
            }

            var localKnowledgeFast = TryGetLocalKnowledgeAnswer(lastUser, knowledgeDir);
            if (!string.IsNullOrWhiteSpace(localKnowledgeFast))
            {
                var plainLastUser = RemoveDiacritics(lastUser.ToLowerInvariant());
                var asksSizeGuide =
                    plainLastUser.Contains("bang size", StringComparison.Ordinal) ||
                    plainLastUser.Contains("size guide", StringComparison.Ordinal) ||
                    plainLastUser.Contains("kich co", StringComparison.Ordinal);
                var asksVariantSpecific = IsVariantIntent(lastUser) && !asksSizeGuide;
                var hasProductCode = ExtractProductCode(lastUser) is not null;

                if (!hasProductCode && !asksVariantSpecific)
                {
                    var messageIdLocal = "m_" + Guid.NewGuid().ToString("N");
                    var textIdLocal = "t_" + Guid.NewGuid().ToString("N");

                    await SendStart(ctx, messageIdLocal);
                    await SendTextStart(ctx, textIdLocal);
                    await SendTextDelta(ctx, textIdLocal, localKnowledgeFast!);
                    await SendTextEnd(ctx, textIdLocal);
                    await SendDone(ctx);

                    conv.Messages.Add(new ChatMessage("assistant", localKnowledgeFast!));
                    conv.UpdatedAtUtc = DateTime.UtcNow;
                    PersistConversations(conversationsPath, conversations);
                    AppendTokenUsageLog(
                        usageLogPath,
                        new TokenUsageLog
                        {
                            AtUtc = DateTime.UtcNow,
                            ConversationId = conversationId,
                            UserKey = requesterKey,
                            Model = "deterministic:knowledge-local",
                            PromptTokens = 0,
                            CompletionTokens = EstimateTokenCount(localKnowledgeFast!),
                            TotalTokens = EstimateTokenCount(localKnowledgeFast!),
                            LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds
                        });
                    return;
                }
            }

            await UpdateConversationSummaryAsync(conv, openAiHttp, openAiApiKey, openAiModel, ctx.RequestAborted);
            await UpdateConversationMemoryAsync(conv, openAiHttp, openAiApiKey, ctx.RequestAborted);

            var sourcesBlocks = new List<string>();

            if (!string.IsNullOrWhiteSpace(lastUser) && IsProductIntent(lastUser))
            {
                try
                {
                    var needVariants = IsVariantIntent(lastUser);
                    var categoryKeyword = ExtractCategoryKeyword(lastUser);
                    var browseIntent =
                        IsBrowseIntent(lastUser) ||
                        IsCategoryOnlyIntent(lastUser) ||
                        !string.IsNullOrWhiteSpace(categoryKeyword);
                    var code = ExtractProductCode(lastUser)
                        ?? (needVariants ? ExtractRecentProductCode(conv) : null);
                    if (string.IsNullOrWhiteSpace(code) && needVariants &&
                        LastProductCodeByRequester.TryGetValue(requesterKey, out var remembered))
                    {
                        code = remembered;
                    }

                    if (!string.IsNullOrWhiteSpace(code))
                        LastProductCodeByRequester[requesterKey] = code;

                    JsonElement? product = null;
                    List<JsonElement>? products = null;

                    if (!string.IsNullOrWhiteSpace(code))
                    {
                        product = await GetProductByCodeAsync(laravelHttp, LARAVEL_BASE, code!, ctx.RequestAborted);
                    }
                    else
                    {
                        if (!string.IsNullOrWhiteSpace(categoryKeyword))
                            products = await GetProductsByCategoryAsync(laravelHttp, LARAVEL_BASE, categoryKeyword!, ctx.RequestAborted);
                        else
                            products = await SearchProductsAsync(laravelHttp, LARAVEL_BASE, lastUser, ctx.RequestAborted);

                        if (products is null || products.Count == 0)
                            products = await SearchProductsAsync(laravelHttp, LARAVEL_BASE, lastUser, ctx.RequestAborted);

                        if (products.Count > 0)
                            product = products[0];
                    }

                    if (browseIntent && products is { Count: > 0 } && string.IsNullOrWhiteSpace(code))
                    {
                        sourcesBlocks.Add(FormatProductList(products, maxItems: 5));
                    }
                    else if (product is not null)
                    {
                        var p = product.Value;
                        var live = new StringBuilder();

                        if (p.TryGetProperty("productCode", out var pCodeEl))
                        {
                            var pCode = pCodeEl.GetString();
                            if (!string.IsNullOrWhiteSpace(pCode))
                                LastProductCodeByRequester[requesterKey] = pCode!;
                        }

                        live.AppendLine(FormatProduct(p));

                        if (needVariants && p.TryGetProperty("productID", out var pid))
                        {
                            var variants = await GetVariantsAsync(laravelHttp, LARAVEL_BASE, pid.GetInt64(), ctx.RequestAborted);
                            if (variants is not null)
                                live.AppendLine().AppendLine(FormatVariants(variants.Value, maxLines: 20));
                        }

                        sourcesBlocks.Add(live.ToString().Trim());
                    }
                }
                catch
                {
                }
            }

            if (kb.Chunks.Count > 0 && !string.IsNullOrWhiteSpace(lastUser))
            {
                try
                {
                    List<(KnowledgeChunk chunk, float score)> top;
                    if (!string.IsNullOrWhiteSpace(openAiApiKey))
                    {
                        var qVec = await KnowledgeIndexer.EmbedAsync(openAiHttp, openAiApiKey, openAiEmbedModel, lastUser, ctx.RequestAborted);
                        top = kb.SearchTopK(qVec, k: 3)
                                .Where(x => x.score >= 0.35f)
                                .ToList();
                    }
                    else
                    {
                        top = kb.SearchTopKLexical(lastUser, k: 3)
                                .Where(x => x.score >= 1f)
                                .ToList();
                    }

                    if (top.Count > 0)
                        sourcesBlocks.Add(KnowledgeBase.FormatSources(top, maxCharsPerChunk: 900));
                }
                catch
                {
                    var top = kb.SearchTopKLexical(lastUser, k: 3)
                                .Where(x => x.score >= 1f)
                                .ToList();
                    if (top.Count > 0)
                        sourcesBlocks.Add(KnowledgeBase.FormatSources(top, maxCharsPerChunk: 900));
                }
            }

            if (sourcesBlocks.Count == 0)
            {
                var messageId0 = "m_" + Guid.NewGuid().ToString("N");
                var textId0 = "t_" + Guid.NewGuid().ToString("N");

                await SendStart(ctx, messageId0);
                await SendTextStart(ctx, textId0);

                var codeNotFound = ExtractProductCode(lastUser);
                var localKnowledge = TryGetLocalKnowledgeAnswer(lastUser, knowledgeDir);
                var fallback =
                    !string.IsNullOrWhiteSpace(localKnowledge) ? localKnowledge :
                    (!string.IsNullOrWhiteSpace(codeNotFound)
                        ? $"Mình chưa tìm thấy sản phẩm có mã {codeNotFound} trong dữ liệu cửa hàng. Bạn kiểm tra lại mã giúp mình nhé."
                        : IsVariantIntent(lastUser)
                            ? "Mình cần mã sản phẩm để kiểm tra size/màu/tồn kho. Bạn gửi giúp mình mã sản phẩm (VD: AT0006)."
                            : "Mình không tìm thấy thông tin này trong tài liệu/nguồn dữ liệu cửa hàng.\n" +
                              "Bạn có thể hỏi về: đổi trả, giao hàng, thanh toán, bảng size, theo dõi đơn, tài khoản, hoặc gửi mã sản phẩm (VD: AT0006).");

                await SendTextDelta(ctx, textId0, fallback);
                await SendTextEnd(ctx, textId0);
                await SendDone(ctx);

                conv.UpdatedAtUtc = DateTime.UtcNow;
                PersistConversations(conversationsPath, conversations);
                AppendTokenUsageLog(
                    usageLogPath,
                    new TokenUsageLog
                    {
                        AtUtc = DateTime.UtcNow,
                        ConversationId = conversationId,
                        UserKey = requesterKey,
                        Model = "deterministic:fallback",
                        PromptTokens = 0,
                        CompletionTokens = EstimateTokenCount(fallback),
                        TotalTokens = EstimateTokenCount(fallback),
                        LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds
                    });
                return;
            }

            if (!string.IsNullOrWhiteSpace(lastUser) &&
                Regex.IsMatch(lastUser.Trim(), @"^[A-Za-z]{2}\d{3,5}$"))
            {
                var liveOnly = sourcesBlocks.FirstOrDefault(s => s.StartsWith("LIVE PRODUCT DATA:", StringComparison.Ordinal));
                if (!string.IsNullOrWhiteSpace(liveOnly))
                {
                    var messageIdCode = "m_" + Guid.NewGuid().ToString("N");
                    var textIdCode = "t_" + Guid.NewGuid().ToString("N");
                    var direct = CleanUserFacingLiveText(liveOnly) + "\nBạn muốn mình kiểm tra thêm size/màu/tồn kho không?";

                    await SendStart(ctx, messageIdCode);
                    await SendTextStart(ctx, textIdCode);
                    await SendTextDelta(ctx, textIdCode, direct);
                    await SendTextEnd(ctx, textIdCode);
                    await SendDone(ctx);

                    conv.Messages.Add(new ChatMessage("assistant", direct));
                    conv.UpdatedAtUtc = DateTime.UtcNow;
                    PersistConversations(conversationsPath, conversations);
                    AppendTokenUsageLog(
                        usageLogPath,
                        new TokenUsageLog
                        {
                            AtUtc = DateTime.UtcNow,
                            ConversationId = conversationId,
                            UserKey = requesterKey,
                            Model = "deterministic:code",
                            PromptTokens = 0,
                            CompletionTokens = EstimateTokenCount(direct),
                            TotalTokens = EstimateTokenCount(direct),
                            LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds
                        });
                    return;
                }
            }

            if (IsVariantIntent(lastUser))
            {
                var liveData = sourcesBlocks.FirstOrDefault(s => s.StartsWith("LIVE PRODUCT DATA:", StringComparison.Ordinal));
                if (!string.IsNullOrWhiteSpace(liveData))
                {
                    var messageIdVariant = "m_" + Guid.NewGuid().ToString("N");
                    var textIdVariant = "t_" + Guid.NewGuid().ToString("N");
                    var direct = CleanUserFacingLiveText(liveData);

                    await SendStart(ctx, messageIdVariant);
                    await SendTextStart(ctx, textIdVariant);
                    await SendTextDelta(ctx, textIdVariant, direct);
                    await SendTextEnd(ctx, textIdVariant);
                    await SendDone(ctx);

                    conv.Messages.Add(new ChatMessage("assistant", direct));
                    conv.UpdatedAtUtc = DateTime.UtcNow;
                    PersistConversations(conversationsPath, conversations);
                    AppendTokenUsageLog(
                        usageLogPath,
                        new TokenUsageLog
                        {
                            AtUtc = DateTime.UtcNow,
                            ConversationId = conversationId,
                            UserKey = requesterKey,
                            Model = "deterministic:variants",
                            PromptTokens = 0,
                            CompletionTokens = EstimateTokenCount(direct),
                            TotalTokens = EstimateTokenCount(direct),
                            LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds
                        });
                    return;
                }
            }

            if (IsBrowseIntent(lastUser) || IsCategoryOnlyIntent(lastUser) || !string.IsNullOrWhiteSpace(ExtractCategoryKeyword(lastUser)))
            {
                var liveList = sourcesBlocks.FirstOrDefault(s => s.StartsWith("LIVE PRODUCT LIST:", StringComparison.Ordinal));
                if (!string.IsNullOrWhiteSpace(liveList))
                {
                    var messageIdList = "m_" + Guid.NewGuid().ToString("N");
                    var textIdList = "t_" + Guid.NewGuid().ToString("N");
                    var direct = CleanUserFacingLiveText(liveList) + "\nBạn muốn xem chi tiết sản phẩm nào? Hãy gửi mã sản phẩm (VD: AT0006).";

                    await SendStart(ctx, messageIdList);
                    await SendTextStart(ctx, textIdList);
                    await SendTextDelta(ctx, textIdList, direct);
                    await SendTextEnd(ctx, textIdList);
                    await SendDone(ctx);

                    conv.Messages.Add(new ChatMessage("assistant", direct));
                    conv.UpdatedAtUtc = DateTime.UtcNow;
                    PersistConversations(conversationsPath, conversations);
                    AppendTokenUsageLog(
                        usageLogPath,
                        new TokenUsageLog
                        {
                            AtUtc = DateTime.UtcNow,
                            ConversationId = conversationId,
                            UserKey = requesterKey,
                            Model = "deterministic:browse",
                            PromptTokens = 0,
                            CompletionTokens = EstimateTokenCount(direct),
                            TotalTokens = EstimateTokenCount(direct),
                            LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds
                        });
                    return;
                }
            }

            var baseRules =
                "Bạn là trợ lý Help Center cho website bán quần áo.\n" +
                "QUY TẮC BẮT BUỘC:\n" +
                "- Chỉ trả lời dựa trên SOURCES.\n" +
                "- Nếu SOURCES không đủ, nói rõ: \"Mình không tìm thấy thông tin này trong tài liệu/nguồn dữ liệu cửa hàng.\".\n" +
                "- Không bịa.\n" +
                "- Trả lời ngắn gọn, gạch đầu dòng.\n" +
                "- Nếu thiếu sản phẩm cụ thể khi hỏi size/màu/tồn kho, hỏi lại 1 câu.\n";

            var (systemText, prompt) = BuildPromptWithTokenBudget(
                conv: conv,
                baseRules: baseRules,
                summary: conv.Summary,
                memory: conv.Memory,
                sourcesBlocks: sourcesBlocks,
                maxPromptTokens: 7000,
                reserveForAnswerTokens: 800,
                maxHistoryMessages: 10
            );

            var messageId = "m_" + Guid.NewGuid().ToString("N");
            var textId = "t_" + Guid.NewGuid().ToString("N");
            var sb = new StringBuilder();
            OpenAiUsage? usage = null;
            var promptTokensEstimated = EstimateTokenCount(prompt);
            var chargeTokens = true;
            var usageNote = "";

            await SendStart(ctx, messageId);
            await SendTextStart(ctx, textId);

            try
            {
                await foreach (var chunk in OpenAIStream(
                    http: openAiHttp,
                    model: openAiModel,
                    apiKey: openAiApiKey,
                    messages: prompt,
                    onUsage: u => usage = u,
                    ct: ctx.RequestAborted))
                {
                    sb.Append(chunk);
                    await SendTextDelta(ctx, textId, chunk);
                }
            }
            catch (Exception ex)
            {
                if ((IsRateLimitError(ex) || IsUnauthorizedError(ex)) && sb.Length == 0)
                {
                    var fallback = BuildRateLimitFallbackFromSources(sourcesBlocks);
                    sb.Append(fallback);
                    await SendTextDelta(ctx, textId, fallback);
                    chargeTokens = false;
                    usageNote = IsUnauthorizedError(ex) ? "openai_unauthorized_fallback" : "openai_rate_limited_fallback";
                }
                else
                {
                    await SendError(ctx, ex.Message);
                    usageNote = "stream_error";
                }
            }

            await SendTextEnd(ctx, textId);

            conv.Messages.Add(new ChatMessage("assistant", sb.ToString()));
            conv.UpdatedAtUtc = DateTime.UtcNow;
            PersistConversations(conversationsPath, conversations);

            var completionTokens = usage?.CompletionTokens ?? EstimateTokenCount(sb.ToString());
            var promptTokens = usage?.PromptTokens ?? promptTokensEstimated;
            var totalTokens = usage?.TotalTokens ?? (promptTokens + completionTokens);
            if (!chargeTokens)
            {
                promptTokens = 0;
                completionTokens = 0;
                totalTokens = 0;
            }

            if (totalTokens > 0)
                AddDailyTokenUsage(dailyUsagePath, requesterKey, totalTokens);

            AppendTokenUsageLog(
                usageLogPath,
                new TokenUsageLog
                {
                    AtUtc = DateTime.UtcNow,
                    ConversationId = conversationId,
                    UserKey = requesterKey,
                    Model = openAiModel,
                    PromptTokens = promptTokens,
                    CompletionTokens = completionTokens,
                    TotalTokens = totalTokens,
                    LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds
                    ,
                    Note = string.IsNullOrWhiteSpace(usageNote) ? null : usageNote
                });

            await SendDone(ctx);
        });

        app.MapGet("/api/conversations/{id}", (string id) =>
        {
            return conversations.TryGetValue(id, out var conv)
                ? Results.Ok(conv)
                : Results.NotFound();
        });

        app.MapGet("/api/admin/usage/today", () =>
        {
            var today = $"{DateOnly.FromDateTime(DateTime.UtcNow):yyyy-MM-dd}|";
            Dictionary<string, long> snapshot;
            lock (UsageFileLock)
            {
                snapshot = DailyTokenUsage
                    .Where(kvp => kvp.Key.StartsWith(today, StringComparison.Ordinal))
                    .OrderByDescending(kvp => kvp.Value)
                    .Take(200)
                    .ToDictionary(k => kvpUserKey(k.Key), v => v.Value);
            }

            return Results.Ok(new
            {
                dateUtc = DateOnly.FromDateTime(DateTime.UtcNow).ToString("yyyy-MM-dd"),
                users = snapshot
            });

            static string kvpUserKey(string key)
            {
                var idx = key.IndexOf('|');
                return idx >= 0 ? key[(idx + 1)..] : key;
            }
        });

        await app.RunAsync();
    }
}

