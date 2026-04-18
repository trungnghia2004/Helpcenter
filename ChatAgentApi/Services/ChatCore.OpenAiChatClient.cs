using System.Collections.Concurrent;
using System.Globalization;
using System.Net.Http.Headers;
using System.Text;
using System.Text.Json;
using System.Text.RegularExpressions;
using System.Linq;
using Microsoft.Extensions.DependencyInjection;
using Microsoft.SemanticKernel;
using Microsoft.SemanticKernel.ChatCompletion;
using Microsoft.SemanticKernel.Connectors.OpenAI;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    static async IAsyncEnumerable<string> OpenAIStream(
        HttpClient http,
        string model,
        string apiKey,
        List<ChatMessage> messages,
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
            Temperature = 0.2
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
}


