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
@php $d = $card['design']; @endphp
<div class="card">
    @include('report-cards._header', ['card' => $card])
    <div class="title">{{ $card['term'] }} {{ $d['term_title'] }}</div>
    @if($card['preview'])<p class="note">Preview: these results have not been published yet.</p>@endif

    <table class="grid">
        <thead>
            <tr>
                <th class="left">Subject</th>
                @if($d['show_parts'])
                    @foreach($card['columns'] as $col)<th>{{ $col['name'] }}<br><span style="font-weight:normal">({{ $n($col['max']) }})</span></th>@endforeach
                @endif
                <th>Total<br><span style="font-weight:normal">(100)</span></th>
                <th>Grade</th>
                @if($d['show_subject_position'])<th>Position</th>@endif
                @if($d['show_class_stats'])<th>Class avg</th><th>Highest</th><th>Lowest</th>@endif
                @if($d['show_remarks'])<th class="left">Remark</th>@endif
            </tr>
        </thead>
        <tbody>
            @foreach($card['rows'] as $row)
            <tr>
                <td class="left">{{ $row['subject'] }}</td>
                @if($d['show_parts'])
                    @foreach($card['columns'] as $col)<td>{{ $n($row['parts'][$col['name']] ?? null) }}</td>@endforeach
                @endif
                <td><strong>{{ $n($row['total']) }}</strong></td>
                <td><strong>{{ $row['grade'] }}</strong></td>
                @if($d['show_subject_position'])<td>{{ $ord($row['position']) }}</td>@endif
                @if($d['show_class_stats'])<td>{{ $n($row['average']) }}</td><td>{{ $n($row['highest']) }}</td><td>{{ $n($row['lowest']) }}</td>@endif
                @if($d['show_remarks'])<td class="left">{{ $row['remarks'] }}</td>@endif
            </tr>
            @endforeach
        </tbody>
    </table>

    <table class="grid">
        <tr>
            <th>Subjects taken</th><th>Total score</th><th>Average</th><th>Overall grade</th>
            @if($d['show_overall_position'])<th>Position in class</th>@endif
            @if($d['show_class_average'])<th>Class average</th>@endif
            @if($d['show_attendance'])<th>Days present</th>@endif
        </tr>
        <tr>
            <td>{{ $card['summary']['subjects'] }}</td>
            <td>{{ $n($card['summary']['total']) }}</td>
            <td><strong>{{ $n($card['summary']['average']) }}%</strong></td>
            <td><strong>{{ $card['summary']['grade'] }}</strong></td>
            @if($d['show_overall_position'])<td><strong>{{ $ord($card['summary']['position']) }}</strong> of {{ $card['summary']['class_size'] }}</td>@endif
            @if($d['show_class_average'])<td>{{ $n($card['summary']['class_average']) }}%</td>@endif
            @if($d['show_attendance'])<td>{{ $card['attendance'] ? $n($card['attendance']['present']) . ' of ' . $card['attendance']['marked'] : '—' }}</td>@endif
        </tr>
    </table>

    @php
        $domains = collect(['affective' => ['Behaviour', $d['show_behaviour']], 'psychomotor' => ['Skills', $d['show_skills']]])
            ->filter(fn ($x, $key) => $x[1] && ! empty($card['ratings'][$key]));
    @endphp
    @if($domains->isNotEmpty())
    <table class="cols"><tr>
        @foreach($domains as $domain => [$label])
        <td style="width:{{ $domains->count() > 1 ? 50 : 100 }}%">
            <table class="grid">
                <tr><th class="left">{{ $label }}</th><th>Rating</th></tr>
                @foreach($card['ratings'][$domain] as $r)
                    <tr><td class="left">{{ $r['name'] }}</td><td>{{ $r['rating'] }} · {{ $ratingLabels[$r['rating']] ?? '' }}</td></tr>
                @endforeach
            </table>
        </td>
        @endforeach
    </tr></table>
    @endif

    @include('report-cards._signers', ['card' => $card])

    @if(($d['show_next_term'] && $card['next_term_begins']) || ($d['show_fees_owed'] && $card['fees_owed'] > 0))
        <p style="margin:6px 0 0">
            @if($d['show_next_term'] && $card['next_term_begins'])<strong>Next term begins:</strong> {{ $card['next_term_begins'] }}@endif
            @if($d['show_fees_owed'] && $card['fees_owed'] > 0)
                &nbsp;&nbsp;<span class="owed">Fees owed: ₦{{ number_format($card['fees_owed'], 2) }}</span>
            @endif
        </p>
    @endif

    @include('report-cards._key', ['card' => $card])
</div>
@endforeach
</body>
</html>
