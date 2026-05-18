namespace ChatAgentApi;

internal static class ApplicationBuilderMiddlewareExtensions
{
    internal static WebApplication UseAppMiddleware(this WebApplication app)
    {
        app.UseExceptionHandler();

        if (!app.Environment.IsDevelopment())
            app.UseHsts();

        app.UseHttpsRedirection();
        app.UseDefaultFiles();
        app.UseStaticFiles();

        app.UseRouting();
        app.UseRequestDecompression();
        app.UseCors(MiddlewarePolicyNames.CorsPolicyName);
        app.UseRateLimiter();
        app.UseRequestTimeouts();

        return app;
    }
}
