<!doctype html>
<html><head><meta charset="utf-8">@include('broadsheets._style')</head>
<body>
@php $b = $sheet; $tail = 5 + count($b['grades']); @endphp
<h1>{{ $b['school'] }}</h1>
<p class="sub">
    <strong>Broadsheet: {{ $b['class'] }}</strong> · {{ $b['term'] }} · {{ $b['session'] }} · {{ count($b['rows']) }} students
    @unless(in_array($b['status'], ['published', 'locked'], true)) · <span class="preview">Not yet published</span>@endunless
</p>
<table class="bs">
    <thead>
        <tr>
            <th rowspan="2">Pos</th>
            <th rowspan="2">Name</th>
            <th rowspan="2">Sex</th>
            @foreach($b['subjects'] as $s)
                <th colspan="{{ count($s['parts']) + 2 }}">{{ $s['name'] }}</th>
            @endforeach
            <th rowspan="2">Total</th>
            <th rowspan="2">Avg</th>
            <th rowspan="2">Grade</th>
            <th rowspan="2">Pos</th>
            @foreach($b['grades'] as $g)<th rowspan="2">{{ $g }}</th>@endforeach
        </tr>
        <tr>
            @foreach($b['subjects'] as $s)
                @foreach($s['parts'] as $p)<th class="part">{{ $p }}</th>@endforeach
                <th class="part"><strong>Tot</strong></th>
                <th class="part">Gr</th>
            @endforeach
        </tr>
    </thead>
    <tbody>
        @foreach($b['rows'] as $i => $r)
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td>{{ $r['position'] }}</td>
                <td class="name">{{ $r['name'] }}</td>
                <td>{{ $r['gender'] }}</td>
                @foreach($b['subjects'] as $s)
                    @php $sc = $r['scores'][$s['id']] ?? null; @endphp
                    @foreach($s['parts'] as $p)<td>{{ isset($sc['parts'][$p]) ? $num($sc['parts'][$p]) : '' }}</td>@endforeach
                    <td class="total {{ $sc && $sc['total'] !== null && $sc['total'] < $b['pass_mark'] ? 'fail' : '' }}">{{ $sc ? $num($sc['total']) : '–' }}</td>
                    <td>{{ $sc['grade'] ?? '' }}</td>
                @endforeach
                <td class="total">{{ $num($r['total']) }}</td>
                <td class="total">{{ $num($r['average']) }}</td>
                <td>{{ $r['grade'] }}</td>
                <td class="total">{{ $r['position'] }}</td>
                @foreach($r['grade_counts'] as $n)<td>{{ $n ?: '' }}</td>@endforeach
            </tr>
        @endforeach
        @foreach(['average' => 'Subject average', 'highest' => 'Highest', 'lowest' => 'Lowest', 'pass_rate' => 'Pass rate %'] as $key => $label)
            <tr class="foot">
                <td></td><td class="name">{{ $label }}</td><td></td>
                @foreach($b['subjects'] as $s)
                    @if(count($s['parts']))<td colspan="{{ count($s['parts']) }}"></td>@endif
                    <td>{{ $num($b['footer'][$s['id']][$key]) }}</td><td></td>
                @endforeach
                <td colspan="{{ $tail }}">@if($key === 'average')Class average {{ $num($b['overall']['average']) }}@elseif($key === 'pass_rate'){{ $b['overall']['passed'] }} of {{ $b['overall']['entries'] }} passed ({{ $num($b['overall']['pass_rate']) }}%)@endif</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p class="note">Pass mark: {{ $num($b['pass_mark']) }}%. Totals below the pass mark are in red. Printed {{ now()->format('j F Y') }}.</p>
</body></html>
