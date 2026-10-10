<!doctype html>
<html><head><meta charset="utf-8">
@php
    $fmt = fn ($date) => $date ? date('j F Y', strtotime($date)) : '—';
    $isTransfer = $certificate->type === 'transfer';
@endphp
<style>
    @page { margin: 22px; }
    body { font-family: 'DejaVu Serif', serif; font-size: 12px; color: #111827; margin: 0; }
    .border-outer { position: fixed; top: 0; left: 0; right: 0; bottom: 0; border: 3px double {{ $color }}; }
    .border-inner { position: fixed; top: 7px; left: 7px; right: 7px; bottom: 7px; border: 1px solid {{ $accent }}; }
    .frame { padding: 30px 40px 0; }
    .watermark { position: fixed; top: 300px; left: 50%; margin-left: -150px; width: 300px; opacity: 0.06; }
    .head { text-align: center; }
    .logo { height: 78px; margin-bottom: 6px; }
    .school { font-size: 22px; font-weight: bold; color: {{ $color }}; letter-spacing: 0.5px; margin: 0; }
    .motto { font-style: italic; margin: 2px 0; }
    .muted { color: #4b5563; font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; }
    .title { margin: 18px 0 4px; text-align: center; font-size: 20px; font-weight: bold; letter-spacing: 4px; text-transform: uppercase; color: {{ $accent }}; }
    .rule { width: 120px; margin: 0 auto 14px; border-top: 2px solid {{ $color }}; }
    table.meta { width: 100%; font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; margin-bottom: 14px; }
    .photo { width: 90px; height: 108px; border: 1px solid #9ca3af; object-fit: cover; }
    .body p { line-height: 1.75; text-align: justify; margin: 0 0 10px; }
    .name { font-weight: bold; text-transform: uppercase; }
    table.details { width: 100%; border-collapse: collapse; margin: 8px 0 16px; font-size: 11px; }
    table.details td { padding: 5px 8px; border-bottom: 1px dotted #9ca3af; vertical-align: top; }
    table.details td.k { width: 38%; color: #374151; }
    table.details td.v { font-weight: bold; }
    .revoked { position: fixed; top: 420px; left: 0; right: 0; text-align: center; font-size: 64px; font-weight: bold; color: #dc2626; opacity: 0.25; letter-spacing: 8px; }
    table.sign { width: 100%; margin-top: 70px; }
    .sigimg { height: 50px; }
    .sigline { border-top: 1px solid #111827; width: 220px; padding-top: 3px; font-family: 'DejaVu Sans', sans-serif; font-size: 9.5px; }
    .stamp { height: 100px; }
    .foot { position: fixed; bottom: 16px; left: 40px; right: 40px; text-align: center; font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #4b5563; }
</style>
</head>
<body>
<div class="border-outer"></div>
<div class="border-inner"></div>
<div class="frame">
    @if($school['logo'])<img class="watermark" src="{{ $school['logo'] }}">@endif
    @if($certificate->isRevoked())<div class="revoked">REVOKED</div>@endif

    <div class="head">
        @if($school['logo'])<img class="logo" src="{{ $school['logo'] }}"><br>@endif
        <p class="school">{{ $school['name'] }}</p>
        @if($school['motto'])<div class="motto">{{ $school['motto'] }}</div>@endif
        <div class="muted">{{ $school['address'] }}</div>
        <div class="muted">{{ collect([$school['phone'], $school['email']])->filter()->implode(' · ') }}</div>
    </div>

    <div class="title">{{ $title }}</div>
    <div class="rule"></div>

    <table class="meta">
        <tr>
            <td style="vertical-align: top">
                <strong>No.:</strong> {{ $certificate->serial }}<br>
                <strong>Date:</strong> {{ $certificate->issued_on->format('j F Y') }}
            </td>
            @if($photo)<td style="text-align: right; width: 100px"><img class="photo" src="{{ $photo }}"></td>@endif
        </tr>
    </table>

    <div class="body">
        <p>
            This is to certify that <span class="name">{{ $d['name'] }}</span>@if(!empty($d['admission_no'])) (Admission No. {{ $d['admission_no'] }})@endif
            was a student of this school from <strong>{{ $fmt($d['date_admitted'] ?? null) }}</strong> to <strong>{{ $fmt($d['date_left'] ?? null) }}</strong>.
            @if(!empty($d['class_admitted']) && ($d['class_admitted'] !== ($d['last_class'] ?? null)))
                {{ $he }} was admitted into <strong>{{ $d['class_admitted'] }}</strong> and was in <strong>{{ $d['last_class'] }}</strong> when {{ strtolower($he) }} left.
            @else
                {{ $he }} was in <strong>{{ $d['last_class'] }}</strong> when {{ strtolower($he) }} left.
            @endif
        </p>
        <p>
            @if($isTransfer)
                {{ $he }} is leaving to continue {{ $his }} education elsewhere, and this certificate is given so that {{ strtolower($he) }} may be admitted to another school.
            @else
                During {{ $his }} time in this school, {{ $his }} conduct and character were found to be <strong>{{ strtolower($d['conduct']) }}</strong>.
            @endif
        </p>
    </div>

    <table class="details">
        @if(!empty($d['date_of_birth']))<tr><td class="k">Date of birth</td><td class="v">{{ $fmt($d['date_of_birth']) }}</td></tr>@endif
        <tr><td class="k">Conduct and character</td><td class="v">{{ $d['conduct'] }}</td></tr>
        @if(!$isTransfer && !empty($d['academic_ability']))<tr><td class="k">Academic ability</td><td class="v">{{ $d['academic_ability'] }}</td></tr>@endif
        @if(!$isTransfer && !empty($d['exams']))<tr><td class="k">Examinations taken</td><td class="v">{{ $d['exams'] }}</td></tr>@endif
        @if(!empty($d['offices_held']))<tr><td class="k">Offices held</td><td class="v">{{ $d['offices_held'] }}</td></tr>@endif
        @if(!empty($d['activities']))<tr><td class="k">Clubs, sports and activities</td><td class="v">{{ $d['activities'] }}</td></tr>@endif
        @if($isTransfer)
            <tr><td class="k">Reason for leaving</td><td class="v">{{ $d['reason_for_leaving'] ?: '—' }}</td></tr>
            @if(!empty($d['destination_school']))<tr><td class="k">School transferring to</td><td class="v">{{ $d['destination_school'] }}</td></tr>@endif
            <tr><td class="k">School fees</td><td class="v">{{ !empty($d['fees_cleared']) ? 'All fees paid' : 'Not fully paid' }}</td></tr>
        @endif
        @if(!empty($d['remark']))<tr><td class="k">Remark</td><td class="v">{{ $d['remark'] }}</td></tr>@endif
    </table>

    <p style="line-height: 1.6">We wish {{ $him }} every success in the future.</p>

    <table class="sign">
        <tr>
            <td style="vertical-align: bottom">
                @if($signer && $signer['signature'])<img class="sigimg" src="{{ $signer['signature'] }}"><br>@else<div style="height: 50px"></div>@endif
                <div class="sigline">
                    @if($signer && $signer['name'])<strong>{{ $signer['name'] }}</strong><br>@endif
                    {{ $signer['label'] ?? 'Principal' }}
                </div>
            </td>
            <td style="text-align: right; vertical-align: bottom">
                @if($stamp)<img class="stamp" src="{{ $stamp }}">@else<div class="muted" style="border: 1px dashed #9ca3af; width: 110px; height: 80px; display: inline-block; text-align: center; line-height: 80px">School stamp</div>@endif
            </td>
        </tr>
    </table>

    <div class="foot">
        To check that this certificate is genuine, visit {{ $verify_url }} (code {{ $certificate->verify_code }}).
    </div>
</div>
</body></html>
