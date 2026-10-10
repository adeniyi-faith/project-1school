<!doctype html>
<html><head><meta charset="utf-8">@include('broadsheets._style')</head>
<body>
@php $b = $sheet; $short = array_map(fn ($n) => $shortTerm((string) $n), $b['term_names']); @endphp
<h1>{{ $b['school'] }}</h1>
<p class="sub"><strong>Full-year broadsheet: {{ $b['class'] }}</strong> · {{ $b['session'] }} · {{ count($b['rows']) }} students</p>
<table class="bs">
    <thead>
        <tr>
            <th rowspan="2">Pos</th>
            <th rowspan="2">Name</th>
            <th rowspan="2">Sex</th>
            @foreach($b['subjects'] as $s)<th colspan="{{ count($short) + 2 }}">{{ $s['name'] }}</th>@endforeach
            @foreach($short as $t)<th rowspan="2">{{ $t }} avg</th>@endforeach
            <th rowspan="2">Year avg</th>
            <th rowspan="2">Grade</th>
            <th rowspan="2">Pos</th>
        </tr>
        <tr>
            @foreach($b['subjects'] as $s)
                @foreach($short as $t)<th class="part">{{ $t }}</th>@endforeach
                <th class="part"><strong>Avg</strong></th>
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
                    @php $sc = $r['scores'][$s['id']]; @endphp
                    @foreach($sc['terms'] as $t)<td>{{ $t === null ? '–' : $num($t) }}</td>@endforeach
                    <td class="total {{ $sc['average'] !== null && $sc['average'] < $b['pass_mark'] ? 'fail' : '' }}">{{ $num($sc['average']) }}</td>
                    <td>{{ $sc['grade'] }}</td>
                @endforeach
                @foreach($r['term_averages'] as $a)<td>{{ $num($a) }}</td>@endforeach
                <td class="total">{{ $num($r['average']) }}</td>
                <td>{{ $r['grade'] }}</td>
                <td class="total">{{ $r['position'] }}</td>
            </tr>
        @endforeach
        @foreach(['average' => 'Subject average', 'highest' => 'Highest', 'lowest' => 'Lowest', 'pass_rate' => 'Pass rate %'] as $key => $label)
            <tr class="foot">
                <td></td><td class="name">{{ $label }}</td><td></td>
                @foreach($b['subjects'] as $s)
                    <td colspan="{{ count($short) }}"></td>
                    <td>{{ $num($b['footer'][$s['id']][$key]) }}</td><td></td>
                @endforeach
                <td colspan="{{ count($short) + 3 }}">@if($key === 'average')Class average {{ $num($b['overall']['average']) }}@elseif($key === 'pass_rate'){{ $b['overall']['passed'] }} of {{ $b['overall']['entries'] }} passed ({{ $num($b['overall']['pass_rate']) }}%)@endif</td>
            </tr>
        @endforeach
    </tbody>
</table>
<p class="note">Year average is the mean of the term averages. Pass mark: {{ $num($b['pass_mark']) }}%. Printed {{ now()->format('j F Y') }}.</p>
</body></html>
