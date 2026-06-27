using Microsoft.AspNetCore.HttpOverrides;

namespace ChatAgentApi;

internal static class ApplicationBuilderMiddlewareExtensions
{
    internal static WebApplication UseAppMiddleware(this WebApplication app)
    {
        app.UseExceptionHandler();

        if (!app.Environment.IsDevelopment())
            app.UseHsts();

        app.UseForwardedHeaders();
        app.UseHttpsRedirection();
        app.UseDefaultFiles();
        app.UseStaticFiles();

        app.UseRouting();
        app.UseRequestDecompression();
        app.UseCors(MiddlewarePolicyNames.CorsPolicyName);
        app.UseMiddleware<ChatAuthenticationMiddleware>();
        app.UseRateLimiter();
        app.UseRequestTimeouts();

        return app;
    }
}
