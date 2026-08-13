# ChatAgentApi

Chat backend C# cho website bán quần áo. Repo này gồm 2 phần:

- `ChatAgentApi/`: API .NET dùng OpenAI + knowledge base + dữ liệu sản phẩm từ Laravel.
- `csdl-main/`: ứng dụng Laravel đang giữ dữ liệu sản phẩm, biến thể, đăng nhập và chat widget phía web.

Luồng chính hiện tại:

1. User gửi câu hỏi vào `POST /api/chat`.
2. Hệ thống phân loại nhanh intent: sản phẩm, knowledge, hoặc general.
3. Nếu là câu hỏi sản phẩm, API truy vấn Laravel trước.
4. Nếu đã có dữ liệu phù hợp, GPT chỉ tổng hợp lại câu trả lời từ nguồn đã nạp, không tự bịa thêm.
5. Nếu là knowledge, hệ thống ưu tiên knowledge local / semantic search.
6. Kết quả được stream về client theo SSE-style chunks.

## Yêu cầu

- .NET SDK 10
- PHP/Laravel để chạy `csdl-main`
- OpenAI API key hợp lệ

## Cấu trúc thư mục

```text
.
|-- ChatAgentApi/
|   |-- Api/
|   |-- Configuration/
|   |-- Infrastructure/
|   |-- Middleware/
|   |-- Plugins/
|   |-- Services/
|   |-- knowledge/
|   `-- wwwroot/
|-- csdl-main/
`-- README.md
```

## Cấu hình

`ChatAgentApi` đọc config từ `appsettings.json` và biến môi trường.

Biến môi trường quan trọng:

```env
OPENAI_API_KEY=your_key
OPENAI_CHAT_MODEL=gpt-4.1-mini
OPENAI_EMBED_MODEL=text-embedding-3-small
LARAVEL_BASE_URL=http://localhost:8000

CHAT_FORCE_GPT_FOR_ALL=false
AGENT_MAX_TOOL_CALLS=8
AGENT_MAX_TOOL_OUTPUT_CHARS=1200
CHAT_RATE_LIMIT_PER_MIN=60
CHAT_DAILY_TOKEN_QUOTA=120000

FORWARDED_HEADERS_KNOWN_PROXIES=
FORWARDED_HEADERS_KNOWN_NETWORKS=
```

Giá trị mặc định đang nằm trong [ChatAgentOptions.cs](/F:/C#/ChatAgentApi/ChatAgentApi/Configuration/ChatAgentOptions.cs).

## Chạy local

### 1. Chạy Laravel backend

Trong thư mục `csdl-main`:

```powershell
php artisan serve
```

Mặc định app C# sẽ gọi sang `http://localhost:8000`.

Laravel hiện cung cấp các API mà chat đang dùng:

- `GET /api/products/search?q=...`
- `GET /api/products/by-code/{code}`
- `GET /api/products/by-category?q=...`
- `GET /api/products/{id}/variants`
- `GET /chat-auth/me`
- `GET /api/user` nếu dùng bearer token

### 2. Chạy ChatAgentApi

Trong thư mục `ChatAgentApi`:

```powershell
dotnet run
```

Hoặc từ root repo:

```powershell
dotnet run --project .\ChatAgentApi\ChatAgentApi.csproj
```

Mặc định API sẽ chạy theo profile local của ASP.NET. Nếu muốn chỉ định port:

```powershell
dotnet run --project .\ChatAgentApi\ChatAgentApi.csproj --urls http://127.0.0.1:5000
```

Sau khi chạy, có thể mở UI test đơn giản ở:

- `http://127.0.0.1:5000/`

## Knowledge base

Knowledge markdown đang nằm trong [knowledge](/F:/C#/ChatAgentApi/ChatAgentApi/knowledge).

Để build semantic index:

```powershell
dotnet run --project .\ChatAgentApi\ChatAgentApi.csproj -- --index
```

Lệnh này:

- đọc toàn bộ file `.md` trong `knowledge/`
- tạo embedding bằng model `OPENAI_EMBED_MODEL`
- ghi index vào `ChatAgentApi/runtime/knowledge/knowledge_index.jsonl`

Nếu chưa có index, hệ thống vẫn có thể fallback sang lexical search và local knowledge answer.

## API

### `POST /api/chat`

Endpoint chính để chat. Response là stream text theo SSE format.

Request body:

```json
{
  "conversationId": "optional-conversation-id",
  "messages": [
    { "role": "user", "content": "có áo thun đen không" }
  ]
}
```

Header xác thực:

- `Authorization: Bearer <token>`, hoặc
- session cookie hợp lệ của Laravel

Nếu chưa đăng nhập, API trả `401` với message tiếng Việt.

Ví dụ:

```powershell
$body = @{
  conversationId = "demo-1"
  messages = @(
    @{ role = "user"; content = "có áo thun đen không" }
  )
} | ConvertTo-Json -Depth 5

Invoke-WebRequest `
  -Uri "http://127.0.0.1:5000/api/chat" `
  -Method Post `
  -ContentType "application/json" `
  -Headers @{ Authorization = "Bearer <token>" } `
  -Body $body
```

### `GET /api/conversations/{id}`

Đọc lại conversation đang giữ trong memory/runtime.

### `GET /api/admin/usage/today`

Xem tổng token usage theo ngày UTC. Hữu ích để debug quota và theo dõi user usage.

## Runtime data

Khi app chạy, dữ liệu phát sinh được ghi trong `ChatAgentApi/runtime/`:

```text
runtime/
|-- data/
|   |-- conversations.json
|   |-- daily_token_usage.json
|   `-- user_memories.json
|-- knowledge/
|   `-- knowledge_index.jsonl
`-- logs/
    |-- token_usage.jsonl
    |-- agent_tool_calls.jsonl
    `-- agent_steps.jsonl
```

Repo hiện có logic migrate file cũ từ `data/`, `logs/`, `knowledge/` sang `runtime/` nếu cần.

## Hành vi hiện tại của chat

- Query sản phẩm không trả thẳng kết quả search thô nữa; hệ thống truy vấn trước rồi mới cho GPT viết câu trả lời.
- Nếu đã có source sản phẩm phù hợp, agent sẽ bị chặn gọi thêm tool để tránh lạc ngữ cảnh hoặc bịa sản phẩm.
- Query knowledge có thể trả lời từ local knowledge nhanh mà không cần gọi model nếu match đủ rõ.
- Có giới hạn rate limit theo phút và quota token theo ngày cho từng user.
- Có ghi log tool calls và agent steps để debug.

## Frontend liên quan

- `ChatAgentApi/wwwroot/`: UI test rất đơn giản cho backend C#.
- `csdl-main/public/chat-widget.js`: widget gắn vào site Laravel.

Widget Laravel sẽ:

- thử cùng origin trước
- fallback sang `http://localhost:5000/api/chat` hoặc `http://127.0.0.1:5000/api/chat`
- dùng bearer token hoặc session cookie để xác thực

## Deployment

Xem thêm [DEPLOYMENT.md](/F:/C#/ChatAgentApi/ChatAgentApi/DEPLOYMENT.md) cho:

- `X-Forwarded-*`
- proxy/IP allowlist
- ví dụ Nginx / IIS / Azure

## Lưu ý phát triển

- `OPENAI_API_KEY` là bắt buộc, app sẽ fail ngay lúc startup nếu thiếu.
- Project target `net10.0`.
- Một số response/user-facing text hiện được chuẩn hóa tiếng Việt ở tầng API C#.
- Nếu muốn ép mọi câu trả lời đi qua GPT, bật `CHAT_FORCE_GPT_FOR_ALL=true`.
- Nếu muốn kiểm tra logic Semantic Kernel, dùng:

```powershell
dotnet run --project .\ChatAgentApi\ChatAgentApi.csproj -- --smoke-sk
```
