using Microsoft.AspNetCore.Http.Timeouts;
using Microsoft.AspNetCore.HttpOverrides;
using Microsoft.AspNetCore.RateLimiting;
using System.Net;
using System.Threading.RateLimiting;

namespace ChatAgentApi;

internal static class ServiceCollectionMiddlewareExtensions
{
    internal static IServiceCollection AddAppMiddleware(this IServiceCollection services, ChatAgentOptions options)
    {
        services.Configure<ForwardedHeadersOptions>(forwardedHeaders =>
        {
            forwardedHeaders.ForwardedHeaders =
                ForwardedHeaders.XForwardedFor |
                ForwardedHeaders.XForwardedProto |
                ForwardedHeaders.XForwardedHost;
            ConfigureKnownForwarders(forwardedHeaders, options);
        });

        services.AddCors(cors =>
        {
            cors.AddPolicy(MiddlewarePolicyNames.CorsPolicyName, policy => policy
                .AllowAnyHeader()
                .AllowAnyMethod()
                .WithOrigins(
                    "http://127.0.0.1:8000",
                    "http://localhost:8000",
                    "http://127.0.0.1:5000",
                    "http://localhost:5000")
                .AllowCredentials());
        });

        services.AddProblemDetails(problem =>
        {
            problem.CustomizeProblemDetails = context =>
            {
                context.ProblemDetails.Extensions["traceId"] = context.HttpContext.TraceIdentifier;
            };
        });

        services.AddRateLimiter(rateLimiter =>
        {
            rateLimiter.RejectionStatusCode = StatusCodes.Status429TooManyRequests;
            rateLimiter.OnRejected = async (context, cancellationToken) =>
            {
                var response = context.HttpContext.Response;
                response.StatusCode = StatusCodes.Status429TooManyRequests;
                response.ContentType = "application/problem+json";

                if (context.Lease.TryGetMetadata(MetadataName.RetryAfter, out TimeSpan retryAfter))
                    response.Headers.RetryAfter = Math.Max(1, (int)Math.Ceiling(retryAfter.TotalSeconds)).ToString();

                const string payload = "{\"title\":\"Too many requests\",\"status\":429,\"detail\":\"Ban gui yeu cau qua nhanh. Vui long thu lai sau.\"}";
                await response.WriteAsync(payload, cancellationToken);
            };

            rateLimiter.AddPolicy(MiddlewarePolicyNames.ChatPolicyName, httpContext =>
                RateLimitPartition.GetFixedWindowLimiter(
                    partitionKey: ResolveRateLimitPartitionKey(httpContext),
                    factory: _ => new FixedWindowRateLimiterOptions
                    {
                        AutoReplenishment = true,
                        PermitLimit = options.RateLimitPerMinute,
                        QueueLimit = 0,
                        Window = TimeSpan.FromMinutes(1)
                    }));
        });

        services.AddRequestTimeouts(timeouts =>
        {
            timeouts.AddPolicy(MiddlewarePolicyNames.ChatTimeoutPolicyName, new RequestTimeoutPolicy
            {
                Timeout = TimeSpan.FromSeconds(90),
                TimeoutStatusCode = StatusCodes.Status408RequestTimeout,
                WriteTimeoutResponse = async context =>
                {
                    context.Response.ContentType = "application/problem+json";
                    const string payload = "{\"title\":\"Request timeout\",\"status\":408,\"detail\":\"Yeu cau xu ly qua lau, vui long thu lai.\"}";
                    await context.Response.WriteAsync(payload, context.RequestAborted);
                }
            });
        });

        services.AddRequestDecompression();

        return services;
    }

    static string ResolveRateLimitPartitionKey(HttpContext ctx)
    {
        if (ctx.Items.TryGetValue(ChatAuthenticationService.UserKeyItemName, out var userKeyObj) &&
            userKeyObj is string userKey &&
            !string.IsNullOrWhiteSpace(userKey))
            return userKey;

        var ip = ctx.Connection.RemoteIpAddress?.ToString();
        if (!string.IsNullOrWhiteSpace(ip))
            return $"ip:{ip}";

        return "ip:unknown";
    }

    static void ConfigureKnownForwarders(ForwardedHeadersOptions forwardedHeaders, ChatAgentOptions options)
    {
        var knownProxies = options.ForwardedHeadersKnownProxies
            .Where(value => !string.IsNullOrWhiteSpace(value))
            .ToList();
        var knownNetworks = options.ForwardedHeadersKnownNetworks
            .Where(value => !string.IsNullOrWhiteSpace(value))
            .ToList();

        if (knownProxies.Count == 0 && knownNetworks.Count == 0)
            return;

        forwardedHeaders.KnownIPNetworks.Clear();
        forwardedHeaders.KnownProxies.Clear();

        foreach (var proxy in knownProxies)
        {
            if (IPAddress.TryParse(proxy, out var parsedProxy))
                forwardedHeaders.KnownProxies.Add(parsedProxy);
        }

        foreach (var network in knownNetworks)
        {
            if (System.Net.IPNetwork.TryParse(network, out var parsedNetwork))
                forwardedHeaders.KnownIPNetworks.Add(parsedNetwork);
        }
    }
}
