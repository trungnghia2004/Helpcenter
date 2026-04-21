using System.Collections.Concurrent;
using System.Globalization;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using System.Linq;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal static void MapApiEndpoints(
        WebApplication app,
        ConcurrentDictionary<string, Conversation> conversations,
        KnowledgeBase kb,
        string conversationsPath,
        string dailyUsagePath,
        string userMemoriesPath,
        string usageLogPath,
        string agentToolLogPath,
        string agentStepLogPath,
        string knowledgeDir,
        string laravelBase,
        string openAiApiKey,
        string openAiModel,
        string openAiEmbedModel,
        AgentRunPolicy agentPolicy,
        int rateLimitPerMinute,
        int dailyTokenQuota)
    {
        app.MapPost("/api/chat", ctx => HandleChatAsync(
            ctx: ctx,
            conversations: conversations,
            kb: kb,
            conversationsPath: conversationsPath,
            dailyUsagePath: dailyUsagePath,
            userMemoriesPath: userMemoriesPath,
            usageLogPath: usageLogPath,
            agentToolLogPath: agentToolLogPath,
            agentStepLogPath: agentStepLogPath,
            knowledgeDir: knowledgeDir,
            laravelBase: laravelBase,
            openAiApiKey: openAiApiKey,
            openAiModel: openAiModel,
            openAiEmbedModel: openAiEmbedModel,
            agentPolicy: agentPolicy,
            rateLimitPerMinute: rateLimitPerMinute,
            dailyTokenQuota: dailyTokenQuota
        ));

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
    }

    static async Task HandleChatAsync(
        HttpContext ctx,
        ConcurrentDictionary<string, Conversation> conversations,
        KnowledgeBase kb,
        string conversationsPath,
        string dailyUsagePath,
        string userMemoriesPath,
        string usageLogPath,
        string agentToolLogPath,
        string agentStepLogPath,
        string knowledgeDir,
        string laravelBase,
        string openAiApiKey,
        string openAiModel,
        string openAiEmbedModel,
        AgentRunPolicy agentPolicy,
        int rateLimitPerMinute,
        int dailyTokenQuota)
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
        var localKnowledgeFast = TryGetLocalKnowledgeAnswer(lastUser, knowledgeDir);
        var hasExplicitProductCode = ExtractProductCode(lastUser) is not null;

        if (!string.IsNullOrWhiteSpace(localKnowledgeFast) && !hasExplicitProductCode)
        {
            var messageIdLocal = "m_" + Guid.NewGuid().ToString("N");
            var textIdLocal = "t_" + Guid.NewGuid().ToString("N");
            var answer = localKnowledgeFast!.Trim();

            await SendStart(ctx, messageIdLocal);
            await SendTextStart(ctx, textIdLocal);
            await SendTextDelta(ctx, textIdLocal, answer);
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
                    product = await GetProductByCodeAsync(laravelHttp, laravelBase, code!, ctx.RequestAborted);
                }
                else
                {
                    if (!string.IsNullOrWhiteSpace(categoryKeyword))
                        products = await GetProductsByCategoryAsync(laravelHttp, laravelBase, categoryKeyword!, ctx.RequestAborted);
                    else
                        products = await SearchProductsAsync(laravelHttp, laravelBase, lastUser, ctx.RequestAborted);

                    if (products is null || products.Count == 0)
                        products = await SearchProductsAsync(laravelHttp, laravelBase, lastUser, ctx.RequestAborted);

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
                        var variants = await GetVariantsAsync(laravelHttp, laravelBase, pid.GetInt64(), ctx.RequestAborted);
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
                "NO_PRELOADED_SOURCE: Khong tim thay nguon tien xu ly. Agent bat buoc can nhac goi tool store truoc khi ket luan thieu du lieu.");
        }

        var baseRules =
            "Bạn là trợ lý Help Center cho website bán quần áo.\n" +
            "QUY TẮC BẮT BUỘC:\n" +
            "- Chỉ trả lời dựa trên SOURCES.\n" +
            "- Nếu SOURCES không đủ, nói rõ: \"Mình không tìm thấy thông tin này trong tài liệu/nguồn dữ liệu cửa hàng.\".\n" +
            "- Không bịa.\n" +
            "- Trả lời ngắn gọn, gạch đầu dòng.\n" +
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
            maxHistoryMessages: 10
        );

        var messageId = "m_" + Guid.NewGuid().ToString("N");
        var textId = "t_" + Guid.NewGuid().ToString("N");
        var sb = new StringBuilder();
        OpenAiUsage? usage = null;
        var promptTokensEstimated = EstimateTokenCount(prompt);
        var chargeTokens = true;
        var usageNote = "";
        var agentOrchestrator = ctx.RequestServices.GetRequiredService<IAgentOrchestrator>();
        var runtimeState = new AgentRuntimeState();
        var lastKnownCode = ExtractRecentProductCode(conv);
        if (string.IsNullOrWhiteSpace(lastKnownCode) &&
            LastProductCodeByRequester.TryGetValue(requesterKey, out var rememberedCode))
            lastKnownCode = rememberedCode;
        Action<AgentToolCallLog> toolLogger = log => AppendAgentToolCallLog(agentToolLogPath, log);
        Action<AgentStepLog> stepLogger = log => AppendAgentStepLog(agentStepLogPath, log);
        var plannerHint = BuildPlannerHint(lastUser);
        var agentContext = new AgentExecutionContext(
            OpenAiHttp: openAiHttp,
            LaravelHttp: laravelHttp,
            LaravelBase: laravelBase,
            KnowledgeBase: kb,
            KnowledgeDir: knowledgeDir,
            OpenAiApiKey: openAiApiKey,
            OpenAiEmbedModel: openAiEmbedModel,
            LastKnownProductCode: lastKnownCode,
            ConversationId: conversationId,
            UserKey: requesterKey,
            TraceId: traceId,
            Policy: agentPolicy,
            RuntimeState: runtimeState,
            ToolLogger: toolLogger,
            StepLogger: stepLogger,
            PlannerHint: plannerHint,
            CancellationToken: ctx.RequestAborted
        );

        await SendStart(ctx, messageId);
        await SendTextStart(ctx, textId);

        try
        {
            var agentRequest = new AgentStreamRequest(
                Model: openAiModel,
                ApiKey: openAiApiKey,
                Messages: prompt,
                Context: agentContext,
                OnUsage: u => usage = u,
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
                await SendTextDelta(ctx, textId, fallback);
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

        var completionTokens = usage?.CompletionTokens ?? EstimateTokenCount(sb.ToString());
        var promptTokens = usage?.PromptTokens ?? promptTokensEstimated;
        var totalTokens = usage?.TotalTokens ?? (promptTokens + completionTokens);
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

        if (Regex.IsMatch(plain, @"\b(ao|quan|short|jean|thun|hoodie|gile|san pham|sp)\b"))
            return "UU tien search_products hoac get_products_by_category. Neu user co dieu kien mau/size thi loc ket qua theo dieu kien.";

        return "Bat dau bang search_knowledge hoac search_products tuy theo y nghia cau hoi, tranh goi qua nhieu tool.";
    }
}


