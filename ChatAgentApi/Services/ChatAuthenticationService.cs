using System.Net.Http.Headers;
using System.Text.Json;

namespace ChatAgentApi;

internal interface IChatAuthenticationService
{
    ValueTask<string?> GetAuthenticatedUserKeyAsync(HttpContext context, CancellationToken ct);
}

internal sealed class ChatAuthenticationService : IChatAuthenticationService
{
    internal const string UserKeyItemName = "__ChatAgentApi.AuthenticatedUserKey";
    internal const string AuthAttemptedItemName = "__ChatAgentApi.AuthAttempted";

    readonly IHttpClientFactory _httpClientFactory;
    readonly ChatAgentOptions _options;

    public ChatAuthenticationService(IHttpClientFactory httpClientFactory, Microsoft.Extensions.Options.IOptions<ChatAgentOptions> options)
    {
        _httpClientFactory = httpClientFactory;
        _options = options.Value;
    }

    public async ValueTask<string?> GetAuthenticatedUserKeyAsync(HttpContext context, CancellationToken ct)
    {
        if (context.Items.TryGetValue(UserKeyItemName, out var cached) &&
            cached is string userKey &&
            !string.IsNullOrWhiteSpace(userKey))
            return userKey;

        if (context.Items.TryGetValue(AuthAttemptedItemName, out var attempted) &&
            attempted is true)
            return null;

        context.Items[AuthAttemptedItemName] = true;

        var authenticatedUserKey = await AuthenticateRequestCoreAsync(context.Request, ct);
        if (!string.IsNullOrWhiteSpace(authenticatedUserKey))
            context.Items[UserKeyItemName] = authenticatedUserKey;

        return authenticatedUserKey;
    }

    async Task<string?> AuthenticateRequestCoreAsync(HttpRequest request, CancellationToken ct)
    {
        var laravelHttp = _httpClientFactory.CreateClient("laravel");

        var token = ExtractBearerToken(request);
        if (!string.IsNullOrWhiteSpace(token))
        {
            if (ChatCore.AuthTokenCache.TryGetValue(token, out var hit) && hit.ExpiresAtUtc > DateTime.UtcNow)
                return hit.UserKey;

            var byToken = await ValidateBearerAsync(laravelHttp, token, ct);
            if (!string.IsNullOrWhiteSpace(byToken))
            {
                ChatCore.AuthTokenCache[token] = new ChatCore.AuthUserHint(byToken, DateTime.UtcNow.AddMinutes(5));
                return byToken;
            }
        }

        var cookieHeader = request.Headers.Cookie.ToString();
        if (!string.IsNullOrWhiteSpace(cookieHeader))
            return await ValidateSessionCookieAsync(laravelHttp, cookieHeader, ct);

        return null;
    }

    async Task<string?> ValidateBearerAsync(HttpClient laravelHttp, string token, CancellationToken ct)
    {
        using var req = new HttpRequestMessage(HttpMethod.Get, $"{_options.LaravelBase}/api/user");
        req.Headers.Authorization = new AuthenticationHeaderValue("Bearer", token);
        req.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

        using var resp = await laravelHttp.SendAsync(req, ct);
        if (!resp.IsSuccessStatusCode)
            return null;

        var body = await resp.Content.ReadAsStringAsync(ct);
        return ParseUserKeyFromJson(body);
    }

    async Task<string?> ValidateSessionCookieAsync(HttpClient laravelHttp, string cookieHeader, CancellationToken ct)
    {
        using var req = new HttpRequestMessage(HttpMethod.Get, $"{_options.LaravelBase}/chat-auth/me");
        req.Headers.Add("Cookie", cookieHeader);
        req.Headers.Accept.Add(new MediaTypeWithQualityHeaderValue("application/json"));

        using var resp = await laravelHttp.SendAsync(req, ct);
        if (!resp.IsSuccessStatusCode)
            return null;

        var body = await resp.Content.ReadAsStringAsync(ct);
        return ParseUserKeyFromJson(body);
    }

    static string? ParseUserKeyFromJson(string body)
    {
        if (string.IsNullOrWhiteSpace(body))
            return null;

        try
        {
            using var doc = JsonDocument.Parse(body);
            var root = doc.RootElement;
            string? id = null;
            if (root.TryGetProperty("id", out var idEl))
                id = idEl.ToString();
            else if (root.TryGetProperty("userId", out var uidEl))
                id = uidEl.ToString();

            return string.IsNullOrWhiteSpace(id) ? null : $"user:{id}";
        }
        catch
        {
            return null;
        }
    }

    static string? ExtractBearerToken(HttpRequest request)
    {
        if (!request.Headers.TryGetValue("Authorization", out var authValues))
            return null;

        var auth = authValues.ToString().Trim();
        if (string.IsNullOrWhiteSpace(auth))
            return null;

        const string prefix = "Bearer ";
        if (!auth.StartsWith(prefix, StringComparison.OrdinalIgnoreCase))
            return null;

        var token = auth[prefix.Length..].Trim();
        return string.IsNullOrWhiteSpace(token) ? null : token;
    }
}
