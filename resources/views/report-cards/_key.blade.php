<div class="key">
    <strong>Grade key:</strong>
    @foreach($card['grade_key'] as $g)
        {{ $g['grade'] }} {{ rtrim(rtrim(number_format($g['min'], 2), '0'), '.') }}–{{ rtrim(rtrim(number_format($g['max'], 2), '0'), '.') }}@if($g['remarks']) ({{ $g['remarks'] }})@endif{{ $loop->last ? '' : ' · ' }}
    @endforeach
</div>
