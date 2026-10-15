A webhook is a message we send to **your** address when something happens. Set it up at **Website > Developer > Webhook**. You get a **signing secret** once; keep it safe.

## Events

| Event | When |
|---|---|
| `inquiry.created` | A new online application or website message |
| `student.enrolled` | An admission is turned into a student |
| `payment.received` | A fee payment is recorded (by staff or online) |

## What we send

A `POST` with `Content-Type: application/json` and these headers:

- `X-SchoolRuns-Event`: the event name
- `X-SchoolRuns-Signature`: `sha256=` followed by an HMAC-SHA256 of the **raw body**, made with your secret

```json
{ "event": "inquiry.created", "sent_at": "2026-11-18T09:30:00+01:00", "school": { "id": 12, "name": "Bright Stars" },
  "data": { "id": 55, "reference": "APP-2026-0007", "student_name": "Tolu Adeyemi", "guardian_name": "Mrs Adeyemi", "guardian_phone": "0803...", "source": "online" } }
```

## Check the signature

```php
$expected = 'sha256=' . hash_hmac('sha256', file_get_contents('php://input'), $secret);
if (! hash_equals($expected, $_SERVER['HTTP_X_SCHOOLRUNS_SIGNATURE'] ?? '')) { http_response_code(401); exit; }
```

```js
const crypto = require('crypto');
const expected = 'sha256=' + crypto.createHmac('sha256', secret).update(rawBody).digest('hex');
const ok = crypto.timingSafeEqual(Buffer.from(expected), Buffer.from(signature));
```

## Rules

- Your address must be a public `https` address. Private networks and redirects are refused.
- We wait up to 5 seconds. Answer with any `2xx` status. A failure never stops the school's work; the last result is shown on the Developer page.
- Use **Send a test** to try it.
