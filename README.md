# ChatAgentApi

Repo này gồm 2 phần:

- `ChatAgentApi/`: API .NET cho chat support.
- `csdl-main/`: Laravel giữ dữ liệu sản phẩm, auth và chat widget.

## ChatAgentApi làm gì

- Nhận câu hỏi tại `POST /api/chat`
- Phân loại intent sản phẩm / knowledge
- Truy vấn Laravel hoặc knowledge trước
- Dùng GPT để tổng hợp câu trả lời từ nguồn đã có
- Stream kết quả về client

## Yêu cầu

- .NET SDK 10
- PHP/Laravel
- `OPENAI_API_KEY`

## Biến môi trường chính

```env
OPENAI_API_KEY=your_key
OPENAI_CHAT_MODEL=gpt-4.1-mini
OPENAI_EMBED_MODEL=text-embedding-3-small
LARAVEL_BASE_URL=http://localhost:8000

CHAT_FORCE_GPT_FOR_ALL=false
CHAT_RATE_LIMIT_PER_MIN=60
CHAT_DAILY_TOKEN_QUOTA=120000
```

## Chạy local

Chạy Laravel:

```powershell
cd .\csdl-main
php artisan serve
```

Chạy API C#:

```powershell
dotnet run --project .\ChatAgentApi\ChatAgentApi.csproj --urls http://127.0.0.1:5000
```

UI test đơn giản:

- `http://127.0.0.1:5000/`

## Knowledge index

Build index từ `ChatAgentApi/knowledge/`:

```powershell
dotnet run --project .\ChatAgentApi\ChatAgentApi.csproj -- --index
```

## API chính

### `POST /api/chat`

Request:

```json
{
  "conversationId": "demo-1",
  "messages": [
    { "role": "user", "content": "có áo thun đen không" }
  ]
}
```

Auth:

- `Authorization: Bearer <token>`, hoặc
- session cookie Laravel

### `GET /api/conversations/{id}`

Lấy lại hội thoại hiện tại.

### `GET /api/admin/usage/today`

Xem token usage theo ngày.

## Dữ liệu runtime

App ghi dữ liệu vào `ChatAgentApi/runtime/`:

- `data/conversations.json`
- `data/daily_token_usage.json`
- `data/user_memories.json`
- `knowledge/knowledge_index.jsonl`
- `logs/token_usage.jsonl`
- `logs/agent_tool_calls.jsonl`
- `logs/agent_steps.jsonl`

