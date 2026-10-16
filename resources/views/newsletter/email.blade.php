{{-- One email, drawn in the school's look. $bodyHtml is already safe (App\Website\Prose escapes everything). --}}
<!doctype html>
<html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>{{ $subject }}</title></head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:Arial,Helvetica,sans-serif;color:#1f2937;">
@if (! empty($preheader))<div style="display:none;max-height:0;overflow:hidden;opacity:0;">{{ $preheader }}</div>@endif
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6;"><tr><td align="center" style="padding:16px 8px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:600px;background:#ffffff;border-radius:8px;overflow:hidden;">
        <tr><td style="background:{{ $colour }};padding:20px 24px;color:#ffffff;">
            <table role="presentation" cellpadding="0" cellspacing="0"><tr>
                @if ($logoUrl)<td style="padding-right:14px;"><img src="{{ $logoUrl }}" alt="" height="44" style="height:44px;width:auto;display:block;border:0;border-radius:6px;background:#ffffff;"></td>@endif
                <td style="font-size:20px;font-weight:bold;color:#ffffff;line-height:1.25;">{{ $headerText }}</td>
            </tr></table>
        </td></tr>
        <tr><td style="padding:24px;font-size:16px;line-height:1.6;color:#1f2937;">
            <style>.nl p{margin:0 0 14px}.nl h2,.nl h3{margin:20px 0 8px;line-height:1.3}.nl a{color:{{ $colour }}}.nl ul,.nl ol{margin:0 0 14px;padding-left:22px}.nl blockquote{margin:0 0 14px;padding-left:12px;border-left:3px solid {{ $colour }};color:#4b5563}</style>
            <div class="nl">{!! $bodyHtml !!}</div>
            @if (! empty($buttonUrl))
                <p style="margin:22px 0 4px;"><a href="{{ $buttonUrl }}" style="display:inline-block;background:{{ $colour }};color:#ffffff;text-decoration:none;font-weight:bold;padding:12px 22px;border-radius:6px;">{{ $buttonText }}</a></p>
            @endif
        </td></tr>
        <tr><td style="padding:16px 24px 22px;border-top:1px solid #e5e7eb;font-size:12px;line-height:1.6;color:#6b7280;">
            @if (! empty($footerText))<p style="margin:0 0 8px;">{{ $footerText }}</p>@endif
            <p style="margin:0 0 8px;"><strong>{{ $schoolName }}</strong>@if ($address) · {{ $address }}@endif</p>
            @if (! empty($reason))<p style="margin:0 0 8px;">{{ $reason }}</p>@endif
            @if (! empty($unsubscribeUrl))<p style="margin:0;"><a href="{{ $unsubscribeUrl }}" style="color:#6b7280;">Unsubscribe</a> at any time.</p>@endif
        </td></tr>
    </table>
</td></tr></table>
</body></html>
