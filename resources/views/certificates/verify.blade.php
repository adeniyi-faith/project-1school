<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Check a certificate</title>
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
        <h1>Certificate check</h1>
        @if(! $found)
            <span class="badge bad">Not found</span>
            <p>No certificate has this code. Check that the code was typed exactly as printed. If it still isn't found, the certificate may not be genuine: contact the school that issued it.</p>
        @else
            @if($revoked)
                <span class="badge bad">Revoked{{ $revoked_on ? ' on ' . $revoked_on : '' }}</span>
                <p>The school has cancelled this certificate. It is no longer valid. Contact the school for a current one.</p>
            @else
                <span class="badge ok">Genuine</span>
                <p>This certificate was issued by the school below and has not been cancelled.</p>
            @endif
            <dl>
                <dt>Certificate</dt><dd>{{ $title }}</dd>
                <dt>Number</dt><dd>{{ $serial }}</dd>
                <dt>Student</dt><dd>{{ $name }}</dd>
                <dt>School</dt><dd>{{ $school }}</dd>
                @if($last_class)<dt>Last class</dt><dd>{{ $last_class }}</dd>@endif
                @if($date_left)<dt>Date left</dt><dd>{{ $date_left }}</dd>@endif
                <dt>Issued on</dt><dd>{{ $issued_on }}</dd>
            </dl>
        @endif
    </div>
</main>
</body>
</html>
