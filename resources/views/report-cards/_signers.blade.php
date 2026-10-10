@php
    $d = $card['design'];
    $remarks = $card['remarks'] ?? [];
    $commenters = collect($d['signers'])->filter(fn ($s) => $s['has_comment'])->values();
@endphp
@if(isset($card['remarks']) && $commenters->isNotEmpty())
    @if($d['template'] === 'compact' && $commenters->count() > 1)
        <table class="cols"><tr>
            @foreach($commenters as $sg)
                <td style="width:{{ floor(100 / $commenters->count()) }}%"><div class="box"><h4>{{ $sg['label'] }}'s comment</h4>{{ $remarks[$sg['id']] ?? ' ' }}</div></td>
            @endforeach
        </tr></table>
    @else
        @foreach($commenters as $sg)
            <div class="box"><h4>{{ $sg['label'] }}'s comment</h4>{{ $remarks[$sg['id']] ?? ' ' }}</div>
        @endforeach
    @endif
@endif
@if($d['show_signatures'] && count($d['signers']))
    <table class="signs"><tr>
        @foreach($d['signers'] as $sg)
            <td>
                @if($sg['signature'])<img class="sigimg" src="{{ $sg['signature'] }}"><br>@else<div style="height:30px"></div>@endif
                <div class="line">{{ $sg['label'] }}@if($sg['name'])<br><strong>{{ $sg['name'] }}</strong>@endif</div>
            </td>
        @endforeach
        @if($d['stamp'])<td style="width:90px"><img class="stamp" src="{{ $d['stamp'] }}"></td>@endif
    </tr></table>
@elseif($d['stamp'])
    <div style="text-align:right"><img class="stamp" src="{{ $d['stamp'] }}"></div>
@endif
