using System.Text.Json;
using System.Text.Json.Nodes;
using Microsoft.SemanticKernel;
using Microsoft.SemanticKernel.Functions;

namespace ChatAgentApi;

internal static partial class ChatCore
{
    internal interface ISemanticKernelToolBridge
    {
        JsonArray BuildTools();

        Task<string> InvokeAsync(string functionName, string argumentsJson, CancellationToken cancellationToken);
    }

    internal sealed class SemanticKernelToolBridge : ISemanticKernelToolBridge
    {
        static readonly JsonSerializerOptions JsonOptions = new(JsonSerializerDefaults.Web);

        readonly Kernel _kernel;

        public SemanticKernelToolBridge(Kernel kernel)
        {
            _kernel = kernel;
        }

        public JsonArray BuildTools()
        {
            var tools = new JsonArray();
            foreach (var function in _kernel.Plugins.GetFunctionsMetadata())
            {
                if (!string.Equals(function.PluginName, StoreKernelPlugin.PluginName, StringComparison.Ordinal))
                    continue;

                var properties = new JsonObject();
                var required = new JsonArray();

                foreach (var parameter in function.Parameters)
                {
                    var schema = ToSchemaNode(parameter);
                    if (schema is JsonObject schemaObject &&
                        !string.IsNullOrWhiteSpace(parameter.Description) &&
                        schemaObject["description"] is null)
                    {
                        schemaObject["description"] = parameter.Description;
                    }

                    properties[parameter.Name] = schema;
                    if (parameter.IsRequired)
                        required.Add(parameter.Name);
                }

                tools.Add(new JsonObject
                {
                    ["type"] = "function",
                    ["name"] = function.Name,
                    ["description"] = function.Description ?? function.Name,
                    ["parameters"] = new JsonObject
                    {
                        ["type"] = "object",
                        ["properties"] = properties,
                        ["required"] = required,
                        ["additionalProperties"] = false
                    }
                });
            }

            return tools;
        }

        public async Task<string> InvokeAsync(string functionName, string argumentsJson, CancellationToken cancellationToken)
        {
            var arguments = new KernelArguments();
            if (!string.IsNullOrWhiteSpace(argumentsJson))
            {
                using var document = JsonDocument.Parse(argumentsJson);
                foreach (var property in document.RootElement.EnumerateObject())
                    arguments[property.Name] = ConvertJsonValue(property.Value);
            }

            var result = await _kernel.InvokeAsync(
                pluginName: StoreKernelPlugin.PluginName,
                functionName: functionName,
                arguments: arguments,
                cancellationToken: cancellationToken);

            return result.ToString() ?? string.Empty;
        }

        static object? ConvertJsonValue(JsonElement value)
        {
            return value.ValueKind switch
            {
                JsonValueKind.String => value.GetString(),
                JsonValueKind.Number when value.TryGetInt64(out var l) => l,
                JsonValueKind.Number when value.TryGetDouble(out var d) => d,
                JsonValueKind.True => true,
                JsonValueKind.False => false,
                JsonValueKind.Null => null,
                _ => value.ToString()
            };
        }

        static JsonNode ToSchemaNode(KernelParameterMetadata parameter)
        {
            if (parameter.Schema is not null)
                return JsonSerializer.SerializeToNode(parameter.Schema, JsonOptions) ?? new JsonObject();

            return new JsonObject
            {
                ["type"] = parameter.ParameterType switch
                {
                    not null when parameter.ParameterType == typeof(bool) || parameter.ParameterType == typeof(bool?) => "boolean",
                    not null when parameter.ParameterType == typeof(int) ||
                                     parameter.ParameterType == typeof(int?) ||
                                     parameter.ParameterType == typeof(long) ||
                                     parameter.ParameterType == typeof(long?) => "integer",
                    not null when parameter.ParameterType == typeof(float) ||
                                     parameter.ParameterType == typeof(float?) ||
                                     parameter.ParameterType == typeof(double) ||
                                     parameter.ParameterType == typeof(double?) ||
                                     parameter.ParameterType == typeof(decimal) ||
                                     parameter.ParameterType == typeof(decimal?) => "number",
                    _ => "string"
                }
            };
        }
    }
}
