<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
@include('report-cards._style')
</head>
<body>
@php
    // 72.50 → 72.5, 70.00 → 70, blank stays blank
    $n = fn ($v) => $v === null || $v === '' ? '' : rtrim(rtrim(number_format((float) $v, 2), '0'), '.');
    $ord = fn ($p) => $p ? $p . (in_array($p % 100, [11, 12, 13]) ? 'th' : (['th', 'st', 'nd', 'rd'][$p % 10] ?? 'th')) : '—';
    $ratingLabels = \App\Models\BehaviourRating::LABELS;
@endphp
@foreach($cards as $card)
<div class="card">
    @include('report-cards._header', ['card' => $card])
    <div class="title">{{ $card['term'] }} Report Card</div>
    @if($card['preview'])<p class="note">Preview: these results have not been published yet.</p>@endif

    <table class="grid">
        <thead>
            <tr>
                <th class="left">Subject</th>
                @foreach($card['columns'] as $col)<th>{{ $col['name'] }}<br><span style="font-weight:normal">({{ $n($col['max']) }})</span></th>@endforeach
                <th>Total<br><span style="font-weight:normal">(100)</span></th>
                <th>Grade</th>
                <th>Position</th>
                <th>Class avg</th>
                <th>Highest</th>
                <th>Lowest</th>
                <th class="left">Remark</th>
            </tr>
        </thead>
        <tbody>
            @foreach($card['rows'] as $row)
            <tr>
                <td class="left">{{ $row['subject'] }}</td>
                @foreach($card['columns'] as $col)<td>{{ $n($row['parts'][$col['name']] ?? null) }}</td>@endforeach
                <td><strong>{{ $n($row['total']) }}</strong></td>
                <td><strong>{{ $row['grade'] }}</strong></td>
                <td>{{ $ord($row['position']) }}</td>
                <td>{{ $n($row['average']) }}</td>
                <td>{{ $n($row['highest']) }}</td>
                <td>{{ $n($row['lowest']) }}</td>
                <td class="left">{{ $row['remarks'] }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="grid">
        <tr>
            <th>Subjects taken</th><th>Total score</th><th>Average</th><th>Overall grade</th><th>Position in class</th><th>Class average</th><th>Days present</th>
        </tr>
        <tr>
            <td>{{ $card['summary']['subjects'] }}</td>
            <td>{{ $n($card['summary']['total']) }}</td>
            <td><strong>{{ $n($card['summary']['average']) }}%</strong></td>
            <td><strong>{{ $card['summary']['grade'] }}</strong></td>
            <td><strong>{{ $ord($card['summary']['position']) }}</strong> of {{ $card['summary']['class_size'] }}</td>
            <td>{{ $n($card['summary']['class_average']) }}%</td>
            <td>{{ $card['attendance'] ? $n($card['attendance']['present']) . ' of ' . $card['attendance']['marked'] : '—' }}</td>
        </tr>
    </table>

    @if(count($card['ratings']))
    <table class="row2"><tr>
        @foreach(['affective' => 'Behaviour', 'psychomotor' => 'Skills'] as $domain => $label)
        <td>
            <table class="grid">
                <tr><th class="left">{{ $label }}</th><th>Rating</th></tr>
                @forelse($card['ratings'][$domain] ?? [] as $r)
                    <tr><td class="left">{{ $r['name'] }}</td><td>{{ $r['rating'] }} · {{ $ratingLabels[$r['rating']] ?? '' }}</td></tr>
                @empty
                    <tr><td class="left muted" colspan="2">Not rated</td></tr>
                @endforelse
            </table>
        </td>
        @endforeach
    </tr></table>
    @endif

    <div class="box"><h4>Class teacher's comment</h4>{{ $card['teacher_comment'] ?: ' ' }}</div>
    <div class="box"><h4>Principal's comment</h4>{{ $card['principal_comment'] ?: ' ' }}</div>
    @if($card['next_term_begins'])<p><strong>Next term begins:</strong> {{ $card['next_term_begins'] }}</p>@endif

    <table class="sign"><tr>
        <td><div class="line">Class teacher's signature</div></td>
        <td><div class="line">Principal's signature and stamp</div></td>
    </tr></table>

    @include('report-cards._key', ['card' => $card])
</div>
@endforeach
</body>
</html>
