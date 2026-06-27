# ChatAgentApi Deployment Notes

## Forwarded Headers

`ChatAgentApi` uses `UseForwardedHeaders()` before `UseHttpsRedirection()`.
For production, only trusted reverse proxies or proxy networks should be allowed
to forward `X-Forwarded-*` headers.

Configuration keys:

```json
{
  "ChatAgent": {
    "ForwardedHeadersKnownProxies": [],
    "ForwardedHeadersKnownNetworks": []
  }
}
```

Environment variable alternatives:

```env
FORWARDED_HEADERS_KNOWN_PROXIES=10.0.0.10,10.0.0.11
FORWARDED_HEADERS_KNOWN_NETWORKS=10.0.0.0/24,192.168.1.0/24
```

Rules:

- Use `ForwardedHeadersKnownProxies` when you know the exact proxy IPs.
- Use `ForwardedHeadersKnownNetworks` when proxies sit inside a fixed subnet.
- Leave both empty only when the app is not behind a reverse proxy.
- Do not trust public or broad networks unless you control them.

## Nginx Example

If `ChatAgentApi` runs behind an internal Nginx proxy at `10.10.0.5`:

```json
{
  "ChatAgent": {
    "ForwardedHeadersKnownProxies": [ "10.10.0.5" ]
  }
}
```

Typical Nginx forwarding:

```nginx
location / {
    proxy_pass         http://127.0.0.1:5000;
    proxy_set_header   Host $host;
    proxy_set_header   X-Forwarded-Host $host;
    proxy_set_header   X-Forwarded-Proto $scheme;
    proxy_set_header   X-Forwarded-For $proxy_add_x_forwarded_for;
}
```

## IIS Example

If IIS or ARR runs on the same machine and forwards to Kestrel locally:

```json
{
  "ChatAgent": {
    "ForwardedHeadersKnownProxies": [ "127.0.0.1", "::1" ]
  }
}
```

If IIS is on a separate reverse-proxy host, use that host's private IP instead.

## Azure App Service Example

If the app is behind a controlled private ingress subnet such as `10.20.0.0/24`:

```json
{
  "ChatAgent": {
    "ForwardedHeadersKnownNetworks": [ "10.20.0.0/24" ]
  }
}
```

If you deploy behind Azure Front Door, App Gateway, or another managed ingress,
use the private IPs or CIDR ranges that actually reach the app, not the public
edge IPs shown to internet clients.

## Validation

`ChatAgentApi` validates these values at startup:

- `ForwardedHeadersKnownProxies` must contain valid IP addresses.
- `ForwardedHeadersKnownNetworks` must contain valid CIDR blocks.

Startup will fail if the values are invalid.
