namespace ChatAgentApi;

public class Program
{
    public static async Task Main(string[] args)
    {
        var builder = WebApplication.CreateBuilder(args);

        builder.Services.AddChatAgentApplication(builder.Configuration, builder.Environment);
        builder.Services.ConfigureHttpJsonOptions(o =>
        {
            o.SerializerOptions.TypeInfoResolverChain.Insert(0, AppJsonContext.Default);
        });

        var app = builder.Build();

        app.UseAppMiddleware();

        if (args.Contains("--index", StringComparer.OrdinalIgnoreCase))
        {
            var runtime = app.Services.GetRequiredService<ChatAgentRuntime>();
            if (string.IsNullOrWhiteSpace(runtime.Options.OpenAiApiKey))
            {
                Console.WriteLine("Missing OPENAI_API_KEY. Set env var then re-run.");
                return;
            }

            var openAiClient = app.Services.GetRequiredService<IHttpClientFactory>().CreateClient("openai");
            await ChatCore.KnowledgeIndexer.BuildIndexJsonl(
                http: openAiClient,
                apiKey: runtime.Options.OpenAiApiKey,
                embeddingModel: runtime.Options.OpenAiEmbedModel,
                knowledgeDir: runtime.KnowledgeDir,
                outPath: runtime.IndexPath,
                chunkChars: 1800,
                overlapChars: 200,
                ct: CancellationToken.None);

            Console.WriteLine($"Index created: {runtime.IndexPath}");
            return;
        }

        if (args.Contains("--smoke-sk", StringComparer.OrdinalIgnoreCase))
        {
            await ChatCore.RunSemanticKernelSmokeAsync(app.Services, CancellationToken.None);
            return;
        }

        app.MapChatAgentApi();

        await app.RunAsync();
    }
}
