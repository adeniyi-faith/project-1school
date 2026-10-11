<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>{{ $paper->title }}</title>
@php
    $letters = 'ABCDEF';
    $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 1, '.', ''), '0'), '.');
    $lines = fn ($text) => array_values(array_filter(preg_split('/\R/', trim((string) $text)) ?: [], fn ($l) => trim($l) !== ''));
@endphp
<style>
    @page { margin: 14mm 15mm 16mm; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #111827; line-height: 1.45; }
    .head { border-bottom: 2.5px solid {{ $color }}; padding-bottom: 7px; margin-bottom: 8px; }
    .head td { vertical-align: middle; text-align: center; }
    .logo { height: 56px; }
    .school { font-size: 16px; font-weight: bold; color: {{ $color }}; margin: 0; text-transform: uppercase; }
    .muted { color: #4b5563; margin: 1px 0; font-size: 9.5px; }
    h1 { font-size: 13px; text-align: center; margin: 2px 0 8px; letter-spacing: .8px; text-transform: uppercase; }
    .secret { text-align: center; color: #b91c1c; font-weight: bold; font-size: 9.5px; margin: -4px 0 8px; letter-spacing: .5px; }
    table.meta { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
    table.meta td { border: 1px solid #9ca3af; padding: 4px 7px; }
    table.meta td.k { background: #f3f4f6; font-weight: bold; width: 13%; }
    .who { width: 100%; margin: 4px 0 10px; }
    .who td { padding: 6px 0 0; font-size: 10px; }
    .dots { border-bottom: 1px dotted #6b7280; }
    .general { border: 1px solid #d1d5db; background: #f9fafb; padding: 6px 8px; margin-bottom: 10px; }
    .general p { margin: 0 0 2px; }
    h2 { font-size: 11.5px; color: {{ $color }}; margin: 12px 0 2px; text-transform: uppercase; letter-spacing: .4px; }
    .note { font-style: italic; margin: 0 0 6px; color: #374151; }
    table.q { width: 100%; border-collapse: collapse; page-break-inside: avoid; margin-bottom: 7px; }
    table.q td { vertical-align: top; padding: 0; }
    td.n { width: 22px; font-weight: bold; }
    td.m { width: 62px; text-align: right; color: #374151; font-size: 9.5px; white-space: nowrap; }
    .text p { margin: 0 0 2px; }
    .pic { max-height: 150px; max-width: 320px; margin: 3px 0; }
    table.opts { width: 100%; border-collapse: collapse; margin-top: 2px; }
    table.opts td { padding: 1px 6px 1px 0; vertical-align: top; }
    .key { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
    .key td { border: 1px solid #d1d5db; padding: 3px 6px; width: 20%; }
    .guide { border-left: 3px solid {{ $color }}; padding: 2px 0 2px 7px; margin: 3px 0 0; color: #1f2937; }
    .guide p { margin: 0 0 1px; }
    .why { color: #4b5563; font-size: 9.5px; }
    .end { text-align: center; margin-top: 16px; font-weight: bold; letter-spacing: 1px; color: #374151; }
</style>
</head>
<body>
<table class="head" width="100%">
    <tr>
        @if($school['logo'])<td width="70" style="text-align:left"><img class="logo" src="{{ $school['logo'] }}"></td>@endif
        <td>
            <p class="school">{{ $school['name'] }}</p>
            @if($school['address'])<p class="muted">{{ $school['address'] }}</p>@endif
            @if($school['phone'] || $school['email'])<p class="muted">{{ collect([$school['phone'], $school['email']])->filter()->join(' · ') }}</p>@endif
            @if($school['motto'])<p class="muted"><i>{{ $school['motto'] }}</i></p>@endif
        </td>
        @if($school['logo'])<td width="70"></td>@endif
    </tr>
</table>

<h1>{{ $paper->title }}@if($paper->term?->academicYear) &nbsp;{{ $paper->term->academicYear->name }}@endif</h1>
@if($answers)<p class="secret">MARKING SCHEME · FOR TEACHERS ONLY</p>@endif

<table class="meta">
    <tr>
        <td class="k">Subject</td><td>{{ $paper->subject?->name ?? '—' }}</td>
        <td class="k">Class</td><td>{{ $paper->schoolClass?->name ?? '—' }}</td>
    </tr>
    <tr>
        <td class="k">Time</td><td>{{ $paper->durationText() ?? '—' }}</td>
        <td class="k">{{ $paper->show_marks || $answers ? 'Total marks' : 'Term' }}</td>
        <td>{{ $paper->show_marks || $answers ? $fmt($total) : ($paper->term?->name ?? '—') }}</td>
    </tr>
</table>

@unless($answers)
    <table class="who">
        <tr>
            <td width="11%">Name:</td><td class="dots" width="52%">&nbsp;</td>
            <td width="4%"></td>
            <td width="13%">Admission no.:</td><td class="dots">&nbsp;</td>
        </tr>
    </table>
    @if($paper->instructions)
        <div class="general">
            <p><b>Instructions</b></p>
            @foreach($lines($paper->instructions) as $l)<p>{{ $l }}</p>@endforeach
        </div>
    @endif
@endunless

@foreach($sections as $section)
    @continue($section['questions']->isEmpty())
    <h2>{{ $section['title'] }}</h2>
    @if($section['instructions'] && ! $answers)<p class="note">{{ $section['instructions'] }}</p>@endif

    @if($answers)
        @php($objective = $section['questions']->filter(fn ($q) => $q->type !== 'theory')->values())
        @php($numbers = $section['questions']->keys()->mapWithKeys(fn ($i) => [$section['questions'][$i]->id => $i + 1]))
        @if($objective->isNotEmpty())
            <table class="key">
                @foreach($objective->chunk(5) as $row)
                    <tr>
                        @foreach($row as $q)<td><b>{{ $numbers[$q->id] }}.</b> {{ $letters[$q->correct_option] ?? '?' }}</td>@endforeach
                        @for($i = $row->count(); $i < 5; $i++)<td></td>@endfor
                    </tr>
                @endforeach
            </table>
        @endif
        @foreach($section['questions'] as $i => $q)
            @continue($q->type !== 'theory' && ! $q->explanation)
            <table class="q">
                <tr>
                    <td class="n">{{ $i + 1 }}.</td>
                    <td>
                        <div class="text why">@foreach($lines($q->text) as $l)<p>{{ $l }}</p>@endforeach</div>
                        @if($q->type === 'theory')
                            <div class="guide">
                                @forelse($lines($q->marking_guide) as $l)<p>{{ $l }}</p>@empty<p class="why">No marking guide was written for this question.</p>@endforelse
                            </div>
                        @else
                            <div class="guide"><p><b>{{ $letters[$q->correct_option] ?? '?' }}.</b> {{ $q->options[$q->correct_option] ?? '' }} · {{ $q->explanation }}</p></div>
                        @endif
                    </td>
                    <td class="m">{{ $fmt($q->marks) }} mark{{ (float) $q->marks === 1.0 ? '' : 's' }}</td>
                </tr>
            </table>
        @endforeach
    @else
        @foreach($section['questions'] as $i => $q)
            <table class="q">
                <tr>
                    <td class="n">{{ $i + 1 }}.</td>
                    <td>
                        <div class="text">@foreach($lines($q->text) as $l)<p>{{ $l }}</p>@endforeach</div>
                        @if(! empty($images[$q->id]))<img class="pic" src="{{ $images[$q->id] }}">@endif
                        @if($q->type !== 'theory')
                            @php($options = $q->options ?? [])
                            @php($longest = collect($options)->map(fn ($o) => mb_strlen($o))->max() ?? 0)
                            @php($perRow = $longest <= 22 ? 4 : ($longest <= 48 ? 2 : 1))
                            <table class="opts">
                                @foreach(array_chunk($options, $perRow, true) as $row)
                                    <tr>
                                        @foreach($row as $o => $option)<td width="{{ 100 / $perRow }}%">({{ strtolower($letters[$o]) }}) {{ $option }}</td>@endforeach
                                        @for($c = count($row); $c < $perRow; $c++)<td></td>@endfor
                                    </tr>
                                @endforeach
                            </table>
                        @endif
                    </td>
                    @if($paper->show_marks && $q->type === 'theory')<td class="m">[{{ $fmt($q->marks) }} mark{{ (float) $q->marks === 1.0 ? '' : 's' }}]</td>@endif
                </tr>
            </table>
        @endforeach
    @endif
@endforeach

<p class="end">{{ $answers ? 'END OF MARKING SCHEME' : 'END OF PAPER' }}</p>
</body>
</html>
