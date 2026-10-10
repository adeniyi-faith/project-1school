<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('report-cards._style')
</head>
<body>
@php
    $n = fn ($v) => $v === null || $v === '' ? '—' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $ord = fn ($p) => $p ? $p . (in_array($p % 100, [11, 12, 13]) ? 'th' : (['th', 'st', 'nd', 'rd'][$p % 10] ?? 'th')) : '—';
@endphp
@foreach($cards as $card)
@php $d = $card['design']; @endphp
<div class="card">
    @include('report-cards._header', ['card' => $card])
    <div class="title">{{ $card['year'] }} {{ $d['session_title'] }}</div>
    @if($card['preview'])<p class="note">Preview: these results have not been published yet.</p>@endif

    <table class="grid">
        <thead>
            <tr>
                <th class="left">Subject</th>
                @foreach($card['term_names'] as $t)<th>{{ $t }}</th>@endforeach
                <th>Year average</th>
                <th>Grade</th>
            </tr>
        </thead>
        <tbody>
            @foreach($card['rows'] as $row)
            <tr>
                <td class="left">{{ $row['subject'] }}</td>
                @foreach($row['terms'] as $t)<td>{{ $n($t) }}</td>@endforeach
                <td><strong>{{ $n($row['average']) }}</strong></td>
                <td><strong>{{ $row['grade'] ?? '—' }}</strong></td>
            </tr>
            @endforeach
            <tr>
                <td class="left"><strong>Term average</strong></td>
                @foreach($card['term_averages'] as $a)<td><strong>{{ $a === null ? '—' : $n($a) . '%' }}</strong></td>@endforeach
                <td colspan="2"></td>
            </tr>
            @if($d['show_overall_position'])
            <tr>
                <td class="left">Position in class</td>
                @foreach($card['term_positions'] as $p)<td>{{ $p ? $ord($p['position']) . ' of ' . $p['class_size'] : '—' }}</td>@endforeach
                <td colspan="2"></td>
            </tr>
            @endif
        </tbody>
    </table>

    <table class="grid">
        <tr><th>Year average</th><th>Overall grade</th>@if($d['show_overall_position'])<th>Position for the year</th>@endif<th>End-of-year decision</th></tr>
        <tr>
            <td><strong>{{ $n($card['summary']['average']) }}%</strong></td>
            <td><strong>{{ $card['summary']['grade'] }}</strong></td>
            @if($d['show_overall_position'])<td><strong>{{ $ord($card['summary']['position']) }}</strong> of {{ $card['summary']['class_size'] }}</td>@endif
            <td><strong>{{ $card['decision'] ?? 'Not decided yet' }}</strong></td>
        </tr>
    </table>
    <p class="muted">The year average is the average of the term averages above. A dash means no result for that term.</p>

    @include('report-cards._signers', ['card' => $card])
    @include('report-cards._key', ['card' => $card])
</div>
@endforeach
</body>
</html>
