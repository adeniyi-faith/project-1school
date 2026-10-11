<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $note->title }}</title>
<style>
    @page { margin: 16mm 16mm 18mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; line-height: 1.5; }
    .head { border-bottom: 2.5px solid {{ $color }}; padding-bottom: 8px; margin-bottom: 12px; }
    .head td { vertical-align: middle; }
    .logo { height: 58px; }
    .school { font-size: 16px; font-weight: bold; color: {{ $color }}; margin: 0; text-transform: uppercase; }
    .muted { color: #64748b; margin: 1px 0; font-size: 9.5px; }
    h1 { font-size: 13px; text-align: center; margin: 4px 0 10px; letter-spacing: 1px; text-transform: uppercase; }
    table.meta { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
    table.meta td { border: 1px solid #cbd5e1; padding: 4px 7px; width: 25%; }
    table.meta td.k { background: #f1f5f9; font-weight: bold; width: 16%; }
    .section { margin-bottom: 10px; page-break-inside: auto; }
    .section h2 { font-size: 11px; color: {{ $color }}; margin: 0 0 3px; text-transform: uppercase; letter-spacing: .4px; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px; }
    .section p { margin: 0 0 4px; }
    .empty { color: #94a3b8; font-style: italic; }
    .foot { margin-top: 18px; width: 100%; }
    .foot td { width: 50%; padding-top: 22px; vertical-align: bottom; }
    .line { border-top: 1px solid #94a3b8; padding-top: 3px; margin-right: 30px; font-size: 9.5px; color: #475569; }
    .stamp { font-size: 9px; color: #64748b; }
</style>
</head>
<body>
<table class="head" width="100%">
    <tr>
        @if($school['logo'])<td width="70"><img class="logo" src="{{ $school['logo'] }}"></td>@endif
        <td>
            <p class="school">{{ $school['name'] }}</p>
            @if($school['address'])<p class="muted">{{ $school['address'] }}</p>@endif
            @if($school['phone'] || $school['email'])<p class="muted">{{ collect([$school['phone'], $school['email']])->filter()->join(' · ') }}</p>@endif
            @if($school['motto'])<p class="muted"><i>{{ $school['motto'] }}</i></p>@endif
        </td>
    </tr>
</table>

<h1>Lesson Note</h1>

<table class="meta">
    <tr>
        <td class="k">Subject</td><td>{{ $note->subject?->name }}</td>
        <td class="k">Class</td><td>{{ $note->schoolClass?->name }}</td>
    </tr>
    <tr>
        <td class="k">Term</td><td>{{ $note->term?->name ?? '—' }}</td>
        <td class="k">Week</td><td>{{ $note->week ?? '—' }}@if($note->week_start) &nbsp;({{ $note->week_start->format('j M Y') }})@endif</td>
    </tr>
    <tr>
        <td class="k">Topic</td><td colspan="3"><b>{{ $note->title }}</b></td>
    </tr>
    <tr>
        <td class="k">Teacher</td><td>{{ $note->teacherName() ?? '—' }}</td>
        <td class="k">Duration</td><td>{{ $note->duration_minutes ? $note->duration_minutes.' minutes' : '—' }}</td>
    </tr>
</table>

@foreach($body as $section)
    <div class="section">
        <h2>{{ $section['label'] }}</h2>
        @php($paragraphs = array_filter(preg_split('/\R/', trim($section['text'])) ?: [], fn ($p) => trim($p) !== ''))
        @forelse($paragraphs as $p)
            <p>{{ $p }}</p>
        @empty
            <p class="empty">Not filled in.</p>
        @endforelse
    </div>
@endforeach

<table class="foot">
    <tr>
        <td><div class="line">Teacher's signature and date</div></td>
        <td>
            @if($note->status === 'approved')
                <div class="stamp">Approved{{ $note->reviewer ? ' by '.$note->reviewer->name : '' }}{{ $note->reviewed_at ? ' on '.$note->reviewed_at->format('j M Y') : '' }}</div>
            @endif
            <div class="line">Head of department / Principal</div>
        </td>
    </tr>
</table>
</body>
</html>
