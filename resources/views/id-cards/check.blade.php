<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Check a student ID card</title>
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, 'Segoe UI', sans-serif; background: #f1f5f9; color: #0f172a; }
        main { max-width: 32rem; margin: 3rem auto; padding: 0 1rem; }
        .card { background: #fff; border-radius: 12px; padding: 1.5rem; box-shadow: 0 1px 3px rgba(0,0,0,.08); }
        .badge { display: inline-block; padding: .25rem .75rem; border-radius: 999px; font-weight: 600; font-size: .875rem; }
        .ok { background: #dcfce7; color: #166534; } .bad { background: #fee2e2; color: #991b1b; }
        dl { display: grid; grid-template-columns: 9rem 1fr; gap: .5rem 1rem; margin: 1.25rem 0 0; }
        dt { color: #64748b; } dd { margin: 0; font-weight: 500; }
        h1 { font-size: 1.25rem; margin: 0 0 1rem; } p { color: #475569; line-height: 1.5; }
        @media (prefers-color-scheme: dark) {
            body { background: #0f172a; color: #e2e8f0; } .card { background: #1e293b; } dt, p { color: #94a3b8; }
        }
    </style>
</head>
<body>
<main>
    <div class="card">
        <h1>Student ID card check</h1>
        @if(! $found)
            <span class="badge bad">Not found</span>
            <p>No ID card matches this code. The card may not be genuine: contact the school named on it.</p>
        @else
            @if($state === 'valid')
                <span class="badge ok">Valid card</span>
                <p>This card was issued by the school below and is in use.</p>
            @elseif($state === 'expired')
                <span class="badge bad">Expired</span>
                <p>This card has passed its end date. The school will issue a new one.</p>
            @else
                <span class="badge bad">No longer valid</span>
                <p>The school has replaced or stopped this card. Please return it to the school.</p>
            @endif
            <dl>
                <dt>Student</dt><dd>{{ $name }}</dd>
                <dt>Class</dt><dd>{{ $class ?? '—' }}</dd>
                <dt>School</dt><dd>{{ $school }}</dd>
                <dt>Card number</dt><dd>{{ $card_no }}</dd>
                @if($expires_on)<dt>Valid until</dt><dd>{{ $expires_on }}</dd>@endif
            </dl>
            <p style="margin-top:1.25rem">If you found this card, please return it to the school.</p>
        @endif
    </div>
</main>
</body>
</html>
