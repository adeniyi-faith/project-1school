A read-only key lets your own script read the school's inquiries. Make one at **Website > Developer > Read-only key**. It is shown once; only a fingerprint is stored. You can have up to 5 and revoke any of them.

Send it in the `Authorization` header:

```bash
curl -H "Authorization: Bearer sr_your_key" https://your-platform/api/school/inquiries?since=2026-11-01&limit=20
```

| Address | Returns |
|---|---|
| `GET /api/school/me` | The school the key belongs to |
| `GET /api/school/inquiries` | Newest inquiries first. Options: `since` (a date), `status`, `limit` (1 to 100) |

Calls are limited to 60 a minute. A missing or revoked key gets `401`. The key can never change anything.
