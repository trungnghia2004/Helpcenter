using System.Collections.Concurrent;
using System.Globalization;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using System.Linq;
using Microsoft.AspNetCore.Http.Timeouts;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal static void MapApiEndpoints(WebApplication app)
    {
        app.MapPost("/api/chat", (HttpContext ctx, IChatRequestHandler handler) =>
                handler.HandleAsync(ctx))
            .RequireRateLimiting(MiddlewarePolicyNames.ChatPolicyName)
            .WithRequestTimeout(MiddlewarePolicyNames.ChatTimeoutPolicyName);

        app.MapGet("/api/conversations/{id}", (string id, ChatAgentRuntime runtime) =>
        {
            return runtime.Conversations.TryGetValue(id, out var conv)
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
    }

    static async Task HandleChatAsync(
        HttpContext ctx,
        ChatAgentRuntime runtime,
        IHttpClientFactory httpClientFactory,
        IAgentOrchestrator agentOrchestrator,
        IChatAuthenticationService authService)
    {
        var conversations = runtime.Conversations;
        var kb = runtime.KnowledgeBase;
        var conversationsPath = runtime.ConversationsPath;
        var dailyUsagePath = runtime.DailyUsagePath;
        var userMemoriesPath = runtime.UserMemoriesPath;
        var usageLogPath = runtime.UsageLogPath;
        var agentToolLogPath = runtime.AgentToolLogPath;
        var agentStepLogPath = runtime.AgentStepLogPath;
        var knowledgeDir = runtime.KnowledgeDir;
        var laravelBase = runtime.Options.LaravelBase;
        var openAiApiKey = runtime.Options.OpenAiApiKey;
        var openAiModel = runtime.Options.OpenAiModel;
        var openAiEmbedModel = runtime.Options.OpenAiEmbedModel;
        var agentPolicy = runtime.AgentPolicy;
        var dailyTokenQuota = runtime.Options.DailyTokenQuota;
        var forceGptForAll = runtime.Options.ForceGptForAll;

        var req = await ParseIncomingChatRequestAsync(ctx.Request, ctx.RequestAborted);
        var requestStarted = DateTime.UtcNow;

        var openAiHttp = httpClientFactory.CreateClient("openai");
        var laravelHttp = httpClientFactory.CreateClient("laravel");

        var requesterKey = await authService.GetAuthenticatedUserKeyAsync(ctx, ctx.RequestAborted);
        if (string.IsNullOrWhiteSpace(requesterKey))
        {
            ctx.Response.StatusCode = StatusCodes.Status401Unauthorized;
            await ctx.Response.WriteAsJsonAsync(new
            {
                error = "unauthorized",
                message = "Vui lòng đăng nhập để sử dụng hỗ trợ."
            }, ctx.RequestAborted);
            return;
        }

        var overQuota = IsDailyQuotaExceeded(requesterKey, dailyTokenQuota, out var usedToday);

        var conversationId = string.IsNullOrWhiteSpace(req.ConversationId)
            ? Guid.NewGuid().ToString("N")
            : req.ConversationId!;
        var traceId = "tr_" + Guid.NewGuid().ToString("N");

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
        ctx.Response.Headers["x-agent-trace-id"] = traceId;

        if (overQuota)
        {
            var messageIdQuota = "m_" + Guid.NewGuid().ToString("N");
            var textIdQuota = "t_" + Guid.NewGuid().ToString("N");
            var text = $"Bạn đã dùng hết quota token trong ngày ({usedToday:N0}/{dailyTokenQuota:N0}). Vui lòng thử lại vào ngày mai.";

            await SendStart(ctx, messageIdQuota);
            await SendTextStart(ctx, textIdQuota);
            await SendTextDeltaChunked(ctx, textIdQuota, text, chunkChars: 40, minDelayMs: 14);
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
        var ruleProductIntent = !string.IsNullOrWhiteSpace(lastUser) && IsProductIntent(lastUser);
        var ruleKnowledgeIntent = IsKnowledgeIntent(lastUser);
        IntentRoutingDecision? routedIntent = null;
        if (!forceGptForAll &&
            !ruleProductIntent &&
            !ruleKnowledgeIntent &&
            !string.IsNullOrWhiteSpace(lastUser))
        {
            try
            {
                routedIntent = await TryClassifyIntentAsync(
                    openAiHttp,
                    openAiApiKey,
                    openAiModel,
                    lastUser,
                    ctx.RequestAborted);
            }
            catch
            {
            }
        }

        var classifierProductIntent =
            string.Equals(routedIntent?.Intent, "product_search", StringComparison.Ordinal);
        var effectiveProductQuery =
            classifierProductIntent && !string.IsNullOrWhiteSpace(routedIntent?.SearchQuery)
                ? routedIntent!.SearchQuery!
                : lastUser;
        var recentConversationProductCode = ExtractRecentProductCode(conv);
        var shortAffirmative = IsShortAffirmative(lastUser);
        var recentRequesterProductCode = TryGetRequesterRecentProductCode(
            requesterKey,
            maxAge: TimeSpan.FromMinutes(30));
        var effectiveRecentProductCode = !string.IsNullOrWhiteSpace(recentConversationProductCode)
            ? recentConversationProductCode
            : recentRequesterProductCode;
        var variantFollowupConfirmation =
            IsVariantFollowupConfirmation(lastUser, conv) ||
            (shortAffirmative && !string.IsNullOrWhiteSpace(effectiveRecentProductCode));
        var localKnowledgeFast = classifierProductIntent
            ? null
            : TryGetLocalKnowledgeAnswer(lastUser, knowledgeDir);
        var hasExplicitProductCode = ExtractProductCode(lastUser) is not null;

        if (!forceGptForAll && shortAffirmative && string.IsNullOrWhiteSpace(effectiveRecentProductCode))
        {
            var messageIdClarify = "m_" + Guid.NewGuid().ToString("N");
            var textIdClarify = "t_" + Guid.NewGuid().ToString("N");
            var clarify = "Bạn muốn mình kiểm tra sản phẩm nào? Vui lòng gửi mã (VD: AT0009) hoặc tên sản phẩm cụ thể.";

            await SendStart(ctx, messageIdClarify);
            await SendTextStart(ctx, textIdClarify);
            await SendTextDeltaChunked(ctx, textIdClarify, clarify, chunkChars: 40, minDelayMs: 14);
            await SendTextEnd(ctx, textIdClarify);
            await SendDone(ctx);

            conv.Messages.Add(new ChatMessage("assistant", clarify));
            conv.UpdatedAtUtc = DateTime.UtcNow;
            PersistConversations(conversationsPath, conversations);
            AppendTokenUsageLog(
                usageLogPath,
                new TokenUsageLog
                {
                    AtUtc = DateTime.UtcNow,
                    ConversationId = conversationId,
                    UserKey = requesterKey,
                    Model = "clarify",
                    PromptTokens = 0,
                    CompletionTokens = 0,
                    TotalTokens = 0,
                    LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds,
                    Note = "short_affirmative_without_context"
                });
            return;
        }

        if (!forceGptForAll && !string.IsNullOrWhiteSpace(localKnowledgeFast) && !hasExplicitProductCode)
        {
            var messageIdLocal = "m_" + Guid.NewGuid().ToString("N");
            var textIdLocal = "t_" + Guid.NewGuid().ToString("N");
            var answer = localKnowledgeFast!.Trim();

            await SendStart(ctx, messageIdLocal);
            await SendTextStart(ctx, textIdLocal);
            await SendTextDeltaChunked(ctx, textIdLocal, answer, chunkChars: 40, minDelayMs: 14);
            await SendTextEnd(ctx, textIdLocal);
            await SendDone(ctx);

            conv.Messages.Add(new ChatMessage("assistant", answer));
            conv.UpdatedAtUtc = DateTime.UtcNow;
            MergeUserMemoryFromConversation(userMemoriesPath, requesterKey, conv);
            PersistConversations(conversationsPath, conversations);
            AppendTokenUsageLog(
                usageLogPath,
                new TokenUsageLog
                {
                    AtUtc = DateTime.UtcNow,
                    ConversationId = conversationId,
                    UserKey = requesterKey,
                    Model = "local-knowledge",
                    PromptTokens = 0,
                    CompletionTokens = 0,
                    TotalTokens = 0,
                    LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds,
                    Note = "local_knowledge_short_circuit"
                });
            return;
        }

        try
        {
            await UpdateConversationSummaryAsync(conv, ctx.RequestAborted);
        }
        catch
        {
        }

        try
        {
            await UpdateConversationMemoryAsync(conv, openAiHttp, openAiApiKey, ctx.RequestAborted);
        }
        catch
        {
        }

        var sourcesBlocks = new List<string>();
        var isProductQuery =
            ruleProductIntent ||
            classifierProductIntent ||
            variantFollowupConfirmation;
        string? fastProductAnswer = null;
        var browseIntent = false;
        var productLookupFailed = false;

        if (isProductQuery)
        {
            try
            {
                var needVariants = IsVariantIntent(lastUser) || variantFollowupConfirmation;
                var categoryOverviewIntent = IsCategoryOverviewIntent(lastUser);
                var categoryKeyword = ExtractCategoryKeyword(effectiveProductQuery);
                browseIntent =
                    classifierProductIntent ||
                    IsBrowseIntent(lastUser) ||
                    IsCategoryOnlyIntent(effectiveProductQuery) ||
                    categoryOverviewIntent ||
                    !string.IsNullOrWhiteSpace(categoryKeyword);
                var code = ExtractProductCode(lastUser)
                    ?? (needVariants
                        ? (ExtractRecentProductCode(conv) ?? recentRequesterProductCode)
                        : null);

                if (categoryOverviewIntent && string.IsNullOrWhiteSpace(code))
                {
                    var overview = await BuildCategoryOverviewAnswerAsync(laravelHttp, laravelBase, ctx.RequestAborted);
                    if (!string.IsNullOrWhiteSpace(overview))
                    {
                        fastProductAnswer = overview;
                        sourcesBlocks.Add(overview);
                    }
                }

                JsonElement? product = null;
                List<JsonElement>? products = null;

                if (!string.IsNullOrWhiteSpace(code))
                {
                    product = await GetProductByCodeAsync(laravelHttp, laravelBase, code!, ctx.RequestAborted);
                }
                else if (string.IsNullOrWhiteSpace(fastProductAnswer))
                {
                    var isPureCategoryQuery = IsCategoryOnlyIntent(effectiveProductQuery);
                    if (!string.IsNullOrWhiteSpace(categoryKeyword) && isPureCategoryQuery)
                    {
                        // Query thuần danh mục (vd: "áo thun", "quần short") thì lấy list theo category.
                        products = await GetProductsByCategoryAsync(laravelHttp, laravelBase, categoryKeyword!, ctx.RequestAborted);
                    }
                    else
                    {
                        // Query có mô tả cụ thể (vd: "áo thun kaki", "áo thun wash")
                        // phải dùng search + ranking để ưu tiên đúng sản phẩm.
                        products = await SearchProductsAsync(laravelHttp, laravelBase, effectiveProductQuery, ctx.RequestAborted);
                    }

                    if (products is null || products.Count == 0)
                        products = await SearchProductsAsync(laravelHttp, laravelBase, lastUser, ctx.RequestAborted);

                    if (products.Count > 1)
                    {
                        var descriptors = ExtractDescriptorKeywords(RemoveDiacritics(lastUser.ToLowerInvariant()));
                        if (descriptors.Count > 0)
                        {
                            var narrowed = products
                                .Where(p =>
                                {
                                    var name = p.TryGetProperty("productName", out var n) ? (n.GetString() ?? string.Empty) : string.Empty;
                                    var category = p.TryGetProperty("categoryName", out var cat) ? (cat.GetString() ?? string.Empty) : string.Empty;
                                    var combined = RemoveDiacritics($"{name} {category}".ToLowerInvariant());
                                    return descriptors.All(d => combined.Contains(d, StringComparison.Ordinal));
                                })
                                .ToList();
                            if (narrowed.Count > 0)
                                products = narrowed;
                        }
                    }

                    var needsSingleProductDetail =
                        products.Count > 0 &&
                        (!browseIntent || !string.IsNullOrWhiteSpace(code) || products.Count == 1 || needVariants);

                    if (needsSingleProductDetail)
                    {
                        // Search result may not include full fields (e.g., productDesc),
                        // so hydrate details by code when possible.
                        var first = products[0];
                        var firstCode = first.TryGetProperty("productCode", out var codeEl)
                            ? codeEl.GetString()
                            : null;
                        if (!string.IsNullOrWhiteSpace(firstCode))
                        {
                            product = await GetProductByCodeAsync(
                                laravelHttp,
                                laravelBase,
                                firstCode!,
                                ctx.RequestAborted) ?? first;
                        }
                        else
                        {
                            product = first;
                        }
                    }
                }

                if (browseIntent && products is { Count: > 1 } && string.IsNullOrWhiteSpace(code))
                {
                    sourcesBlocks.Add(FormatProductList(products, maxItems: 5));
                    fastProductAnswer = BuildFastProductAnswer(
                        mainContent: FormatProductList(products, maxItems: 5),
                        followup: "Bạn muốn xem chi tiết sản phẩm nào hoặc lọc theo màu/size nào không?");
                }
                else if (product is not null)
                {
                    var p = product.Value;
                    var live = new StringBuilder();
                    if (p.TryGetProperty("productCode", out var pCodeEl))
                    {
                        var pCode = pCodeEl.GetString();
                        if (!string.IsNullOrWhiteSpace(pCode))
                        {
                            conv.MemoryFacts["product_code"] = pCode!;
                            UpdateRequesterRecentProductCode(requesterKey, pCode!);
                        }
                    }

                    live.AppendLine(FormatProduct(p));

                    if (needVariants && p.TryGetProperty("productID", out var pid))
                    {
                        var variants = await GetVariantsAsync(laravelHttp, laravelBase, pid.GetInt64(), ctx.RequestAborted);
                        if (variants is not null)
                            live.AppendLine().AppendLine(FormatVariants(variants.Value, maxLines: 20));
                    }

                    sourcesBlocks.Add(live.ToString().Trim());
                    fastProductAnswer = BuildFastProductAnswer(
                        mainContent: live.ToString().Trim(),
                        followup: needVariants
                            ? "Bạn cần mình kiểm tra thêm màu/size khác không?"
                            : "Bạn muốn mình kiểm tra thêm size/màu/tồn kho không?");
                }
            }
            catch
            {
                productLookupFailed = true;
                if (string.IsNullOrWhiteSpace(fastProductAnswer))
                {
                    fastProductAnswer = "Tạm thời không lấy được dữ liệu sản phẩm. Bạn vui lòng thử lại sau ít phút.";
                }
            }
        }

        if (isProductQuery && string.IsNullOrWhiteSpace(fastProductAnswer))
        {
            fastProductAnswer = browseIntent && !productLookupFailed
                ? "Mình chưa tìm thấy sản phẩm phù hợp trong hệ thống cho nhu cầu này. Bạn có thể thử từ khóa gần hơn như áo thun, hoodie, quần short..."
                : "Mình chưa xác định được sản phẩm cụ thể. Bạn gửi thêm mã sản phẩm (VD: AT0009) hoặc tên chi tiết hơn nhé.";
        }

        if (isProductQuery && !string.IsNullOrWhiteSpace(fastProductAnswer))
        {
            var normalizedFastProductAnswer = fastProductAnswer.Trim();
            if (!sourcesBlocks.Any(x => string.Equals(x, normalizedFastProductAnswer, StringComparison.Ordinal)))
                sourcesBlocks.Add(normalizedFastProductAnswer);
        }
        if (shortAffirmative && !string.IsNullOrWhiteSpace(effectiveRecentProductCode))
        {
            sourcesBlocks.Add(
                $"FOLLOWUP_VARIANTS_REQUIRED: user_confirmed=co; product_code={effectiveRecentProductCode}; must_return=size_color_stock; do_not_ask_product_again");
        }

        var shouldSearchKnowledgeBase =
            kb.Chunks.Count > 0 &&
            !string.IsNullOrWhiteSpace(lastUser) &&
            (!isProductQuery || sourcesBlocks.Count == 0 || IsKnowledgeIntent(lastUser));

        if (shouldSearchKnowledgeBase)
        {
            try
            {
                List<(KnowledgeChunk chunk, float score)> top;
                if (!string.IsNullOrWhiteSpace(openAiApiKey))
                {
                    var qVec = await KnowledgeIndexer.EmbedAsync(openAiHttp, openAiApiKey, openAiEmbedModel, lastUser, ctx.RequestAborted);
                    top = kb.SearchTopK(qVec, k: 2)
                            .Where(x => x.score >= 0.35f)
                            .ToList();
                }
                else
                {
                    top = kb.SearchTopKLexical(lastUser, k: 2)
                            .Where(x => x.score >= 1f)
                            .ToList();
                }

                if (top.Count > 0)
                    sourcesBlocks.Add(KnowledgeBase.FormatSources(top, maxCharsPerChunk: 900));
            }
            catch
            {
                var top = kb.SearchTopKLexical(lastUser, k: 2)
                            .Where(x => x.score >= 1f)
                            .ToList();
                if (top.Count > 0)
                    sourcesBlocks.Add(KnowledgeBase.FormatSources(top, maxCharsPerChunk: 900));
            }
        }

        if (!string.IsNullOrWhiteSpace(localKnowledgeFast))
            sourcesBlocks.Add(localKnowledgeFast!);

        var userProfileMemory = BuildUserMemoryForPrompt(requesterKey, maxFacts: 10);
        var mergedMemory = conv.Memory;
        if (!string.IsNullOrWhiteSpace(userProfileMemory))
        {
            mergedMemory = string.IsNullOrWhiteSpace(mergedMemory)
                ? userProfileMemory
                : $"{mergedMemory}\n{userProfileMemory}";
        }

        if (sourcesBlocks.Count == 0)
        {
            sourcesBlocks.Add(
                "NO_PRELOADED_SOURCE: Không tìm thấy thông tin liên quan trong tài liệu/nguồn dữ liệu của cửa hàng.");
        }

        var baseRules =
            "Bạn là trợ lý Help Center cho website bán quần áo.\n" +
            "QUY TẮC BẮT BUỘC:\n" +
            "- Chỉ trả lời dựa trên SOURCES.\n" +
            "- Nếu SOURCES không đủ, nói rõ: \"Mình không tìm thấy thông tin này trong tài liệu/nguồn dữ liệu của cửa hàng.\".\n" +
            "- Không bịa đặt.\n" +
            "- Trả lời ngắn gọn, gạch đầu dòng.\n" +
            "- Khi liệt kê sản phẩm, mỗi sản phẩm phải ở một dòng riêng bắt đầu bằng '- '.\n" +
            "- Nếu thiếu sản phẩm cụ thể khi hỏi size/màu/tồn kho, hỏi lại 1 câu.\n" +
            "- Chủ động dùng tool của plugin store để tra dữ liệu sản phẩm/knowledge trước khi kết luận không có dữ liệu.\n" +
            $"- Giới hạn gọi tool tối đa: {Math.Max(1, agentPolicy.MaxToolCalls)} lần cho mỗi lượt trả lời.\n";

        var (_, prompt) = BuildPromptWithTokenBudget(
            conv: conv,
            baseRules: baseRules,
            summary: conv.Summary,
            memory: mergedMemory,
            sourcesBlocks: sourcesBlocks,
            maxPromptTokens: 7000,
            reserveForAnswerTokens: 800,
            maxHistoryMessages: 6
        );

        var messageId = "m_" + Guid.NewGuid().ToString("N");
        var textId = "t_" + Guid.NewGuid().ToString("N");
        var sb = new StringBuilder();
        var promptTokensEstimated = EstimateTokenCount(prompt);
        var chargeTokens = true;
        var usageNote = "";
        var runtimeState = new AgentRuntimeState();
        var lastKnownCode = ExtractRecentProductCode(conv);
        var hasPreloadedProductSource = isProductQuery && !string.IsNullOrWhiteSpace(fastProductAnswer);
        Action<AgentToolCallLog> toolLogger = log => AppendAgentToolCallLog(agentToolLogPath, log);
        Action<AgentStepLog> stepLogger = log => AppendAgentStepLog(agentStepLogPath, log);
        var plannerHint = BuildPlannerHint(classifierProductIntent ? effectiveProductQuery : lastUser);
        if (hasPreloadedProductSource)
        {
            plannerHint =
                "Da co du lieu san pham tu he thong. Chi duoc tong hop cau tra loi tu SOURCES, " +
                "khong goi them tool, khong bo sung san pham khong co trong SOURCES.";
        }
        if (shortAffirmative && !string.IsNullOrWhiteSpace(effectiveRecentProductCode))
        {
            plannerHint =
                $"Bat buoc goi get_product_variants_by_code cho ma {effectiveRecentProductCode}. " +
                "Tra ve size/mau/ton kho. Khong hoi lai user ve ten san pham.";
        }
        var agentContext = BuildAgentExecutionContext(
            lastKnownProductCode: lastKnownCode,
            conversationId: conversationId,
            userKey: requesterKey,
            traceId: traceId,
            policy: agentPolicy,
            runtimeState: runtimeState,
            toolLogger: toolLogger,
            stepLogger: stepLogger,
            plannerHint: plannerHint,
            allowToolCalls: !hasPreloadedProductSource);

        await SendStart(ctx, messageId);
        await SendTextStart(ctx, textId);

        try
        {
            var agentRequest = new AgentStreamRequest(
                Model: openAiModel,
                ApiKey: openAiApiKey,
                Messages: prompt,
                Context: agentContext,
                CancellationToken: ctx.RequestAborted
            );

            await foreach (var chunk in agentOrchestrator.StreamAsync(agentRequest))
            {
                sb.Append(chunk);
                await SendTextDelta(ctx, textId, chunk);
            }
        }
        catch (Exception ex)
        {
            if (sb.Length == 0)
            {
                var fallback = BuildRateLimitFallbackFromSources(sourcesBlocks);
                sb.Append(fallback);
                await SendTextDeltaChunked(ctx, textId, fallback, chunkChars: 40, minDelayMs: 14);
                chargeTokens = false;
                usageNote =
                    IsUnauthorizedError(ex) ? "openai_unauthorized_fallback" :
                    IsRateLimitError(ex) ? "openai_rate_limited_fallback" :
                    "agent_stream_fallback";
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
        MergeUserMemoryFromConversation(userMemoriesPath, requesterKey, conv);
        PersistConversations(conversationsPath, conversations);

        var completionTokens = EstimateTokenCount(sb.ToString());
        var promptTokens = promptTokensEstimated;
        var totalTokens = promptTokens + completionTokens;
        if (!chargeTokens)
        {
            promptTokens = 0;
            completionTokens = 0;
            totalTokens = 0;
        }

        if (runtimeState.ToolCallCount > 0)
        {
            var toolCallInfo = $"tool_calls:{runtimeState.ToolCallCount}";
            usageNote = string.IsNullOrWhiteSpace(usageNote)
                ? toolCallInfo
                : $"{usageNote};{toolCallInfo}";
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
                LatencyMs = (long)(DateTime.UtcNow - requestStarted).TotalMilliseconds,
                Note = string.IsNullOrWhiteSpace(usageNote) ? null : usageNote
            });

        await SendDone(ctx);
    }

    static string BuildFastProductAnswer(string mainContent, string followup)
    {
        var cleaned = CleanUserFacingLiveText(mainContent);
        if (string.IsNullOrWhiteSpace(cleaned))
            return "Mình không tìm thấy thông tin này trong tài liệu/nguồn dữ liệu của cửa hàng.";

        if (cleaned.EndsWith("\n", StringComparison.Ordinal))
            cleaned = cleaned.TrimEnd();

        return $"{cleaned}\n{followup}";
    }

    static async Task<string> BuildCategoryOverviewAnswerAsync(HttpClient laravelHttp, string laravelBase, CancellationToken ct)
    {
        var categories = new (string label, string keyword)[]
        {
            ("Áo thun", "Thun"),
            ("Quần short", "Short"),
            ("Quần jeans", "Jeans"),
            ("Áo hoodie", "Hoodie"),
            ("Áo gile", "Gile"),
            ("Áo khoác", "Khoa")
        };

        var lines = new List<string>();
        foreach (var (label, keyword) in categories)
        {
            var items = await GetProductsByCategoryAsync(laravelHttp, laravelBase, keyword, ct);
            if (items.Count > 0)
                lines.Add($"- {label}: {items.Count} sản phẩm");
        }

        if (lines.Count == 0)
            return "Hiện tại mình chưa lấy được danh mục sản phẩm từ hệ thống.";

        return "Hiện shop đang có các danh mục sản phẩm:\n" +
               string.Join("\n", lines) +
               "\nBạn muốn mình mở chi tiết danh mục nào?";
    }

    static bool IsVariantFollowupConfirmation(string userText, Conversation conv)
    {
        if (string.IsNullOrWhiteSpace(userText)) return false;
        var plain = RemoveDiacritics(userText.ToLowerInvariant()).Trim();
        var isAffirmative = IsShortAffirmative(plain);
        if (!isAffirmative) return false;

        ChatMessage? previousAssistant = null;
        for (int i = conv.Messages.Count - 2; i >= 0; i--)
        {
            if (string.Equals(conv.Messages[i].Role, "assistant", StringComparison.OrdinalIgnoreCase))
            {
                previousAssistant = conv.Messages[i];
                break;
            }
        }

        if (previousAssistant is null || string.IsNullOrWhiteSpace(previousAssistant.Content))
            return false;

        var assistantPlain = RemoveDiacritics(previousAssistant.Content.ToLowerInvariant());
        var askedVariant =
            (assistantPlain.Contains("size", StringComparison.Ordinal) ||
             assistantPlain.Contains("mau", StringComparison.Ordinal) ||
             assistantPlain.Contains("ton kho", StringComparison.Ordinal)) &&
            (assistantPlain.Contains("ban muon", StringComparison.Ordinal) ||
             assistantPlain.Contains("ban can", StringComparison.Ordinal) ||
             assistantPlain.Contains("kiem tra", StringComparison.Ordinal));

        if (!askedVariant) return false;
        return !string.IsNullOrWhiteSpace(ExtractRecentProductCode(conv));
    }

    static bool IsShortAffirmative(string text)
    {
        if (string.IsNullOrWhiteSpace(text)) return false;
        var plain = RemoveDiacritics(text.ToLowerInvariant()).Trim();
        return plain == "co" ||
               plain == "ok" ||
               plain == "oke" ||
               plain == "yes" ||
               plain == "uhm" ||
               plain == "duoc" ||
               plain == "xem" ||
               plain == "check";
    }

    static string? TryGetRequesterRecentProductCode(string requesterKey, TimeSpan maxAge)
    {
        if (string.IsNullOrWhiteSpace(requesterKey))
            return null;
        if (!RecentProductByRequester.TryGetValue(requesterKey, out var hint))
            return null;
        if (DateTime.UtcNow - hint.AtUtc > maxAge)
        {
            RecentProductByRequester.TryRemove(requesterKey, out _);
            return null;
        }
        return string.IsNullOrWhiteSpace(hint.ProductCode) ? null : hint.ProductCode;
    }

    static void UpdateRequesterRecentProductCode(string requesterKey, string productCode)
    {
        if (string.IsNullOrWhiteSpace(requesterKey) || string.IsNullOrWhiteSpace(productCode))
            return;
        RecentProductByRequester[requesterKey] = new RecentProductHint(productCode.Trim().ToUpperInvariant(), DateTime.UtcNow);
    }

    static string BuildPlannerHint(string userText)
    {
        var safeUserText = userText ?? string.Empty;
        var plain = RemoveDiacritics(safeUserText.ToLowerInvariant());
        if (string.IsNullOrWhiteSpace(plain))
            return "Neu khong du thong tin, hoi lai 1 cau ngan gon truoc khi goi nhieu tool.";

        if (ExtractProductCode(safeUserText) is not null)
            return "UU tien get_product_by_code truoc. Neu user hoi size/mau/ton kho thi goi tiep get_product_variants_by_code.";

        if (Regex.IsMatch(plain, @"\b(size|kich co|mau|con hang|ton kho|stock)\b"))
            return "UU tien search_products hoac get_products_by_category de xac dinh ma san pham, sau do goi get_product_variants_by_code.";

        if (Regex.IsMatch(plain, @"\b(doi mat khau|quen mat khau|tai khoan|giao hang|thanh toan|doi tra|bao mat)\b"))
            return "UU tien search_knowledge. Chi goi tool san pham neu user hoi ro ve san pham.";

        if (Regex.IsMatch(plain, @"\b(ao|quan|short|jean|thun|hoodie|gile|san pham|sp|the thao|do the thao)\b"))
            return "UU tien search_products hoac get_products_by_category. Neu user co dieu kien mau/size thi loc ket qua theo dieu kien.";

        return "Bat dau bang search_knowledge hoac search_products tuy theo y nghia cau hoi, tranh goi qua nhieu tool.";
    }
}

